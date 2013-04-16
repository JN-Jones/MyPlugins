<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function myplugins_meta()
{
	global $page, $lang, $db;
	$lang->load("myplugins");

//	$query = $db->simple_select("settinggroups", "gid", "name='Wiki'");
//    $g = $db->fetch_array($query);

	$sub_menu = array();
	$sub_menu['5'] = array("id" => "list", "title" => $lang->myplugins_overview, "link" => "index.php?module=myplugins");
//	$sub_menu['10'] = array("id" => "verification", "title" => $lang->myplugins_verification, "link" => "index.php?module=myplugins-verification");
	
//	$query = $db->simple_select("settinggroups", "gid", "name='Wiki'");
//	if($db->num_rows($query))

	if(function_exists("myplugins_info"))
		$page->add_menu_item($lang->myplugins, "myplugins", "index.php?module=myplugins", 45, $sub_menu);

	return true;
}

function myplugins_action_handler($action)
{
	global $page, $lang, $plugins, $info;
	
	$page->active_module = "myplugins";
	
	$actions = array(
		'list' => array('active' => 'list', 'file' => 'list.php'),
		'verification' => array('active' => 'verification', 'file' => 'verification.php')
	);

	$info = $action;
	$actions = $plugins->run_hooks("myplugins_actions", $actions);
	
	if(isset($actions[$action]))
	{
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
	else
	{
		$page->active_action = "list";
		return "list.php";
	}
}

function myplugins_admin_permissions()
{
	global $lang, $plugins;
	if(!isset($lang->myplugins))
		$lang->load("myplugins");
	
	$admin_permissions = array(
		"list"	=> $lang->myplugins_permission_list,
		"verification"	=> $lang->myplugins_permission_verification
	);
	
	$admin_permissions = $plugins->run_hooks("myplugins_permission", $admin_permissions);

	return array("name" => $lang->myplugins, "permissions" => $admin_permissions, "disporder" => 45);
}
?>