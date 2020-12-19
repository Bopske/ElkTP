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
use \TinyPortal\Admin as TPAdmin;
use \TinyPortal\Article as TPArticle;
use \TinyPortal\Block as TPBlock;
use \TinyPortal\Util as TPUtil;


if (!defined('ELK')) {
	die('Hacking attempt...');
}

// TinyPortal admin
function TPortalAdmin()
{
	global $scripturl, $context, $txt;

	if(loadLanguage('TPortalAdmin') == false)
		loadLanguage('TPortalAdmin', 'english');
	if(loadLanguage('TPortal') == false)
		loadLanguage('TPortal', 'english');

	require_once(SOURCEDIR . '/TPSubs.php');
	require_once(SOURCEDIR . '/TPcommon.php');
	require_once(SUBSDIR . '/Post.subs.php');

	$context['TPortal']['frontpage_visualopts_admin'] = array(
		'left' => 0,
		'right' => 0,
		'center' => 0,
		'top' => 0,
		'bottom' => 0,
		'lower' => 0,
		'nolayer' => 0,
		'sort' => 'date',
		'sortorder' => 'desc'
	);

	$w = explode(',', $context['TPortal']['frontpage_visual']);

	if(in_array('left',$w))
		$context['TPortal']['frontpage_visualopts_admin']['left'] = 1;
	if(in_array('right',$w))
		$context['TPortal']['frontpage_visualopts_admin']['right'] = 1;
	if(in_array('center',$w))
		$context['TPortal']['frontpage_visualopts_admin']['center'] = 1;
	if(in_array('top',$w))
		$context['TPortal']['frontpage_visualopts_admin']['top'] = 1;
	if(in_array('bottom',$w))
		$context['TPortal']['frontpage_visualopts_admin']['bottom'] = 1;
	if(in_array('lower',$w))
		$context['TPortal']['frontpage_visualopts_admin']['lower'] = 1;
	if(in_array('nolayer',$w))
		$context['TPortal']['frontpage_visualopts_admin']['nolayer'] = 1;
	foreach($w as $r)
	{
		if(substr($r, 0, 5) == 'sort_')
			$context['TPortal']['frontpage_visualopts_admin']['sort'] = substr($r, 5);
		elseif(substr($r ,0, 10) == 'sortorder_')
			$context['TPortal']['frontpage_visualopts_admin']['sortorder'] = substr($r, 10);
	}

	TPadd_linktree($scripturl.'?action=tpadmin', $txt['tp-admin']);

	// some GET values set up
	$context['TPortal']['tpstart'] = isset($_GET['tpstart']) ? $_GET['tpstart'] : 0;

	// a switch to make it clear what is "forum" and not
	$context['TPortal']['not_forum'] = true;

	// get all member groups
	tp_groups();

	// get the layout schemes
	get_catlayouts();

	// get the categories
	get_catnames();

	if(isset($_GET['id'])) {
		$context['TPortal']['subaction_id'] = $_GET['id'];
    }

	// check POST values
	$return = do_postchecks();
 
	if(!empty($return)) {
		redirectexit('action=tpadmin;sa=' . $return);
    }
	
    $tpsub = '';

	$subAction  = TPUtil::filter('sa', 'get', 'string');
    $subActions = array();
   
    call_integration_hook('integrate_tp_pre_admin_subactions', array(&$subActions));

    $context['TPortal']['subaction'] = $subAction;
    // If it exists in our new subactions array load it
    if(!empty($subAction) && array_key_exists($subAction, $subActions)) {
        if (!empty($subActions[$subAction][0])) {
            require_once(SOURCEDIR . '/' . $subActions[$subAction][0]);
        }

        call_user_func_array($subActions[$subAction][1], $subActions[$subAction][2]);
    }
    elseif(isset($_GET['sa'])) {
		$context['TPortal']['subaction'] = $tpsub = $_GET['sa'];
		if(substr($_GET['sa'], 0, 11) == 'editarticle') {
			loadTemplate('TParticle');
            $context['sub_template'] = 'submitarticle';
            $tpsub = 'articles';
			$context['TPortal']['subaction'] = 'editarticle';
		}
		elseif(substr($_GET['sa'], 0, 11) == 'addarticle_') {
            loadTemplate('TParticle');
            $context['sub_template'] = 'submitarticle';
			$tpsub = 'articles';
			$context['TPortal']['subaction'] = $_GET['sa'];
            if($_GET['sa'] == 'addarticle_html') {
                TPwysiwyg_setup();
            }
		}
		do_subaction($tpsub);
	}
	elseif(isset($_GET['blktype']) || isset($_GET['addblock']) || isset($_GET['blockon']) || isset($_GET['blockoff']) || isset($_GET['blockleft']) || isset($_GET['blockright']) || isset($_GET['blockcenter']) || isset($_GET['blocktop']) || isset($_GET['blockbottom']) || isset($_GET['blockfront']) || isset($_GET['blocklower']) || isset($_GET['blockdelete']) || isset($_GET['addpos']) || isset($_GET['subpos'])) {
        if(allowedTo('tp_blocks')) {		
            $context['TPortal']['subaction'] = $tpsub = 'blocks';
		    do_blocks($tpsub);
        }
        else {
            fatal_error($txt['tp-noadmin'], false);
        }
	}
	elseif(isset($_GET['catdelete']) || isset($_GET['artfeat']) || isset($_GET['artfront']) || isset($_GET['artdelete']) || isset($_GET['arton']) || isset($_GET['artoff']) || isset($_GET['artsticky']) || isset($_GET['artlock']) || isset($_GET['catcollapse'])) {
        if(allowedTo('tp_articles')) {
		    $context['TPortal']['subaction'] = $tpsub = 'articles';
		    do_articles($tpsub);
        }    
        else {
            fatal_error($txt['tp-noadmin'], false);
        }
	}
    else {
		$context['TPortal']['subaction'] = $tpsub = 'overview';
		do_admin($tpsub);
	}

	// done with all POST values, go to the correct screen
	$context['TPortal']['subtabs'] = '';
    if(in_array($tpsub,array('articles', 'addarticle_php', 'addarticle_html', 'addarticle_bbc', 'addarticle_import', 'strays', 'submission')) && allowedTo('tp_articles')) {
        $context['TPortal']['subtabs'] = array(
				'articles' => array(
					'lang' => true,
					'text' => 'tp-articles',
					'url' => $scripturl . '?action=tpadmin;sa=articles',
					'active' => ($context['TPortal']['subaction'] == 'articles' || $context['TPortal']['subaction'] == 'editarticle') && $context['TPortal']['subaction'] != 'strays',
					),
				'articles_nocat' => array(
					'lang' => true,
					'text' => 'tp-uncategorised' ,
					'url' => $scripturl . '?action=tpadmin;sa=articles;sa=strays',
					'active' => $context['TPortal']['subaction'] == 'strays',
					),
				'submissions' => array(
					'lang' => true,
					'text' => 'tp-tabs4' ,
					'url' => $scripturl . '?action=tpadmin;sa=articles;sa=submission',
					'active' => $context['TPortal']['subaction'] == 'submission',
					),
				'addarticle' => array(
					'lang' => true,
					'text' => 'tp-tabs2',
					'url' => $scripturl . '?action=tpadmin;sa=addarticle_html' . (isset($_GET['cu']) ? ';cu='.$_GET['cu'] : ''),
					'active' => $context['TPortal']['subaction'] == 'addarticle_html',
					),
				'addarticle_php' => array(
					'lang' => true,
					'text' => 'tp-tabs3',
					'url' => $scripturl . '?action=tpadmin;sa=addarticle_php' . (isset($_GET['cu']) ? ';cu='.$_GET['cu'] : ''),
					'active' => $context['TPortal']['subaction'] == 'addarticle_php',
					),
				'addarticle_bbc' => array(
					'lang' => true,
					'text' => 'tp-addbbc',
					'url' => $scripturl . '?action=tpadmin;sa=addarticle_bbc' . (isset($_GET['cu']) ? ';cu='.$_GET['cu'] : ''),
					'active' => $context['TPortal']['subaction'] == 'addarticle_bbc',
					),
				'article_import' => array(
					'lang' => true,
					'text' => 'tp-addimport',
					'url' => $scripturl . '?action=tpadmin;sa=addarticle_import' . (isset($_GET['cu']) ? ';cu='.$_GET['cu'] : ''),
					'active' => $context['TPortal']['subaction'] == 'addarticle_import',
					),
				);
    }
    elseif(in_array($tpsub,array('addcategory','categories','clist')) && allowedTo('tp_articles')) {
        $context['TPortal']['subtabs'] = array(
                'categories' => array(
                    'lang' => true,
                    'text' => 'tp-tabs5',
                    'url' => $scripturl . '?action=tpadmin;sa=categories',
                    'active' => $tpsub == 'categories',
                    ),
                'addcategory' => array(
                    'lang' => true,
                    'text' => 'tp-tabs6',
                    'url' => $scripturl . '?action=tpadmin;sa=addcategory',
                    'active' => $tpsub == 'addcategory',
                    ),
                'clist' => array(
                    'lang' => true,
                    'text' => 'tp-tabs11',
                    'url' => $scripturl . '?action=tpadmin;sa=clist',
                    'active' => $tpsub == 'clist',
                    ),
                );
    }
    elseif(in_array($tpsub,array('blocks','panels','menubox','addmenu')) && allowedTo('tp_blocks')) {
        $context['TPortal']['subtabs'] = array(
                'panels' => array(
                    'lang' => true,
                    'text' => 'tp-panels',
                    'url' => $scripturl . '?action=tpadmin;sa=panels',
                    'active' => $tpsub == 'panels',
                ),
				'blocks' => array(
                    'lang' => true,
                    'text' => 'tp-blocks',
                    'url' => $scripturl . '?action=tpadmin;sa=blocks',
                    'active' => $tpsub == 'blocks' && !isset($_GET['overview']),
                ),
				'addblock' => array(
                    'lang' => true,
                    'text' => 'tp-addblock',
                    'url' => $scripturl . '?action=tpadmin;addblock=;' . $context['session_var'] . '=' . $context['session_id'].'',
                    'active' => $tpsub == 'addblock',
                ),
                'blockoverview' => array(
                    'lang' => true,
                    'text' => 'tp-blockoverview',
                    'url' => $scripturl . '?action=tpadmin;sa=blocks;overview',
                    'active' => $tpsub == 'blocks' && isset($_GET['overview']),
                ),
            );
    }

    if(!in_array('tpadm', Template_Layers::getInstance()->getLayers())) {
        Template_Layers::getInstance()->add('tpadm');
        Template_Layers::getInstance()->add('subtab');
    }

	\loadTemplate('TPortalAdmin');
	\loadTemplate('TPsubs');
	TPadminIndex($tpsub);

    call_integration_hook('integrate_tp_post_admin_subactions');
}

