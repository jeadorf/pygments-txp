<?php

/* interface */

function jea_highlight($attrs, $thing='') {
    return jea_highlight::highlight($attrs, $thing);
}

/* installation */

register_callback('jea_pygments_txp_on_install', 'plugin_lifecycle.jea_pygments_txp', 'installed');
register_callback('jea_pygments_txp_on_delete', 'plugin_lifecycle.jea_pygments_txp', 'deleted');

// TODO: use option system instead of plugging into standard preferences tab
function jea_pygments_txp_on_install() {
    set_pref('jea_highlight_pygmentize', '/usr/bin/pygmentize', 'admin', 1, 'text_input', 50);
    safe_delete('txp_lang', "name='jea_highlight_pygmentize'");
    safe_insert('txp_lang', "name='jea_highlight_pygmentize',data='Pygmentize script location',lang='en-gb',event='prefs'");
}

function jea_pygments_txp_on_delete() {
    safe_delete('txp_prefs', "name='jea_highlight_pygmentize'");
    safe_delete('txp_lang', "name='jea_highlight_pygmentize'");
}

/* implementation */

class jea_highlight { // serves as namespace only

    private static $patterns = array(
        'file' => '/[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)*\.?/',
        'linenos' => '/[a-zA-Z0-9_\-]*/',
        'from' => '/[0-9]+/',
        'to' => '/[0-9]+/',
        'pygmentize' => '/[0-9a-zA-Z\-_\/]*\/pygmentize/'
    );

    private static $css_included = False;

    public static function highlight($raw_attrs, $thing='') {
        global $txpcfg;

        $attrs = lAtts(array(
            'file' => '',
            'from' => '1',
            'to' => '' . PHP_INT_MAX,
            'linenos' => ''
        ), $raw_attrs);

        $attrs['pygmentize'] = get_pref('jea_highlight_pygmentize', '/usr/bin/pygmentize');

        if (jea_highlight::invalid_exists($attrs, $ret_msg)) {
            return $ret_msg;
        }

        extract($attrs);

        if (!jea_highlight::$css_included) {
            $o = '<style><!--';
            $o .= shell_exec("$pygmentize -f html -S default -a .highlight");
            $o .= '--></style>';
            jea_highlight::$css_included = 1;
        }

        $path = dirname($txpcfg['txpath']) . '/files/' . $file;

        if (!file_exists($path)) {
            return "<p>jea_highlight: File '$path' does not exist.</p>";
        }

        $path = escapeshellarg($path);
        $linenos = escapeshellarg($linenos);

        if (jea_highlight::snippet_filter_available()) {
            $from = escapeshellarg($from);
            $to = escapeshellarg($to);
            $o .= shell_exec("$pygmentize -f html -F snippet:fromline=$from,toline=$to -O linenostart=$from,linenos=$linenos $path");
        } else if (strcmp($from, '1') == 0 && strcmp($to, ''.PHP_INT_MAX) == 0) {
            $o .= shell_exec("$pygmentize -f html -O linenos=$linenos $path");
        } else {
            return "<p>jea_highlight: pygments-snippet-filter not installed.</p>";
        }
        return $o;
    }

    private static function invalid_exists($attrs, &$ret_msg) {
        foreach ($attrs as $n => $v) {
            if (jea_highlight::invalid($n, $v, jea_highlight::$patterns[$n], $ret_msg)) {
                return True;
            }
        }
    }

    private static function invalid($description, $subject, $pattern, &$ret_msg) {
        preg_match($pattern, $subject, $matches);
        if (count($matches) == 0 || strlen($matches[0]) != strlen($subject)) {
            $ret_msg = "<p>jea_highlight: invalid value for attribute '$description' </p>";
            return True;
        } else {
            return False;
        }
    }

    private static function snippet_filter_available() {
        return stripos(shell_exec('pygmentize -L filters'), '* snippet') !== False;
    }

}

?>