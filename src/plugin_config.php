<?php
include_once('src/plugin_util.php');

$plugin['name'] = 'jea_pygments_txp';
$plugin['version'] = '0.1';
$plugin['author'] = 'Julius Adorf';
$plugin['author_uri'] = 'http://bitbucket.org/jeadorf/pygments-txp';
$plugin['description'] = 'Syntax highlighting using pygments';
$plugin['help'] = textile_to_html(file_get_contents('README'));
$plugin['code'] = extract_code('src/jea_pygments_txp.php');
$plugin['md5'] = md5($plugin['code']);

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 1;
$plugin['flags'] = 3;  // should be PLUGIN_LIFECYCLE_NOTIFY | PLUGIN_HAS_PREFS
?>
