<?php

function pyg_highlight_invalid($subject, $pattern) {
    preg_match($pattern, $subject, $matches);
    return count($matches) == 0 || strlen($matches[0]) != strlen($subject);
}

function pyg_highlight_invalid_attr_error($msg) {
    return "<p>pyg_highlight: invalid value for attribute '$msg' </p>";
}

function pyg_highlight_snippet_filter_available() {
    return stripos(shell_exec('pygmentize -L filters'), '* snippet') !== False;
}

/**
 * Textpattern tag for syntax highlighting.
 */
function pyg_highlight($atts, $thing='') {
    extract(lAtts(array(
        'file' => '',
        'from' => '1',
        'to' => '' . PHP_INT_MAX,
        'linenos' => ''
    ), $atts));

    global $txpcfg;
    $pygmentize = $txpcfg['pygmentize'];
    if ($pygmentize == '') {
        $pygmentize = '/usr/bin/pygmentize';
    }

    if (pyg_highlight_invalid($file, '/[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)*\.?/'))
        return pyg_highlight_invalid_attr_error('file');
    if (pyg_highlight_invalid($linenos, '/[a-zA-Z0-9_\-]*/'))
        return pyg_highlight_invalid_attr_error('linenos');
    if (pyg_highlight_invalid($from, '/[0-9]+/'))
        return pyg_highlight_invalid_attr_error('from');
    if (pyg_highlight_invalid($to, '/[0-9]+/'))
        return pyg_highlight_invalid_attr_error('to');
    if (pyg_highlight_invalid($pygmentize, '/[0-9a-zA-Z\-_\/]*\/pygmentize/'))
        return pyg_highlight_invalid_attr_error('pygmentize');

    global $pyg_highlight_css_included;
    if (!$pyg_highlight_css_included) {
        $o = '<style><!--';
        $o .= shell_exec("$pygmentize -f html -S default -a .highlight");
        $o .= '--></style>';
        $pyg_highlight_css_included = 1;
    }

    $path = dirname($txpcfg['txpath']) . '/files/' . $file;

    if (!file_exists($path)) {
        return "<p>pyg_highlight: File '$path' does not exist.</p>";
    }

    $path = escapeshellarg($path);
    $linenos = escapeshellarg($linenos);

    if (pyg_highlight_snippet_filter_available()) {
        $from = escapeshellarg($from);
        $to = escapeshellarg($to);
        $o .= shell_exec("$pygmentize -f html -F snippet:fromline=$from,toline=$to -O linenostart=$from,linenos=$linenos $path");
    } else if (strcmp($from, '1') == 0 && strcmp($to, ''.PHP_INT_MAX) == 0) {
        $o .= shell_exec("$pygmentize -f html -O linenos=$linenos $path");
    } else {
        return "<p>pyg_highlight: pygments-snippet-filter not installed.</p>";
    }
    return $o;
}

?>
