<?php

/**
 * Textpattern tag for syntax highlighting.
 */
function pyg_highlight($atts, $thing='') {
    extract(lAtts(array(
        'lang' => '',
        'file' => ''
    ), $atts));

    // Perform rigorous validity check on the supplied filename.
    // Unusual file names will not be matched. This is intentional.
    // Unusual file names suck anyway.
    $pattern = '/[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)*\.?/';
    preg_match($pattern, $file, $matches);
    if (count($matches) == 0 || strlen($matches[0]) != strlen($file)) {
        return "<p>pyg_highlight: Not a valid filename. Filenames must match the PCRE $pattern!</p>";
    }

    // Perform rigorous validity check on the supplied language.
    // Unusual language names will not be matched. This is intentional.
    $pattern = '/[a-zA-Z0-9_\-]+/';
    preg_match($pattern, $lang, $matches);
    if (count($matches) == 0 || strlen($matches[0]) != strlen($lang)) {
        return "<p>pyg_highlight: Not a valid language. Languages must match the PCRE $pattern!</p>";
    }


    global $txpcfg; 
    $url = 'file://' . dirname($txpcfg['txpath']) . '/files/' . $file;
    return file_get_contents("http://localhost/Temp/test-env/pygmentize_cgi.py?lang=$lang&url=$url");
}

?>
