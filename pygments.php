<?php

/**
 * Textpattern tag for syntax highlighting.
 */
function pyg_highlight($atts, $thing='') {
    extract(lAtts(array(
        'lang' => '',
        'file' => ''
    ), $atts));

    return "language=$lang, file=$file";
}

?>
