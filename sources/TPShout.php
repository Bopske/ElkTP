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
use \TinyPortal\Mentions as TPMentions;
use \TinyPortal\Shout as TPShout;
use \TinyPortal\Util as TPUtil;

if (!defined('ELK')) {
	die('Hacking attempt...');
}

function TPShout() {{{
    
    global $context, $settings, $options, $modSettings;

    if(isset($_REQUEST['shout'])) {
        $shoutAction = TPUtil::filter('shout', 'request', 'string');
        if($shoutAction == 'admin') {
            TPShoutAdmin();
        }
        elseif($shoutAction == 'del') {
            TPShoutDelete( $_POST['s'] );
            tpshout_bigscreen(false, $context['TPortal']['shoutbox_limit'], $_POST['b'] , $_POST['l']);
        }
        elseif($shoutAction == 'save') {
            if (empty($context['TPortal']['shout_allow_links']) && shoutHasLinks() == true) {
                    return;
            }
            TPShoutPost();
            tpshout_bigscreen(false, $context['TPortal']['shoutbox_limit'], $_POST['b'], $_POST['l']);
        }
        elseif($shoutAction == 'refresh') {
            var_dump(TPShoutFetch( $_POST['b'] , $_POST['l'], false, $context['TPortal']['shoutbox_limit'], true));
            die;
        }
        elseif($shoutAction == 'fetch') {
            tpshout_bigscreen(false, $context['TPortal']['shoutbox_limit'], $_POST['b'], $_POST['l']);
        }
        else {
            $number = substr($shoutAction, 4);
            if(!is_numeric($number)) {
                $number = 10;
            }
            tpshout_bigscreen(true, $number, $_REQUEST['b'], $_REQUEST['l']);
        }
    }

    return true;
    
}}}

function TPShoutLoad() {{{

    global $context, $settings, $options, $modSettings;

    if(loadLanguage('TPortal') == false) {
        loadLanguage('TPortal', 'english');
    }

    if(TP_ELK21) {
        loadCSSFile('jquery.sceditor.css');
    }

    // if in admin screen, turn off blocks
    if($context['TPortal']['action'] == 'tpshout' && isset($_GET['shout']) && substr($_GET['shout'], 0, 5) == 'admin') {
        $in_admin = true;
    }

    if($context['TPortal']['hidebars_admin_only'] =='1' && isset($in_admin)) {
        tp_hidebars();
    }

    // bbc code for shoutbox
    $context['html_headers'] .= '
        <script type="text/javascript"><!-- // --><![CDATA[
            var tp_images_url = "' .$settings['tp_images_url'] . '";
            var tp_session_id = "' . $context['session_id'] . '";
            var tp_session_var = "' . $context['session_var'] . '";
            var tp_shout_key_press = false;
            var current_header_smiley = ';

    if(empty($options['expand_header_smiley'])) {
        $context['html_headers'] .= 'false;';
    }
    else {
        $context['html_headers'] .= 'true;';
    }

    $context['html_headers'] .= 'var current_header_bbc = ';

    if(empty($options['expand_header_bbc'])) {
        $context['html_headers'] .= 'false;';
    }
    else {
        $context['html_headers'] .= 'true;';
    }

    $context['html_headers'] .= '
        // ]]></script>
        <script type="text/javascript" src="'. $settings['default_theme_url']. '/scripts/tinyportal/TPShout.js?'.TPVERSION.'"></script>';

    if(file_exists($settings['theme_dir'].'/css/tp-shout.css')) {
        $context['html_headers'] .= '<link rel="stylesheet" type="text/css" href="'. $settings['theme_url']. '/css/tp-shout.css?'.TPVERSION.'" />';
    }
    else {
        $context['html_headers'] .= '<link rel="stylesheet" type="text/css" href="'. $settings['default_theme_url']. '/css/tp-shout.css?'.TPVERSION.'" />';
    }

}}}

// Post the shout via ajax
function TPShoutPost( ) {{{
	global $context, $smcFunc, $user_info, $scripturl, $sourcedir, $modSettings;

	isAllowedTo('tp_can_shout');

	if(!empty($_POST['tp_shout']) && !empty($_POST['tp-shout-name'])) {
		// Check the session id.
		checkSession('post');
		require_once($sourcedir . '/Subs-Post.php');
		$shout = $smcFunc['htmlspecialchars'](substr($_POST['tp_shout'], 0, 300));
		preparsecode($shout);

		// collect the color for shoutbox
		$request = $smcFunc['db_query']('', '
			SELECT grp.online_color AS onlineColor
			FROM {db_prefix}members AS m
            INNER JOIN {db_prefix}membergroups AS grp
			ON m.id_group = grp.id_group
            WHERE id_member = {int:user} LIMIT 1',
			array('user' => $context['user']['id'])
		);
		if($smcFunc['db_num_rows']($request) > 0) {
			$row = $smcFunc['db_fetch_row']($request);
			$context['TPortal']['usercolor'] = $row[0];
			$smcFunc['db_free_result']($request);
		}

		// Build the name with color for user, otherwise strip guests name of html tags.
		$shout_name = ($user_info['id'] != 0) ? '<a href="'.$scripturl.'?action=profile;u='.$user_info['id'].'"' : strip_tags($_POST['tp-shout-name']);
		if(!empty($context['TPortal']['usercolor'])) {
			$shout_name .= ' style="color: '. $context['TPortal']['usercolor'] . '"';
        }
		$shout_name .= ($user_info['id'] != 0) ? '>'.$context['user']['name'].'</a>' : '';

		$shout_time = time();

		// register the IP and userID, if any
		$ip         = $user_info['ip'];
		$member_id  = $user_info['id'];

		$shout      = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $shout);

        $shoutbox_id   = TPUtil::filter('b', 'post', 'int');
        if(empty($shoutbox_id)) {
            $shoutbox_id = 0;
        }

        if($shout != '') {
            $tpShout = TPShout::getInstance();
            $shout_id = $tpShout->insertShout(
                array(
                    'content'       => $shout,
                    'time'          => $shout_time,
                    'member_link'   => $shout_name,
                    'type'          => 'shoutbox',
                    'member_ip'     => $ip,
                    'member_id'     => $member_id,
                    'edit'          => 0,
                    'shoutbox_id'   => $shoutbox_id
                )
            );
            $mention_data['id']             = $shout_id;
            $mention_data['content']        = $shout;
            $mention_data['type']           = 'shout';
            $mention_data['member_id']      = $user_info['id'];
            $mention_data['username']       = $user_info['username'];
            $mention_data['action']         = 'mention';
            $mention_data['event_title']    = 'Shoutbox Mention';
            $mention_data['text']           = 'Shout';

            $tpMention = TPMentions::getInstance();
            $tpMention->addMention($mention_data); 
        }
    }

}}}

