<?php
/**
 * @package TinyPortal
 * @version 2.0.0
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
use \TinyPortal\Util as TPUtil;

if (!defined('ELK')) {
        die('Hacking attempt...');
}

// TinyPortal module entrance
function TPCredits()
{
	tp_hidebars();
	$context['TPortal']['not_forum'] = false;

	if(loadLanguage('TPhelp') == false)
		loadLanguage('TPhelp', 'english');

	loadtemplate('TPhelp');
}
?>
