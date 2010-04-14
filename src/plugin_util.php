<?php
/**
 * @return the plugin meta data as a string
 */
function plugin_info($plugin) {
    $info = <<<EOF
       Name: {$plugin['name']}
    Version: {$plugin['version']}
     Author: {$plugin['author']}
 Author-URI: {$plugin['author_uri']}
Description: {$plugin['description']}
       Type: {$plugin['type']}
      Flags: {$plugin['flags']}

EOF;
    return $info;
}

/**
 * Encodes all plugin data into a base-64 string that can be pasted
 * into the installation text-area in the backend adminstration.
 *
 * @return  the plugin in base-64 encoding (string).
 */
function plugin_to_base64($plugin) {
    return trim(chunk_split(base64_encode(serialize($plugin)), 72)). "\n";
}

/**
 * Constructs a SQL statement that can be used to insert the plugin
 * into a textpattern database. Tested with textpattern-4.2.0.
 *
 * @return  the plugin as a SQL statement (string)
 */
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
    $f['flags'] = mysql_real_escape_string($plugin['flags']);

    // TODO: find out what plugin order means
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
    {$f['flags']}
 )

EOF;
    return $sql;
}

/**
 * Transforms textile markup into HTML.
 *
 * @return  generated HTML markup (string)
 */
function textile_to_html($s) {
    include_once('lib/classTextile.php');
    $textile = new Textile();
    return $textile->TextileThis($s);
}

function extract_code($file) {
    return substr(file_get_contents($file), 5, -3);
}

// -------------------------------------------------------------
// MAIN
// -------------------------------------------------------------

if ($argc != 2) {
    print "Usage: plugin_util.php [info|base64|sql]";
    exit(-1);
} else if (strcmp($argv[1], "info") == 0) {
    include_once("src/plugin_config.php");
    global $plugin;
    print plugin_info($plugin);
} else if (strcmp($argv[1], "base64") == 0) {
    include_once("src/plugin_config.php");
    global $plugin;
    print plugin_to_base64($plugin);
} else if (strcmp($argv[1], "sql") == 0) {
    include_once("src/plugin_config.php");
    global $plugin;
    print plugin_to_sql($plugin);
}

?>
