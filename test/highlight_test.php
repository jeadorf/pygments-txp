<?php

require_once('PHPUnit/Framework.php');
require_once('../src/jea_pygments_txp.php');

/* mock overrides */

$txpcfg['txpath'] = '.';

function get_pref($str, $defaultval) {
    return $defaultval;
}

/* test case */

class HighlightTest extends PHPUnit_Framework_TestCase {

    /** @dataProvider highlightTestData */
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

    function highlightTestData() {
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
           )
        );
    }

}

?>
