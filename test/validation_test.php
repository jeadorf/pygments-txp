<?php

require_once('PHPUnit/Framework.php');
require_once('../src/jea_pygments_txp.php');

class ValidationTest extends PHPUnit_Framework_TestCase {

    /** @dataProvider validation_test_data */
    function test_valid($name, $strval, $valid) {
        $this->assertEquals($valid, jea_pygments_txp::valid($name, $strval, $ret_msg));
    }

    function validation_test_data() {
        return array(
            /* file positives */
            array('file', 'plugin.php', True),
            array('file', 'plugin_foo.php', True),
            array('file', 'plugin-bar.php', True),
            array('file', 'BIG.txt', True),
            array('file', 'some.py.orig', True),
            /* file negatives */
            array('file', '.plugin.php', False),
            array('file', './plugin_foo.php', False),
            array('file', '/etc/xyz', False),
            array('file', '.htxyz', False),
            array('file', '../some', False),
            /* from positives */
            array('from', '1', True),
            array('from', '1245', True),
            /* from negatives */
            array('from', '0', False),
            array('from', '-2', False),
            array('from', 'abc', False),
            array('from', '/any', False),
            /* inline_css positives */
            array('inline_css', 'On', True),
            array('inline_css', 'TrUe', True),
            array('inline_css', 'OfF', True),
            array('inline_css', 'FaLse', True),
            array('inline_css', '0', True),
            array('inline_css', '1', True),
            /* inline_css negatives */
            array('inline_css', 'off ', False),
            array('inline_css', '/some', False),
            array('inline_css', '2', False),
            /* lang positives */
            array('lang', 'python', True),
            array('lang', 'alang-3', True),
            /* lang negatives */
            array('lang', '/somepl', False),
            array('lang', '..', False),
            /* linenos positives */
            array('linenos', 'On', True),
            array('linenos', 'TrUe', True),
            array('linenos', 'OfF', True),
            array('linenos', 'FaLse', True),
            array('linenos', '0', True),
            array('linenos', '1', True),
            /* linenos negatives */
            array('linenos', 'off ', False),
            array('linenos', '/some', False),
            array('linenos', '2', False),
            /* pygmentize positives */
            array('pygmentize', '/usr/bin/pygmentize', True),
            array('pygmentize', '/usr/local/bin/pygmentize', True),
            array('pygmentize', 'pygmentize', True),
            /* pygmentize negatives */
            array('pygmentize', 'ls', False),
            array('pygmentize', '/usr/bin/pygmentize; cat /some/file', False),
            array('pygmentize', '/dir/../pygmentize', False),
            array('pygmentize', '/dir/.some/pygmentize', False),
            array('pygmentize', '/usr/bin/ls', False),
            /* style positives */
            array('style', 'python', True),
            array('style', 'alang-3', True),
            /* style negatives */
            array('style', '/somesty', False),
            array('style', '..', False),
            /* to positives */
            array('to', '1', True),
            array('to', '1245', True),
            /* to negatives */
            array('to', '0', False),
            array('to', '-2', False),
            array('to', 'abc', False),
            array('to', '/any', False),
            /* undefined */
            array('someundefined', 'foo', False)
        );
    }

}

?>
