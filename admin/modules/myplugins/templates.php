<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}
$page->add_breadcrumb_item($lang->myplugins, "index.php?module=myplugins");
$page->add_breadcrumb_item($lang->myplugins_template, "index.php?module=myplugins-templates");

$page->output_header($lang->myplugins_template);

$plug = $cache->read("plugins");
$plug = $plug['active'];

$query = $db->simple_select("templatesets", "title");
while($set = $db->fetch_array($query))
    $nsets[] = $set['title'];

$hooks = $plugins->hooks;
foreach($plug as $plugin) {
	if(!file_exists(MYBB_ROOT."inc/plugins/{$plugin}.php"))
		continue;
	require_once MYBB_ROOT."inc/plugins/{$plugin}.php";

	$info = $plugin."_info";
	$info = $info();
	
	if($info['author'] != "Jones")
	    continue;
	
	$f = file(MYBB_ROOT."inc/plugins/{$plugin}.php");
	
	$replaces = array();
	$in_active = false;
	$replace = "";
	$brackets = 0;
	foreach($f as $l) {
		if(trim($l) == "function {$plugin}_activate() {") {
			$brackets = 1;
			$in_active = true;
		} elseif(trim($l) == "function {$plugin}_activate()") {
			$in_active = true;
		}
    	if(!$in_active)
		    continue;

		if(strpos($l, "find_replace_templatesets") !== false || $replace != "") {
		    $replace .= $l;
			if(substr(trim($l), -3) == "');") {
				$start = strpos($replace, "(");
				$first = strpos($replace, ",");
				$second = strpos($replace, ",", $first+1);
				$template = substr($replace, $start+2, $first-$start-3);
				$search = substr($replace, $first+3, $second-$first-4);
				if(strpos($search, "preg_quote") !== false)
				    $search = substr($search, strpos($search, "('")+2, -6);
				$replaced = substr($replace, $second+3, strpos($replace, "');")-$second-3);

				$add = str_replace("\\", "", str_replace(($search), "", ($replaced)));
				//Announcement needs a workaround
				if($plugin == "announcement" && $template == "headerinclude") {
					$add = str_replace("\\", "", str_replace(htmlentities($search), "", htmlentities($replaced)));
					$add = substr($add, 0, -17);
				}
				$replaces[] = array(
					"template" => htmlentities($template),
					"search" => htmlentities($search),
					"replace" => htmlentities($replaced),
					"add" => $add
				);
				$replace = "";
			}
		}

		$brackets = $brackets + substr_count($l, "{");
		$brackets = $brackets - substr_count($l, "}");

		if($brackets <= 0 && trim($l) != "function {$plugin}_activate()")
		    $in_active = false;
	}
	
	if(!empty($replaces)) {
		$table = new Table;
		$table->construct_header($lang->template, array("style"=>"width: 20%"));
		$table->construct_header($lang->search, array("style"=>"width: 20%"));
		$table->construct_header($lang->replace);
		$table->construct_header($lang->added, array("style"=>"width: 25%"));
		foreach($replaces as $replace) {
			$table->construct_cell($replace['template']);
			$table->construct_cell($replace['search']);
			$table->construct_cell($replace['replace']);
			
			$sets = array();
			$query = 	"SELECT
							template, s.title
						FROM
							".TABLE_PREFIX."templates t
						LEFT JOIN
							".TABLE_PREFIX."templatesets s
						ON
							s.sid=t.sid
						WHERE
							t.title='{$replace['template']}'
						AND
							s.sid > 0";
			$query = $db->write_query($query);
			while($template = $db->fetch_array($query)) {
				//Workaround Part 2
				if($plugin == "announcement" && $replace['template'] == "headerinclude")
					$template['template'] = htmlentities($template['template']);

				if(strpos($template['template'], $replace['add']) === false)
				    $sets[$template['title']] = "m";
				else
					$sets[$template['title']] = "a";
			}
			
			$message = "";
			foreach($nsets as $set) {
				$message .= "<b>{$set}: </b>";
				if(!isset($sets[$set])) {
				    $message .= "<span style=\"color: red;\">{$lang->template_missing}</span><br />";
				    continue;
				}
				
				if($sets[$set] == "a")
				    $message .= "<span style=\"color: green;\">{$lang->changed}</span><br />";
				else
				    $message .= "<span style=\"color: red;\">{$lang->not_changed}</span><br />";
			}
			$message = substr($message, 0, -6);
			
			$table->construct_cell($message);
			$table->construct_row();
		}
		$table->output($info['name']);
	}
}
$plugins->hooks = $hooks;

$page->output_footer();
?>