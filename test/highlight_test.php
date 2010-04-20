<?php

require_once('PHPUnit/Framework.php');
require_once('../src/jea_pygments_txp.php');
require_once('mock.php');

/* test case */

class HighlightTest extends PHPUnit_Framework_TestCase {

    /** @dataProvider highlight_test_data */
    function test_highlight($attrs, $thing, $words, $nowords = array()) {
        try {
            $out = jea_highlight::highlight($attrs, $thing);
        } catch (Exception $e) {
            $out = $e->getMessage();
        }

        foreach ($words as $w) {
            $this->assertTrue(stripos($out, $w) !== False);
        }
        foreach ($nowords as $nw) {
            $this->assertTrue(stripos($out, $nw) === False);
        }
    }

    function highlight_test_data() {
        return array(
            // test case with code in tag content
            array(
                array('lang' => 'text'),
                'some text',
                array('<div', 'some', 'text')
           ),
           // test case with file
           array(
                array('file' => 'test.py'),
                '',
                array('<div', 'hypotenuse', 'sqrt', 'python')
           ),
           // test case with both content and file given
            array(
                array('file' => 'test.py'),
                'some text that should not be displayed',
                array('<div', 'hypotenuse', 'sqrt', 'python'),
                array('displayed', 'should')
           ),
           // test case with non-existing file
           array(
                array('file' => 'doesnotexist'),
                '',
                array('does', 'doesnotexist', 'not', 'exist'),
           ),
           // test case with style
           array(
                array('file' => 'test.py', 'style' => 'native'),
                '',
                array('class=', 'jea_pygments_txp_native', 'hypotenuse')
           )
        );
    }

}

?>
