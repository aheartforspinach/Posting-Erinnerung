<?php
define("IN_MYBB", 1);
require_once "global.php";
require_once "inc/datahandlers/postingreminder.php";

add_breadcrumb("Posting-Erinnerung", "postingreminder.php");
global $db, $templates, $mybb, $lang;

//kein Zutritt für Gäste
if ($mybb->user['uid'] == 0) {
    error_no_permission();
}

$prHandler = new postingreminderHandler();
$lang->load('postingreminder');
$dayDifference = intval($mybb->settings['postingreminder_day']);
$characterOpenScenes = '';

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
            eval("\$openScenes .= \"" . $templates->get("postingreminderScenes") . "\";");
        }
        eval("\$characterOpenScenes .= \"" . $templates->get("postingreminderCharacters") . "\";");
    }
}

if($characterOpenScenes == ''){
    $characterOpenScenes = '<center>'. $lang->postingreminder_noOpenScenes .'</center>';
}

eval("\$page = \"" . $templates->get("postingreminder") . "\";");
output_page($page);