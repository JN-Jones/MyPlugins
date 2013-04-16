<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}
$page->add_breadcrumb_item($lang->myplugins, "index.php?module=myplugins");
$page->add_breadcrumb_item($lang->myplugins_verification, "index.php?module=myplugins-verification");

$page->output_header($lang->myplugins);

$plugins_list = get_plugins_list();
$url = "http://jonesboard.de/plugins-api.php?action=verification";

foreach($plugins_list as $plugin_file)
{
	require_once MYBB_ROOT."inc/plugins/".$plugin_file;
	$codename = str_replace(".php", "", $plugin_file);
	$infofunc = $codename."_info";

	if(!function_exists($infofunc))
	{
		continue;
	}

	$plugininfo = $infofunc();
	$plugininfo['codename'] = $codename;

	if($plugininfo['author'] != "Jones")
	    continue;
	    
	if(isset($plugininfo['myplugins_id']))
	    $code = $plugininfo['myplugins_id'];
	else
		$code = $plugininfo['codename'];

	$dplugins[$code] = $plugininfo;

	$url .= "&amp;plugins[]={$code}";
}

$content = fetch_remote_file($url);
if($content) {
	require_once MYBB_ROOT."inc/class_xml.php";
	$parser = new XMLParser($content);
	$tree = $parser->get_tree();
	if(!is_array($tree['output']))
	{
		$oplugins = array();
	}
	else
	{
		foreach($tree['output'] as $output) {
			//$kennung = "";

    		if($output == "output")
			    continue;
			
			if(is_array($output)) {
				foreach($output as $key => $value) {
//				    $kennung = $value['attributes']['kennung'];

					$table = new Table;
					$table->construct_header($lang->file);
					$table->construct_header($lang->md5, array("width" => "300", "colspan"=>"2"));
					
					foreach($value['file'] as $key2 => $value2) {

						if(is_array($value2) && array_key_exists("attributes", $value2)) {
//							$oplugins[$kennung][$value2['attributes']['file']] = $value2['attributes']['md5'];
							create_file($value2['attributes']['file'], $value2['attributes']['md5']);
   						} elseif($key2 == "attributes") {
//							$oplugins[$kennung][$value2['file']] = $value2['md5'];
							create_file($value2['file'], $value2['md5']);
						}
					}

					$table->output($dplugins[$value['attributes']['kennung']]['name']);
				}
			}
		}
	}
} else {
	$table = new Table;
	$table->construct_header($lang->file);
	$table->construct_header($lang->md5, array("colspan"=>"2"));
	$table->construct_cell($lang->no_plugins, array("colspan"=>"3"));
	$table->construct_row();
	$table->output();
}

$page->output_footer();

function create_file($file, $md5)
{
	global $config, $lang, $table;
	$file = str_replace("{admin_dir}", $config['admin_dir'], $file);
	$color = "green"; $status = "";
	if(!file_exists(MYBB_ROOT.$file)) {
		$color = "red";
		$status = "<br />".$lang->missing;
	} else {
		$files = implode("\n", file(MYBB_ROOT.$file));
		$md52 = md5($files);
		if($md5 != $md52) {
			$color = "red";
			$status = "<br />".$lang->changed;
		}
	}
	$table->construct_cell("<span style=\"color: {$color};\"><b>{$file}</b>{$status}</span>");
	$table->construct_cell(htmlentities($md5));
	$table->construct_cell(htmlentities($md52));
	$table->construct_row();
}
?>