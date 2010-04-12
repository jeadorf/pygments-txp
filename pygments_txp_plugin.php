<?php

/**
 * Textpattern plugin that provides tags for syntax highlighting.
 */

$plugin['version'] = '0.1';
$plugin['author'] = 'Julius Adorf';
$plugin['author_uri'] = 'http://bitbucket.org/jeadorf/pygments-txp';
$plugin['description'] = 'Syntax highlighting using pygments';

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 0; 

@include_once('txp_plugin_sql.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h1. Pygments-binding for Textpattern 

# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---

function pyg_highlight($atts, $thing='') {
    extract(lAtts(array(
        'lang' => '',
        'file' => ''
    ), $atts));

    return "language=$lang, file=$file";
}

# --- END PLUGIN CODE ---

?>