// This is to delete a shout via ajax
function TPShoutDelete( $shout_id = null ) {{{
    $tpShout = TPShout::getInstance();

	// A couple of security checks
	checkSession('post');
	isAllowedTo('tp_can_admin_shout');

	if(!empty($shout_id)) {
        $tpShout->deleteShout($shout_id);
	}

}}}

// fetch all the shouts for output
function TPShoutFetch($shoutbox_id = null, $shoutbox_layout = null, $render = true, $limit = 1, $ajaxRequest = false) {{{
	global $context, $scripturl, $modSettings, $smcFunc;
	global $image_proxy_enabled, $image_proxy_secret, $boardurl;

	// get x number of shouts
	$context['TPortal']['profile_shouts_hide'] = empty($context['TPortal']['profile_shouts_hide']) ? '0' : '1';
	$context['TPortal']['usercolor'] = '';
	// collect the color for shoutbox
	$request = $smcFunc['db_query']('', '
		SELECT grp.online_color AS onlineColor
		FROM {db_prefix}members AS m 
        INNER JOIN {db_prefix}membergroups AS grp
		ON m.id_group = grp.id_group
		WHERE id_member = {int:user} LIMIT 1',
		array('user' => $context['user']['id'])
	);

	if($smcFunc['db_num_rows']($request) > 0){
		$row = $smcFunc['db_fetch_row']($request);
		$context['TPortal']['usercolor'] = $row[0];
		$smcFunc['db_free_result']($request);
	}

	if(is_numeric($context['TPortal']['shoutbox_limit']) && $limit == 1) {
		$limit = $context['TPortal']['shoutbox_limit'];
    }

	// don't fetch more than a hundred - save the poor server! :D
	$nshouts = '';
	if($limit > 100)
		$limit = 100;

	loadTemplate('TPShout');


    $block_shout = ' 1 = 1';
    if(!is_null($shoutbox_id)) {
        $block_shout = ' s.shoutbox_id = {int:shoutbox_id} ';
    }

	$members = array();
	$request =  $smcFunc['db_query']('', '
		SELECT s.*
			FROM {db_prefix}tp_shoutbox AS s
		WHERE 
        '.$block_shout.'
		ORDER BY s.time DESC LIMIT {int:limit}',
		array(
            'limit' => $limit,
            'shoutbox_id' => $shoutbox_id,
        )
	);

	if($smcFunc['db_num_rows']($request) > 0 ) {
		while($row = $smcFunc['db_fetch_assoc']($request)) {
			$fetched[] = $row;
			if(!empty($row['member_id']) && !in_array($row['member_id'], $members)) {
				$members[] = $row['member_id'];
            }
		}
		$smcFunc['db_free_result']($request);
	}

	if(count($members) > 0 ) {
		$request2 =  $smcFunc['db_query']('', '
		    SELECT mem.id_member, mem.real_name AS real_name, mem.email_address AS email_address, 
			    mem.avatar, COALESCE(a.id_attach,0) AS id_attach, a.filename, COALESCE(a.attachment_type,0) AS attachment_type, mgrp.online_color AS mg_online_color, pgrp.online_color AS pg_online_color
		    FROM {db_prefix}members AS mem
			LEFT JOIN {db_prefix}membergroups AS mgrp ON
				(mgrp.id_group = mem.id_group)
			LEFT JOIN {db_prefix}membergroups AS pgrp ON
				(pgrp.id_group = mem.id_post_group)
			LEFT JOIN {db_prefix}attachments AS a ON 
                (a.id_member = mem.id_member and a.attachment_type!=3)
		    WHERE mem.id_member IN(' . implode(",",$members) . ')' 
	    );
    }

	$memberdata = array();
	if(isset($request2) && $smcFunc['db_num_rows']($request2)>0) {
		while($row = $smcFunc['db_fetch_assoc']($request2)) {
            $row['avatar'] = set_avatar_data( array(      
                    'avatar' => $row['avatar'],
                    'email' => $row['email_address'],
                    'filename' => !empty($row['filename']) ? $row['filename'] : '',
                    'id_attach' => $row['id_attach'],
                    'attachment_type' => $row['attachment_type'],
                )
            )['image'];
			$memberdata[$row['id_member']] = $row;
		}
		$smcFunc['db_free_result']($request2);
	}

	if(!empty($fetched) && count($fetched)>0) {
		$ns = array();
		foreach($fetched as $b => $row) {
			$row['avatar'] = !empty($memberdata[$row['member_id']]['avatar']) ? $memberdata[$row['member_id']]['avatar'] : '';
			$row['real_name'] = !empty($memberdata[$row['member_id']]['real_name']) ? $memberdata[$row['member_id']]['real_name'] : $row['member_link'];
			$row['content'] = parse_bbc(censorText($row['content']), true);
			$row['online_color'] = !empty($memberdata[$row['member_id']]['mg_online_color']) ? $memberdata[$row['member_id']]['mg_online_color'] : (!empty($memberdata[$row['member_id']]['pg_online_color']) ? $memberdata[$row['member_id']]['pg_online_color'] : '');
			$ns[] = template_singleshout($row, $shoutbox_id, $shoutbox_layout);
		}
		$nshouts .= implode('', $ns);

		$context['TPortal']['shoutbox'] = $nshouts;
	}

	// its from a block, render it
	if($render && !$ajaxRequest) {
		template_tpshout_shoutblock( $shoutbox_id , $shoutbox_layout);
    }
	else {
		return $nshouts;
    }

}}}

function tpshout_bigscreen($state = false, $number = 10, $shoutbox_id = 0, $shoutbox_layout = null ) {{{
    global $context;

    loadTemplate('TPShout');

    if ($state == false) {
        $context['template_layers']         = array();
        $context['sub_template']            = 'tpshout_ajax';
        $context['TPortal']['rendershouts'] = TPShoutFetch($shoutbox_id, $shoutbox_layout, $state, $number, true);
    }
    else {
        $context['TPortal']['rendershouts'] = TPShoutFetch($shoutbox_id, $shoutbox_layout, false, $number, false);
        TP_setThemeLayer('tpshout', 'TPortal', 'tpshout_bigscreen');
        $context['page_title'] = 'Shoutbox';
    }
}}}

