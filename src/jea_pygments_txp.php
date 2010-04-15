<?php

/* interface */

function jea_highlight($attrs, $thing='') {
    return jea_highlight::highlight($attrs, $thing);
}

/* installation */

if (@txpinterface == 'admin') {
    @require_plugin('soo_plugin_pref');
    add_privs('jea_pygments_txp', '1,2');
    add_privs('plugin_prefs.jea_pygments_txp', '1,2');
    register_callback('jea_pygments_txp_on_prefs', 'plugin_prefs.jea_pygments_txp');
}

function jea_pygments_txp_defaults() {
    return array(
            'pygmentize' => array(
                'val'   => '/usr/bin/pygmentize',
                'html'  => 'text_input',
                'text'  => 'Pygmentize script location'
            )
    );
}

function jea_pygments_txp_on_prefs($event, $step) {
    if (function_exists('soo_plugin_pref')) {
        return soo_plugin_pref($event, $step, jea_pygments_txp_defaults());
    } else if ( substr($event, 0, 12) == 'plugin_prefs' ) {
        $plugin = substr($event, 13);
        $message = '<p style=\'text-align: center\'><br /><strong>' . gTxt('edit') . " $plugin " .
            gTxt('edit_preferences') . ':</strong><br /><br/>' .
            gTxt('install_plugin') . ' <a
            href="http://ipsedixit.net/txp/92/soo_plugin_pref">soo_plugin_pref</a><br/></p>';
        pagetop(gTxt('edit_preferences') . " &#8250; $plugin", $message);
        print $message;
    }
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

        $attrs['pygmentize'] = get_pref('jea_pygments_txp.pygmentize', '/usr/bin/pygmentize');

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
