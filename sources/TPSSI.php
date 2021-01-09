<?php
/**
 * @package TinyPortal
 * @version 1.0.0 RC1
 * @author TinyPortal - http://www.tinyportal.net
 * @license BSD 3.0 http://opensource.org/licenses/BSD-3-Clause/
 *
 * Copyright (C) 2020 - The TinyPortal Team
 *
 */
use \TinyPortal\Model\Article as TPArticle;

function ssi_TPIntegrate() {{{

    return true;

}}}

function ssi_TPCategoryArticles($category = 0, $current = '-1', $output = 'echo', $display = 'list') {{{
    global $scripturl;
    
    // if category is not a number, return
    if(!is_numeric($category)) {
        return;
    }   
    
    $articles = array();
    $render = '';
    
    if($output != 'array') {
        $render .= '<ul class="tp_articleList">';
    }   
    
    $tpArticle  = TPArticle::getInstance();
    $data       = $tpArticle->getArticlesInCategory($category);
    foreach($data as $article) {
        $render .= '<li';
        if($current == $article['id'] || strtolower($current) == $article['shortname']) {
             $render .= ' class="current_art"';
        }    
        $render .= '><a href="' . $scripturl . '?page=' . $article['shortname'] . '">' . $article['subject'] . '</a></li>';
        $articles[] = array(
            'id' => $article['id'],
            'subject' => $article['subject'],
            'href' => $scripturl. '?page=' .$article['shortname'],
            'link' => '<a href="' . $scripturl. '?page=' .$article['shortname'] . '">' . $article['subject']. '</a>',
            'selected' => ($current == $article['id'] || strtolower($current) == $article['shortname']) ? true : false,
        );  
    }   
    
    if($output == 'array') {
        return $articles;
    }   
    
    // render it
    if($display == 'list') {
        echo $render;
    }   
    else {
        $art = array();
        $i = 0;
        $curr = 0;
        foreach($articles as $rt) {
            $art[$i] = '<a href="' . $rt['href']. '">'.$rt['subject'].'</a>';
            if($rt['selected']) {
                $curr = $i;
            }   
            $i++;
        }   
        if($curr > 0) {
            $art_previous = $art[$curr - 1];
        }   
        else {
            $art_previous = $art[0];
        }   
        
        if($curr < $i - 1) {
            $art_next = $art[$curr + 1];
        }   
        else {
            $art_next = $art[$i];
        }   
        
        echo '
        <form name="articlejump" id="articlejump" action="#">
            &#171; ' . $art_previous , '
            <select name="articlejump_menu" onchange="javascript:location=document.articlejump.articlejump_menu.options[document.articlejump.articlejump_menu.selectedIndex].value;">';
        foreach($articles as $art) {
            echo '<option value="' . $art['href']. '"' , $art['selected'] ? ' selected="selected"' : '' , '>'.$art['subject'].'</option>';
        }
        echo '
            </select>  &nbsp;
            ' . $art_next . ' &#187;
        </form>';
    }

}}}

?>