function shout_bcc_code($collapse = true) {{{
	global $context, $txt, $settings, $option;

	loadLanguage('Post');

	echo '
	<script type="text/javascript"><!-- // --><![CDATA[
		function tp_bbc_highlight(something, mode)
		{
			something.style.backgroundImage = "url(" + smf_images_url + (mode ? "/bbc/bbc_hoverbg.gif)" : "/bbc/bbc_bg.gif)");
		}
	// ]]></script>';
    
    // The below array makes it dead easy to add images to this page. Add it to the array and everything else is done for you!
    $context['tp_bbc_tags'] = array();
    $context['tp_bbc_tags2'] = array();

    if(!TP_ELK21) {
        $context['tp_bbc_tags'][] = array(
            'bold' => array('code' => 'b', 'before' => '[b]', 'after' => '[/b]', 'description' => $txt['bold']),
            'italicize' => array('code' => 'i', 'before' => '[i]', 'after' => '[/i]', 'description' => $txt['italic']),
            'underline' => array('code' => 'u', 'before' => '[u]', 'after' => '[/u]', 'description' => $txt[ 'underline']),
            'strike' => array('code' => 's', 'before' => '[s]', 'after' => '[/s]', 'description' => $txt['strike']),
        );
        $context['tp_bbc_tags2'][] = array(
            'glow' => array('code' => 'glow', 'before' => '[glow=red,2,300]', 'after' => '[/glow]', 'description' => $txt[ 'glow']),
            'shadow' => array('code' => 'shadow', 'before' => '[shadow=red,left]', 'after' => '[/shadow]', 'description' => $txt[ 'shadow']),
            'move' => array('code' => 'move', 'before' => '[move]', 'after' => '[/move]', 'description' => $txt[ 'marquee']),
			'img' => array('code' => 'img', 'before' => '[img]', 'after' => '[/img]', 'description' => $txt['image']),
            'quote' => array('code' => 'quote', 'before' => '[quote]', 'after' => '[/quote]', 'description' => $txt['bbc_quote']),
        );

    }
    else {
        global $editortxt;
        loadLanguage('Editor');

        $context['tp_bbc_tags'][] = array(
            'bold' => array('code' => 'b', 'before' => '[b]', 'after' => '[/b]', 'description' => $editortxt['bold']),
            'italic' => array('code' => 'i', 'before' => '[i]', 'after' => '[/i]', 'description' => $editortxt['italic']),
            'underline' => array('code' => 'u', 'before' => '[u]', 'after' => '[/u]', 'description' => $editortxt['underline']),
            'strike' => array('code' => 's', 'before' => '[s]', 'after' => '[/s]', 'description' => $editortxt['strikethrough']),
        );
        $context['tp_bbc_tags2'][] = array(
        );
    }

	if($collapse) {
		echo '  <a href="#" onclick="expandHeaderBBC(!current_header_bbc, ' . ($context['user']['is_guest'] ? 'true' : 'false') . ', \'' . $context['session_id'] . '\'); return false;">
		            <img id="expand_bbc" src="', $settings['tp_images_url'], '/', empty($options['expand_header_bbc']) ? 'TPexpand.png' : 'TPcollapse.png', '" alt="*" title="', array_key_exists('upshrink_description', $txt) ? $txt['upshrink_description'] : '', '" style="margin-right: 5px;float:left" />
	            </a>
                <div id="shoutbox_bbc" style="text-align:left;">';
    }
	else {
		echo '  <div>';
    }

	$found_button = false;
	// Here loop through the array, printing the images/rows/separators!
	if(isset($context['tp_bbc_tags'][0]) && count($context['tp_bbc_tags'][0]) > 0) {
		foreach ($context['tp_bbc_tags'][0] as $image => $tag) {
            if(!TP_ELK21) {
                // Is there a "before" part for this bbc button? If not, it can't be a button!!
                if (isset($tag['before'])) {
                    // Is this tag disabled?
                    if (!empty($context['disabled_tags'][$tag['code']]))
                        continue;

                    $found_button = true;

                    // If there's no after, we're just replacing the entire selection in the post box.
                    if (!isset($tag['after']))
                        echo '<a href="javascript:void(0);" onclick="replaceText(\'', $tag['before'], '\', document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '); return false;">';
                    // On the other hand, if there is one we are surrounding the selection ;).
                    else
                        echo '<a href="javascript:void(0);" onclick="surroundText(\'', $tag['before'], '\', \'', $tag['after'], '\', document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '); return false;">';

                    // Okay... we have the link. Now for the image and the closing </a>!
                    echo '<img onmouseover="tp_bbc_highlight(this, true);" onmouseout="if (window.tp_bbc_highlight) tp_bbc_highlight(this, false);" src="', $settings['images_url'], '/bbc/', $image, '.gif" width="23" height="22" alt="', $tag['description'], '" title="', $tag['description'], '" style="background-image: url(', $settings['images_url'], '/bbc/bbc_bg.gif); margin: 1px 2px 1px 1px;vertical-align:bottom" /></a>';
                }
                // I guess it's a divider...
                elseif ($found_button) {
                    echo '<img src="', $settings['images_url'], '/bbc/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />';
                    $found_button = false;
                }
            }
            else {
				echo '<a class="sceditor-button sceditor-button-'.$image.'" onclick="surroundText(\'', $tag['before'], '\', \'', $tag['after'], '\', document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '); return false;" style="padding:0px;"><div unselectable="on">'.$tag['description'].'</div></a>';
            }
		}
	}

	if($collapse) {
		echo '<div id="expandHeaderBBC"', empty($options['expand_header_bbc']) ? ' style="display: none;"' : 'style="display: inline;"' , '>';
    }
	else {
		echo '<div style="display: inline;">';
    }

	$found_button1 = false;
	// Here loop through the array, printing the images/rows/separators!
	if(isset($context['tp_bbc_tags2'][0]) && count($context['tp_bbc_tags2'][0])>0)
	{
		foreach ($context['tp_bbc_tags2'][0] as $image => $tag)
		{
            if(!TP_ELK21) {
                // Is there a "before" part for this bbc button? If not, it can't be a button!!
                if (isset($tag['before']))
                {
                    // Is this tag disabled?
                    if (!empty($context['disabled_tags'][$tag['code']]))
                        continue;

                    $found_button1 = true;

                    // If there's no after, we're just replacing the entire selection in the post box.
                    if (!isset($tag['after']))
                        echo '<a href="javascript:void(0);" onclick="replaceText(\'', $tag['before'], '\', document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '); return false;">';
                    // On the other hand, if there is one we are surrounding the selection ;).
                    else
                        echo '<a href="javascript:void(0);" onclick="surroundText(\'', $tag['before'], '\', \'', $tag['after'], '\', document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '); return false;">';

                    // Okay... we have the link. Now for the image and the closing </a>!
                    echo '<img onmouseover="tp_bbc_highlight(this, true);" onmouseout="if (window.tp_bbc_highlight) tp_bbc_highlight(this, false);" src="', $settings['images_url'], '/bbc/', $image, '.gif" width="23" height="22" alt="', $tag['description'], '" title="', $tag['description'], '" style="background-image: url(', $settings['images_url'], '/bbc/bbc_bg.gif); margin: 1px 2px 1px 1px;vertical-align:bottom" /></a>';
                }
                // I guess it's a divider...
                elseif ($found_button1)
                {
                    echo '<img src="', $settings['images_url'], '/bbc/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />';
                    $found_button1 = false;
                }
            }
            else {
                echo '<a class="sceditor-button sceditor-button-'.$image.'" onclick="surroundText(\'', $tag['before'], '\', \'', $tag['after'], '\', document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '); return false;" style="padding:0px;"><div unselectable="on">'.$tag['description'].'</div></a>';
            }
		}
	}

	// Print a drop down list for all the colors we allow!
	if (!isset($context['shout_disabled_tags']['color']))
		echo ' <p class="clearthefloat"></p>
				<select onchange="surroundText(\'[color=\' + this.options[this.selectedIndex].value.toLowerCase() + \']\', \'[/color]\', document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '); this.selectedIndex = 0; document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '.focus(document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '.caretPos);" style="margin: 5px auto 10px auto;">
						<option value="" selected="selected">'. $txt['change_color']. '</option>
						<option value="Black">Black</option>
						<option value="Red">Red</option>
						<option value="Yellow">Yellow</option>
						<option value="Pink">Pink</option>
						<option value="Green">Green</option>
						<option value="Orange">Orange</option>
						<option value="Purple">Purple</option>
						<option value="Blue">Blue</option>
						<option value="Beige">Beige</option>
						<option value="Brown">Brown</option>
						<option value="Teal">Teal</option>
						<option value="Navy">Navy</option>
						<option value="Maroon">Maroon</option>
						<option value="LimeGreen">LimeGreen</option>
					</select>';
	echo '<br />';

	$found_button2 = false;
	// Print the bottom row of buttons!
	if(isset($context['tp_bbc_tags'][1]) && count($context['tp_bbc_tags'][1])>0)
	{
		foreach ($context['tp_bbc_tags'][1] as $image => $tag)
		{
			if (isset($tag['before']))
			{
				// Is this tag disabled?
				if (!empty($context['shout_disabled_tags'][$tag['code']]))
					continue;

				$found_button2 = true;

				// If there's no after, we're just replacing the entire selection in the post box.
				if (!isset($tag['after']))
					echo '<a href="javascript:void(0);" onclick="replaceText(\'', $tag['before'], '\', document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '); return false;">';
				// On the other hand, if there is one we are surrounding the selection ;).
				else
					echo '<a href="javascript:void(0);" onclick="surroundText(\'', $tag['before'], '\', \'', $tag['after'], '\', document.forms.', $context['tp_shoutbox_form'], '.', $context['shout_post_box_name'], '); return false;">';

				// Okay... we have the link. Now for the image and the closing </a>!
				echo '<img onmouseover="tp_bbc_highlight(this, true);" onmouseout="if (window.tp_bbc_highlight) tp_bbc_highlight(this, false);" src="', $settings['images_url'], '/bbc/', $image, '.gif" width="23" height="22" alt="', $tag['description'], '" title="', $tag['description'], '" style="background-image: url(', $settings['images_url'], '/bbc/bbc_bg.gif); margin: 1px 2px 1px 1px;vertical-align:bottom" /></a>';
			}
			// I guess it's a divider...
			elseif ($found_button2)
			{
				echo '<img src="', $settings['images_url'], '/bbc/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />';
				$found_button2 = false;
			}
		}
	}
	echo '</div>
	</div>';

}}}

