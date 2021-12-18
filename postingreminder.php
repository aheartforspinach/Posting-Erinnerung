<?php
define("IN_MYBB", 1);
require_once "global.php";
require_once "inc/datahandlers/postingreminder.php";

add_breadcrumb("Posting-Erinnerung", "postingreminder.php");
global $db, $templates, $mybb, $lang;

if ($mybb->user['uid'] == 0) error_no_permission();

$prHandler = new PostingreminderHandler();
$lang->load('postingreminder');
$dayDifference = intval($mybb->settings['postingreminder_day']);
$characterOpenScenes = '';

// unseen banner for x days
if ($_POST['seen'] == 1) {
    $prHandler->setUnseenBanner($mybb->user['uid']);
}

$lang->postingreminder_explanation = $lang->sprintf($lang->postingreminder_explanation, $dayDifference);
$inactiveScenes = $prHandler->getOwnInactiveScenes($mybb->user['uid']);
foreach ($inactiveScenes as $uid => $scenes) {
    $openScenes = '';
    $characterName = get_user($uid)['username'];
    foreach($scenes as $scene){
        $thread = get_thread($scene);
        $sceneLink = '<a href="/showthread.php?tid='. $thread['tid'] .'">'. $thread['subject'] .'</a>';
        $lastPost = $lang->postingreminder_lastDate . date('d.m.Y', $thread['lastpost']);
        eval("\$openScenes .= \"" . $templates->get("postingreminder_scenes") . "\";");
    }
    eval("\$characterOpenScenes .= \"" . $templates->get("postingreminder_characters") . "\";");
}

if($characterOpenScenes == '') $characterOpenScenes = '<center>'. $lang->postingreminder_noOpenScenes .'</center>';

eval("\$page = \"" . $templates->get("postingreminder") . "\";");
output_page($page);
