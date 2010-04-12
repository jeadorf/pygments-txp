<?php
/* Contains the whole bunch of woohoo that is used to compile the script
 * into a SQL statement or to base-64-encoded plugin data */

function plugin_info($plugin) {
    $info = <<<EOF
       Name: {$plugin['name']}
    Version: {$plugin['version']}
     Author: {$plugin['author']}
 Author-URI: {$plugin['author_uri']} 
Description: {$plugin['description']}
       Type: {$plugin['type']}   

EOF;

    return $info;
}

function plugin_to_base64($plugin) {
    return trim(chunk_split(base64_encode(serialize($plugin)), 72)). "\n";
}

function plugin_to_sql($plugin) {
    $f['name'] = mysql_real_escape_string($plugin['name']);
    $f['author'] = mysql_real_escape_string($plugin['author']);
    $f['author_uri'] = mysql_real_escape_string($plugin['author_uri']);
    $f['version'] = mysql_real_escape_string($plugin['version']);
    $f['description'] = mysql_real_escape_string($plugin['description']);
    $f['help'] = mysql_real_escape_string($plugin['help']);
    $f['code'] = mysql_real_escape_string($plugin['code']);
    $f['md5'] = mysql_real_escape_string($plugin['md5']);
    $f['type'] = mysql_real_escape_string($plugin['type']);


	$sql = <<<EOF
DELETE FROM txp_plugin WHERE name='{$f["name"]}';
INSERT INTO txp_plugin (
    name,
    status,
    author,
    author_uri,
    version,
    description,
    help,
    code,
    code_restore,
    code_md5,
    type,
    load_order,
    flags
  ) VALUES (
    '{$f['name']}',
    1, 
    '{$f['author']}',
    '{$f['author_uri']}',
    '{$f['version']}',
    '{$f['description']}',
    '{$f['help']}',
    '{$f['code']}',
    '{$f['code']}',
    '{$f['md5']}',
    '{$f['type']}',
    5,
    0 
 )

EOF;

    return $sql;
}

@include_once("plugin_config.php");

global $plugin;

if ($argc != 2) {
    print "Usage: plugin_util.php [info|base64|sql]";
    exit(-1);
} else if (strcmp($argv[1], "info") == 0) { 
    print plugin_info($plugin);
} else if (strcmp($argv[1], "base64") == 0) { 
    print plugin_to_base64($plugin);
} else if (strcmp($argv[1], "sql") == 0) { 
    print plugin_to_sql($plugin);
}

?>