function shout_smiley_code() {{{
    global $context, $settings, $user_info, $txt, $modSettings, $smcFunc;

    // Initialize smiley array...
    $context['tp_smileys'] = array(
        'postform' => array(),
        'popup' => array(),
    );

    // Load smileys - don't bother to run a query in 2.0 if we're not using the database's ones anyhow.
    if (empty($modSettings['smiley_enable']) && $user_info['smiley_set'] != 'none' && !TP_ELK21) {
            $context['tp_smileys']['postform'][] = array(
                'smileys' => array(
                    array('code' => ':)', 'filename' => 'smiley.gif', 'description' => $txt['icon_smiley']),
                    array('code' => ';)', 'filename' => 'wink.gif', 'description' => $txt['icon_wink']),
                    array('code' => ':D', 'filename' => 'cheesy.gif', 'description' => $txt['icon_cheesy']),
                    array('code' => ';D', 'filename' => 'grin.gif', 'description' => $txt['icon_grin']),
                    array('code' => '>:(', 'filename' => 'angry.gif', 'description' => $txt['icon_angry']),
                    array('code' => ':(', 'filename' => 'sad.gif', 'description' => $txt[ 'icon_sad']),
                    array('code' => ':o', 'filename' => 'shocked.gif', 'description' => $txt['icon_shocked']),
                    array('code' => '8)', 'filename' => 'cool.gif', 'description' => $txt[ 'icon_cool']),
                    array('code' => '???', 'filename' => 'huh.gif', 'description' => $txt['icon_huh']),
                    array('code' => '::)', 'filename' => 'rolleyes.gif', 'description' => $txt[ 'icon_rolleyes']),
                    array('code' => ':P', 'filename' => 'tongue.gif', 'description' => $txt['icon_tongue']),
                    array('code' => ':-[', 'filename' => 'embarrassed.gif', 'description' => $txt['icon_embarrassed']),
                    array('code' => ':-X', 'filename' => 'lipsrsealed.gif', 'description' => $txt['icon_lips']),
                    array('code' => ':-\\', 'filename' => 'undecided.gif', 'description' => $txt[ 'icon_undecided']),
                    array('code' => ':-*', 'filename' => 'kiss.gif', 'description' => $txt['icon_kiss']),
                    array('code' => ':\'(', 'filename' => 'cry.gif', 'description' => $txt['icon_cry'])
                ),
                'last' => true,
            );
	}
	elseif ($user_info['smiley_set'] != 'none') {
		if (($temp = cache_get_data('posting_smileys', 480)) == null)
		{
        if(!TP_ELK21) {
			$request = $smcFunc['db_query']('', '
			    SELECT code, filename, description, smiley_row, hidden
				FROM {db_prefix}smileys
				WHERE hidden IN ({int:val1}, {int:val2})
				ORDER BY smiley_row, smiley_order',
				array(
                    'val1' => 0,
				    'val2' => 2
                )
			);			
		}
		else {
			$request = $smcFunc['db_query']('', '
			    SELECT smiley.code, files.filename, smiley.description, smiley.smiley_row, smiley.hidden
				FROM {db_prefix}smileys AS smiley
				LEFT JOIN {db_prefix}smiley_files AS files ON				
				(smiley.id_smiley = files.id_smiley)
				WHERE hidden IN ({int:val1}, {int:val2}) and files.smiley_set = {string:smiley_set}
				ORDER BY smiley_row, smiley_order',
				array(
                    'val1' => 0,
				    'val2' => 2,
					'smiley_set' => $user_info['smiley_set']
				)
			);
		}
			
		    while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				$row['code'] = htmlspecialchars($row['code']);
				$row['filename'] = htmlspecialchars($row['filename']);
				$row['description'] = htmlspecialchars($row['description']);

				$context['tp_smileys'][empty($row['hidden']) ? 'postform' : 'popup'][$row['smiley_row']]['smileys'][] = $row;
			}
			$smcFunc['db_free_result']($request);

			cache_put_data('posting_smileys', $context['tp_smileys'], 480);
		}
		else {
			$context['tp_smileys'] = $temp;
        }
	}

	$file_ext = '';

	// Clean house... add slashes to the code for javascript.
	foreach (array_keys($context['tp_smileys']) as $location)
	{
		foreach ($context['tp_smileys'][$location] as $j => $row)
		{
			$n = count($context['tp_smileys'][$location][$j]['smileys']);
			for ($i = 0; $i < $n; $i++)
			{
				$context['tp_smileys'][$location][$j]['smileys'][$i]['code']            = addslashes($context['tp_smileys'][$location][$j]['smileys'][$i]['code']);
                $context['tp_smileys'][$location][$j]['smileys'][$i]['js_description']  = addslashes($context['tp_smileys'][$location][$j]['smileys'][$i]['description']);
				$context['tp_smileys'][$location][$j]['smileys'][$i]['filename']        = $context['tp_smileys'][$location][$j]['smileys'][$i]['filename'].$file_ext;
			}

			$context['tp_smileys'][$location][$j]['smileys'][$n - 1]['last'] = true;
		}
		if (!empty($context['tp_smileys'][$location]))
			$context['tp_smileys'][$location][count($context['tp_smileys'][$location]) - 1]['last'] = true;
	}

	$settings['smileys_url'] = $modSettings['smileys_url'] . '/' . $user_info['smiley_set'];
}}}

