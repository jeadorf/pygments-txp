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
        'from' => '1',
        'inline_css' => '1',
        'lang' => '',
        'linenos' => '0',
        'pygmentize' => '/usr/bin/pygmentize',
        'style' => 'default',
        'to' => '65536'
    );

    public static $patterns = array(
        'file' => '/[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)*\.?/',
        'from' => '/[0-9]+/',
        'inline_css' => '/[a-zA-Z0-9_\-]*/',
        'lang' => '/[a-zA-Z0-9_\-]*/',
        'linenos' => '/[a-zA-Z0-9_\-]*/',
        'pygmentize' => '/[0-9a-zA-Z\-_\/]*\/pygmentize/',
        'style' => '/[a-zA-Z0-9_\-]*/',
        'to' => '/[0-9]+/'
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

    /** Retrieves a parameter value from preferences / given array / defaults and
     * validates its value. If it does not match the regular expression that describes the
     * set of allowed words, an exception is raised. */
    public function get_valid($name, $attrs = NULL) {
        if ($attrs !== NULL && array_key_exists($name, $attrs)) {
            $val = $attrs[$name];
        } else {
            $val = get_pref("jea_pygments_txp.$name", jea_pygments_txp::$defaults[$name]);
        }

        if (jea_highlight::invalid($name, $val, jea_pygments_txp::$patterns[$name], $ret_msg)) {
            throw new Exception($ret_msg);
            return ''; // if exceptions don't work for whatever reason
        } else {
            return $val;
        }
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
    $pygmentize = jea_pygments_txp::get_valid('pygmentize');
    $textile = new Textile();
    $help = $textile->TextileThis(shell_exec(escapeshellcmd("$pygmentize -L")));
    print "<div style='border: 1px solid ; margin: 30px auto; padding: 20px; width: 500px'>
           <strong>Pygmentize features generated with 'pygmentize -L'</strong>
           <hr/><p/>$help</div>";
}

/* highlight tag */

class jea_highlight { // serves as namespace only

    public static function highlight($raw_attrs, $thing='') {
        $lang = jea_pygments_txp::get_valid('lang', $raw_attrs);
        $file = jea_pygments_txp::get_valid('file', $raw_attrs);
        $linenos= jea_pygments_txp::as_bool(jea_pygments_txp::get_valid('linenos', $raw_attrs));
        $from = jea_pygments_txp::get_valid('from', $raw_attrs);
        $to = jea_pygments_txp::get_valid('to', $raw_attrs);
        $style = jea_pygments_txp::get_valid('style', $raw_attrs);
        $inline_css = jea_pygments_txp::as_bool(jea_pygments_txp::get_valid('inline_css', $raw_attrs));
        $pygmentize = jea_pygments_txp::get_valid('pygmentize');

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

    public static function invalid($description, $subject, $pattern, &$ret_msg) {
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
