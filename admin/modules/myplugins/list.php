<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}
$page->add_breadcrumb_item($lang->myplugins, "index.php?module=myplugins");

$page->output_header($lang->myplugins);

$lang->load("config_plugins");
$plugins_cache = $cache->read("plugins");
$active_plugins = $plugins_cache['active'];
$got = $download_plugins = get_remote_plugins();

$plugins_list = get_plugins_list();

$a_plugins = $i_plugins = array();

$hooks = $plugins->hooks;
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

	if($active_plugins[$codename])
	{
		// This is an active plugin
		$plugininfo['is_active'] = 1;

		$a_plugins[] = $plugininfo;
		continue;
	}

	// Either installed and not active or completely inactive
	$i_plugins[] = $plugininfo;
}
$plugins->hooks = $hooks;

//Active Plugins
$table = new Table;
$table->construct_header($lang->plugin);
$table->construct_header($lang->version, array("class" => "align_center"));
$table->construct_header($lang->controls, array("colspan" => 2, "class" => "align_center", "width" => 300));

if(empty($a_plugins))
{
	$table->construct_cell($lang->no_active_plugins, array('colspan' => 4));
	$table->construct_row();
}
else
{
	build_plugin_list($a_plugins);
}

$table->output($lang->active_plugin);


//Inactive Plugins
$table = new Table;
$table->construct_header($lang->plugin);
$table->construct_header($lang->version, array("class" => "align_center"));
$table->construct_header($lang->controls, array("colspan" => 2, "class" => "align_center", "width" => 300));

if(empty($i_plugins))
{
	$table->construct_cell($lang->no_inactive_plugins, array('colspan' => 4));
	$table->construct_row();
}
else
{
	build_plugin_list($i_plugins);
}

$table->output($lang->inactive_plugin);



//Download Plugins
$table = new Table;
$table->construct_header($lang->plugin);
$table->construct_header($lang->version, array("class" => "align_center"));
//$table->construct_header($lang->controls, array("colspan" => 2, "class" => "align_center", "width" => 300));

if($got) {
	if(sizeOf($download_plugins) < 1)
	{
		$table->construct_cell($lang->no_downloadable_plugins, array('colspan' => 4));
		$table->construct_row();
	}
	else
	{
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;
		$parser_options = array(
			"allow_html" => 0,
			"allow_mycode" => 1,
			"allow_smilies" => 1,
			"allow_imgcode" => 0,
			"allow_videocode" => 0,
			"filter_badwords" => 0
		);

		foreach($download_plugins as $plugin) {
			$desc = $parser->parse_message($plugin['desc'], $parser_options);
			$table->construct_cell("<strong>{$plugin['name']}</strong><br /><small>{$desc}</small>");
			$table->construct_cell($plugin['version'], array("class" => "align_center"));
	
			$table->construct_row();			
		}
	}
} else {
	$table->construct_cell($lang->no_connection, array('colspan' => 4));
	$table->construct_row();	
}
$table->output($lang->downloadable_plugin);


$page->output_footer();

