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
        'linenos' => '0',
        'inline_css' => '1',
        'style' => 'default'
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
            ),
            'inline_css' => array(
                'val'   => jea_pygments_txp::$defaults['inline_css'],
                'html'  => 'yesnoradio',
                'text'  => 'Inline CSS'
            ),
            'style' => array(
                'val'   => jea_pygments_txp::$defaults['style'],
                'html'  => 'text_input',
                'text'  => 'Highlighting style'
            )

        );
    }

    public static function get_string_pref($name) {
        return get_pref("jea_pygments_txp.$name", jea_pygments_txp::$defaults[$name]);
    }

    public static function as_bool($s) {
        $s = strtolower($s);
        return (strcmp($s, 'true') == 0
            || strcmp($s, 'on') == 0
            || strcmp($s, 'yes') == 0
            || strcmp($s, '1') == 0);
    }

}

/* preferences management */

function jea_pygments_txp_on_prefs($event, $step) {
    if (function_exists('soo_plugin_pref')) {
        soo_plugin_pref($event, $step, jea_pygments_txp::preferences());
        jea_pygments_txp_print_features();
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

function jea_pygments_txp_print_features() {
        require_once('lib/classTextile.php');
        $attrs = array('pygmentize' => jea_pygments_txp::get_string_pref('pygmentize'));
        if (jea_highlight::invalid_exists($attrs, $ret_msg)) {
            print $ret_msg;
            return;
        }
        extract($attrs);
        $textile = new Textile();
        $help = $textile->TextileThis(shell_exec(escapeshellcmd("$pygmentize -L")));
        print "<div style='border: 1px solid ; margin: 30px auto; padding: 20px; width: 500px'><strong>Pygmentize features generated with 'pygmentize -L'</strong><hr/><p/>$help</div>";
}

/* highlight tag */

class jea_highlight { // serves as namespace only

    private static $patterns = array(
        'lang' => '/[a-zA-Z0-9_\-]*/',
        'file' => '/[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)*\.?/',
        'linenos' => '/[a-zA-Z0-9_\-]*/',
        'from' => '/[0-9]+/',
        'to' => '/[0-9]+/',
        'style' => '/[a-zA-Z0-9_\-]*/',
        'inline_css' => '/[a-zA-Z0-9_\-]*/',
        'pygmentize' => '/[0-9a-zA-Z\-_\/]*\/pygmentize/'
    );

    public static function highlight($raw_attrs, $thing='') {
        $attrs = lAtts(array(
            'lang' => '',
            'file' => '',
            'from' => '1',
            'to' => '' . PHP_INT_MAX,
            'linenos' => jea_pygments_txp::get_string_pref('linenos'),
            'style' => jea_pygments_txp::get_string_pref('style'),
            'inline_css' => jea_pygments_txp::get_string_pref('inline_css')
        ), $raw_attrs);

        $attrs['pygmentize'] = jea_pygments_txp::get_string_pref('pygmentize');

        if (jea_highlight::invalid_exists($attrs, $ret_msg)) {
            return $ret_msg;
        }

        extract($attrs);
        $linenos = jea_pygments_txp::as_bool($linenos);
        $inline_css = jea_pygments_txp::as_bool($inline_css);

        $o = '';

        global $txpcfg;
        $path = dirname($txpcfg['txpath']) . '/files/' . $file;
        if (!file_exists($path)) {
            return "<p>jea_highlight: File '$path' does not exist.</p>";
        }

        $cmd = escapeshellcmd($pygmentize);
        $cmd .= ' -f html';
        if (strcmp($lang, '') != 0) {
            $cmd .= ' -l '.escapeshellarg($lang);
        }
        if (strcmp($from, '1') != 0 || strcmp($to, ''.PHP_INT_MAX) != 0) {
            if (jea_highlight::snippet_filter_available()) {
                $cmd .= ' -F '.escapeshellarg("snippet:fromline=$from,toline=$to");
                $cmd .= ' -O '.escapeshellarg("linenostart=$from");
            } else {
                return "<p>jea_highlight: pygments-snippet-filter not installed.</p>";
            }
        }
        if ($linenos) {
            $cmd .= ' -O linenos=inline';
        }
        if ($inline_css) {
            $cmd .= ' -O noclasses=True';
            // workaround for lost background issue
            // http://dev.pocoo.org/projects/pygments/ticket/427
            $csscmd = escapeshellcmd($pygmentize);
            $csscmd .= ' -f html';
            $csscmd .= ' -S ' . escapeshellarg($style);
            $csshack = shell_exec($csscmd);
            if (preg_match('/\.hll {([^\}]*)}/', $csshack, $matches)) {
                $cmd .= ' -O '.escapeshellarg('cssstyles='.substr($matches[0], 6, -1));
            }
        }
        $cmd .= ' -O '.escapeshellarg("cssclass=jea_pygments_txp_$style");
        $cmd .= ' -O '.escapeshellarg("style=$style");
        $cmd .= ' '.escapeshellarg($path);

        // print "<pre>$cmd</pre>";
        $o .= shell_exec($cmd);
        return $o;
    }

    static function invalid_exists($attrs, &$ret_msg) {
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
