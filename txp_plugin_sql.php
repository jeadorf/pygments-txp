<?php

// Either copy classTextile.php to your plugin directory, or uncomment the following
// line and edit it to give the location where classTextile.php can be found
#ini_set('include_path', ini_get('include_path') . ':/full/path/to/textile');

if (empty($test)) {
	echo compile_plugin();
	exit;
}

// -----------------------------------------------------

function extract_section($lines, $section) {
	$result = "";
	
	$start_delim = "# --- BEGIN PLUGIN $section ---";
	$end_delim = "# --- END PLUGIN $section ---";

	$start = array_search($start_delim, $lines) + 1;
	$end = array_search($end_delim, $lines);

	$content = array_slice($lines, $start, $end-$start);

	return join("\n", $content);

}

function compile_plugin($file='') {
	global $plugin;

	if (empty($file))
		$file = $_SERVER['SCRIPT_FILENAME'];

	if (!isset($plugin['name'])) {
		$plugin['name'] = basename($file, '.php');
	}

	# Read the contents of this file, and strip line ends
	$content = file($file);
	for ($i=0; $i < count($content); $i++) {
		$content[$i] = rtrim($content[$i]);
	}

	$plugin['help'] = extract_section($content, 'HELP');
	$plugin['code'] = extract_section($content, 'CODE');

	@include('classTextile.php');
	if (class_exists('Textile')) {
		$textile = new Textile();
		$plugin['help'] = $textile->TextileThis($plugin['help']);
	}

	$plugin['md5'] = md5( $plugin['code'] );

	// to produce a copy of the plugin for distribution, load this file in a browser. 

	header('Content-type: text/plain');
    $plugin['name'] = mysql_real_escape_string($plugin['name']);
    $plugin['author'] = mysql_real_escape_string($plugin['author']);
    $plugin['author_uri'] = mysql_real_escape_string($plugin['author_uri']);
    $plugin['version'] = mysql_real_escape_string($plugin['version']);
    $plugin['description'] = mysql_real_escape_string($plugin['description']);
    $plugin['help'] = mysql_real_escape_string($plugin['help']);
    $plugin['code'] = mysql_real_escape_string($plugin['code']);
    $plugin['md5'] = mysql_real_escape_string($plugin['md5']);
    $plugin['type'] = mysql_real_escape_string($plugin['type']);

	$header = <<<EOF
    DELETE FROM txp_plugin WHERE name='{$plugin["name"]}';
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
        '{$plugin['name']}',
        1, 
        '{$plugin['author']}',
        '{$plugin['author_uri']}',
        '{$plugin['version']}',
        '{$plugin['description']}',
        '{$plugin['help']}',
        '{$plugin['code']}',
        '{$plugin['code']}',
        '{$plugin['md5']}',
        '{$plugin['type']}',
        5,
        0 
     )
EOF;

	return $header . "\n";

}


?>