function print_shout_smileys($collapse = true) {{{
	global $context, $txt, $settings, $options;

	loadLanguage('Post');

	if($collapse) {
		echo '
	<a href="#" onclick="expandHeaderSmiley(!current_header_smiley, '. ($context['user']['is_guest'] ? 'true' : 'false') .', \''. $context['session_id'] .'\'); return false;">
		<img id="expand_smiley" src="', $settings['tp_images_url'], '/', empty($options['expand_header_smiley']) ? 'TPexpand.png' : 'TPcollapse.png', '" alt="*" title="', array_key_exists('upshrink_description', $txt) ? $txt['upshrink_description'] : '', '" style="margin-right: 5px;float:left" />
	</a>
	<div id="shoutbox_smiley" style="text-align:left;">
		';
    }
	else {
		echo '<div>';
    }

	// Now start printing all of the smileys.
	if (!empty($context['tp_smileys']['postform'])) {
		// counter...
		$sm_counter = 0;
		// Show each row of smileys ;).
		foreach ($context['tp_smileys']['postform'] as $smiley_row) {
			foreach ($smiley_row['smileys'] as $smiley) {
				if($sm_counter == 5 && $collapse) {
					echo '<div id="expandHeaderSmiley"', empty($options['expand_header_smiley']) ? ' style="display: none;"' : 'style="display: inline;"' , '>';
                }

				echo '<a href="javascript:void(0);" onclick="replaceText(\' ', $smiley['code'], '\', document.forms.', $context['tp_shoutbox_form'], '.', $context['tp_shout_post_box_name'], '); return false;"><img src="', $settings['smileys_url'], '/', $smiley['filename'], '" style="vertical-align:bottom" alt="', $smiley['description'], '" title="', $smiley['description'], '" /></a>';
				$sm_counter++;
			}
		}
	}

	echo '
		</div>
	</div>';
}}}

// show a dedicated frontpage
function tpshout_frontpage() {{{
	loadtemplate('TPShout');
    tpshout_bigscreen(true);
}}}

function shoutHasLinks() {{{
	global $context;
	$shout = !empty($_POST['tp_shout']) ? $_POST['tp_shout'] : '';
    if(TPUtil::hasLinks($shout)) {
		loadTemplate('TPShout');
		$context['TPortal']['shoutError'] = true;
		$context['TPortal']['rendershouts'] = 'Links are not allowed!';
		$context['template_layers'] = array();
		$context['sub_template'] = 'tpshout_ajax';
		return true;
	}
	return false;
}}}

function tp_shoutb($member_id) {{{
    global $txt, $context;
    loadtemplate('TPprofile');
    $context['page_title'] = $txt['shoutboxprofile'];
    tpshout_profile($member_id);
}}}

