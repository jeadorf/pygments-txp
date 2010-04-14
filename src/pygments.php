<?php

/* interface */

function pyg_highlight($attrs, $thing='') {
    return pyg_highlight::highlight($attrs, $thing);
}

/* installation */

register_callback('pyg_pygments_txp_on_install', 'plugin_lifecycle.pygments_txp', 'installed');
register_callback('pyg_pygments_txp_on_delete', 'plugin_lifecycle.pygments_txp', 'deleted');

function pyg_pygments_txp_on_install() {
    set_pref('pyg_highlight_pygmentize', '/usr/bin/pygmentize', 'admin', 1, 'text_input', 50);
}

function pyg_pygments_txp_on_delete() {
    safe_delete('txp_prefs', 'name=\'pyg_highlight_pygmentize\'');
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

        if (pyg_highlight::invalid($file, '/[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)*\.?/'))
            return _pyg_highlight::invalid_attr_error('file');
        if (pyg_highlight::invalid($linenos, '/[a-zA-Z0-9_\-]*/'))
            return pyg_highlight::invalid_attr_error('linenos');
        if (pyg_highlight::invalid($from, '/[0-9]+/'))
            return pyg_highlight::invalid_attr_error('from');
        if (pyg_highlight::invalid($to, '/[0-9]+/'))
            return pyg_highlight::invalid_attr_error('to');
        if (pyg_highlight::invalid($pygmentize, '/[0-9a-zA-Z\-_\/]*\/pygmentize/'))
            return pyg_highlight::invalid_attr_error('pygmentize');

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

    private function invalid($subject, $pattern) {
        preg_match($pattern, $subject, $matches);
        return count($matches) == 0 || strlen($matches[0]) != strlen($subject);
    }

    private function invalid_attr_error($msg) {
        return "<p>pyg_highlight: invalid value for attribute '$msg' </p>";
    }

    private function snippet_filter_available() {
        return stripos(shell_exec('pygmentize -L filters'), '* snippet') !== False;
    }

}

?>
