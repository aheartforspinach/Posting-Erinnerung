<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item("Posting-Erinnerung", "index.php?module=tools-postingreminder");
require_once MYBB_ROOT."/inc/datahandlers/postingreminder.php";

// Navigation erstellen
$page->output_header("Posting-Erinnerung Administration");

// format Table
$form_container = new FormContainer('Inaktive Szenen');
$form_container->output_row_header('Szenenname');
$form_container->output_row_header('User, der posten muss');
$form_container->output_row_header('letzter Post');
    
$prHandler = new postingreminderHandler();
$scenes = $prHandler->getAllInactiveScenes();

foreach($scenes as $tid => $uid){
    $user = get_user($uid);
    $thread = get_thread($tid);
    $form_container->output_cell("<a href=\"{$mybb->settings['bburl']}/showthread.php?tid={$thread['tid']}\" target=\"blank\">{$thread['subject']}</a>");
    $form_container->output_cell($user['username']);
    $form_container->output_cell(date('d.m.Y', $thread['lastpost']));
	$form_container->construct_row();
}

// Keine inaktiven Szenen?
if(empty($scenes)) {
    $form_container->output_cell("<center>Keine inaktiven Szenen gefunden!</center>", array("colspan" => 3));
    $form_container->construct_row();
}
$form_container->end();
$page->output_footer();
