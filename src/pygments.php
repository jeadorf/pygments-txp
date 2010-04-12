<?php

function pyg_highlight_invalid($subject, $pattern) {
    preg_match($pattern, $subject, $matches);
    return count($matches) == 0 || strlen($matches[0]) != strlen($subject);
}

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
    if (pyg_highlight_invalid($file, '/[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)*\.?/')) {
        return "<p>pyg_highlight: Filename not allowed.</p>";
    }

    // Perform rigorous validity check on the supplied language.
    // Unusual language names will not be matched. This is intentional.
    if (pyg_highlight_invalid($lang, '/[a-zA-Z0-9_\-]+/')) {
        return "<p>pyg_highlight: Language not allowed.</p>";
    }

    global $pyg_highlight_css_included;
    if (!$pyg_highlight_css_included) {
        $o = '<style><!--';
        $o .= `pygmentize -f html -S colorful -a .highlight`;
        $o .= '--></style>';
        $o = $o . "--></style>";
        $pyg_highlight_css_included = 1;
    }

    // This is the most dangerous line in the complete PHP script.
    global $txpcfg;
    $path = escapeshellarg(dirname($txpcfg['txpath']) . '/files/' . $file);
    $o .=`pygmentize -f html $path`;

    return $o; 
}

?>
