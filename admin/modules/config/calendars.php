<?php
/**
 * MyBB 1.6
 * Copyright 2010 MyBB Group, All Rights Reserved
 *
 * Website: http://mybb.com
 * License: http://mybb.com/about/license
 *
 * $Id$
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item($lang->calendars, "index.php?module=config-calendars");

if($mybb->input['action'] == "add" || $mybb->input['action'] == "permissions" || !$mybb->input['action'])
{
	$sub_tabs['manage_calendars'] = array(
		'title' => $lang->manage_calendars,
		'link' => "index.php?module=config-calendars",
		'description' => $lang->manage_calendars_desc
	);
	$sub_tabs['add_calendar'] = array(
		'title' => $lang->add_calendar,
		'link' => "index.php?module=config-calendars&amp;action=add",
	);
}

$plugins->run_hooks("admin_config_calendars_begin");

if($mybb->input['action'] == "add")
{
	$plugins->run_hooks("admin_config_calendars_add");
	
	if($mybb->request_method == "post")
	{
		$plugins->run_hooks("admin_config_calendars_add_commit");
		
		if(!trim($mybb->input['name']))
		{
			$errors[] = $lang->error_missing_name;
		}

		if(!isset($mybb->input['disporder']))
		{
			$errors[] = $lang->error_missing_order;
		}

		if(!$errors)
		{
			$calendar = array(
				"name" => $db->escape_string($mybb->input['name']),
				"disporder" => intval($mybb->input['disporder']),
				"startofweek" => intval($mybb->input['startofweek']),
				"eventlimit" => intval($mybb->input['eventlimit']),
				"showbirthdays" => intval($mybb->input['showbirthdays']),
				"moderation" => intval($mybb->input['moderation']),
				"allowhtml" => $db->escape_string($mybb->input['allowhtml']),
				"allowmycode" => $db->escape_string($mybb->input['allowmycode']),
				"allowimgcode" => $db->escape_string($mybb->input['allowimgcode']),
				"allowvideocode" => $db->escape_string($mybb->input['allowvideocode']),
				"allowsmilies" => $db->escape_string($mybb->input['allowsmilies'])
			);
			
			$cid = $db->insert_query("calendars", $calendar);

			// Log admin action
			log_admin_action($cid, $mybb->input['name']);

			flash_message($lang->success_calendar_created, 'success');
			admin_redirect("index.php?module=config-calendars");
		}
	}
	else
	{
		$mybb->input = array(
			"allowhtml" => 0,
			"eventlimit" => 4,
			"disporder" => 1,
			"moderation" => 0
		);
	}
	
	$page->add_breadcrumb_item($lang->add_calendar);
	$page->output_header($lang->calendars." - ".$lang->add_calendar);
	
	$sub_tabs['add_calendar'] = array(
		'title' => $lang->add_calendar,
		'link' => "index.php?module=config-calendars&amp;action=add",
		'description' => $lang->add_calendar_desc
	);
	
	$page->output_nav_tabs($sub_tabs, 'add_calendar');
	$form = new Form("index.php?module=config-calendars&amp;action=add", "post");
	
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}

	$form_container = new FormContainer($lang->add_calendar);
	$form_container->output_row($lang->name." <em>*</em>", "", $form->generate_text_box('name', $mybb->input['name'], array('id' => 'name')), 'name');
	$form_container->output_row($lang->display_order, $lang->display_order_desc, $form->generate_text_box('disporder', $mybb->input['disporder'], array('id' => 'disporder')), 'disporder');
	$select_list = array($lang->sunday, $lang->monday, $lang->tuesday, $lang->wednesday, $lang->thursday, $lang->friday, $lang->saturday);
	$form_container->output_row($lang->week_start, $lang->week_start_desc, $form->generate_select_box('startofweek', $select_list, $mybb->input['startofweek'], array('id' => 'startofweek')), 'startofweek');
	$form_container->output_row($lang->event_limit, $lang->event_limit_desc, $form->generate_text_box('eventlimit', $mybb->input['eventlimit'], array('id' => 'eventlimit')), 'eventlimit');
	$form_container->output_row($lang->show_birthdays, $lang->show_birthdays_desc, $form->generate_yes_no_radio('showbirthdays', $mybb->input['showbirthdays'], true));
	$form_container->output_row($lang->moderate_events, $lang->moderate_events_desc, $form->generate_yes_no_radio('moderation', $mybb->input['moderation'], true));
	$form_container->output_row($lang->allow_html, "", $form->generate_yes_no_radio('allowhtml', $mybb->input['allowhtml']));
	$form_container->output_row($lang->allow_mycode, "", $form->generate_yes_no_radio('allowmycode', $mybb->input['allowmycode']));
	$form_container->output_row($lang->allow_img, "", $form->generate_yes_no_radio('allowimgcode', $mybb->input['allowimgcode']));
	$form_container->output_row($lang->allow_video, "", $form->generate_yes_no_radio('allowvideocode', $mybb->input['allowvideocode']));
	$form_container->output_row($lang->allow_smilies, "", $form->generate_yes_no_radio('allowsmilies', $mybb->input['allowsmilies']));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_calendar);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "permissions")
{
	$plugins->run_hooks("admin_config_calendars_permissions");
	
	$query = $db->simple_select("calendars", "*", "cid='".intval($mybb->input['cid'])."'");
	$calendar = $db->fetch_array($query);
	
	// Does the calendar not exist?
	if(!$calendar['cid'])
	{
		flash_message($lang->error_invalid_calendar, 'error');
		admin_redirect("index.php?module=config-calendars");
	}

	$query = $db->simple_select("usergroups", "*", "", array("order_dir" => "name"));
	while($usergroup = $db->fetch_array($query))
	{
		$usergroups[$usergroup['gid']] = $usergroup;
	}
	
	$query = $db->simple_select("calendarpermissions", "*", "cid='{$calendar['cid']}'");
	while($existing = $db->fetch_array($query))
	{
		$existing_permissions[$existing['gid']] = $existing;
	}
	
	if($mybb->request_method == "post")
	{
		foreach(array_keys($usergroups) as $group_id)
		{
			$permissions = $mybb->input['permissions'][$group_id];
			$db->delete_query("calendarpermissions", "cid='{$calendar['cid']}' AND gid='".intval($group_id)."'");

			if(!$mybb->input['default_permissions'][$group_id])
			{
				foreach(array('canviewcalendar','canaddevents','canbypasseventmod','canmoderateevents') as $calendar_permission)
				{
					if($permissions[$calendar_permission] == 1)
					{
						$permissions_array[$calendar_permission] = 1;
					}
					else
					{
						$permissions_array[$calendar_permission] = 0;
					}
				}
				$permissions_array['gid'] = intval($group_id);
				$permissions_array['cid'] = $calendar['cid'];
				$db->insert_query("calendarpermissions", $permissions_array);
			}
		}
		
		$plugins->run_hooks("admin_config_calendars_permissions_commit");

		// Log admin action
		log_admin_action($calendar['cid'], $calendar['name']);

		flash_message($lang->success_calendar_permissions_updated, 'success');
		admin_redirect("index.php?module=config-calendars");
	}
	
	$calendar['name'] = htmlspecialchars_uni($calendar['name']);
	$page->add_breadcrumb_item($calendar['name'], "index.php?module=config-calendars&amp;action=edit&amp;cid={$calendar['cid']}");
	$page->add_breadcrumb_item($lang->permissions);
	$page->output_header($lang->calendars." - ".$lang->edit_permissions);

	$form = new Form("index.php?module=config-calendars&amp;action=permissions", "post");
	echo $form->generate_hidden_field("cid", $calendar['cid']);

	$table = new Table;
	$table->construct_header($lang->permissions_group);
	$table->construct_header($lang->permissions_view, array("class" => "align_center", "width" => "10%"));
	$table->construct_header($lang->permissions_post_events, array("class" => "align_center", "width" => "10%"));
	$table->construct_header($lang->permissions_bypass_moderation, array("class" => "align_center", "width" => "10%"));
	$table->construct_header($lang->permissions_moderator, array("class" => "align_center", "width" => "10%"));
	$table->construct_header($lang->permissions_all, array("class" => "align_center", "width" => "10%"));
	
	foreach($usergroups as $usergroup)
	{
		if($existing_permissions[$usergroup['gid']])
		{
			$perms = $existing_permissions[$usergroup['gid']];
			$default_checked = false;
		}
		else
		{
			$perms = $usergroup;
			$default_checked = true;
		}
		$perm_check = $all_check = "";
		$all_checked = true;
		foreach(array('canviewcalendar','canaddevents','canbypasseventmod','canmoderateevents') as $calendar_permission)
		{
			if($usergroup[$calendar_permission] == 1)
			{
				$value = "true";
			}
			else
			{
				$value = "false";
			}
			if($perms[$calendar_permission] != 1)
			{
				$all_checked = false;
			}
			if($perms[$calendar_permission] == 1)
			{
				$perms_checked[$calendar_permission] = 1;
			}
			else
			{
				$perms_checked[$calendar_permission] = 0;
			}
			$all_check .= "\$('permissions_{$usergroup['gid']}_{$calendar_permission}').checked = \$('permissions_{$usergroup['gid']}_all').checked;\n";
			$perm_check .= "\$('permissions_{$usergroup['gid']}_{$calendar_permission}').checked = $value;\n";
		}
		$default_click = "if(this.checked == true) { $perm_check }";
		$reset_default = "\$('default_permissions_{$usergroup['gid']}').checked = false; if(this.checked == false) { \$('permissions_{$usergroup['gid']}_all').checked = false; }\n";
		$usergroup['title'] = htmlspecialchars_uni($usergroup['title']);
		$table->construct_cell("<strong>{$usergroup['title']}</strong><br /><small style=\"vertical-align: middle;\">".$form->generate_check_box("default_permissions[{$usergroup['gid']}];", 1, "", array("id" => "default_permissions_{$usergroup['gid']}", "checked" => $default_checked, "onclick" => $default_click))." <label for=\"default_permissions_{$usergroup['gid']}\">{$lang->permissions_use_group_default}</label></small>");
		$table->construct_cell($form->generate_check_box("permissions[{$usergroup['gid']}][canviewcalendar]", 1, "", array("id" => "permissions_{$usergroup['gid']}_canviewcalendar", "checked" => $perms_checked['canviewcalendar'], "onclick" => $reset_default)), array('class' => 'align_center'));
		$table->construct_cell($form->generate_check_box("permissions[{$usergroup['gid']}][canaddevents]", 1, "", array("id" => "permissions_{$usergroup['gid']}_canaddevents", "checked" => $perms_checked['canaddevents'], "onclick" => $reset_default)), array('class' => 'align_center'));
		$table->construct_cell($form->generate_check_box("permissions[{$usergroup['gid']}][canbypasseventmod]", 1, "", array("id" => "permissions_{$usergroup['gid']}_canbypasseventmod", "checked" => $perms_checked['canbypasseventmod'], "onclick" => $reset_default)), array('class' => 'align_center'));
		$table->construct_cell($form->generate_check_box("permissions[{$usergroup['gid']}][canmoderateevents]", 1, "", array("id" => "permissions_{$usergroup['gid']}_canmoderateevents", "checked" => $perms_checked['canmoderateevents'], "onclick" => $reset_default)), array('class' => 'align_center'));
		$table->construct_cell($form->generate_check_box("permissions[{$usergroup['gid']}][all]", 1, "", array("id" => "permissions_{$usergroup['gid']}_all", "checked" => $all_checked, "onclick" => $all_check)), array('class' => 'align_center'));
		$table->construct_row();
	}
	$table->output("{$lang->calendar_permissions_for} {$calendar['name']}");

	if(!$no_results)
	{
		$buttons[] = $form->generate_submit_button($lang->save_permissions);
		$form->output_submit_wrapper($buttons);
	}

	$form->end();

	$page->output_footer();

}

if($mybb->input['action'] == "edit")
{
	$plugins->run_hooks("admin_config_calendars_edit");
	
	$query = $db->simple_select("calendars", "*", "cid='".intval($mybb->input['cid'])."'");
	$calendar = $db->fetch_array($query);
	
	// Does the calendar not exist?
	if(!$calendar['cid'])
	{
		flash_message($lang->error_invalid_calendar, 'error');
		admin_redirect("index.php?module=config-calendars");
	}

	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['name']))
		{
			$errors[] = $lang->error_missing_name;
		}

		if(!isset($mybb->input['disporder']))
		{
			$errors[] = $lang->error_missing_order;
		}

		if(!$errors)
		{
			$updated_calendar = array(
				"name" => $db->escape_string($mybb->input['name']),
				"disporder" => intval($mybb->input['disporder']),
				"startofweek" => intval($mybb->input['startofweek']),
				"eventlimit" => intval($mybb->input['eventlimit']),
				"showbirthdays" => intval($mybb->input['showbirthdays']),
				"moderation" => intval($mybb->input['moderation']),
				"allowhtml" => $db->escape_string($mybb->input['allowhtml']),
				"allowmycode" => $db->escape_string($mybb->input['allowmycode']),
				"allowimgcode" => $db->escape_string($mybb->input['allowimgcode']),
				"allowvideocode" => $db->escape_string($mybb->input['allowvideocode']),
				"allowsmilies" => $db->escape_string($mybb->input['allowsmilies'])
			);
			
			$db->update_query("calendars", $updated_calendar, "cid = '".intval($mybb->input['cid'])."'");
			
			$plugins->run_hooks("admin_config_calendars_edit_commit");
			
			// Log admin action
			log_admin_action($calendar['cid'], $mybb->input['name']);

			flash_message($lang->success_calendar_updated, 'success');
			admin_redirect("index.php?module=config-calendars");
		}
	}
	
	$page->add_breadcrumb_item($lang->edit_calendar);
	$page->output_header($lang->calendars." - ".$lang->edit_calendar);
	
	$sub_tabs['edit_calendar'] = array(
		'title' => $lang->edit_calendar,
		'link' => "index.php?module=config-calendars&amp;action=edit",
		'description' => $lang->edit_calendar_desc
	);
	
	$page->output_nav_tabs($sub_tabs, 'edit_calendar');
	$form = new Form("index.php?module=config-calendars&amp;action=edit", "post");
	
	echo $form->generate_hidden_field("cid", $calendar['cid']);
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input = $calendar;
	}

	$form_container = new FormContainer($lang->edit_calendar);
	$form_container->output_row($lang->name." <em>*</em>", "", $form->generate_text_box('name', $mybb->input['name'], array('id' => 'name')), 'name');
	$form_container->output_row($lang->display_order." <em>*</em>", $lang->display_order_desc, $form->generate_text_box('disporder', $mybb->input['disporder'], array('id' => 'disporder')), 'disporder');
	$select_list = array($lang->sunday, $lang->monday, $lang->tuesday, $lang->wednesday, $lang->thursday, $lang->friday, $lang->saturday);
	$form_container->output_row($lang->week_start, $lang->week_start_desc, $form->generate_select_box('startofweek', $select_list, $mybb->input['startofweek'], array('id' => 'startofweek')), 'startofweek');
	$form_container->output_row($lang->event_limit, $lang->event_limit_desc, $form->generate_text_box('eventlimit', $mybb->input['eventlimit'], array('id' => 'eventlimit')), 'eventlimit');
	$form_container->output_row($lang->show_birthdays, $lang->show_birthdays_desc, $form->generate_yes_no_radio('showbirthdays', $mybb->input['showbirthdays'], true));
	$form_container->output_row($lang->moderate_events, $lang->moderate_events_desc, $form->generate_yes_no_radio('moderation', $mybb->input['moderation'], true));
	$form_container->output_row($lang->allow_html, "", $form->generate_yes_no_radio('allowhtml', $mybb->input['allowhtml']));
	$form_container->output_row($lang->allow_mycode, "", $form->generate_yes_no_radio('allowmycode', $mybb->input['allowmycode']));
	$form_container->output_row($lang->allow_img, "", $form->generate_yes_no_radio('allowimgcode', $mybb->input['allowimgcode']));
	$form_container->output_row($lang->allow_video, "", $form->generate_yes_no_radio('allowvideocode', $mybb->input['allowvideocode']));
	$form_container->output_row($lang->allow_smilies, "", $form->generate_yes_no_radio('allowsmilies', $mybb->input['allowsmilies']));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_calendar);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "delete")
{
	$plugins->run_hooks("admin_config_calendars_delete");
	
	$query = $db->simple_select("calendars", "*", "cid='".intval($mybb->input['cid'])."'");
	$calendar = $db->fetch_array($query);
	
	// Does the calendar not exist?
	if(!$calendar['cid'])
	{
		flash_message($lang->error_invalid_calendar, 'error');
		admin_redirect("index.php?module=config-calendars");
	}

	// User clicked no
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=config-calendars");
	}

	if($mybb->request_method == "post")
	{
		// Delete the calendar
		$db->delete_query("calendars", "cid='{$calendar['cid']}'");
		$db->delete_query("calendarpermissions", "cid='{$calendar['cid']}'");
		$db->delete_query("events", "cid='{$calendar['cid']}'");
		
		$plugins->run_hooks("admin_config_calendars_delete_commit");

		// Log admin action
		log_admin_action($calendar['cid'], $calendar['name']);

		flash_message($lang->success_calendar_deleted, 'success');
		admin_redirect("index.php?module=config-calendars");
	}
	else
	{
		$page->output_confirm_action("index.php?module=config-calendars&amp;action=delete&amp;cid={$calendar['cid']}", $lang->confirm_calendar_deletion);
	}
}

if($mybb->input['action'] == "update_order" && $mybb->request_method == "post")
{
	$plugins->run_hooks("admin_config_calendars_update_order");
	
	if(!is_array($mybb->input['disporder']))
	{
		admin_redirect("index.php?module=config-calendars");
	}

	foreach($mybb->input['disporder'] as $cid => $order)
	{
		$update_query = array(
			"disporder" => intval($order)
		);
		$db->update_query("calendars", $update_query, "cid='".intval($cid)."'");
	}
	
	$plugins->run_hooks("admin_config_calendars_update_order_commit");

	// Log admin action
	log_admin_action();

	flash_message($lang->success_calendar_orders_updated, 'success');
	admin_redirect("index.php?module=config-calendars");
}

if(!$mybb->input['action'])
{
	$page->output_header($lang->manage_calendars);

	$page->output_nav_tabs($sub_tabs, 'manage_calendars');

	$form = new Form("index.php?module=config-calendars&amp;action=update_order", "post");
	$table = new Table;
	$table->construct_header($lang->calendar);
	$table->construct_header($lang->order, array('width' => '5%', 'class' => 'align_center'));
	$table->construct_header($lang->controls, array("class" => "align_center", "colspan" => 3, "width" => 300));
	
	$query = $db->simple_select("calendars", "*", "", array('order_by' => 'disporder'));
	while($calendar = $db->fetch_array($query))
	{
		$calendar['name'] = htmlspecialchars_uni($calendar['name']);
		$table->construct_cell("<a href=\"index.php?module=config-calendars&amp;action=edit&amp;cid={$calendar['cid']}\"><strong>{$calendar['name']}</strong></a>");
		$table->construct_cell($form->generate_text_box("disporder[{$calendar['cid']}]", $calendar['disporder'], array('id' => 'disporder', 'style' => 'width: 80%', 'class' => 'align_center')));
		$table->construct_cell("<a href=\"index.php?module=config-calendars&amp;action=edit&amp;cid={$calendar['cid']}\">{$lang->edit}</a>", array("width" => 100, "class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=config-calendars&amp;action=permissions&amp;cid={$calendar['cid']}\">{$lang->permissions}</a>", array("width" => 100, "class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=config-calendars&amp;action=delete&amp;cid={$calendar['cid']}&amp;my_post_key={$mybb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_calendar_deletion}')\">{$lang->delete}</a>", array("width" => 100, "class" => "align_center"));
		$table->construct_row();
	}
	
	if($table->num_rows()  == 0)
	{
		$table->construct_cell($lang->no_calendars, array('colspan' => 5));
		$table->construct_row();
		$no_results = true;
	}
	
	$table->output($lang->manage_calendars);

	if(!$no_results)
	{
		$buttons[] = $form->generate_submit_button($lang->save_calendar_orders);
		$form->output_submit_wrapper($buttons);
	}

	$form->end();

	$page->output_footer();
}

?>