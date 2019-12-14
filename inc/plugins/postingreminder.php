<?php

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function postingreminder_info()
{
    return array(
        "name"            => "Posting-Erinnerung",
        "description"    => "Erinnert User daran in einer Szene zu posten und gibt dem Team eine List an inaktiven Szenen",
        "author"        => "aheartforspinach",
        "authorsite"    => "https://github.com/aheartforspinach",
        "version"        => "1.0",
        "compatibility" => "18*"
    );
}

function postingreminder_install()
{
    global $db, $cache, $mybb;

    //Einstellungen 
    $setting_group = array(
        'name' => 'postingreminder',
        'title' => 'Posting-Erinnerung',
        'description' => 'Einstellungen für das Posting-Erinnerungs-Plugin',
        'isdefault' => 0
    );
    $gid = $db->insert_query("settinggroups", $setting_group);

    $setting_array = array(
        'postingreminder_day' => array(
            'title' => 'Erinnerung nach x Tagen',
            'description' => 'Nach wie vielen Tagen soll ein User erinnert werden?',
            'optionscode' => 'text',
            'value' => '30', 
            'disporder' => 1
        ),
        'postingreminder_inplayID' => array(
            'title' => 'Inplay-ID',
            'description' => 'Wie lautet die ID des Inplay-Bereiches',
            'optionscode' => 'text',
            'value' => '0', 
            'disporder' => 2
        ),
        'postingreminder_groups'	=> array(
            'title'			=> 'Ausgeschlossene Gruppen',
            'description'	=> 'Welche Gruppen (primäre Nutzergruppe) sollen ausgeschlossen werden?',
            'optionscode'	=> ($mybb->version_code >= 1800 ? 'groupselect' : 'text'),
             'value'			=>	'',
             'disporder' => 3
         ),
    );

    foreach ($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }

    //Template postingreminder bauen
    $insert_array = array(
        'title'        => 'postingreminder',
        'template'    => $db->escape_string('<html xml:lang="de" lang="de" xmlns="http://www.w3.org/1999/xhtml">

        <head>
            <title>{$mybb->settings[\'bbname\']} - Posting-Erinnerung</title>
            {$headerinclude}
        </head>
        
        <body>
            {$header}
            <div class="panel" id="panel">
                <div id="panel">$menu</div>
                <h1>Offene Szenen</h1>
				<blockquote>{$lang->postingreminder_explanation}</blockquote>
				{$characterOpenScenes}
					</blockquote>
            </div>
            {$footer}
        </body>
        
        </html>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

      //Template postingreminderCharacters bauen
      $insert_array = array(
        'title'        => 'postingreminderCharacters',
        'template'    => $db->escape_string('<table width="100%">
        <tr>
            <td class="thead" colspan="2">{$characterName}</td>
        </tr>
         {$openScenes}
        </table>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //Template postingreminderCharacters bauen
    $insert_array = array(
        'title'        => 'postingreminderScenes',
        'template'    => $db->escape_string('<tr>
        <td width="70%">{$sceneLink}</td> 
        <td width="30%">{$lastPost}</td>
    </tr>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //Template postingreminderHeader bauen
    $insert_array = array(
        'title'        => 'postingreminderHeader',
        'template'    => $db->escape_string('<div class="red_alert">{$lang->postingreminder_inactiveScenes}</div>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);


    rebuild_settings();
}

function postingreminder_is_installed()
{
    global $db, $mybb;
    if (isset($mybb->settings['postingreminder_day'])) {
        return true;
    }
    return false;
}

function postingreminder_uninstall()
{
    global $db;
    $db->delete_query('settings', "name IN('postingreminder_day', 'postingreminder_inplayID')");
    $db->delete_query('settinggroups', "name = 'postingreminder'");
    $db->delete_query("templates", "title IN('postingreminder', 'postingreminderCharacters', 'postingreminderScenes', 'postingreminderHeader')");
    rebuild_settings();
}

function postingreminder_activate()
{ }

function postingreminder_deactivate()
{ }

//Benachrichtung bei inaktiver Szene
$plugins->add_hook('global_intermediate', 'postingreminder_alert');
function postingreminder_alert()
{
    global $db, $mybb, $templates, $postingreminderAlert;

    $today = new DateTime(date("Y-m-d", time())); //heute
    $timeForApplication = intval($mybb->settings['postingreminder__time']);
    

    eval("\$postingreminderAlert .= \"" . $templates->get("postingreminderAlert") . "\";");
}

//Übersicht über inaktive Szenen
$plugins->add_hook('global_intermediate', 'postingreminder_notifications');
function postingreminder_notifications()
{
    global $db, $mybb, $templates, $lang, $characterOpenScenes, $header_postingreminder;
    require_once "inc/datahandlers/postingreminder.php";
    $prHandler = new postingreminderHandler();
    $lang->load('postingreminder');

    if($mybb->user['uid'] == 0) return;

    $characterOpenScenes = $lang->sprintf($lang->postingreminder_explanation, intval($mybb->settings['postingreminder_day']));
    $allCharacters = $prHandler->getAllCharacters($mybb->user['uid']);
    foreach ($allCharacters as $character) {
        $scenes = $prHandler->getInactiveScenesFrom($character);
        if(!empty($scenes)){
            $openScenes = '';
            $characterName = get_user($character)['username'];
            foreach($scenes as $scene){
                $thread = get_thread($scene);
                $sceneLink = '<a href="/showthread.php?tid='. $thread['tid'] .'">'. $thread['subject'] .'</a>';
                $lastPost = date('d.m.Y', $thread['lastpost']);
                eval("\$openScenes .= \"" . $templates->get("postingreminderScenes") . "\";");
            }
            if($openScenes == ''){
                $characterOpenScenes .= $lang->postingreminder_noOpenScenes;
            }
            eval("\$characterOpenScenes .= \"" . $templates->get("postingreminderCharacters") . "\";");
            eval("\$header_postingreminder .= \"" . $templates->get("postingreminderHeader") . "\";");
        }
    }  

    if($characterOpenScenes == $lang->sprintf($lang->postingreminder_explanation, intval($mybb->settings['postingreminder_day']))){
        $characterOpenScenes .= '<center>'. $lang->postingreminder_noOpenScenes .'</center>';
    }
}

$plugins->add_hook("admin_tools_menu", "postingreminder_tools_menu");
function postingreminder_tools_menu($sub_menu) {
    $ctr = 0;

	while(true){
		if($sub_menu[$ctr] == null) {
			$sub_menu[$ctr] = array(
				'id'	=> 'postingreminder',
				'title'	=> 'Posting-Erinnerung',
				'link'	=> 'index.php?module=tools-postingreminder'
			);
            return $sub_menu;
		} else {
			$ctr++;
		}
    }
}

$plugins->add_hook("admin_tools_action_handler", "postingreminder_tools_action_handler");
function postingreminder_tools_action_handler($actions) {
	$actions['postingreminder'] = array('active' => 'postingreminder', 'file' => 'postingreminder.php');
	return $actions;
}