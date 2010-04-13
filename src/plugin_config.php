<?php
include_once('src/plugin_util.php');

$plugin['name'] = 'pygments-txp';
$plugin['version'] = '0.1.dev';
$plugin['author'] = 'Julius Adorf';
$plugin['author_uri'] = 'http://bitbucket.org/jeadorf/pygments-txp';
$plugin['description'] = 'Syntax highlighting using pygments';
$plugin['help'] = textile_to_html(file_get_contents('README'));
$plugin['code'] = extract_code('src/pygments.php'); 
$plugin['md5'] = md5($plugin['code']);

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 0; 
?>
