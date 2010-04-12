<?php
$plugin['name'] = 'pygments';
$plugin['version'] = '0.1';
$plugin['author'] = 'Julius Adorf';
$plugin['author_uri'] = 'http://bitbucket.org/jeadorf/pygments-txp';
$plugin['description'] = 'Syntax highlighting using pygments';
// TODO: textile this
$plugin['help'] = file_get_contents('HELP.txt');
// TODO: refactor this
$plugin['code'] = substr(file_get_contents('pygments.php'), 5, -3);
$plugin['md5'] = md5($plugin['code']);

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 0; 
?>
