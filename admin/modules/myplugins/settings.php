<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}
$page->add_breadcrumb_item($lang->myplugins, "index.php?module=myplugins");
$page->add_breadcrumb_item($lang->myplugins_settings, "index.php?module=myplugins-settings");

$page->output_header($lang->myplugins_settings);

$plug = get_plugins_list();

if($mybb->request_method == "post") {
	if(!is_writable(MYBB_ROOT."inc/settings.php")) {
		flash_message($lang->error_chmod_settings_file, "error");
		admin_redirect("index.php?module=myplugins-settings");
	}
	
	if(is_array($mybb->input['upsetting'])) {
		foreach($mybb->input['upsetting'] as $name => $value) {
			$value = $db->escape_string($value);
			$db->update_query("settings", array("value"=>$value), "name='".$db->escape_string($name)."'");
		}
	}
	
	rebuild_settings();
	
	flash_message($lang->success_settings_updated, "success");
	admin_redirect("index.php?module=myplugins-settings");
}

$hooks = $plugins->hooks;
$form = new Form("index.php?module=myplugins-settings", "post");
$buttons[] = $form->generate_submit_button($lang->save);
foreach($plug as $plugin_file) {
	require_once MYBB_ROOT."inc/plugins/".$plugin_file;
	$plugin = str_replace(".php", "", $plugin_file);

	$info = $plugin."_info";
	if(!function_exists($info))
	    continue;
	$info = $info();
	
	if($info['author'] != "Jones")
	    continue;
	
	if(isset($info['settings_id']))
	    $where = "name='".$db->escape_string($info['settings_id'])."'";
	else
		$where = "name='".$db->escape_string($plugin)."' OR name='".$db->escape_string($info['name'])."'";
	
	$query = $db->simple_select("settinggroups", "*", $where);
	if($db->num_rows($query) != 1)
	    continue;

	$group = $db->fetch_array($query);

	$group_lang_var = "setting_group_{$group['name']}";
	if(isset($lang->$group_lang_var))
		$group_title = htmlspecialchars_uni($lang->$group_lang_var);
	else
		$group_title = htmlspecialchars_uni($group['title']);

	$group_desc_lang_var = "setting_group_{$group['name']}_desc";
	if(isset($lang->$group_desc_lang_var))
		$group_desc = htmlspecialchars_uni($lang->$group_desc_lang_var);
	else
		$group_desc = htmlspecialchars_uni($group['description']);

	$query = $db->simple_select("settings", "*", "gid='{$group['gid']}'", array("order_by"=>"disporder"));
	$num = $db->num_rows($query);
	
	$form_container = new FormContainer("<b>{$group_title}</b> ({$num})<br />{$group_desc}");
	
	while($setting = $db->fetch_array($query))
	{
		$options = "";
		$type = explode("\n", $setting['optionscode']);
		$type[0] = trim($type[0]);
		$element_name = "upsetting[{$setting['name']}]";
		$element_id = "setting_{$setting['name']}";
		if($type[0] == "text" || $type[0] == "")
			$setting_code = $form->generate_text_box($element_name, $setting['value'], array('id' => $element_id));
		else if($type[0] == "textarea")
			$setting_code = $form->generate_text_area($element_name, $setting['value'], array('id' => $element_id));
		else if($type[0] == "yesno")
			$setting_code = $form->generate_yes_no_radio($element_name, $setting['value'], true, array('id' => $element_id.'_yes', 'class' => $element_id), array('id' => $element_id.'_no', 'class' => $element_id));
		else if($type[0] == "onoff")
			$setting_code = $form->generate_on_off_radio($element_name, $setting['value'], true, array('id' => $element_id.'_on', 'class' => $element_id), array('id' => $element_id.'_off', 'class' => $element_id));
		else if($type[0] == "cpstyle") {
			$dir = @opendir(MYBB_ROOT.$config['admin_dir']."/styles");
			while($folder = readdir($dir)) {
				if($file != "." && $file != ".." && @file_exists(MYBB_ROOT.$config['admin_dir']."/styles/$folder/main.css")) {
					$folders[$folder] = ucfirst($folder);
				}
			}
			closedir($dir);
			ksort($folders);
			$setting_code = $form->generate_select_box($element_name, $folders, $setting['value'], array('id' => $element_id));
		} else if($type[0] == "language") {
			$languages = $lang->get_languages();
			$setting_code = $form->generate_select_box($element_name, $languages, $setting['value'], array('id' => $element_id));
		} else if($type[0] == "adminlanguage") {
			$languages = $lang->get_languages(1);
			$setting_code = $form->generate_select_box($element_name, $languages, $setting['value'], array('id' => $element_id));
		} else if($type[0] == "passwordbox")
			$setting_code = $form->generate_password_box($element_name, $setting['value'], array('id' => $element_id));
		else if($type[0] == "php") {
			$setting['optionscode'] = substr($setting['optionscode'], 3);
			eval("\$setting_code = \"".$setting['optionscode']."\";");
		} else {
			for($i=0; $i < count($type); $i++) {
				$optionsexp = explode("=", $type[$i]);
				if(!isset($optionsexp[1]))
					continue;
				$title_lang = "setting_{$setting['name']}_{$optionsexp[0]}";
				if(isset($lang->$title_lang))
					$optionsexp[1] = $lang->$title_lang;

				if($type[0] == "select")
					$option_list[$optionsexp[0]] = htmlspecialchars_uni($optionsexp[1]);
				else if($type[0] == "radio") {
					if($setting['value'] == $optionsexp[0])
						$option_list[$i] = $form->generate_radio_button($element_name, $optionsexp[0], htmlspecialchars_uni($optionsexp[1]), array('id' => $element_id.'_'.$i, "checked" => 1, 'class' => $element_id));
					else
						$option_list[$i] = $form->generate_radio_button($element_name, $optionsexp[0], htmlspecialchars_uni($optionsexp[1]), array('id' => $element_id.'_'.$i, 'class' => $element_id));
				} else if($type[0] == "checkbox") {
					if($setting['value'] == $optionsexp[0])
						$option_list[$i] = $form->generate_check_box($element_name, $optionsexp[0], htmlspecialchars_uni($optionsexp[1]), array('id' => $element_id.'_'.$i, "checked" => 1, 'class' => $element_id));
					else
						$option_list[$i] = $form->generate_check_box($element_name, $optionsexp[0], htmlspecialchars_uni($optionsexp[1]), array('id' => $element_id.'_'.$i, 'class' => $element_id));
				}
			}
			if($type[0] == "select")
				$setting_code = $form->generate_select_box($element_name, $option_list, $setting['value'], array('id' => $element_id));
			else
				$setting_code = implode("<br />", $option_list);
			$option_list = array();
		}

		$title_lang = "setting_".$setting['name'];
		$desc_lang = $title_lang."_desc";
		if(isset($lang->$title_lang))
			$setting['title'] = $lang->$title_lang;
		if(isset($lang->$desc_lang))
			$setting['description'] = $lang->$desc_lang;
		$form_container->output_row(htmlspecialchars_uni($setting['title']), $setting['description'], $setting_code, '', array(), array('id' => 'row_'.$element_id));
	}
	$form_container->end();
}
$form->output_submit_wrapper($buttons);
$form->end();
$plugins->hooks = $hooks;

$page->output_footer();
?>