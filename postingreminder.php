<?php
define("IN_MYBB", 1);
require_once "global.php";
require_once "inc/datahandlers/postingreminder.php";

add_breadcrumb("Posting-Erinnerung", "postingreminder.php");
global $db, $templates, $mybb, $lang;

//kein Zutritt für Gäste
if ($mybb->user['uid'] == 0) error_no_permission();

$prHandler = new postingreminderHandler();
$lang->load('postingreminder');
$dayDifference = intval($mybb->settings['postingreminder_day']);
$characterOpenScenes = '';

// unseen banner for x days
if ($_GET['seen'] == 1) {
    if($prHandler->setUnseenBanner($mybb->user['uid'])) redirect('postingreminder.php', $lang->postingreminder_hideBanner_success);
}

$lang->postingreminder_explanation = $lang->sprintf($lang->postingreminder_explanation, $dayDifference);
$allCharacters = $prHandler->getAllCharacters($mybb->user['uid']);
foreach ($allCharacters as $character) {
    $scenes = $prHandler->getInactiveScenesFrom($character);
    if(!empty($scenes)){
        $openScenes = '';
        $characterName = get_user($character)['username'];
        foreach($scenes as $scene){
            $thread = get_thread($scene);
            $sceneLink = '<a href="/showthread.php?tid='. $thread['tid'] .'">'. $thread['subject'] .'</a>';
            $lastPost = $lang->postingreminder_lastDate . date('d.m.Y', $thread['lastpost']);
            eval("\$openScenes .= \"" . $templates->get("postingreminder_scenes") . "\";");
        }
        eval("\$characterOpenScenes .= \"" . $templates->get("postingreminder_characters") . "\";");
    }
}

if($characterOpenScenes == '') $characterOpenScenes = '<center>'. $lang->postingreminder_noOpenScenes .'</center>';

eval("\$page = \"" . $templates->get("postingreminder") . "\";");
output_page($page);