/* ******************************************************************************************************************** */
function do_subaction($tpsub) {
    global $context, $txt;

	if(in_array($tpsub, array('articles', 'strays', 'categories', 'addcategory', 'submission', 'artsettings', 'articons', 'clist')) && (allowedTo(array('tp_articles', 'tp_editownarticle'))) )  {
		do_articles();
    }
	elseif(in_array($tpsub, array('blocks', 'panels')) && (allowedTo('tp_blocks')) ) {
		do_blocks();
	}
    elseif(in_array($tpsub, array('frontpage', 'overview', 'credits', 'permissions')) && (allowedTo('tp_settings')) ) {
		do_admin($tpsub);
	}
    elseif($tpsub == 'settings' && (allowedTo('tp_settings')) ) {
		do_admin('settings');
    }
    elseif(!$context['user']['is_admin']) {
		fatal_error($txt['tp-noadmin'], false);
    }
    else {
		redirectexit('action=tpadmin');
    }

}

function do_blocks() {
    require_once( SOURCEDIR . '/TPBlock.php' );
    adminBlocks();
}

// articles
function do_articles()
{
	global $context, $txt, $settings, $boardurl, $scripturl;

    $db = database();

    if(allowedTo('tp_articles') == false) {
        if(isset($_GET['sa']) && substr($_GET['sa'], 0, 11) == 'editarticle') {
		    $article = TPUtil::filter('article', 'get', 'string');
        	$request = $db->query('', '
		        SELECT id FROM {db_prefix}tp_articles
		        WHERE id = {int:article_id}
                AND author_id = {int:member_id}',
		        array(  
                    'article_id'    => $article,
                    'member_id'     => $context['user']['id']
                )
	        );
	        if($db->num_rows($request) == 0) {           
                fatal_error($txt['tp-noadmin'], false);
            }
            $db->free_result($request);
        }
        else {
            fatal_error($txt['tp-noadmin'], false);
        }
    }

	// do an update of stray articles and categories
	$acats = array();
	$request = $db->query('', '
		SELECT id FROM {db_prefix}tp_variables
		WHERE type = {string:type}',
		array('type' => 'category')
	);
	if($db->num_rows($request) > 0)
	{
		while($row = $db->fetch_assoc($request))
			$acats[] = $row['id'];
		$db->free_result($request);
	}
	if(count($acats) > 0)
	{
		$db->query('', '
			UPDATE {db_prefix}tp_variables
			SET value2 = {int:val2}
			WHERE type = {string:type}
			AND value2 NOT IN ({array_string:value2})',
			array('val2' => 0, 'type' => 'category', 'value2' => $acats)
		);
		$db->query('', '
			UPDATE {db_prefix}tp_articles
			SET category = {int:cat}
			WHERE category NOT IN({array_int:category})
			AND category > 0',
			array('cat' => 0, 'category' => $acats)
		);
	}

    require_once(SOURCEDIR.'/TPArticle.php');
    articleAjax();

	// for the non-category articles, do a count.
	$request = $db->query('', '
		SELECT COUNT(*) as total
		FROM {db_prefix}tp_articles
		WHERE category = 0 OR category = 9999'
	);

	$row = $db->fetch_assoc($request);
	$context['TPortal']['total_nocategory'] = $row['total'];
	$db->free_result($request);

	// for the submissions too
	$request = $db->query('', '
		SELECT COUNT(*) as total
		FROM {db_prefix}tp_articles
		WHERE approved = 0'
	);

	$row = $db->fetch_assoc($request);
	$context['TPortal']['total_submissions'] = $row['total'];
	$db->free_result($request);

	// we are on categories screen
	if(in_array($context['TPortal']['subaction'], array('categories', 'addcategory', 'clist'))) {
		TPadd_linktree($scripturl.'?action=tpadmin;sa=categories', $txt['tp-categories']);
		// first check if we simply want to copy or set as child
		if(isset($_GET['cu']) && is_numeric($_GET['cu'])) {
			$ccat = $_GET['cu'];
			if(isset($_GET['copy'])) {
				$request = $db->query('', '
					SELECT * FROM {db_prefix}tp_variables
					WHERE id = {int:varid}',
					array('varid' => $ccat)
				);
				if($db->num_rows($request) > 0) {
					$row = $db->fetch_assoc($request);
					$row['value1'] .= '__copy';
					$db->free_result($request);
					$db->insert('insert',
						'{db_prefix}tp_variables',
						array(
							'value1' => 'string',
							'value2' => 'string',
							'value3' => 'string',
							'type' => 'string',
							'value4' => 'string',
							'value5' => 'int',
							'subtype' => 'string',
							'value7' => 'string',
							'value8' => 'string',
							'subtype2'=> 'int'
						),
						array(
							$row['value1'],
							$row['value2'],
							$row['value3'],
							$row['type'],
							$row['value4'],
							$row['value5'],
							$row['subtype'],
							$row['value7'],
							$row['value8'],
							$row['subtype2']
						),
						array('id')
					);
				}
				redirectexit('action=tpadmin;sa=categories');
			}
			elseif(isset($_GET['child'])) {
				$request = $db->query('', '
					SELECT * FROM {db_prefix}tp_variables
					WHERE id = {int:varid}',
					array('varid' => $ccat)
				);
				if($db->num_rows($request) > 0) {
					$row = $db->fetch_assoc($request);
					$row['value1'] .= '__copy';
					$db->free_result($request);
					$db->insert('INSERT',
						'{db_prefix}tp_variables',
						array(
							'value1' => 'string',
							'value2' => 'string',
							'value3' => 'string',
							'type' => 'string',
							'value4' => 'string',
							'value5' => 'int',
							'subtype' => 'string',
							'value7' => 'string',
							'value8' => 'string',
							'subtype2'=> 'int'
						),
						array(
							$row['value1'],
							$row['id'],
							$row['value3'],
							$row['type'],
							$row['value4'],
							$row['value5'],
							$row['subtype'],
							$row['value7'],
							$row['value8'],
							$row['subtype2']
						),
						array('id')
					);
				}
				redirectexit('action=tpadmin;sa=categories');
			}
			// guess we only want the category then
			else {
				// get membergroups
				get_grps();
			$context['html_headers'] .= '
			<script type="text/javascript"><!-- // --><![CDATA[
				function changeIllu(node,name)
				{
					node.src = \'' . $boardurl . '/tp-files/tp-articles/illustrations/\' + name;
				}
			// ]]></script>';

				$request = $db->query('', '
					SELECT * FROM {db_prefix}tp_variables
					WHERE id = {int:varid} LIMIT 1',
					array('varid' => $ccat)
				);
				if($db->num_rows($request) > 0) {
					$row = $db->fetch_assoc($request);
					$o = explode('|', $row['value7']);
					foreach($o as $t => $opt) {
						$b = explode('=', $opt);
						if(isset($b[1])) {
							$row[$b[0]] = $b[1];
                        }
					}
					$db->free_result($request);
					$check = array('layout', 'catlayout', 'toppanel', 'bottompanel', 'leftpanel', 'rightpanel', 'upperpanel', 'lowerpanel', 'showchild');
					foreach($check as $c => $ch) {
						if(!isset($row[$ch])) {
							$row[$ch] = 0;
                        }
					}
					$context['TPortal']['editcategory'] = $row;
				}
				// fetch all categories and subcategories
				$request = $db->query('', '
					SELECT	id, value1 as name, value2 as parent, value3, value4,
						value5, subtype, value7, value8, subtype2
					FROM {db_prefix}tp_variables
					WHERE type = {string:type}',
					array('type' => 'category')
				);

				$context['TPortal']['editcats'] = array();
				$allsorted = array();
				$alcats = array();
				if($db->num_rows($request) > 0) {
					while ($row = $db->fetch_assoc($request)) {
						$row['indent'] = 0;
						$allsorted[$row['id']] = $row;
						$alcats[] = $row['id'];
					}
					$db->free_result($request);
					if(count($allsorted) > 1) {
						$context['TPortal']['editcats'] = chain('id', 'parent', 'name', $allsorted);
					}
                    else {
						$context['TPortal']['editcats'] = $allsorted;
                    }
				}
				TPadd_linktree($scripturl.'?action=tpadmin;sa=categories;cu='. $ccat, $txt['tp-editcategory']);
			}
			return;
		}

		// fetch all categories and subcategories
		$request = $db->query('', '
			SELECT id, value1 as name, value2 as parent, value3, value4,
				value5, subtype, value7, value8, subtype2
			FROM {db_prefix}tp_variables
			WHERE type = {string:type}',
			array('type' => 'category')
		);

		$context['TPortal']['editcats'] = array();
		$allsorted = array();
		$alcats = array();
		if($db->num_rows($request) > 0) {
			while ($row = $db->fetch_assoc($request)) {
				$row['indent'] = 0;
				$allsorted[$row['id']] = $row;
				$alcats[] = $row['id'];
			}
			$db->free_result($request);
			if(count($allsorted) > 1) {
				$context['TPortal']['editcats'] = chain('id', 'parent', 'name', $allsorted);
            }
			else {
				$context['TPortal']['editcats'] = $allsorted;
            }
		}
		// get the filecount as well
		if(count($alcats) > 0) {
			$request = $db->query('', '
				SELECT	art.category as id, COUNT(art.id) as files
				FROM {db_prefix}tp_articles as art
				WHERE art.category IN ({array_int:cats})
				GROUP BY art.category',
				array('cats' => $alcats)
			);

			if($db->num_rows($request) > 0) {
				$context['TPortal']['cats_count'] = array();
				while ($row = $db->fetch_assoc($request)) {
					$context['TPortal']['cats_count'][$row['id']] = $row['files'];
                }
				$db->free_result($request);
			}
		}
		if($context['TPortal']['subaction'] == 'addcategory') {
			TPadd_linktree($scripturl.'?action=tpadmin;sa=addcategory', $txt['tp-addcategory']);
        }
		if($context['TPortal']['subaction'] == 'clist') {
			TPadd_linktree($scripturl.'?action=tpadmin;sa=clist', $txt['tp-tabs11']);
        }

		return;
	}
	TPadd_linktree($scripturl.'?action=tpadmin;sa=articles', $txt['tp-articles']);
	// are we inside a category?
	if(isset($_GET['cu']) && is_numeric($_GET['cu'])) {
		$where = $_GET['cu'];
	}
	// show the no category articles?
	if(isset($_GET['sa']) && $_GET['sa'] == 'strays') {
		TPadd_linktree($scripturl.'?action=tpadmin;sa=strays', $txt['tp-strays']);
		$show_nocategory = true;
	}

	// submissions?
	if(isset($_GET['sa']) && $_GET['sa'] == 'submission') {
		TPadd_linktree($scripturl.'?action=tpadmin;sa=submission', $txt['tp-submissions']);
		$show_submission = true;
	}

	// single article?
	if(isset($_GET['sa']) && substr($_GET['sa'], 0, 11) == 'editarticle') {
		$whatarticle = TPUtil::filter('article', 'get', 'string');
		TPadd_linktree($scripturl.'?action=tpadmin;sa='.$_GET['sa'].';article='.$whatarticle, $txt['tp-editarticle']);
	}
	// are we starting a new one?
	if(isset($_GET['sa']) && substr($_GET['sa'], 0, 11) == 'addarticle_') {
		TPadd_linktree($scripturl.'?action=tpadmin;sa='.$_GET['sa'], $txt['tp-addarticle']);
		$context['TPortal']['editarticle'] = array(
            'id' => '',
            'date' => time(),
            'body' => '',
            'intro' => '',
            'useintro' => 0,
            'category' => !empty($_GET['cu']) ? $_GET['cu'] : 0,
            'frontpage' => 1,
            'author_id' => $context['user']['id'],
            'subject' => '',
            'author' => $context['user']['name'],
            'frame' => 'theme',
            'approved' => 0,
            'off' => 1,
            'options' => 'date,title,author,linktree,top,cblock,rblock,lblock,bblock,tblock,lbblock,category,catlist,comments,commentallow,commentupshrink,views,rating,ratingallow,avatar,inherit,social,nofrontsetting',
            'parse' => 0,
            'comments' => 0,
            'comments_var' => '',
            'views' => 0,
            'rating' => 0,
            'voters' => '',
            'id_theme' => 0,
            'shortname' => '',
            'sticky' => 0,
            'fileimport' => '',
            'topic' => 0,
            'locked' => 0,
            'illustration' => '',
            'headers' => '',
            'type' => substr($_GET['sa'],11),
            'featured' => 0,
            'real_name' => $context['user']['name'],
            'author_id' => $context['user']['id'],
            'articletype' => substr($_GET['sa'],11),
            'id_theme' => 0,
			'pub_start' => 0,
			'pub_end' => 0,
        );
		$context['html_headers'] .= '
			<script type="text/javascript"><!-- // --><![CDATA[
				function changeIllu(node,name)
				{
					node.src = \'' . $boardurl . '/tp-files/tp-articles/illustrations/\' + name;
				}
			// ]]></script>';
		// Add in BBC editor before we call in template so the headers are there
		if(substr($_GET['sa'], 11) == 'bbc') {
			$context['TPortal']['editor_id'] = 'tp_article_body';
			TP_prebbcbox($context['TPortal']['editor_id']);
		}
	}

	// fetch categories and subcategories
	if(!isset($show_nocategory)) {
		$request = $db->query('', '
			SELECT DISTINCT var.id AS id, var.value1 AS name, var.value2 AS parent
			FROM {db_prefix}tp_variables AS var
			WHERE var.type = {string:type} 
			' . (isset($where) ? 'AND var.value2'.((TP_PGSQL == true) ? '::Integer' : ' ' ).' = {int:whereval}' : '') . '
			ORDER BY parent, id DESC',
			array('type' => 'category', 'whereval' => isset($where) ? $where : 0)
		);

		if($db->num_rows($request) > 0) {
			$context['TPortal']['basecats'] = isset($where) ? array($where) : array('0', '9999');
			$cats = array();
			$context['TPortal']['cats'] = array();
			$sorted = array();
			while ($row = $db->fetch_assoc($request)) {
				$sorted[$row['id']] = $row;
				$cats[] = $row['id'];
			}
			$db->free_result($request);
			if(count($sorted) > 1) {
				$context['TPortal']['cats'] = chain('id', 'parent', 'name', $sorted);
            }
			else {
				$context['TPortal']['cats'] = $sorted;
            }
		}
	}

	if(isset($show_submission) && $context['TPortal']['total_submissions'] > 0) {
		// check if we have any start values
		$start = (!empty($_GET['p']) && is_numeric($_GET['p'])) ? $_GET['p'] : 0;
		// sorting?
		$sort = $context['TPortal']['sort'] = (!empty($_GET['sort']) && in_array($_GET['sort'], array('date', 'id','author_id', 'type', 'subject', 'parse'))) ? $_GET['sort'] : 'date';
		$context['TPortal']['pageindex'] = TPageIndex($scripturl . '?action=tpadmin;sa=submission;sort=' . $sort , $start, $context['TPortal']['total_submissions'], 15);
		$request = $db->query('', '
			SELECT	art.id, art.date, art.frontpage, art.category, art.author_id as author_id,
				COALESCE(mem.real_name, art.author) as author, art.subject, art.approved,
				art.sticky, art.type, art.featured, art.locked, art.off, art.parse as pos
			FROM {db_prefix}tp_articles AS art
			LEFT JOIN {db_prefix}members AS mem ON (art.author_id = mem.id_member)
			WHERE art.approved = {int:approved}
			ORDER BY art.{raw:col} {raw:sort}
			LIMIT {int:start}, 15',
			array(
				'approved' => 0,
				'col' => $sort,
				'start' => $start,
				'sort' => in_array($sort, array('sticky', 'locked', 'frontpage', 'date', 'active')) ? 'DESC' : 'ASC',
			)
		);

		if($db->num_rows($request) > 0) {
			$context['TPortal']['arts_submissions']=array();
			while ($row = $db->fetch_assoc($request)) {
				$context['TPortal']['arts_submissions'][] = $row;
			}
			$db->free_result($request);
		}
	}

	if(isset($show_nocategory) && $context['TPortal']['total_nocategory'] > 0) {
		// check if we have any start values
		$start = (!empty($_GET['p']) && is_numeric($_GET['p'])) ? $_GET['p'] : 0;
		// sorting?
		$sort = $context['TPortal']['sort'] = (!empty($_GET['sort']) && in_array($_GET['sort'], array('off', 'date', 'id', 'author_id', 'locked', 'frontpage', 'sticky', 'featured', 'type', 'subject', 'parse'))) ? $_GET['sort'] : 'date';
		$context['TPortal']['pageindex'] = TPageIndex($scripturl . '?action=tpadmin;sa=articles;sort=' . $sort , $start, $context['TPortal']['total_nocategory'], 15);
		$request = $db->query('', '
			SELECT	art.id, art.date, art.frontpage, art.category, art.author_id as author_id,
				COALESCE(mem.real_name, art.author) as author, art.subject, art.approved, art.sticky,
				art.type, art.featured,art.locked, art.off, art.parse as pos
			FROM {db_prefix}tp_articles AS art
			LEFT JOIN {db_prefix}members AS mem ON (art.author_id = mem.id_member)
			WHERE (art.category = 0 OR art.category = 9999)
			ORDER BY art.{raw:col} {raw:sort}
			LIMIT {int:start}, 15',
			array(
				'col' => $sort,
				'sort' => in_array($sort, array('sticky', 'locked', 'frontpage', 'date', 'active')) ? 'DESC' : 'ASC',
				'start' => $start,
			)
		);

		if($db->num_rows($request) > 0) {
			$context['TPortal']['arts_nocat'] = array();
			while ($row = $db->fetch_assoc($request)) {
				$context['TPortal']['arts_nocat'][] = $row;
			}
			$db->free_result($request);
		}
	}
	// ok, fetch single article
	if(isset($whatarticle)) {
		$request = $db->query('', '
			SELECT	art.*,  COALESCE(mem.real_name, art.author) AS real_name, art.author_id AS author_id,
				art.type as articletype, art.id_theme as id_theme
			FROM {db_prefix}tp_articles as art
			LEFT JOIN {db_prefix}members as mem ON (art.author_id = mem.id_member)
			WHERE art.id = {int:artid}',
			array(
				'artid' => is_numeric($whatarticle) ? $whatarticle : 0,
			)
		);

		if($db->num_rows($request) > 0) {
			$context['TPortal']['editarticle'] = $db->fetch_assoc($request);
			$context['TPortal']['editing_article'] = true;
			$context['TPortal']['editarticle']['body'] = TPUtil::htmlspecialchars($context['TPortal']['editarticle']['body'], ENT_QUOTES);
			$db->free_result($request);
		}

        if($context['TPortal']['editarticle']['articletype'] == 'html') {
            TPwysiwyg_setup();
        }

		// Add in BBC editor before we call in template so the headers are there
		if($context['TPortal']['editarticle']['articletype'] == 'bbc') {
			$context['TPortal']['editor_id'] = 'tp_article_body';
			TP_prebbcbox($context['TPortal']['editor_id'], strip_tags($context['TPortal']['editarticle']['body']));
		}

		// fetch the WYSIWYG value
		$request = $db->query('', '
			SELECT value1 FROM {db_prefix}tp_variables
			WHERE subtype2 = {int:subtype}
			AND type = {string:type} LIMIT 1',
			array(
				'subtype' => is_numeric($whatarticle) ? $whatarticle : 0, 'type' => 'editorchoice',
			)
		);
		if($db->num_rows($request) > 0) {
			$row = $db->fetch_assoc($request);
			$db->free_result($request);
			$context['TPortal']['editorchoice'] = $row['value1'];
		}
		else {
			$context['TPortal']['editorchoice'] = 1;
        }

		$context['html_headers'] .= '
			<script type="text/javascript"><!-- // --><![CDATA[
				function changeIllu(node,name)
				{
					node.src = \'' . $boardurl . '/tp-files/tp-articles/illustrations/\' + name;
				}
			// ]]></script>';

	}
	// fetch article count for these
	if(isset($cats)) {
		$request = $db->query('', '
			SELECT	art.category as id, COUNT(art.id) as files
			FROM {db_prefix}tp_articles as art
			WHERE art.category IN ({array_int:cat})
			GROUP BY art.category',
			array('cat' => $cats)
		);

		$context['TPortal']['cats_count'] = array();
		if($db->num_rows($request) > 0) {
			while ($row = $db->fetch_assoc($request))
				$context['TPortal']['cats_count'][$row['id']] = $row['files'];
			$db->free_result($request);
		}
	}
	// get the icons needed
	tp_collectArticleIcons();

	// fetch all categories and subcategories
	$request = $db->query('', '
		SELECT	id, value1 as name, value2 as parent
		FROM {db_prefix}tp_variables
		WHERE type = {string:type}',
		array('type' => 'category')
	);

	$context['TPortal']['allcats'] = array();
	$allsorted = array();

	if($db->num_rows($request) > 0) {
		while ($row = $db->fetch_assoc($request)) {
			$allsorted[$row['id']] = $row;
        }

		$db->free_result($request);
		if(count($allsorted) > 1) {
			$context['TPortal']['allcats'] = chain('id', 'parent', 'name', $allsorted);
        }
		else {
			$context['TPortal']['allcats'] = $allsorted;
        }
	}
	// not quite done yet lol, now we need to sort out if articles are to be listed
	if(isset($where)) {
		// check if we have any start values
		$start = (!empty($_GET['p']) && is_numeric($_GET['p'])) ? $_GET['p'] : 0;
		// sorting?
		$sort = $context['TPortal']['sort'] = (!empty($_GET['sort']) && in_array($_GET['sort'], array('off', 'date', 'id', 'author_id' , 'locked', 'frontpage', 'sticky', 'featured', 'type', 'subject', 'parse'))) ? $_GET['sort'] : 'date';
		$context['TPortal']['categoryID'] = $where;
		// get the name
		$request = $db->query('', '
			SELECT value1
			FROM {db_prefix}tp_variables
			WHERE id = {int:varid} LIMIT 1',
			array(
				'varid' => $where
			)
		);
		$f = $db->fetch_assoc($request);
		$db->free_result($request);
		$context['TPortal']['categoryNAME'] = $f['value1'];
		// get the total first
		$request = $db->query('', '
			SELECT	COUNT(*) as total
			FROM {db_prefix}tp_articles
			WHERE category = {int:cat}',
			array(
				'cat' => $where
			)
		);

		$row = $db->fetch_assoc($request);
		$context['TPortal']['pageindex'] = TPageIndex($scripturl . '?action=tpadmin;sa=articles;sort=' . $sort . ';cu=' . $where, $start, $row['total'], 15);
		$db->free_result($request);

		$request = $db->query('', '
			SELECT art.id, art.date, art.frontpage, art.category, art.author_id AS author_id,
				COALESCE(mem.real_name, art.author) AS author, art.subject, art.approved, art.sticky,
				art.type, art.featured, art.locked, art.off, art.parse AS pos
			FROM {db_prefix}tp_articles AS art
			LEFT JOIN {db_prefix}members AS mem ON (art.author_id = mem.id_member)
			WHERE art.category = {int:cat}
			ORDER BY art.{raw:sort} {raw:sorter}
			LIMIT 15 OFFSET {int:start}',
			array('cat' => $where,
				'sort' => $sort,
				'sorter' => in_array($sort, array('sticky', 'locked', 'frontpage', 'date', 'active')) ? 'DESC' : 'ASC',
				'start' => $start
			)
		);
		TPadd_linktree($scripturl.'?action=tpadmin;sa=articles;cu='.$where, $txt['tp-blocktype19']);

		if($db->num_rows($request) > 0) {
			$context['TPortal']['arts']=array();
			while ($row = $db->fetch_assoc($request)) {
				$context['TPortal']['arts'][] = $row;
			}
			$db->free_result($request);
		}
	}

    // get all themes for selection
    $context['TPthemes'] = array();
    $request = $db->query('', '
            SELECT th.value AS name, th.id_theme as id_theme, tb.value AS path
            FROM {db_prefix}themes AS th
            LEFT JOIN {db_prefix}themes AS tb ON th.id_theme = tb.id_theme
            WHERE th.variable = {string:thvar}
            AND tb.variable = {string:tbvar}
            AND th.id_member = {int:id_member}
            ORDER BY th.value ASC',
            array(
                'thvar' => 'name', 'tbvar' => 'images_url', 'id_member' => 0,
                )
            );
    if($db->num_rows($request) > 0) {
        while ($row = $db->fetch_assoc($request)) {
            $context['TPthemes'][] = array(
                    'id' => $row['id_theme'],
                    'path' => $row['path'],
                    'name' => $row['name']
                    );
        }
        $db->free_result($request);
    }

	$context['html_headers'] .= '
	<script type="text/javascript" src="'. $settings['default_theme_url']. '/scripts/editor.js?rc1"></script>
	<script type="text/javascript"><!-- // --><![CDATA[
		function getXMLHttpRequest()
		{
			if (window.XMLHttpRequest)
				return new XMLHttpRequest;
			else if (window.ActiveXObject)
				return new ActiveXObject("MICROSOFT.XMLHTTP");
			else
				alert("Sorry, but your browser does not support Ajax");
		}

		window.onload = startToggle;

		function startToggle()
		{
			var img = document.getElementsByTagName("img");

			for(var i = 0; i < img.length; i++)
			{
				if (img[i].className == "toggleFront")
					img[i].onclick = toggleFront;
				else if (img[i].className == "toggleSticky")
					img[i].onclick = toggleSticky;
				else if (img[i].className == "toggleLock")
					img[i].onclick = toggleLock;
				else if (img[i].className == "toggleActive")
					img[i].onclick = toggleActive;
				else if (img[i].className == "toggleFeatured")
					img[i].onclick = toggleFeatured;
			}
		}

		function toggleActive(e)
		{
			var e = e ? e : window.event;
			var target = e.target ? e.target : e.srcElement;

			while(target.className != "toggleActive")
				  target = target.parentNode;

			var id = target.id.replace("artActive", "");
			var Ajax = getXMLHttpRequest();

			Ajax.open("POST", "?action=tpadmin;arton=" + id + ";' . $context['session_var'] . '=' . $context['session_id'].'");
			Ajax.setRequestHeader("Content-type", "application/x-www-form-urlencode");

			var source = target.src;
			target.src = "' . $settings['tp_images_url'] . '/ajax.gif"

			Ajax.onreadystatechange = function()
			{
				if(Ajax.readyState == 4)
				{
					target.src = source == "' . $settings['tp_images_url'] . '/TPactive2.png" ? "' . $settings['tp_images_url'] . '/TPactive1.png" : "' . $settings['tp_images_url'] . '/TPactive2.png";
				}
			}

			var params = "?action=tpadmin;arton=" + id + ";' . $context['session_var'] . '=' . $context['session_id'].'";
			Ajax.send(params);
		}
		function toggleFront(e)
		{
			var e = e ? e : window.event;
			var target = e.target ? e.target : e.srcElement;

			while(target.className != "toggleFront")
				  target = target.parentNode;

			var id = target.id.replace("artFront", "");
			var Ajax = getXMLHttpRequest();

			Ajax.open("POST", "?action=tpadmin;artfront=" + id + ";' . $context['session_var'] . '=' . $context['session_id'].'");
			Ajax.setRequestHeader("Content-type", "application/x-www-form-urlencode");

			var source = target.src;
			target.src = "' . $settings['tp_images_url'] . '/ajax.gif"

			Ajax.onreadystatechange = function()
			{
				if(Ajax.readyState == 4)
				{
					target.src = source == "' . $settings['tp_images_url'] . '/TPfront.png" ? "' . $settings['tp_images_url'] . '/TPfront2.png" : "' . $settings['tp_images_url'] . '/TPfront.png";
				}
			}

			var params = "?action=tpadmin;artfront=" + id + ";' . $context['session_var'] . '=' . $context['session_id'].'";
			Ajax.send(params);
		}
		function toggleSticky(e)
		{
			var e = e ? e : window.event;
			var target = e.target ? e.target : e.srcElement;

			while(target.className != "toggleSticky")
				  target = target.parentNode;

			var id = target.id.replace("artSticky", "");
			var Ajax = getXMLHttpRequest();

			Ajax.open("POST", "?action=tpadmin;artsticky=" + id + ";' . $context['session_var'] . '=' . $context['session_id'].'");
			Ajax.setRequestHeader("Content-type", "application/x-www-form-urlencode");

			var source = target.src;
			target.src = "' . $settings['tp_images_url'] . '/ajax.gif"

			Ajax.onreadystatechange = function()
			{
				if(Ajax.readyState == 4)
				{
					target.src = source == "' . $settings['tp_images_url'] . '/TPsticky1.png" ? "' . $settings['tp_images_url'] . '/TPsticky2.png" : "' . $settings['tp_images_url'] . '/TPsticky1.png";
				}
			}

			var params = "?action=tpadmin;artsticky=" + id + ";' . $context['session_var'] . '=' . $context['session_id'].'";
			Ajax.send(params);
		}
		function toggleLock(e)
		{
			var e = e ? e : window.event;
			var target = e.target ? e.target : e.srcElement;

			while(target.className != "toggleLock")
				  target = target.parentNode;

			var id = target.id.replace("artLock", "");
			var Ajax = getXMLHttpRequest();

			Ajax.open("POST", "?action=tpadmin;artlock=" + id + ";' . $context['session_var'] . '=' . $context['session_id'].'");
			Ajax.setRequestHeader("Content-type", "application/x-www-form-urlencode");

			var source = target.src;
			target.src = "' . $settings['tp_images_url'] . '/ajax.gif"

			Ajax.onreadystatechange = function()
			{
				if(Ajax.readyState == 4)
				{
					target.src = source == "' . $settings['tp_images_url'] . '/TPlock1.png" ? "' . $settings['tp_images_url'] . '/TPlock2.png" : "' . $settings['tp_images_url'] . '/TPlock1.png";
				}
			}

			var params = "?action=tpadmin;artlock=" + id + ";' . $context['session_var'] . '=' . $context['session_id'].'";
			Ajax.send(params);
		}
		function toggleFeatured(e)
		{
			var e = e ? e : window.event;
			var target = e.target ? e.target : e.srcElement;

			var aP=document.getElementsByTagName(\'img\');
			for(var i=0; i<aP.length; i++)
			{
				if(aP[i].className===\'toggleFeatured\' && aP[i] != target)
				{
					aP[i].src=\'' . $settings['tp_images_url'] . '/TPflag2.png\';
				}
			}


			while(target.className != "toggleFeatured")
				  target = target.parentNode;

			var id = target.id.replace("artFeatured", "");
			var Ajax = getXMLHttpRequest();

			Ajax.open("POST", "?action=tpadmin;artfeat=" + id + ";' . $context['session_var'] . '=' . $context['session_id'].'");
			Ajax.setRequestHeader("Content-type", "application/x-www-form-urlencode");

			var source = target.src;
			target.src = "' . $settings['tp_images_url'] . '/ajax.gif"

			Ajax.onreadystatechange = function()
			{
				if(Ajax.readyState == 4)
				{
					target.src = source == "' . $settings['tp_images_url'] . '/TPflag.png" ? "' . $settings['tp_images_url'] . '/TPflag2.png" : "' . $settings['tp_images_url'] . '/TPflag.png";
				}
			}

			var params = "?action=tpadmin;artfeat=" + id + ";' . $context['session_var'] . '=' . $context['session_id'].'";
			Ajax.send(params);
		}
	// ]]></script>';

	if($context['TPortal']['subaction'] == 'artsettings') {
		TPadd_linktree($scripturl.'?action=tpadmin;sa=artsettings', $txt['tp-settings']);
    }
	elseif($context['TPortal']['subaction'] == 'articons') {
		TPadd_linktree($scripturl.'?action=tpadmin;sa=articons', $txt['tp-adminicons']);
    }

}

function do_admin($tpsub = 'overview')
{
	global $context, $txt, $scripturl;

	get_boards();
	$context['TPortal']['SSI_boards'] = explode(',', $context['TPortal']['SSI_board']);

	if($tpsub == 'overview')
	{
		if(!TPcheckAdminAreas())
			fatal_error($txt['tp-noadmin'], false);
	}
	elseif($tpsub == 'permissions')
	{
		TPadd_linktree($scripturl.'?action=tpadmin;sa=permissions', $txt['tp-permissions']);
		$context['TPortal']['perm_all_groups'] = get_grps(true, true);
		$context['TPortal']['perm_groups'] = tp_fetchpermissions($context['TPortal']['modulepermissions']);
	}
	else
	{
		if($tpsub == 'settings')
			TPadd_linktree($scripturl.'?action=tpadmin;sa=settings', $txt['tp-settings']);
		elseif($tpsub == 'frontpage')
			TPadd_linktree($scripturl.'?action=tpadmin;sa=frontpage', $txt['tp-frontpage']);

		isAllowedTo('tp_settings');
	}
}

function do_postchecks()
{
	global $context, $txt, $settings, $boarddir, $sourcedir;

    $db = database();

	// If we have any setting changes add them to this array
	$updateArray = array();
	if($context['TPortal']['action'] && (isset($_GET['sa']) && $_GET['sa'] == 'settings')) {
		    // get all the themes
            $context['TPallthem'] = array();
			$request = $db->query('', '
				SELECT th.value AS name, th.id_theme as id_theme, tb.value AS path
				FROM {db_prefix}themes AS th
				LEFT JOIN {db_prefix}themes AS tb ON th.id_theme = tb.id_theme
				WHERE th.variable = {string:thvar}
				AND tb.variable = {string:tbvar}
				AND th.id_member = {int:id_member}
				ORDER BY th.value ASC',
				array(
					'thvar' => 'name', 'tbvar' => 'images_url', 'id_member' => 0,
				)
			);
			if($db->num_rows($request) > 0)
			{
				while ($row = $db->fetch_assoc($request))
				{
					$context['TPallthem'][] = array(
						'id' => $row['id_theme'],
						'path' => $row['path'],
						'name' => $row['name']
					);
				}
				$db->free_result($request);
			}
	}
	// which screen do we come from?
	if(!empty($_POST['tpadmin_form']))
	{
		// get it
		$from = $_POST['tpadmin_form'];
		// block permissions overview
		if($from == 'blockoverview')
		{
			checkSession('post');
			isAllowedTo('tp_blocks');

			$block = array();
			foreach($_POST as $what => $value)
			{
				if(substr($what, 5, 7) == 'tpblock')
				{
					// get the id
					$bid = substr($what, 12);
					if(!isset($block[$bid]))
						$block[$bid] = array();

					if($value != 'control' && !in_array($value, $block[$bid]))
						$block[$bid][] = $value;
				}
			}
			foreach($block as $bl => $blo)
			{
				$request = $db->query('', '
					SELECT access FROM {db_prefix}tp_blocks
					WHERE id = {int:blockid}',
					array('blockid' => $bl)
				);
				if($db->num_rows($request) > 0)
				{
					$row = $db->fetch_assoc($request);
					$db->free_result($request);
					$request = $db->query('', '
						UPDATE {db_prefix}tp_blocks
						SET access = {string:access} WHERE id = {int:blockid}',
						array(
							'access' => implode(',', $blo),
							'blockid' => $bl,
						)
					);
				}
			}
			return 'blocks;overview';
		}
		elseif(in_array($from, array('settings', 'frontpage', 'artsettings', 'panels')))
		{
			checkSession('post');
			isAllowedTo('tp_settings');
			$w = array();
			$ssi = array();

            switch($from) {
                case 'settings':
                    $checkboxes = array('imageproxycheck', 'admin_showblocks', 'oldsidebar', 'disable_template_eval', 'fulltextsearch', 'hideadminmenu', 'useroundframepanels', 'showcollapse', 'blocks_edithide', 'uselangoption', 'use_groupcolor', 'showstars');
                    foreach($checkboxes as $v) {
                        if(TPUtil::checkboxChecked('tp_'.$v)) {
                            $updateArray[$v] = "1";
                        }
                        else {
                            $updateArray[$v] = "";
                        }
                        // remove the variable so we don't process it twice before the old logic is removed
                        unset($_POST['tp_'.$v]);
                    }
                    break;
				case 'frontpage':
                    $checkboxes = array('allow_guestnews', 'forumposts_avatar', 'use_attachment');
                    foreach($checkboxes as $v) {
                        if(TPUtil::checkboxChecked('tp_'.$v)) {
                            $updateArray[$v] = "1";
                        }
                        else {
                            $updateArray[$v] = "";
                        }
                        // remove the variable so we don't process it twice before the old logic is removed
                        unset($_POST['tp_'.$v]);
                    }
                    break;
				case 'artsettings':
                    $checkboxes = array('use_wysiwyg', 'use_dragdrop', 'hide_editarticle_link', 'print_articles', 'allow_links_article_comments', 'hide_article_facebook', 'hide_article_twitter', 'hide_article_reddit', 'hide_article_digg', 'hide_article_delicious', 'hide_article_stumbleupon');
                    foreach($checkboxes as $v) {
                        if(TPUtil::checkboxChecked('tp_'.$v)) {
                            $updateArray[$v] = "1";
                        }
                        else {
                            $updateArray[$v] = "";
                        }
                        // remove the variable so we don't process it twice before the old logic is removed
                        unset($_POST['tp_'.$v]);
                    }
                    break;
				case 'panels':
                    $checkboxes = array('hidebars_admin_only', 'hidebars_profile', 'hidebars_pm', 'hidebars_memberlist', 'hidebars_search', 'hidebars_calendar');
                    foreach($checkboxes as $v) {
                        if(TPUtil::checkboxChecked('tp_'.$v)) {
                            $updateArray[$v] = "1";
                        }
                        else {
                            $updateArray[$v] = "";
                        }
                        // remove the variable so we don't process it twice before the old logic is removed
                        unset($_POST['tp_'.$v]);
                    }
                    break;

                default:
                    break;
            }

			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 3) == 'tp_')
				{
					$where = substr($what, 3);
					$clean = $value;
					// for frontpage, do some extra
					if($from == 'frontpage')
					{
						if(substr($what, 0, 20) == 'tp_frontpage_visual_')
						{
							$w[] = substr($what, 20);
							unset($clean);
						}
						elseif(substr($what, 0, 21) == 'tp_frontpage_usorting')
						{
							$w[] = 'sort_'.$value;
							unset($clean);
						}
						elseif(substr($what, 0, 26) == 'tp_frontpage_sorting_order')
						{
							$w[] = 'sortorder_'.$value;
							unset($clean);
						}
						// SSI boards
						elseif(substr($what, 0, 11) == 'tp_ssiboard') {
                            $data   = file_get_contents("php://input");
                            $output = TPUtil::http_parse_query($data)['tp_ssiboard'];
                            if(is_string($output)) {
                                $ssi[] = $output;
                            } 
                            else if(is_array($output)) {
                                $ssi = $output;
                            }
                            else {
                                $ssi = array();
                            }
						}
					}
					if($from == 'settings' && $what == 'tp_frontpage_title') {
						$updateArray['frontpage_title'] = $clean;
                    }
					else {
						if(isset($clean))
							$updateArray[$where] = $clean;
					}
					// START non responsive themes form
					if($from == 'settings') {
						if(substr($what, 0, 7) == 'tp_resp') {

							$postname = substr($what, 7);
							if(!isset($themeschecked)) {
								$themeschecked = array();
							}
							$themeschecked[] = $postname;
							if(isset($themeschecked)) {
								$db->query('', '
									UPDATE {db_prefix}tp_settings
									SET value = {string:value}
									WHERE name = {string:name}',
									array('value' => implode(',', $themeschecked), 'name' => 'resp',)
								);
							}
						}
					}
					// END  non responsive themes form
                    if($what == 'tp_image_upload_path') {
                        unset($updateArray['image_upload_path']);
                        if(strcmp($context['TPortal']['image_upload_path'],$value) != 0) {
                            // Only allow if part of the boarddir
                            if(strncmp($value, $boarddir, strlen($boarddir)) == 0) {
                                // It cann't be part of the existing path
                                if(strncmp($value, $context['TPortal']['image_upload_path'], strlen($context['TPortal']['image_upload_path'])) != 0) {
                                    if(tp_create_dir($value)) {
                                        tp_recursive_copy($context['TPortal']['image_upload_path'], $value);
                                        tp_delete_dir($context['TPortal']['image_upload_path']);
                                        $updateArray['image_upload_path'] = $value;
                                    }
                                }
                            }
                        }
                    }
				}
			}

			// check the frontpage visual setting..
			if($from == 'frontpage') {
				$updateArray['frontpage_visual'] = implode(',', $w);
				$updateArray['SSI_board'] = implode(',', $ssi);
			}
			updateTPSettings($updateArray);
			return $from;
		}
		// categories
		elseif($from == 'categories')
		{
			checkSession('post');
			isAllowedTo('tp_articles');

			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 3) == 'tp_')
				{
					// for frontpage, do some extra
					if($from == 'categories')
					{
						if(substr($what, 0, 19) == 'tp_category_value2_')
						{
							$where = substr($what, 19);
							//make sure parent are not its own parent
							$request = $db->query('', '
								SELECT value2 FROM {db_prefix}tp_variables
								WHERE id = {string:varid} LIMIT 1',
								array(
									'varid' => $value
								)
							);
							$row = $db->fetch_assoc($request);
							$db->free_result($request);
							if($row['value2'] == $where)
								$db->query('', '
									UPDATE {db_prefix}tp_variables
									SET value2 = {string:val2}
									WHERE id = {string:varid}',
									array(
										'val2' => '0',
										'varid' => $value,
									)
								);

							$db->query('', '
								UPDATE {db_prefix}tp_variables
								SET value2 = {string:val2}
								WHERE id = {string:varid}',
								array(
									'val2' => $value,
									'varid' => $where,
								)
							);
						}
					}
				}
			}
			return $from;
		}
		// articles
		elseif($from == 'articles')
		{
			checkSession('post');
			isAllowedTo('tp_articles');

			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 14) == 'tp_article_pos')
				{
					$where = substr($what, 14);
						$db->query('', '
							UPDATE {db_prefix}tp_articles
							SET parse = {int:parse}
							WHERE id = {int:artid}',
							array(
								'parse' => $value,
								'artid' => $where,
							)
						);
				}
			}
			if(isset($_POST['tpadmin_form_category']) && is_numeric($_POST['tpadmin_form_category']))
				return $from.';cu=' . $_POST['tpadmin_form_category'];
			else
				return $from;
		}
		// all the items
		elseif($from == 'menuitems')
		{
			checkSession('post');
			isAllowedTo('tp_blocks');

			$all = explode(',', $context['TPortal']['sitemap_items']);
			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 8) == 'menu_pos')
					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET subtype = {string:subtype}
						WHERE id = {int:varid}',
						array(
							'subtype' => $value,
							'varid' => substr($what, 8),
						)
					);
				elseif(substr($what, 0, 8) == 'menu_sub')
					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET value4 = {string:val4}
						WHERE id = {int:varid}',
						array(
							'val4' => $value,
							'varid' => substr($what, 8),
						)
					);
				elseif(substr($what, 0, 15) == 'tp_menu_sitemap')
				{
					$new = substr($what, 15);
					if($value == 0 && in_array($new, $all))
					{
						foreach ($all as $key => $value)
						{
							if ($all[$key] == $new)
								unset($all[$key]);
						}
					}
					elseif($value == 1 && !in_array($new, $all))
						$all[] = $new;

					$updateArray['sitemap_items'] = implode(',', $all);
				}
			}
			updateTPSettings($updateArray);

			redirectexit('action=tpadmin;sa=menubox;mid='. $_POST['tp_menuid']);
		}
		// all the menus
		elseif($from == 'menus')
		{
			checkSession('post');
			isAllowedTo('tp_blocks');

			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 12) == 'tp_menu_name')
					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET value1 = {string:val1}
						WHERE id = {int:varid}',
						array(
							'val1' => $value,
							'varid' => substr($what, 12),
						)
					);
			}
			redirectexit('action=tpadmin;sa=menubox');
		}
		elseif($from == 'singlemenuedit')
		{
			checkSession('post');
			isAllowedTo('tp_blocks');

			$where = isset($_POST['tpadmin_form_id']) ? $_POST['tpadmin_form_id'] : 0;

			foreach($_POST as $what => $value)
			{
				if($what == 'tp_menu_name')
				{
					// make sure special charachters can't be done
					$value = preg_replace('~&#\d+$~', '', $value);
					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET value1 = {string:val1}
						WHERE id = {int:varid}',
						array(
							'val1' => $value,
							'varid' => $where,
						)
					);
				}
				elseif($what == 'tp_menu_newlink')
					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET value2 = {string:var2}
						WHERE id = {int:varid}',
						array(
							'var2' => $value,
							'varid' => $where,
						)
					);
				elseif($what == 'tp_menu_menuid')
					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET subtype2 = {int:subtype2}
						WHERE id = {int:varid}',
						array(
							'subtype2' => $value,
							'varid' => $where,
						)
					);
				elseif($what == 'tp_menu_type')
				{
					if($value == 'cats')
						$idtype = 'cats'.$_POST['tp_menu_category'];
					elseif($value == 'arti')
						$idtype = 'arti'.$_POST['tp_menu_article'];
					elseif($value == 'link')
						$idtype = $_POST['tp_menu_link'];
					elseif($value == 'head')
						$idtype = 'head';
					elseif($value == 'spac')
						$idtype = 'spac';
					elseif($value == 'menu')
						$idtype = 'menu'.$_POST['tp_menu_link'];

					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET value3 = {string:val3}
						WHERE id = {int:varid}',
						array(
							'val3' => $idtype,
							'varid' => $where,
						)
					);
				}
				elseif($what == 'tp_menu_sub')
					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET value4 = {string:val4}
						WHERE id = {int:varid}',
						array(
							'val4' => $value,
							'varid' => $where,
						)
                    );
				elseif($what == 'tp_menu_position')
					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET value7 = {string:val7}
						WHERE id = {int:varid}',
						array(
							'val7' => $value,
							'varid' => $where,
						)
					);
				elseif($what == 'tp_menu_icon')
					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET value8 = {string:val8}
						WHERE id = {int:varid}',
						array(
							'val8' => $value,
							'varid' => $where,
						)
					);
				elseif(substr($what, 0, 15) == 'tp_menu_newlink')
					$db->query('', '
						UPDATE {db_prefix}tp_variables
						SET value2 =
						WHERE id = {int:varid}',
						array(
							'val2' => $value,
							'varid' => $where,
						)
					);
			}
			redirectexit('action=tpadmin;linkedit='.$where.';' . $context['session_var'] . '=' . $context['session_id']);
		}
		// add a category
		elseif($from == 'addcategory')
		{
			checkSession('post');
			isAllowedTo('tp_articles');
			$name = !empty($_POST['tp_cat_name']) ? $_POST['tp_cat_name'] : $txt['tp-noname'];
			$parent = !empty($_POST['tp_cat_parent']) ? $_POST['tp_cat_parent'] : '0';
			$shortname = !empty($_POST['tp_cat_shortname']) ? $_POST['tp_cat_shortname'] : '';

			$db->insert('INSERT',
				'{db_prefix}tp_variables',
				array(
					'value1' => 'string',
					'value2' => 'string',
					'value3' => 'string',
					'type' => 'string',
					'value4' => 'string',
					'value5' => 'int',
					'subtype' => 'string',
					'value7' => 'string',
					'value8' => 'string',
					'subtype2'=> 'int'
				),
				array(strip_tags($name), $parent, '', 'category', '', 0, '', 'sort=date|sortorder=desc|articlecount=5|layout=1|catlayout=1|showchild=0|leftpanel=1|rightpanel=1|toppanel=1|centerpanel=1|lowerpanel=1|bottompanel=1', $shortname, 0),
				array('id')
			);

			$go = $db->insert_id('{db_prefix}tp_variables', 'id');
			redirectexit('action=tpadmin;sa=categories;cu='.$go);
		}
		// the categort list
		elseif($from == 'clist')
		{
			checkSession('post');
			isAllowedTo('tp_articles');

			$cats = array();
			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 8) == 'tp_clist')
					$cats[] = $value;
			}
			if(sizeof($cats) > 0)
				$catnames = implode(',', $cats);
			else
				$catnames = '';

			$updateArray['cat_list'] = $catnames;

			updateTPSettings($updateArray);

			return $from;
		}

		// edit a category
		elseif($from == 'editcategory')
		{
			checkSession('post');
			isAllowedTo('tp_articles');

			$options = array();
			$groups = array();
			$where = $_POST['tpadmin_form_id'];
			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 3) == 'tp_')
				{
					$clean = $value;
					$param = substr($what, 12);
					if(in_array($param, array('value5', 'value6', 'value8')))
						$db->query('', '
							UPDATE {db_prefix}tp_variables
							SET '. $param .' = {string:val}
							WHERE id = {int:varid}',
							array('val' => $value, 'varid' => $where)
						);
					// parents needs some checking..
					elseif($param == 'value2')
					{
						//make sure parent are not its own parent
						$request = $db->query('', '
							SELECT value2 FROM {db_prefix}tp_variables
							WHERE id = {int:varid} LIMIT 1',
							array('varid' => $value)
						);
						$row = $db->fetch_assoc($request);
						$db->free_result($request);
						if(isset($row['value2']) && ( $row['value2'] == $where ))
							$db->query('', '
								UPDATE {db_prefix}tp_variables
								SET value2 = {string:val2}
								WHERE id = {int:varid}',
								array('val2' => '0', 'varid' => $value)
							);

						$db->query('', '
							UPDATE {db_prefix}tp_variables
							SET value2 = {string:val2}
							WHERE id = {int:varid}',
							array('val2' => $value, 'varid' => $where)
						);
					}
					elseif($param == 'value1')
						$db->query('', '
							UPDATE {db_prefix}tp_variables
							SET value1 = {string:val1}
							WHERE id = {int:varid}',
							array('val1' => strip_tags($value), 'varid' => $where)
						);
					elseif($param == 'value4')
						$db->query('', '
							UPDATE {db_prefix}tp_variables
							SET value4 = {string:val4}
							WHERE id = {int:varid}',
							array('val4' => $value, 'varid' => $where)
						);
					elseif($param == 'value9')
						$db->query('', '
							UPDATE {db_prefix}tp_variables
							SET value9 = {string:val9}
							WHERE id = {int:varid}',
							array('val9' => $value, 'varid' => $where)
						);
					elseif(substr($param, 0, 6) == 'group_')
						$groups[] = substr($param, 6);
					else
						$options[] = $param. '=' . $value;
				}
			}
			$db->query('', '
				UPDATE {db_prefix}tp_variables
				SET value3 = {string:val3}, value7 = {string:val7}
				WHERE id = {int:varid}',
				array('val3' => implode(',', $groups), 'val7' => implode('|', $options), 'varid' => $where)
			);
			$from = 'categories;cu=' . $where;
			return $from;
		}
		// stray articles
		elseif($from == 'strays')
		{
			checkSession('post');
			isAllowedTo('tp_articles');

			$ccats = array();
			// check if we have some values
			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 16) == 'tp_article_stray')
					$ccats[] = substr($what, 16);
				elseif($what == 'tp_article_cat')
					$straycat = $value;
				elseif($what == 'tp_article_new')
					$straynewcat = $value;
			}
			// update
			if(isset($straycat) && sizeof($ccats) > 0)
			{
				$category = $straycat;
				if($category == 0 && !empty($straynewcat))
				{
					$request = $db->insert('INSERT',
						'{db_prefix}tp_variables',
						array('value1' => 'string', 'value2' => 'string', 'type' => 'string'),
						array(strip_tags($straynewcat), '0', 'category'),
						array('id')
					);

					$newcategory = $db->insert_id('{db_prefix}tp_variables', 'id');
					$db->free_result($request);
				}
				$db->query('', '
					UPDATE {db_prefix}tp_articles
					SET category = {int:cat}
					WHERE id IN ({array_int:artid})',
					array(
						'cat' => !empty($newcategory) ? $newcategory : $category,
						'artid' => $ccats,
					)
				);
			}
			return $from;
		}
		// from articons...
		elseif($from == 'articons')
		{
			checkSession('post');
			isAllowedTo('tp_articles');

			if(file_exists($_FILES['tp_article_newillustration']['tmp_name']))
			{
				$name = TPuploadpicture('tp_article_newillustration', '', $context['TPortal']['icon_max_size'], 'jpg,gif,png', 'tp-files/tp-articles/illustrations');
				tp_createthumb('tp-files/tp-articles/illustrations/'. $name, $context['TPortal']['icon_width'], $context['TPortal']['icon_height'], 'tp-files/tp-articles/illustrations/s_'. $name);
				unlink('tp-files/tp-articles/illustrations/'. $name);
			}
			// how about deleted?
			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 15) == 'artillustration')
					unlink($boarddir.'/tp-files/tp-articles/illustrations/'.$value);
			}
			return $from;
		}
		// adding a full menu.
		elseif($from == 'menuadd')
		{
			checkSession('post');
			isAllowedTo('tp_blocks');

			if(!empty($_POST['tp_menu_title']))
			{
				$mtitle = strip_tags($_POST['tp_menu_title']);
				$db->insert('INSERT',
					'{db_prefix}tp_variables',
					array('value1' => 'string', 'type' => 'string'),
					array($mtitle, 'menus'),
					array('id')
				);
				redirectexit('action=tpadmin;sa=menubox');
			}
		}
		// adding a menu item.
		elseif($from == 'menuaddsingle')
		{
			checkSession('post');
			isAllowedTo('tp_blocks');

			$mid = $_POST['tp_menu_menuid'];
			$mtitle = strip_tags($_POST['tp_menu_name']);
			if($mtitle == '')
				$mtitle = $txt['tp-no_title'];

			$mtype = $_POST['tp_menu_type'];
			$mcat = isset($_POST['tp_menu_category']) ? $_POST['tp_menu_category'] : '';
			$mart = isset($_POST['tp_menu_article']) ? $_POST['tp_menu_article'] : '';
			$mlink = isset($_POST['tp_menu_link']) ? $_POST['tp_menu_link'] : '';
			$mhead = isset($_POST['tp_menu_head']) ? $_POST['tp_menu_head'] : '';
			$mnewlink = isset($_POST['tp_menu_newlink']) ? $_POST['tp_menu_newlink'] : '0';
			$menuicon = isset($_POST['tp_menu_icon']) ? $_POST['tp_menu_icon'] : '0';

			if($mtype == 'cats')
				$mtype = 'cats'.$mcat;
			elseif($mtype == 'arti')
				$mtype = 'arti'.$mart;
			elseif($mtype == 'head')
				$mtype = 'head'.$mhead;
			elseif($mtype == 'spac')
				$mtype = 'spac';
			elseif($mtype == 'menu')
				$mtype = 'menu'.$mlink;
			else
				$mtype = $mlink;

			$msub = $_POST['tp_menu_sub'];
			$mpos = $_POST['tp_menu_position'];
			$db->insert('INSERT',
				'{db_prefix}tp_variables',
				array(
					'value1' => 'string',
					'value2' => 'string',
					'value3' => 'string',
					'type' => 'string',
					'value4' => 'string',
					'value5' => 'int',
					'subtype2'=> 'int',
                    'value7' => 'string',
                    'value8' => 'string'
				),
				array($mtitle, $mnewlink, $mtype, 'menubox', $msub, -1, $mid, $mpos, $menuicon),
				array('id')
			);

			redirectexit('action=tpadmin;sa=menubox;mid='.$mid);
		}
		// submitted ones
		elseif($from == 'submission')
		{
			checkSession('post');
			isAllowedTo('tp_articles');

			$ccats = array();
			// check if we have some values
			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 21) == 'tp_article_submission')
					$ccats[] = substr($what,21);
				elseif($what == 'tp_article_cat')
					$straycat = $value;
				elseif($what == 'tp_article_new')
					$straynewcat = $value;
			}
			// update
			if(isset($straycat) && sizeof($ccats) > 0)
			{
				$category = $straycat;
				if($category == 0 && !empty($straynewcat))
				{
					$request = $db->insert('INSERT',
						'{db_prefix}tp_variables',
						array(
							'value1' => 'string',
							'value2' => 'string',
							'type' => 'string',
						),
						array($straynewcat, '0', 'category'),
						array('id')
					);

					$newcategory = $db->insert_id('{db_prefix}tp_variables', 'id');
					$db->free_result($request);
				}
				$db->query('', '
					UPDATE {db_prefix}tp_articles
					SET approved = {int:approved}, category = {int:cat}
					WHERE id IN ({array_int:artid})',
					array(
						'approved' => 1,
						'cat' => !empty($newcategory) ? $newcategory : $category,
						'artid' => $ccats,
					)
				);
				$db->query('', '
					DELETE FROM {db_prefix}tp_variables
					WHERE type = {string:type}
					AND value5 IN ({array_int:val5})',
					array(
						'type' => 'art_not_approved',
						'val5' => $ccats,
					)
				);
			}
			return $from;
		}
		// from blocks screen
		elseif($from == 'blocks')
		{
			checkSession('post');
			isAllowedTo('tp_blocks');

			foreach($_POST as $what => $value)
			{
				if(substr($what, 0, 3) == 'pos')
				{
					$where = substr($what, 3);
					if(is_numeric($where))
						$db->query('', '
							UPDATE {db_prefix}tp_blocks
							SET pos = {int:pos}
							WHERE id = {int:blockid}',
							array(
								'pos' => $value,
								'blockid' => $where
							)
						);
				}
				elseif(substr($what, 0, 6) == 'addpos')
				{
					$where = substr($what, 6);
					if(is_numeric($where))
						$db->query('', '
							UPDATE {db_prefix}tp_blocks
							SET pos = (pos + 11)
							WHERE id = {int:blockid}',
							array(
								'blockid' => $where
							)
						);
				}
				elseif(substr($what, 0, 6) == 'subpos')
				{
					$where = substr($what, 6);
					if(is_numeric($where))
						$db->query('', '
							UPDATE {db_prefix}tp_blocks SET pos = (pos - 11)
							WHERE id = {int:blockid}',
							array(
								'blockid' => $where
							)
						);
				}
				elseif(substr($what, 0, 4) == 'type')
				{
					$where = substr($what, 4);
					$db->query('', '
						UPDATE {db_prefix}tp_blocks
						SET type = {int:type}
						WHERE id = {int:blockid}',
						array(
							'type' => $value,
							'blockid' => $where,
						)
					);
				}
				elseif(substr($what, 0, 5) == 'title')
				{
					$where = strip_tags(substr($what, 5));
					$db->query('', '
						UPDATE {db_prefix}tp_blocks
						SET title = {string:title}
						WHERE id = {int:blockid}',
						array(
							'title' => $value,
							'blockid' => $where,
						)
					);
				}
				elseif(substr($what, 0, 9) == 'blockbody')
				{
					$where = substr($what, 9);
					$db->query('', '
						UPDATE {db_prefix}tp_blocks
						SET body = {string:body}
						WHERE id = {int:blockid}',
						array(
							'body' => $value,
							'blockid' => $where,
						)
					);
				}
			}
			redirectexit('action=tpadmin;sa=blocks');
		}
		// from editing block
		elseif($from == 'addblock')
		{
			checkSession('post');
			isAllowedTo('tp_blocks');

			$title = empty($_POST['tp_addblocktitle']) ? $txt['tp-no_title'] : ($_POST['tp_addblocktitle']);
			$panel = $_POST['tp_addblockpanel'];
			$type = $_POST['tp_addblock'];
			if(!is_numeric($type))
			{
				if(substr($type, 0, 3) == 'mb_')
				{
					$request = $db->query('', '
						SELECT * FROM {db_prefix}tp_blocks
						WHERE id = {int:blockid}',
						array(
							'blockid' => substr($type, 3)
						)
					);
					if($db->num_rows($request) > 0)
					{
						$cp = $db->fetch_assoc($request);
						$db->free_result($request);
					}
				}
				else
					$od = TPparseModfile(file_get_contents($context['TPortal']['blockcode_upload_path'] . $type.'.blockcode') , array('code'));
			}
			if(isset($od['code']))
			{
				$body = tp_convertphp($od['code']);
				$type = 10;
			}
			$defblocks = array("18", "19");
			if(in_array($type , $defblocks))
			{
				$body = '0';
			}
			else
				$body = '';

            $request = $db->query('', '
                SELECT pos FROM {db_prefix}tp_blocks
                WHERE bar = {int:bar}
                ORDER BY pos DESC LIMIT 1',
                array(
                    'bar' => $panel
                )
            );
            if($db->num_rows($request) > 0) {
                $pos = $db->fetch_assoc($request);
                $pos = $pos['pos'] + 1;
                $db->free_result($request);
            }
            else {
                $pos = 0;
            }

			if(isset($cp))
				$db->insert('INSERT',
					'{db_prefix}tp_blocks',
					array(
						'type' => 'int',
						'frame' => 'string',
						'title' => 'string',
						'body' => 'string',
						'access' => 'string',
						'bar' => 'int',
						'pos' => 'int',
						'off' => 'int',
						'visible' => 'string',
						'lang' => 'string',
						'display' => 'string',
						'editgroups' => 'string',
                        'settings' => 'string',
					),
					array(
						$cp['type'],
						$cp['frame'],
						$title,
						$cp['body'],
						$cp['access'],
						$panel,
						$pos,
						1,
						1,
						$cp['lang'],
						$cp['display'],
						$cp['editgroups'],
                        json_encode(array(
                            'var1' => json_decode($cp['settings'], true)['var1'],
                            'var2' => json_decode($cp['settings'], true)['var2'],
                            'var3' => 0,
                            'var4' => 0,
                            'var5' => 0
                            )
                        ),
					),
					array('id')
				);
			else
				$db->insert('INSERT',
					'{db_prefix}tp_blocks',
					array(
						'type' => 'int',
						'frame' => 'string',
						'title' => 'string',
						'body' => 'string',
						'access' => 'string',
						'bar' => 'int',
						'pos' => 'int',
						'off' => 'int',
						'visible' => 'string',
						'lang' => 'string',
						'display' => 'string',
						'editgroups' => 'string',
                        'settings' => 'string',
					),
					array(
                        $type, 'theme', $title, $body, '-1,0,1', $panel, $pos, 1, 1, '', 'allpages', '', 
                        json_encode(array('var1' => 0, 'var2' => 0, 'var3' => 0, 'var4' => 0, 'var5' => 0 )),
					),
					array('id')
				);

			$where = $db->insert_id('{db_prefix}tp_blocks', 'id');
			if(!empty($where))
				redirectexit('action=tpadmin&sa=editblock&id='.$where.';sesc='. $context['session_id']);
			else
				redirectexit('action=tpadmin;sa=blocks');
		}
		// from editing block
		elseif($from == 'blockedit') {
			checkSession('post');
			isAllowedTo('tp_blocks');

			$where = is_numeric($_POST['tpadmin_form_id']) ? $_POST['tpadmin_form_id'] : 0;
			$tpgroups = array();
			$editgroups = array();
			$access = array();
			$lang = array();
			foreach($_POST as $what => $value)
			{

				// We have a empty post value just skip it
				if(empty($value) && $value == '') {
					continue;
				}

				if(substr($what, 0, 9) == 'tp_block_')
				{
					$setting = substr($what, 9);

					if($setting == 'body')
					{
						// If we came from WYSIWYG then turn it back into BBC regardless.
						if (!empty($_REQUEST['tp_block_body_mode']) && isset($_REQUEST['tp_block_body']))
						{
							require_once(SOURCEDIR . '/Editor.subs.php');
							$_REQUEST['tp_block_body'] = html_to_bbc($_REQUEST['tp_block_body']);
							// We need to unhtml it now as it gets done shortly.
							$_REQUEST['tp_block_body'] = un_htmlspecialchars($_REQUEST['tp_block_body']);
							// We need this for everything else.
							$value = $_POST['tp_block_body'] = $_REQUEST['tp_block_body'];
						}

						// PHP block?
						if($_POST['tp_block_type'] == 10)
							$value = tp_convertphp($value);

						$db->query('', '
							UPDATE {db_prefix}tp_blocks
							SET '. $setting .' = {string:value}
							WHERE id = {int:blockid}',
							array('value' => $value, 'blockid' => $where)
						);
					}
					elseif($setting == 'title')
					{
						$db->query('', '
							UPDATE {db_prefix}tp_blocks
							SET title = {string:title}
							WHERE id = {int:blockid}',
							array('title' => $value, 'blockid' => $where)
						);
					}
					elseif($setting == 'body_mode' || $setting == 'body_choice' || $setting == 'body_pure')
						$go = '';
					elseif($setting == 'frame')
						$db->query('', '
							UPDATE {db_prefix}tp_blocks
							SET frame = {string:val}
							WHERE id = {int:blockid}',
							array('val' => $value, 'blockid' => $where)
						);
                    elseif(in_array($setting, array( 'var1', 'var2', 'var3', 'var4', 'var5')) ) {
                        // Check for blocks in table, if none insert default blocks.
						$request = $db->query('', '
                            SELECT settings FROM {db_prefix}tp_blocks
                            WHERE id = {int:varid} LIMIT 1',
                            array('varid' => $where)
                        );
                            
                        $data = array();
                        if($db->num_rows($request) > 0) {
                            $row    = $db->fetch_assoc($request);
                            $data   = json_decode($row['settings'], true);
                            $db->free_result($request);
                        }
                        $data[$setting] = $value;
						$db->query('', '
                            UPDATE {db_prefix}tp_blocks 
                            SET settings = {string:data}
                            WHERE id = {int:blockid}',
                            array('data' => json_encode($data), 'blockid' => $where)
                        );
                    }
					else {
						$db->query('', '
							UPDATE {db_prefix}tp_blocks
							SET '. $setting .' = {raw:val}
							WHERE id = {int:blockid}',
							array('val' => $value, 'blockid' => $where)
						);
                    }
				}
				elseif(substr($what, 0, 8) == 'tp_group')
					$tpgroups[] = substr($what, 8);
				elseif(substr($what, 0, 12) == 'tp_editgroup')
					$editgroups[] = substr($what, 12);
				elseif(substr($what, 0, 10) == 'actiontype')
					$access[] = '' . $value;
				elseif(substr($what, 0, 9) == 'boardtype')
					$access[] = 'board=' . $value;
				elseif(substr($what, 0, 11) == 'articletype')
					$access[] = 'tpage=' . $value;
				elseif(substr($what, 0, 12) == 'categorytype')
					$access[] = 'tpcat=' . $value;
				elseif(substr($what, 0, 8) == 'langtype')
					$access[] = 'tlang=' . $value;
				elseif(substr($what, 0, 9) == 'dlcattype')
					$access[] = 'dlcat=' . $value;
				elseif(substr($what, 0, 9) == 'tpmodtype')
					$access[] = 'tpmod=' . $value;
				elseif(substr($what, 0, 9) == 'custotype' && !empty($value))
				{
					$items = explode(',', $value);
					foreach($items as $iti => $it)
						$access[] = '' . $it;
				}
				elseif(substr($what, 0, 8) == 'tp_lang_')
				{
					if(substr($what, 8) != '' )
						$lang[] = substr($what, 8). '|' . $value;
				}
				elseif(substr($what, 0, 18) == 'tp_userbox_options')
				{
					if(!isset($userbox))
						$userbox = array();
					$userbox[] = $value;
				}
				elseif(substr($what, 0, 8) == 'tp_theme')
				{
					$theme = substr($what, 8);
					if(!isset($themebox))
						$themebox = array();
					// get the path too
					if(isset($_POST['tp_path'.$theme]))
						$tpath = $_POST['tp_path'.$theme];
					else
						$tpath = '';

					$themebox[] = $theme . '|' . $value . '|' . $tpath;
				}
			}
			// construct the access++
			$db->query('', '
				UPDATE {db_prefix}tp_blocks
				SET	display = {string:acc2},
					access = {string:acc},
					lang = {string:lang},
					editgroups = {string:editgrp}
				WHERE id = {int:blockid}',
				array(
					'acc2' => implode(',', $access),
					'acc' => implode(',', $tpgroups),
					'lang' => implode('|', $lang),
					'editgrp' => implode(',', $editgroups),
					'blockid' => $where,
				)
			);

			if(isset($userbox))
				$updateArray['userbox_options'] = implode(',', $userbox);

			if(isset($themebox))
				$db->query('', '
					UPDATE {db_prefix}tp_blocks
					SET body = {string:body}
					WHERE id = {int:blockid}',
					array('body' => implode(',', $themebox), 'blockid' => $where,)
				);

			// anything from PHP block?
			if(isset($_POST['blockcode_overwrite']))
			{
				// get the blockcode
				$newval = TPparseModfile(file_get_contents($context['TPortal']['blockcode_upload_path'] . $_POST['tp_blockcode'].'.blockcode') , array('code'));
				$db->query('', '
					UPDATE {db_prefix}tp_blocks
					SET body = {string:body}
					WHERE id = {int:blockid}',
					array('body' => $newval['code'], 'blockid' => $where)
				);
			}

			// check if uploaded picture
			if(isset($_FILES['qup_blockbody']) && file_exists($_FILES['qup_blockbody']['tmp_name']))
			{
                $name = TPuploadpicture( 'qup_blockbody', $context['user']['id'].'uid', null, null, $context['TPortal']['image_upload_path']);
				tp_createthumb($context['TPortal']['image_upload_path'] . $name, 50, 50, $context['TPortal']['image_upload_path'] . 'thumbs/thumb_'. $name);
			}
			updateTPSettings($updateArray);

			redirectexit('action=tpadmin&sa=editblock&id='.$where.';' . $context['session_var'] . '=' . $context['session_id']);
		}
		// Editing an article?
		elseif(substr($from, 0, 11) == 'editarticle') {
            require_once(SOURCEDIR . '/TPArticle.php');
            return articleEdit();
		}
	}
	else {
		return;
    }
}