// fetch all the shouts for output
function tpshout_profile($member_id) {{{
    global $context, $scripturl, $txt, $smcFunc;
    $context['page_title'] = $txt['shoutboxprofile'] ;
    if(isset($context['TPortal']['mystart'])) {
        $start = $context['TPortal']['mystart'];
    }
    else {
        $start = 0;
    }
    $context['TPortal']['member_id'] = $member_id;
    $sorting = 'time';
    $max = 0;
    // get all shouts
    $request = $smcFunc['db_query']('', '
        SELECT COUNT(*) FROM {db_prefix}tp_shoutbox
        WHERE member_id = {int:member_id} AND type = {string:type}',
        array('member_id' => $member_id, 'type' => 'shoutbox')
    );
    $result = $smcFunc['db_fetch_row']($request);
    $max    = $result[0];
    $smcFunc['db_free_result']($request);
    $context['TPortal']['all_shouts'] = $max;
    $context['TPortal']['profile_shouts'] = array();
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}tp_shoutbox
        WHERE member_id = {int:member_id}
        AND type = {string:type}
        ORDER BY {raw:sort} DESC LIMIT 15 OFFSET {int:start}',
        array('member_id' => $member_id, 'type' => 'shoutbox', 'sort' => $sorting, 'start' => $start)
    );
    if($smcFunc['db_num_rows']($request) > 0){
        while($row = $smcFunc['db_fetch_assoc']($request)){
            $context['TPortal']['profile_shouts'][] = array(
                'id' => $row['id'],
                'shout' => parse_bbc(censorText($row['content'])),
                'created' => timeformat($row['time']),
                'ip' => $row['member_ip'],
                'editlink' => allowedTo('tp_shoutbox') ? $scripturl.'?action=tpshout;shout=admin;u='.$member_id : '',
            );
        }
        $smcFunc['db_free_result']($request);
    }
    // construct pageindexes
    if($max > 0) {
        $context['TPortal']['pageindex'] = TPageIndex($scripturl.'?action=profile;area=tpshoutbox;u='.$member_id.';tpsort='.$sorting, $start, $max, '15', true);
    }
    else {
        $context['TPortal']['pageindex'] = '';
    }
    loadtemplate('TPShout');
    if(loadLanguage('TPortal') == false) {
        loadLanguage('TPortal', 'english');
    }
    $context['sub_template'] = 'tpshout_profile';
}}}

