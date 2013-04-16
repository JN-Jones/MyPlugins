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
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => "16*"
	);
}

function myplugins_activate() {}

function myplugins_deactivate() {}

function myplugins_activate_commit()
{
	global $message, $codename;
	
	$infofunc = $codename."_info";
	
	if(!function_exists($infofunc))
		return;
	
	$plugininfo = $infofunc();
	
	if($plugininfo['author'] != "Jones")
	    return;
	    
	flash_message($message, 'success');
	admin_redirect("index.php?module=myplugins");
}

function myplugins_deactivate_commit()
{
	global $message, $codename;

	if($codename == "myplugins")
	    return;

	$infofunc = $codename."_info";

	if(!function_exists($infofunc))
		return;

	$plugininfo = $infofunc();

	if($plugininfo['author'] != "Jones")
	    return;

	flash_message($message, 'success');
	admin_redirect("index.php?module=myplugins");
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