<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}
$page->add_breadcrumb_item($lang->myplugins, "index.php?module=myplugins");
$page->add_breadcrumb_item($lang->download, "index.php?module=myplugins-download");

$page->output_header($lang->download);

$url = "http://jonesboard.de/plugins-api.php?action=get_file&plugin={$mybb->input['plugin']}";
$language = substr($lang->language, 0, strpos($lang->language, "/"));
if($language != "english")
    $url .= "&lang={$language}";

echo $lang->get_package;

$content = fetch_remote_file($url);

if($content) {
	require_once MYBB_ROOT."inc/class_xml.php";
	$parser = new XMLParser($content);
	$tree = $parser->get_tree();

	$file = $tree['output']['file']['value'];
	if(isset($tree['output']['lang']))
	    $langfile = $tree['output']['lang']['value'];
} else {
	flash_message($lang->no_connection, 'error');
	admin_redirect("index.php?module=myplugins");	
}

if(!$file) {
	flash_message($lang->invalid_file, 'error');
	admin_redirect("index.php?module=myplugins");	
}
$uselang = false;
if(isset($langfile)) {
	if($langfile != false) {
		echo $lang->download_package1;
	} else {
		echo $lang->download_package2;
		$uselang = true;
	}
} else {
	echo $lang->download_package3;
}

echo $lang->create_temp;
$success = true;
if(!is_dir(MYBB_ROOT."myplugins-temp/"))
	$success = mkdir(MYBB_ROOT."myplugins-temp/");
if(!$success) {
	flash_message($lang->create_temp_error, 'error');
	admin_redirect("index.php?module=myplugins");	
}
echo $lang->sprintf($lang->start_download, $mybb->input['plugin']);
$file = fetch_remote_file($file);
$f = fopen(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}.zip", "w+b");
if($f === false) {
	flash_message($lang->write_error, 'error');
	admin_redirect("index.php?module=myplugins");
}
fwrite($f, $file);
fclose($f);
echo $lang->file_saved;
if($uselang) {
	echo $lang->start_download_lang;
	$file = fetch_remote_file($langfile);
	$f = fopen(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}-lang.zip", "w+b");
	if($f === false) {
		flash_message($lang->write_error, 'error');
		admin_redirect("index.php?module=myplugins");
	}
	fwrite($f, $file);
	fclose($f);
	echo $lang->file_saved;
}

echo $lang->extract;
if(!class_exists("ZipArchive")) {
	flash_message($lang->extract_error, 'error');
	admin_redirect("index.php?module=myplugins");
}
$zip = new ZipArchive();
$zip->open(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}.zip");
$success = $zip->extractTo(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}/");
$zip->close();
if(!$success) {
	flash_message($lang->extract_error, 'error');
	admin_redirect("index.php?module=myplugins");
}
if($uselang) {
	$zip = new ZipArchive();
	$zip->open(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}-lang.zip");
	$success = $zip->extractTo(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}/");
	$zip->close();
	if(!$success) {
		flash_message($lang->extract_error, 'error');
		admin_redirect("index.php?module=myplugins");
	}
}

echo $lang->move;
$dir = opendir(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}/");
$dirs = array();
while(($d = readdir($dir)) !== false) {
	if($d == "." || $d == "..")
	    continue;
	
	$dirs[] = $d;
}
closedir($dir);
if(sizeOf($dirs) != 1) {
	flash_message($lang->move_error, 'error');
	admin_redirect("index.php?module=myplugins");	
}
$dir = MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}/{$dirs[0]}/";
if(!is_dir($dir."inc/plugins")) {
	flash_message($lang->move_error, 'error');
	admin_redirect("index.php?module=myplugins");
}
//Let's do it
move($dir);

echo $lang->delete_temp;
recrmdir(MYBB_ROOT."myplugins-temp/");

echo $lang->download_complete;

$page->output_footer();

function move($direction) {
	global $lang, $mybb;
	if(substr($direction, -1, 1) != "/")
	    $direction .= "/";
	if(!is_dir($direction))
	    die($lang->internal_error);
	$dir = opendir($direction);
	while(($new = readdir($dir)) !== false) {
		if($new == "." || $new == "..")
		    continue;
				
		if(is_file($direction.$new)) {
			$old_dir = $direction.$new;
			$offset = strpos($old_dir, "/", $offset+1);
			$offset = strpos($old_dir, "/", $offset+1);
			$offset = strpos($old_dir, "/", $offset+1);
			$start = strpos($old_dir, "/", $offset+1);
			$relative = substr($old_dir, $start+1);
			if(substr($relative, 0, 6) == "admin/")
			    $relative = $mybb->config['admin_dir']."/".substr($relative, 6);

			$new_dir = MYBB_ROOT.$relative;
			
			rename($old_dir, $new_dir);
			echo $lang->sprintf($lang->move_to, str_replace(MYBB_ROOT, "", $old_dir), str_replace(MYBB_ROOT, "", $new_dir));
		} elseif(is_dir($direction.$new)) {
			move($direction.$new);
		}
	}
	closedir($dir);
}

function recrmdir($direction) {
	global $lang;
	if(substr($direction, -1, 1) != "/")
	    $direction .= "/";
	if(!is_dir($direction))
	    die($lang->internal_error);
	$dir = opendir($direction);
	while(($new = readdir($dir)) !== false) {
		if($new == "." || $new == "..")
		    continue;

		if(is_file($direction.$new)) {
			unlink($direction.$new);
		} elseif(is_dir($direction.$new)) {
			recrmdir($direction.$new);
		}
	}
	closedir($dir);
	rmdir($direction);
}
?>