function get_langfiles()
{
	global $context, $settings;

	// get all languages for blocktitles
	$language_dir = $settings['default_theme_dir'] . '/languages';
	$context['TPortal']['langfiles'] = array();
	$dir = dir($language_dir);
	while ($entry = $dir->read())
		if (substr($entry, 0, 6) == 'index.' && substr($entry,(strlen($entry) - 4) ,4) == '.php' && strlen($entry) > 9)
	$context['TPortal']['langfiles'][] = substr(substr($entry, 6), 0, -4);
	$dir->close();
}

function get_catlayouts()
{
	global $context, $txt;

	// setup the layoutboxes
	$context['TPortal']['admin_layoutboxes'] = array(
		array('value' => '1', 'label' => $txt['tp-catlayout1']),
		array('value' => '2', 'label' => $txt['tp-catlayout2']),
		array('value' => '4', 'label' => $txt['tp-catlayout4']),
		array('value' => '8', 'label' => $txt['tp-catlayout8']),
		array('value' => '6', 'label' => $txt['tp-catlayout6']),
		array('value' => '5', 'label' => $txt['tp-catlayout5']),
		array('value' => '3', 'label' => $txt['tp-catlayout3']),
		array('value' => '9', 'label' => $txt['tp-catlayout9']),
		array('value' => '7', 'label' => $txt['tp-catlayout7']),
	);
}

