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

echo "Bekomm zust&auml;ndiges Paket<br />";

$content = fetch_remote_file($url);

if($content) {
	require_once MYBB_ROOT."inc/class_xml.php";
	$parser = new XMLParser($content);
	$tree = $parser->get_tree();

	$file = $tree['output']['file']['value'];
	if(isset($tree['output']['lang']))
	    $langfile = $tree['output']['lang']['value'];
} else {
	flash_message("Kein Server", 'error');
	admin_redirect("index.php?module=myplugins");	
}

if(!$file) {
	flash_message("Keine Datei gefunden", 'error');
	admin_redirect("index.php?module=myplugins");	
}
$uselang = false;
if(isset($langfile)) {
	if($langfile != false) {
		echo "Es wurde kein Paket in deiner Sprache gefunden, das englische Paket wird heruntergeladen<br />";
	} else {
		echo "Englisches Paket + dein Sprachpaket werden runtergeladen<br />";
		$uselang = true;
	}
} else {
	echo "Dein Paket wird heruntergeladen<br />";
}

echo "Erstelle tempor&auml;res Verzeichniss<br />";
$success = true;
if(!is_dir(MYBB_ROOT."myplugins-temp/"))
	$success = mkdir(MYBB_ROOT."myplugins-temp/");
if(!$success) {
	flash_message("Konnte das Verzeichnis \"myplugins-temp\" nicht erstellen, bitte erstelle es manuell", 'error');
	admin_redirect("index.php?module=myplugins");	
}
echo "Starte Download des Plugins \"{$mybb->input['plugin']}\"<br />";
$file = fetch_remote_file($file);
$f = fopen(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}.zip", "w+b");
if($f === false) {
	flash_message("Konnte die Tempor&auml;re Datei nicht &ouml;ffnen", 'error');
	admin_redirect("index.php?module=myplugins");
}
fwrite($f, $file);
fclose($f);
echo "Datei erfolgreich lokal gespeichert<br />";
if($uselang) {
	echo "Starte Download der Sprachdatei<br />";
	$file = fetch_remote_file($langfile);
	$f = fopen(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}-lang.zip", "w+b");
	if($f === false) {
		flash_message("Konnte die Tempor&auml;re Datei nicht &ouml;ffnen", 'error');
		admin_redirect("index.php?module=myplugins");
	}
	fwrite($f, $file);
	fclose($f);
	echo "Datei erfolgreich lokal gespeichert<br />";
}

echo "Entpacke .zips...<br />";
if(!class_exists("ZipArchive")) {
	flash_message("Konnte die .zip Datei(en) nicht entpacken (1)", 'error');
	admin_redirect("index.php?module=myplugins");
}
$zip = new ZipArchive();
$zip->open(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}.zip");
$success = $zip->extractTo(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}/");
$zip->close();
if(!$success) {
	flash_message("Konnte die .zip Datei(en) nicht entpacken (2)", 'error');
	admin_redirect("index.php?module=myplugins");
}
if($uselang) {
	$zip = new ZipArchive();
	$zip->open(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}-lang.zip");
	$success = $zip->extractTo(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}/");
	$zip->close();
	if(!$success) {
		flash_message("Konnte die .zip Datei(en) nicht entpacken (3)", 'error');
		admin_redirect("index.php?module=myplugins");
	}
}

echo "Verschiebe alle Dateien<br />";
$dir = opendir(MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}/");
$dirs = array();
while(($d = readdir($dir)) !== false) {
	if($d == "." || $d == "..")
	    continue;
	
	$dirs[] = $d;
}
closedir($dir);
if(sizeOf($dirs) != 1) {
	flash_message("Konnte den Ordner nicht genau bestimmen (1)", 'error');
	admin_redirect("index.php?module=myplugins");	
}
$dir = MYBB_ROOT."myplugins-temp/{$mybb->input['plugin']}/{$dirs[0]}/";
if(!is_dir($dir."inc/plugins")) {
	flash_message("Konnte den Ordner nicht genau bestimmen (2)", 'error');
	admin_redirect("index.php?module=myplugins");
}
//Let's do it
move($dir);

echo "Versuche alle tempor&auml;re Dateien zu l&ouml;schen<br />";
recrmdir(MYBB_ROOT."myplugins-temp/");

echo "<b>Download komplett</b>";

$page->output_footer();

function move($direction) {
	if(substr($direction, -1, 1) != "/")
	    $direction .= "/";
	if(!is_dir($direction))
	    die("Schwerer Fehler");
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
			$new_dir = str_replace("myplugins-temp/", "", $old_dir);
			$new_dir = MYBB_ROOT.$relative;
			
			rename($old_dir, $new_dir);
			echo "Verschiebe {$old_dir} -> {$new_dir}<br />";
		} elseif(is_dir($direction.$new)) {
			move($direction.$new);
		}
	}
	closedir($dir);
}

function recrmdir($direction) {
	if(substr($direction, -1, 1) != "/")
	    $direction .= "/";
	if(!is_dir($direction))
	    die("Schwerer Fehler");
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