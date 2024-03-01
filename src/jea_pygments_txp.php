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
        'inline_css' => '1',
        'linenos' => '0',
        'pygmentize' => '/usr/bin/pygmentize',
        'style' => 'default',
    );

    public static $patterns = array(
        'file' => '/[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)*\.?/',
        'from' => '/[1-9][0-9]*/',
        'inline_css' => '/[A-Za-z01]+/',
        'lang' => '/[a-zA-Z0-9_\-]*/',
        'linenos' => '/[A-Za-z01]+/',
        'pygmentize' => '/([0-9a-zA-Z\-_\/]*\/)?pygmentize/',
        'style' => '/[a-zA-Z0-9_\-]*/',
        'to' => '/[1-9][0-9]*/',
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

    public static function get_valid($name, $attrs = NULL) {
        if ($attrs !== NULL && array_key_exists($name, $attrs)) {
            $val = $attrs[$name];
        } else {
            $defaultval = NULL;
            if (array_key_exists($name, jea_pygments_txp::$defaults)) {
                $defaultval = jea_pygments_txp::$defaults[$name];
            }
            $val = get_pref("jea_pygments_txp.$name", $defaultval);
        }

        if ($val === NULL || jea_pygments_txp::valid($name, $val, $ret_msg)) {
            return $val;
        } else {
            throw new Exception($ret_msg);
        }
    }

    public static function valid($name, $strval, &$ret_msg) {
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

        $cmd = escapeshellcmd($pygmentize);
        $cmd .= ' -f html';

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

        if ($lang === NULL && $file !== NULL) {
            $lang = jea_highlight::guess_lexer($file);
        }
        if ($lang === NULL) {
            $cmd .= ' -g';
        } else {
            $cmd .= ' -l '.escapeshellarg($lang);
        }

        if ($file !== NULL) {
            global $txpcfg;
            $path = dirname($txpcfg['txpath']) . '/files/' . $file;
            if (!file_exists($path)) {
                throw new Exception("<p>jea_pygments_txp: File '$file' does not exist.</p>");
            }
            $thing = file_get_contents($path);
        }

        if ($from !== NULL || $from !== '1' || $to !== NULL) {
            $lines = explode("\n", $thing);
            if ($from === NULL) {
                $f = 0;
            } else {
                $f = max(0, intval($from) - 1);
            }
            if ($to === NULL) {
                $t = count($lines);
            } else {
                $t = min(count($lines), intval($to));
            }
            $lines = array_slice($lines, $f, $t - $f);
            $thing = implode("\n", $lines);
            $cmd .= ' -O '.escapeshellarg('linenostart='.strval($f+1));
        }

        $o .= jea_pygments_txp::subprocess($cmd, $thing);
        return $o;
    }

    public static function guess_lexer($file) {
        $pygmentize = jea_pygments_txp::get_valid('pygmentize');
        return trim(shell_exec("$pygmentize -N ".escapeshellarg($file)));
    }

}

?>