function get_boards()
{
	global $context;

    $db = database();

	$context['TPortal']['boards'] = array();
	$request = $db->query('', '
		SELECT b.id_board as id, b.name, b.board_order
		FROM {db_prefix}boards as b
		WHERE 1=1
		ORDER BY b.board_order ASC',
		array()
	);
	if($db->num_rows($request) > 0) {
		while($row = $db->fetch_assoc($request)) {
			$context['TPortal']['boards'][] = $row;
        }
		$db->free_result($request);
	}
}

function get_articles()
{

	global $context;
    
    $db = database();

	$context['TPortal']['edit_articles'] = array();

	$request = $db->query('', '
		SELECT id, subject, shortname FROM {db_prefix}tp_articles
		WHERE approved = 1 AND off = 0
		ORDER BY subject ASC');

	if($db->num_rows($request) > 0) {
		while($row=$db->fetch_assoc($request)) {
			$context['TPortal']['edit_articles'][] = $row;
        }

		$db->free_result($request);
	}
}

function get_catnames()
{

	global $context;

    $db = database();

	$context['TPortal']['catnames'] = array();

	$request = $db->query('', '
		SELECT id, value1 FROM {db_prefix}tp_variables
		WHERE type = {string:type}',
		array('type' => 'category')
	);
	if($db->num_rows($request) > 0) {
		while($row = $db->fetch_assoc($request))
			$context['TPortal']['catnames'][$row['id']] = $row['value1'];

		$db->free_result($request);
	}
}

function tp_create_dir($path) {{{
    global $sourcedir;

    require_once(SOURCEDIR . '/Package.subs.php');

    // Load up the package FTP information?
    create_chmod_control();

    if (!mktree($path, 0755)) {
        deltree($path, true);
        fatal_error($txt['tp-failedcreatedir'], false);
    }

    return TRUE;
}}}

function tp_delete_dir($path) {{{
    global $sourcedir;

    require_once(SOURCEDIR . '/Package.subs.php');

    // Load up the package FTP information?
    create_chmod_control();

    deltree($path, true);

    return TRUE;
}}}

function tp_recursive_copy($src, $dst) {{{

    $dir = opendir($src);
    tp_create_dir($dst);
    while(false !== ($file = readdir($dir)) ) {
        if(($file != '.') && ($file != '..')) {
            if(is_dir($src . '/' . $file)) {
                tp_recursive_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);

}}}

?>
