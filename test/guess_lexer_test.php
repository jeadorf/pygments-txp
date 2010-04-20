<?php
require_once('PHPUnit/Framework.php');
require_once('../src/jea_pygments_txp.php');
require_once('mock.php');

class GuessLexerTest extends PHPUnit_Framework_TestCase {

    /** @dataProvider guess_lexer_test_data */
    function test_guess_lexer($file, $expected) {
        $this->assertEquals($expected, jea_highlight::guess_lexer($file));
    }

    function guess_lexer_test_data() {
        return array(
            array('file.php', 'php'),
            array('file.py', 'python'),
            array('file.txt', 'text'),
            array('file.html', 'html'),
        );
    }

}

?>