// Block Callback
function TPShoutBlock(&$row) {{{
    global $context, $txt, $sourcedir;

    static $id = 1;

    if(loadLanguage('TPortal') == false) {
        loadLanguage('TPortal', 'english');
    }

    $set = json_decode($row['settings'], TRUE);

    $context['TPortal']['tpblocks']['blockrender'][$id] = array(
        'id'                => $row['id'],
        'shoutbox_id'       => $set['var2'],
        'shoutbox_layout'   => $set['var3'],
        'shoutbox_height'   => $set['var4'],
        'name'              => $txt['tp-shoutbox'],
        'function'          => 'TPShoutFetch',
        'sourcefile'        => $sourcedir .'/TPShout.php',
    );

    // Force var1 to change...
    $set['var1']        = $id++;
    $row['settings']    = json_encode($set, TRUE);

    if(!empty($context['TPortal']['shoutbox_refresh'])) {
        $context['html_headers'] .= '
        <script type="text/javascript"><!-- // --><![CDATA[
            window.setInterval("TPupdateShouts(\'fetch\', '.$set['var2'].' , null , '.$set['var3'].')", '. $context['TPortal']['shoutbox_refresh'] * 1000 . ');
        // ]]></script>';
    }


    if($context['TPortal']['shoutbox_usescroll'] > 0) {
        $context['html_headers'] .= '
        <script type="text/javascript" src="tp-files/tp-plugins/javascript/jquery.marquee.js"></script>
        <script type="text/javascript">
            $j(document).ready(function(){
                $j("marquee").marquee("tp_marquee").mouseover(function () {
                        $j(this).trigger("stop");
                    }).mouseout(function () {
                        $j(this).trigger("start");
                    });
                });
        </script>';
    }

    if(!empty($context['TPortal']['shout_submit_returnkey'])) {
        if($context['TPortal']['shout_submit_returnkey'] == 1) {
            $context['html_headers'] .= '
            <script type="text/javascript"><!-- // --><![CDATA[
                $(document).ready(function() {
                    $("#tp_shout_'.$set['var2'].'").keypress(function(event) {
                        if(event.which == 13 && !event.shiftKey) {
                            tp_shout_key_press = true;
                            // set a 100 millisecond timeout for the next key press
                            window.setTimeout(function() { tp_shout_key_press = false; }, 100);
                            TPupdateShouts(\'save\' , '.$set['var2'].' , null , '.$set['var3'].');
                        }
                    });
                });
            // ]]></script>';
        } 
        else if($context['TPortal']['shout_submit_returnkey'] == 2) {
            $context['html_headers'] .= '
            <script type="text/javascript"><!-- // --><![CDATA[
            $(document).ready(function() {
                $("#tp_shout_'.$set['var2'].'").keydown(function (event) {
                    if((event.metaKey || event.ctrlKey) && event.keyCode == 13) {
                        tp_shout_key_press = true;
                        // set a 100 millisecond timeout for the next key press
                        window.setTimeout(function() { tp_shout_key_press = false; }, 100);
                        TPupdateShouts(\'save\' , '.$set['var2'].' , null , '.$set['var3'].');
                    }
                    else if (event.keyCode == 13) {
                        event.preventDefault();
                    }
                });
            });
            // ]]></script>';
        }
    }
    else {
        $context['html_headers'] .= '
            <script type="text/javascript"><!-- // --><![CDATA[
            $(document).ready(function() {
                $("#tp_shout_'.$set['var2'].'").keydown(function (event) {
                    if (event.keyCode == 13) {
                        event.preventDefault();
                    }
                });
            });
            // ]]></script>';
    }

}}}

// Admin Area
function TPShoutAdminActions(&$subActions) {{{

   $subActions = array_merge(
        array (
            'shout'      => array('TPShout.php', 'TPShoutAdmin',   array()),
        ),
        $subActions
    );

}}}

function TPShoutAdmin() {{{
	global $context, $scripturl, $txt, $smcFunc, $sourcedir;

	// check permissions
	isAllowedTo('tp_can_admin_shout');

    if(!(isset($_GET['shout']) && $_GET['shout'] == 'admin')) {
        return;
    }

	if(!isset($context['tp_panels'])) {
		$context['tp_panels'] = array();
    }

	if(isset($_GET['p']) && is_numeric($_GET['p'])) {
		$tpstart = $_GET['p'];
    }
	else {
		$tpstart = 0;
    }

	require_once($sourcedir . '/Subs-Post.php');
	loadtemplate('TPShout');

	$context['template_layers'][] = 'tpadm';
	$context['template_layers'][] = 'subtab';
	loadlanguage('TPortalAdmin');

	TPadminIndex('shout');
	$context['current_action'] = 'admin';

    // clear the linktree first
    TPstrip_linktree();
	
	// Set the linktree
	TPadd_linktree($scripturl.'?action=tpshout', 'TPshout');

	if(isset($_REQUEST['send']) || isset($_REQUEST[$txt['tp-send']]) || isset($_REQUEST['tp_preview']) || isset($_REQUEST['TPadmin_blocks'])) {
		$go = 0;
		$changeArray = array();
		foreach ($_POST as $what => $value) {
			if(substr($what, 0, 18) == 'tp_shoutbox_remove') {
				$val = substr($what, 18);
				$smcFunc['db_query']('', '
					DELETE FROM {db_prefix}tp_shoutbox
					WHERE id = {int:shout}',
					array('shout' => $val)
				);
				$go = 2;
			}
			elseif($what == 'tp_shoutsdelall' && $value == 'ON') {
				$smcFunc['db_query']('', '
					DELETE FROM {db_prefix}tp_shoutbox
					WHERE type = {string:type}',
					array('type' => 'shoutbox')
				);
				$go = 2;
			}
			elseif(substr($what, 0, 16) == 'tp_shoutbox_item') {
				$val = substr($what, 16);
				$bshout = $smcFunc['htmlspecialchars'](substr($value, 0, 300));
				preparsecode($bshout);
				$bshout = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $bshout);
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}tp_shoutbox
					SET content = {string:val1}
					WHERE id = {int:val}',
					array('val1' => $bshout, 'val' => $val)
				);
				$go = 2;
			}
			else {
				$what = substr($what, 3);
				if($what == 'shoutbox_smile') {
					$changeArray['show_shoutbox_smile'] = $value;
                }
				
                if($what == 'shoutbox_icons') {
					$changeArray['show_shoutbox_icons'] = $value;
                }
				
                if($what == 'shoutbox_height') {
					$changeArray['shoutbox_height'] = $value;
                }

				if($what == 'shoutbox_usescroll') {
					$changeArray['shoutbox_usescroll'] = $value;
                }

				if($what == 'shoutbox_scrollduration') {
					if($value > 5) {
						$value = 5;
                    }
					else if($value < 1) {
						$value = 1;
                    }

					$changeArray['shoutbox_scrollduration'] = $value;
				}

				if($what == 'shoutbox_limit') {
					if(!is_numeric($value)) {
						$value = 10;
                    }
					$changeArray['shoutbox_limit'] = $value;
				}

				if($what == 'shoutbox_refresh') {
					if(empty($value)) {
						$value = '0';
                    }
					$changeArray['shoutbox_refresh'] = $value;
				}

				if($what == 'show_profile_shouts') {
					$changeArray['profile_shouts_hide'] = $value;
                }

				if($what == 'shout_allow_links') {
					$changeArray['shout_allow_links'] = $value;
                }

				if($what == 'shoutbox_layout') {
					$changeArray['shoutbox_layout'] = $value;
                }

				if($what == 'shout_submit_returnkey') {
					$changeArray['shout_submit_returnkey'] = $value;
                }

				if($what == 'shoutbox_stitle') {
					$changeArray['shoutbox_stitle'] = $value;
                }

				if($what == 'shoutbox_maxlength') {
					$changeArray['shoutbox_maxlength'] = $value;
                }

				if($what == 'shoutbox_timeformat') {
					$changeArray['shoutbox_timeformat'] = $value;
                }

				if($what == 'shoutbox_use_groupcolor') {
					$changeArray['shoutbox_use_groupcolor'] = $value;
                }

				if($what == 'shoutbox_textcolor') {
					$changeArray['shoutbox_textcolor'] = $value;
                }

				if($what == 'shoutbox_timecolor') {
					$changeArray['shoutbox_timecolor'] = $value;
                }

				if($what == 'shoutbox_linecolor1') {
					$changeArray['shoutbox_linecolor1'] = $value;
                }

				if($what == 'shoutbox_linecolor2') {
					$changeArray['shoutbox_linecolor2'] = $value;
				}
            }
		}
		updateTPSettings($changeArray, true);

		if(empty($go)) {
			redirectexit('action=tpshout;shout=admin;settings');
        }
		else {
			redirectexit('action=tpshout;shout=admin');
        }
	}

	// get latest shouts for admin section
	// check that a member has been filtered
	if(isset($_GET['u'])) {
		$member_id = $_GET['u'];
    }

	// check that a IP has been filtered
	if(isset($_GET['ip'])) {
		$ip = $_GET['ip'];
    }

	// check that a Shoutbox ID has been filtered
	if(isset($_GET['shoutbox_id'])) {
		$shoutbox_id = $_GET['shoutbox_id'];
    }

	if(isset($_GET['s'])) {
		$single = $_GET['s'];
    }

	$context['TPortal']['admin_shoutbox_items'] = array();

	if(isset($member_id)) {
		$shouts =  $smcFunc['db_query']('', '
			SELECT COUNT(*) FROM {db_prefix}tp_shoutbox
			WHERE type = {string:type}
			AND member_id = {int:val5}',
			array('type' => 'shoutbox', 'val5' => $member_id)
		);
		$weh = $smcFunc['db_fetch_row']($shouts);
		$smcFunc['db_free_result']($shouts);
		$allshouts = $weh[0];
		$context['TPortal']['admin_shoutbox_items_number'] = $allshouts;
		$context['TPortal']['shoutbox_pageindex'] = 'Member '.$member_id.' ' .$txt['tp-filtered'] . ' (<a href="'.$scripturl.'?action=tpshout;shout=admin">' . $txt['remove'] . '</a>) <br />'.TPageIndex($scripturl.'?action=tpshout;shout=admin;u='.$member_id, $tpstart, $allshouts, 10, true);
		$request = $smcFunc['db_query']('', '
			SELECT * FROM {db_prefix}tp_shoutbox
			WHERE type = {string:type}
			AND member_id = {int:val5}
			ORDER BY time DESC LIMIT {int:start},10',
			array('type' => 'shoutbox', 'val5'=> $member_id, 'start' => $tpstart)
		);
	}
	elseif(isset($ip)) {
		$shouts =  $smcFunc['db_query']('', '
			SELECT COUNT(*) FROM {db_prefix}tp_shoutbox
			WHERE type = {string:type}
			AND member_ip = {string:val4}',
			array('type' => 'shoutbox', 'val4' => $ip)
		);
		$weh = $smcFunc['db_fetch_row']($shouts);
		$smcFunc['db_free_result']($shouts);
		$allshouts = $weh[0];
		$context['TPortal']['admin_shoutbox_items_number'] = $allshouts;
		$context['TPortal']['shoutbox_pageindex'] = 'IP '.$ip.' filtered (<a href="'.$scripturl.'?action=tpshout;shout=admin">' . $txt['remove'] . '</a>) <br />'.TPageIndex($scripturl.'?action=tpshout;shout=admin;ip='.urlencode($ip) , $tpstart, $allshouts, 10,true);
		$request =  $smcFunc['db_query']('', '
			SELECT * FROM {db_prefix}tp_shoutbox
			WHERE type = {string:type}
			AND member_ip = {string:val4}
			ORDER BY time DESC LIMIT {int:start}, 10',
			array('type' => 'shoutbox', 'val4' => $ip, 'start' => $tpstart)
		);
	}
	elseif(isset($shoutbox_id)) {
		$shouts =  $smcFunc['db_query']('', '
			SELECT COUNT(*) FROM {db_prefix}tp_shoutbox
			WHERE type = {string:type}
			AND shoutbox_id = {string:val4}',
			array('type' => 'shoutbox', 'val4' => $shoutbox_id)
		);
		$weh = $smcFunc['db_fetch_row']($shouts);
		$smcFunc['db_free_result']($shouts);
		$allshouts = $weh[0];
		$context['TPortal']['admin_shoutbox_items_number'] = $allshouts;
		$context['TPortal']['shoutbox_pageindex'] = 'Shoutbox_ID '.$shoutbox_id.' filtered (<a href="'.$scripturl.'?action=tpshout;shout=admin">' . $txt['remove'] . '</a>) <br />'.TPageIndex($scripturl.'?action=tpshout;shout=admin;shoutbox_id='.urlencode($shoutbox_id) , $tpstart, $allshouts, 10,true);
		$request =  $smcFunc['db_query']('', '
			SELECT * FROM {db_prefix}tp_shoutbox
			WHERE type = {string:type}
			AND shoutbox_id = {string:val4}
			ORDER BY time DESC LIMIT {int:start}, 10',
			array('type' => 'shoutbox', 'val4' => $shoutbox_id, 'start' => $tpstart)
		);
	}
	elseif(isset($single)) {
		// check session
		checkSession('get');
		$context['TPortal']['shoutbox_pageindex'] = '';
		$request = $smcFunc['db_query']('', '
			SELECT * FROM {db_prefix}tp_shoutbox
			WHERE type = {string:type}
			AND id = {int:shout}',
			array('type' => 'shoutbox', 'shout' => $single)
		);
	}
	else {
		$shouts = $smcFunc['db_query']('', '
			SELECT COUNT(*) FROM {db_prefix}tp_shoutbox
			WHERE type = {string:type}',
			array('type' => 'shoutbox')
		);
		$weh = $smcFunc['db_fetch_row']($shouts);
		$smcFunc['db_free_result']($shouts);
		$allshouts = $weh[0];
		$context['TPortal']['admin_shoutbox_items_number'] = $allshouts;
		$context['TPortal']['shoutbox_pageindex'] = TPageIndex($scripturl.'?action=tpshout;shout=admin', $tpstart, $allshouts, 10,true);
		$request = $smcFunc['db_query']('', '
			SELECT * FROM {db_prefix}tp_shoutbox
			WHERE type = {string:type}
			ORDER BY time DESC LIMIT 10 OFFSET {int:start}',
			array('type' => 'shoutbox', 'start' => $tpstart)
		);
	}

	if($smcFunc['db_num_rows']($request) > 0) {
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$context['TPortal']['admin_shoutbox_items'][] = array(
				'id' => $row['id'],
				'body' => html_entity_decode($row['content'], ENT_QUOTES),
				'poster' => $row['member_link'],
				'timestamp' => $row['time'],
				'time' => timeformat($row['time']),
				'ip' => $row['member_ip'],
				'id_member' => $row['member_id'],
				'sort_member' => '<a href="'.$scripturl.'?action=tpshout;shout=admin;u='.$row['member_id'].'">'.$txt['tp-allshoutsbymember'].'</a>',
				'sort_ip' => '<a href="'.$scripturl.'?action=tpshout;shout=admin;ip='.$row['member_ip'].'">'.$txt['tp-allshoutsbyip'].'</a>',
				'sort_shoutbox_id' => '<a href="'.$scripturl.'?action=tpshout;shout=admin;shoutbox_id='.$row['shoutbox_id'].'">'.$txt['tp-allshoutsbyid'].'</a>',
				'single' => isset($single) ? '<hr><a href="'.$scripturl.'?action=tpshout;shout=admin"><b>'.$txt['tp-allshouts'].'</b></a>' : '',
				'shoutbox_id' => $row['shoutbox_id'],
			);
		}
		$smcFunc['db_free_result']($request);
	}

	$context['TPortal']['subtabs'] = '';
	// setup menu items
	if (allowedTo('tp_can_admin_shout')) {
		$context['TPortal']['subtabs'] = array(
			'shoutbox_settings' => array(
				'text' => 'tp-settings',
				'url' => $scripturl . '?action=tpshout;shout=admin;settings',
				'active' => (isset($_GET['action']) && ($_GET['action']=='tpshout' || $_GET['action']=='tpadmin' ) && isset($_GET['shout']) && $_GET['shout']=='admin' && isset($_GET['settings'])) ? true : false,
			),
			'shoutbox' => array(
				'text' => 'tp-shoutbox',
				'url' => $scripturl . '?action=tpshout;shout=admin',
				'active' => (isset($_GET['action']) && ($_GET['action']=='tpshout' || $_GET['action']=='tpadmin' ) && isset($_GET['shout']) && $_GET['shout']=='admin' && !isset($_GET['settings'])) ? true : false,
			),
		);
		$context['admin_header']['tp_shout'] = $txt['tp_shout'];
	}

	// on settings screen?
	if(isset($_GET['settings'])) {
		$context['sub_template'] = 'tpshout_admin_settings';
    }
	else {
		$context['sub_template'] = 'tpshout_admin';
    }

	$context['page_title'] = 'Shoutbox admin';

/*	tp_hidebars();*/
}}}

function TPShoutAdminAreas() {{{

    global $context, $scripturl;

	if (allowedTo('tp_can_admin_shout')) {
		$context['admin_tabs']['custom_modules']['tpshout'] = array(
			'title' => 'TPShout',
			'description' => '',
			'href' => $scripturl . '?action=tpshout;shout=admin',
			'is_selected' => isset($_GET['shout']),
		);
		$admin_set = true;
	}

}}}

?>
