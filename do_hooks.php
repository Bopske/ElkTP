<?php
/**
 * @package TinyPortal
 * @version 2.1.0
 * @author IchBin - http://www.tinyportal.net
 * @founder Bloc
 * @license MPL 2.0
 *
 * The contents of this file are subject to the Mozilla Public License Version 2.0
 * (the "License"); you may not use this package except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Copyright (C) 2020 - The TinyPortal Team
 *
 */

global $hooks, $mod_name, $forum_version;

$hooks = array(
    'integrate_pre_load'                        => 'BOARDDIR/TinyPortal/Integrate.php|TinyPortal\Integrate::hookPreLoad'
);

$mod_name = 'TinyPortal';

// ---------------------------------------------------------------------------------------------------------------------
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK')) {
	require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('ELK')) {
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as ELK\'s index.php.');
}

if (ELK == 'SSI') {
	// Let's start the main job
	install_mod();
	// and then let's throw out the template! :P
	obExit(null, null, true);
}
else {
	setup_hooks();
}

function install_mod ()
{
	global $context, $mod_name;

	$context['mod_name'] = $mod_name;
	$context['sub_template'] = 'install_script';
	$context['page_title_html_safe'] = 'Hook installer for: ' . $mod_name;
	if (isset($_GET['action']))
		$context['uninstalling'] = $_GET['action'] == 'uninstall' ? true : false;
	$context['html_headers'] .= '
	<style type="text/css">
    .buttonlist ul {
      margin:0 auto;
			display:table;
		}
	</style>';

	// Sorry, only logged in admins...
	isAllowedTo('admin_forum');

	if (isset($context['uninstalling']))
		setup_hooks();
}

function setup_hooks ()
{
	global $context, $hooks, $forum_version;
    
    $smf21 = true;
    if(isset($forum_version) && strpos($forum_version, '2.0') !== false) {
        $smf21 = false;
    }
    elseif(defined('ELK_VERSION') && strpos(ELK_VERSION, '2.0') !== false) {
        $smf21 = false;
    }
    elseif( (ELK == 'SSI') && !function_exists('ssi_version')) {
        $smf21 = false;
    }

    if($smf21 == false) {
        define('ELK_INTEGRATION_SETTINGS', serialize(array('integrate_menu_buttons' => 'install_menu_button',)));
        $hooks['integrate_pre_include'] = '$sourcedir/TPCompat.php';
        $hooks['integrate_pre_load']    = 'TPHookPreLoad';
    }

	$integration_function = empty($context['uninstalling']) ? 'add_integration_function' : 'remove_integration_function';

	foreach ($hooks as $hook => $function) {
		if(strpos($function, ',') === false) {
			$integration_function($hook, $function);
		}
		else {
			$tmpFunc = explode(',', $function);
			foreach($tmpFunc as $func) {
				$integration_function($hook, $func);
			}
		}
    }

    if(!empty($context['uninstalling'])) {
        updateSettings(array('integrate_default_action' => ''));
    }

	$context['installation_done'] = true;
}

function install_menu_button (&$buttons)
{
	global $boardurl, $context;

	$context['sub_template'] = 'install_script';
	$context['current_action'] = 'install';

	$buttons['install'] = array(
		'title' => 'Installation script',
		'show' => allowedTo('admin_forum'),
		'href' => $boardurl . '/do_hooks.php',
		'active_button' => true,
		'sub_buttons' => array(
		),
	);
}

function template_install_script ()
{
	global $boardurl, $context, $mod_name;

	echo '
	<div class="tborder login"">
		<div class="cat_bar">
			<h3 class="catbg">
				Welcome to the install script of the mod: ' . $context['mod_name'] . '
			</h3>
		</div>
		<span class="upperframe"><span></span></span>
		<div class="roundframe centertext">';
	if (!isset($context['installation_done']))
		echo '
			<strong>Please select the action you want to perform:</strong>
			<div class="buttonlist">
				<ul>
					<li>
						<a class="active" href="' . $boardurl . '/do_hooks.php?action=install">
							<span>Install</span>
						</a>
					</li>
					<li>
						<a class="active" href="' . $boardurl . '/do_hooks.php?action=uninstall">
							<span>Uninstall</span>
						</a>
					</li>
				</ul>
			</div>';
	else
		echo '<strong>Database adaptation successful!</strong>';

	echo '
		</div>
		<span class="lowerframe"><span></span></span>
	</div>';
}
?>
