<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("admin_config_plugins_activate_commit", "myplugins_activate_commit");
$plugins->add_hook("admin_config_plugins_deactivate_commit", "myplugins_deactivate_commit");

function myplugins_info()
{
	return array(
		"name"			=> "MyPlugins",
		"description"	=> "Adds an acp module for showing all informations about my plugins",
		"website"		=> "http://jonesboard.de/",
		"author"		=> "Jones",
		"authorsite"	=> "http://jonesboard.de/",
		"version"		=> "1.0.1",
		"guid" 			=> "dfff9c030f3b81df0533452117565fb8",
		"compatibility" => "16*"
	);
}

function myplugins_activate() {}

function myplugins_deactivate() {}

function myplugins_activate_commit()
{
	global $message, $codename, $cache;
	
	$infofunc = $codename."_info";
	
	if(!function_exists($infofunc))
		return;
	
	$plugininfo = $infofunc();
	
	if($plugininfo['author'] != "Jones")
	    return;
	
	$stats = $cache->read("myplugins_stats");
	if($stats['enabled']) {
		$plugininfo['codename'] = $codename;
		if(isset($plugininfo['myplugins_id']))
		    $plugininfo['codename'] = $plugininfo['myplugins_id'];

		$url = "http://jonesboard.de/plugins-api.php?action=plugins&do=activate&code={$stats['code']}&plugin=".urlencode($plugininfo['codename'])."&version=".urlencode($plugininfo['version']);

		fetch_remote_file($url);
	}
	
	flash_message($message, 'success');
	admin_redirect("index.php?module=myplugins");
}

function myplugins_deactivate_commit()
{
	global $message, $codename, $cache;

	if($codename == "myplugins")
	    return;

	$infofunc = $codename."_info";

	if(!function_exists($infofunc))
		return;

	$plugininfo = $infofunc();

	if($plugininfo['author'] != "Jones")
	    return;

	$stats = $cache->read("myplugins_stats");
	if($stats['enabled']) {
		$plugininfo['codename'] = $codename;
		if(isset($plugininfo['myplugins_id']))
		    $plugininfo['codename'] = $plugininfo['myplugins_id'];

		$url = "http://jonesboard.de/plugins-api.php?action=plugins&do=deactivate&code={$stats['code']}&plugin=".urlencode($plugininfo['codename'])."&version=".urlencode($plugininfo['version']);

		fetch_remote_file($url);
	}

	flash_message($message, 'success');
	admin_redirect("index.php?module=myplugins");
}

function generate_url($info, $el = "") {
	$string = "";
	foreach($info as $key => $value)
	{
		if($el != "")
		    $key = "[{$key}]";
		if(is_array($value)) {
			$string .= generate_url($value, "{$el}{$key}");
		} else {
			
			$string .= "&".$el.$key."=".urlencode($value);
		}
	}

	return $string;
}

global $mybb;
if(!function_exists("get_plugins_list") && $mybb->input['module'] != "config-plugins") {
	function get_plugins_list()
	{
		// Get a list of the plugin files which exist in the plugins directory
		$dir = @opendir(MYBB_ROOT."inc/plugins/");
		if($dir)
		{
			while($file = readdir($dir))
			{
				$ext = get_extension($file);
				if($ext == "php")
				{
					$plugins_list[] = $file;
				}
			}
			@sort($plugins_list);
		}
		@closedir($dir);
	
		return $plugins_list;
	}
}
?>