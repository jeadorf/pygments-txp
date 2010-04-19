<?php

/* interface */

function jea_highlight($attrs, $thing='') {
    try {
        return jea_highlight::highlight($attrs, $thing);
    } catch (Exception $e) {
        return '<p><pre>'.$e->getMessage().'</pre></p>';
    }
}

/* setup */

if (@txpinterface == 'admin') {
    @require_plugin('soo_plugin_pref');
    add_privs('jea_pygments_txp', '1,2');
    add_privs('plugin_prefs.jea_pygments_txp', '1,2');
    register_callback('jea_pygments_txp_on_prefs', 'plugin_prefs.jea_pygments_txp');
}

/* default configuration and utilities */

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

    public function get_valid($name, $attrs = NULL) {
        if ($attrs !== NULL && array_key_exists($name, $attrs)) {
            $val = $attrs[$name];
        } else {
            $val = get_pref("jea_pygments_txp.$name", jea_pygments_txp::$defaults[$name]);
        }

        if (jea_pygments_txp::valid($name, $val, $ret_msg)) {
            return $val;
        } else {
            throw new Exception($ret_msg);
        }
    }

    private static function valid($name, $strval, &$ret_msg) {
        if (array_key_exists($name, jea_pygments_txp::$patterns)) {
            preg_match(jea_pygments_txp::$patterns[$name], $strval, $matches);
            if (count($matches) == 0 || strlen($matches[0]) != strlen($strval)) {
                $ret_msg = "<p>jea_pygments_txp: invalid value for attribute '$name' </p>";
                return False;
            } else {
                return True;
            }
        } else {
            $ret_msg = "<p>jea_pygments_txp: invalid attribute '$name'</p>";
            return False;
        }
    }

    public static function as_bool($s) {
        $s = strtolower($s);
        return (strcmp($s, 'true') == 0
            || strcmp($s, 'on') == 0
            || strcmp($s, 'yes') == 0
            || strcmp($s, '1') == 0);
    }

    public static function subprocess($cmd, $input) {
        $dspec = array(
            0 => array("pipe", "r"), // stdin 
            1 => array("pipe", "w")  // stdout 
        );
        $p = proc_open($cmd, $dspec, $pipes, NULL, NULL); 
        if (is_resource($p)) {
            fwrite($pipes[0], $input);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            if (proc_close($p) == 0) {
                return $output;
            } else {
                throw new Exception("Child process exited with error");
            }
        } else {
            throw new Exception("Cannot execute command");
        }
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

class jea_highlight {

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
                throw new Exception("<p>jea_pygments_txp:t pygments-snippet-filter not installed.</p>";
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

        $o .= jea_pygments_txp::subprocess($cmd, '');
        return $o;
    }

    private static function snippet_filter_available() {
        $pygmentize = jea_pygments_txp::get_valid('pygmentize');
        return stripos(shell_exec("$pygmentize -L filters"), '* snippet') !== False;
    }

}

?>
