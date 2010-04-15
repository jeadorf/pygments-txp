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

/* default configuration */

class jea_pygments_txp {

    public static $defaults = array(
        'pygmentize' => '/usr/bin/pygmentize',
        'linenos' => '0'
    );

    public static function preferences() {
        return array(
            'pygmentize' => array(
                'val'   => jea_pygments_txp::$defaults['pygmentize'],
                'html'  => 'text_input',
                'text'  => 'Pygmentize script location'
            ),
            'linenos' => array(
                'val'   => jea_pygments_txp::$defaults['linenos'],
                'html'  => 'yesnoradio',
                'text'  => 'Line numbers'
            )
        );
    }

    public static function get_pref_value($name) {
        return get_pref("jea_pygments_txp.$name", jea_pygments_txp::$defaults[$name]);
    }

}

/* preferences management */

function jea_pygments_txp_on_prefs($event, $step) {
    if (function_exists('soo_plugin_pref')) {
        return soo_plugin_pref($event, $step, jea_pygments_txp::preferences());
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

/* highlight tag */

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
            'linenos' => jea_pygments_txp::get_pref_value('linenos')
        ), $raw_attrs);

        $attrs['pygmentize'] = jea_pygments_txp::get_pref_value('pygmentize');

        if (jea_highlight::invalid_exists($attrs, $ret_msg)) {
            return $ret_msg;
        }

        extract($attrs);
        var_dump($linenos);

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

        $cmd = "$pygmentize ";
        $cmd .= '-f html ';

        if (strcmp($from, '1') != 0 || strcmp($to, ''.PHP_INT_MAX) != 0) {
            if (jea_highlight::snippet_filter_available()) {
                $from = escapeshellarg($from);
                $to = escapeshellarg($to);
                $cmd .= "-F snippet:fromline=$from,toline=$to ";
                $cmd .= "-O linenostart=$from ";
            } else {
                return "<p>jea_highlight: pygments-snippet-filter not installed.</p>";
            }
        }
        if ($linenos !== '0') {
            $cmd .= '-O linenos=inline ';
        }
        $cmd .= "$path ";

        $o .= shell_exec($cmd);
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