function build_plugin_list($plugin_list)
{
	global $lang, $mybb, $plugins, $table, $download_plugins;

	foreach($plugin_list as $plugininfo)
	{
		if($plugininfo['website'])
		{
			$plugininfo['name'] = "<a href=\"".$plugininfo['website']."\">".$plugininfo['name']."</a>";
		}

		if($plugins->is_compatible($plugininfo['codename']) == false)
		{
			$compatibility_warning = "<span style=\"color: red;\">".$lang->sprintf($lang->plugin_incompatible, $mybb->version)."</span>";
		}
		else
		{
			$compatibility_warning = "";
		}

		$installed_func = "{$plugininfo['codename']}_is_installed";
		$install_func = "{$plugininfo['codename']}_install";
		$uninstall_func = "{$plugininfo['codename']}_uninstall";

		$installed = true;
		$install_button = false;
		$uninstall_button = false;

		if(function_exists($installed_func) && $installed_func() != true)
		{
			$installed = false;
		}

		if(function_exists($install_func))
		{
			$install_button = true;
		}

		if(function_exists($uninstall_func))
		{
			$uninstall_button = true;
		}
		
		$color = "black";
		if($download_plugins) {
			if(isset($plugininfo['myplugins_id']))
			    $code = $plugininfo['myplugins_id'];
			else
				$code = $plugininfo['codename'];
			
			if(isset($download_plugins[$code])) {
				if(version_compare($plugininfo['version'], $download_plugins[$code]['version'], ">="))
				    $color = "green";
				else
					$color = "red";
				unset($download_plugins[$code]);
			}
		}

		$table->construct_cell("<strong>{$plugininfo['name']}</strong><br /><small>{$plugininfo['description']}</small>");
		$table->construct_cell("<span style=\"color: {$color};\">{$plugininfo['version']}</span>", array("class" => "align_center"));

		// Plugin is not installed at all
		if($installed == false)
		{
			if($compatibility_warning)
			{
				$table->construct_cell("{$compatibility_warning}", array("class" => "align_center", "colspan" => 2));
			}
			else
			{
				$table->construct_cell("<a href=\"index.php?module=config-plugins&amp;action=activate&amp;plugin={$plugininfo['codename']}&amp;my_post_key={$mybb->post_code}\">{$lang->install_and_activate}</a>", array("class" => "align_center", "colspan" => 2));
			}
		}
		// Plugin is activated and installed
		else if($plugininfo['is_active'])
		{
			$table->construct_cell("<a href=\"index.php?module=config-plugins&amp;action=deactivate&amp;plugin={$plugininfo['codename']}&amp;my_post_key={$mybb->post_code}\">{$lang->deactivate}</a>", array("class" => "align_center", "width" => 150));
			if($uninstall_button)
			{
				$table->construct_cell("<a href=\"index.php?module=config-plugins&amp;action=deactivate&amp;uninstall=1&amp;plugin={$plugininfo['codename']}&amp;my_post_key={$mybb->post_code}\">{$lang->uninstall}</a>", array("class" => "align_center", "width" => 150));
			}
			else
			{
				$table->construct_cell("&nbsp;", array("class" => "align_center", "width" => 150));
			}
		}
		// Plugin is installed but not active
		else if($installed == true)
		{
			$table->construct_cell("<a href=\"index.php?module=config-plugins&amp;action=activate&amp;plugin={$plugininfo['codename']}&amp;my_post_key={$mybb->post_code}\">{$lang->activate}</a>", array("class" => "align_center", "width" => 150));
			if($uninstall_button)
			{
				$table->construct_cell("<a href=\"index.php?module=config-plugins&amp;action=deactivate&amp;uninstall=1&amp;plugin={$plugininfo['codename']}&amp;my_post_key={$mybb->post_code}\">{$lang->uninstall}</a>", array("class" => "align_center", "width" => 150));
			}
			else
			{
				$table->construct_cell("&nbsp;", array("class" => "align_center", "width" => 150));
			}
		}
		$table->construct_row();
	}
}

function get_remote_plugins()
{
	global $lang;
	
	$langu = substr($lang->language, 0, strpos($lang->language, "/"));
	$content = fetch_remote_file("http://jonesboard.de/plugins-api.php?lang={$langu}");
	if($content) {
		require_once MYBB_ROOT."inc/class_xml.php";
		$parser = new XMLParser($content);
		$tree = $parser->get_tree();
		if(!is_array($tree['output']['plugin']))
		{
			$plugins = array();
		}
		else
		{
			foreach($tree['output']['plugin'] as $plugin) {
				$plugins[$plugin['kennung']['value']]['id'] = $plugin['id']['value'];
				$plugins[$plugin['kennung']['value']]['name'] = $plugin['name']['value'];
				$plugins[$plugin['kennung']['value']]['kennung'] = $plugin['kennung']['value'];
				$plugins[$plugin['kennung']['value']]['version'] = $plugin['version']['value'];
				$plugins[$plugin['kennung']['value']]['desc'] = $plugin['desc']['value'];
				$plugins[$plugin['kennung']['value']]['screens'] = $plugin['screens']['value'];
				$plugins[$plugin['kennung']['value']]['changes'] = $plugin['changes']['value'];
				$plugins[$plugin['kennung']['value']]['date'] = $plugin['date']['value'];
				$plugins[$plugin['kennung']['value']]['lastUpdate'] = $plugin['lastUpdate']['value'];
				$plugins[$plugin['kennung']['value']]['langfiles'] = $plugin['langfiles']['value'];
				$plugins[$plugin['kennung']['value']]['support'] = $plugin['support']['value'];
				$plugins[$plugin['kennung']['value']]['github'] = $plugin['github']['value'];
				$plugins[$plugin['kennung']['value']]['visible'] = $plugin['visible']['value'];
				$plugins[$plugin['kennung']['value']]['registered'] = $plugin['registered']['value'];
				$plugins[$plugin['kennung']['value']]['downloads'] = $plugin['downloads']['value'];
			}
		}
		return $plugins;
	} else {
		return false;
	}
}
?>