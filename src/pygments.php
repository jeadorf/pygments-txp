<?php

/* interface */

function pyg_highlight($attrs, $thing='') {
    return pyg_highlight::highlight($attrs, $thing);
}

/* installation */

register_callback('pyg_pygments_txp_on_install', 'plugin_lifecycle.pygments_txp', 'installed');
register_callback('pyg_pygments_txp_on_delete', 'plugin_lifecycle.pygments_txp', 'deleted');

// TODO: use option system instead of plugging into standard preferences tab
function pyg_pygments_txp_on_install() {
    set_pref('pyg_highlight_pygmentize', '/usr/bin/pygmentize', 'admin', 1, 'text_input', 50);
    safe_delete('txp_lang', "name='pyg_highlight_pygmentize'");
    safe_insert('txp_lang', "name='pyg_highlight_pygmentize',data='Pygmentize script location',lang='en-gb',event='prefs'");
}

function pyg_pygments_txp_on_delete() {
    safe_delete('txp_prefs', "name='pyg_highlight_pygmentize'");
    safe_delete('txp_lang', "name='pyg_highlight_pygmentize'");
}

/* implementation */

class pyg_highlight {

    function highlight($atts, $thing='') {
        global $txpcfg;
        global $pyg_highlight_css_included;

        extract(lAtts(array(
            'file' => '',
            'from' => '1',
            'to' => '' . PHP_INT_MAX,
            'linenos' => ''
        ), $atts));

        $pygmentize = get_pref('pyg_highlight_pygmentize', '/usr/bin/pygmentize');

        if (pyg_highlight::invalid('file', $file, '/[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)*\.?/', $ret_msg)
                || pyg_highlight::invalid('linenos', $linenos, '/[a-zA-Z0-9_\-]*/', $ret_msg)
                || pyg_highlight::invalid('from', $from, '/[0-9]+/', $ret_msg)
                || pyg_highlight::invalid('to', $to, '/[0-9]+/', $ret_msg)
                || pyg_highlight::invalid('pygmentize', $pygmentize, '/[0-9a-zA-Z\-_\/]*\/pygmentize/', $ret_msg)) {
            return $ret_msg;
        }

        if (!$pyg_highlight_css_included) {
            $o = '<style><!--';
            $o .= shell_exec("$pygmentize -f html -S default -a .highlight");
            $o .= '--></style>';
            $pyg_highlight_css_included = 1;
        }

        $path = dirname($txpcfg['txpath']) . '/files/' . $file;

        if (!file_exists($path)) {
            return "<p>pyg_highlight: File '$path' does not exist.</p>";
        }

        $path = escapeshellarg($path);
        $linenos = escapeshellarg($linenos);

        if (pyg_highlight::snippet_filter_available()) {
            $from = escapeshellarg($from);
            $to = escapeshellarg($to);
            $o .= shell_exec("$pygmentize -f html -F snippet:fromline=$from,toline=$to -O linenostart=$from,linenos=$linenos $path");
        } else if (strcmp($from, '1') == 0 && strcmp($to, ''.PHP_INT_MAX) == 0) {
            $o .= shell_exec("$pygmentize -f html -O linenos=$linenos $path");
        } else {
            return "<p>pyg_highlight: pygments-snippet-filter not installed.</p>";
        }
        return $o;
    }

    private function invalid($description, $subject, $pattern, &$ret_msg) {
        preg_match_all($pattern, $subject, $matches);
        if (count($matches) == 0 || strlen($matches[0][0]) != strlen($subject)) {
            $ret_msg = "<p>pyg_highlight: invalid value for attribute '$description' </p>";
            return True;
        } else {
            return False;
        }
    }

    private function snippet_filter_available() {
        return stripos(shell_exec('pygmentize -L filters'), '* snippet') !== False;
    }

}

?>
