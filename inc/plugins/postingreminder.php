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
        "version"        => "2.0.2",
        "compatibility" => "18*"
    );
}

function postingreminder_install()
{
    global $db, $mybb;

    $db->add_column('users', 'postingreminder_hide_alert', 'date default NULL');

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
            'optionscode' => 'numeric',
            'value' => 30,
            'disporder' => 1
        ),
        'postingreminder_groups'    => array(
            'title'            => 'Ausgeschlossene Gruppen',
            'description'    => 'Welche Gruppen (primäre Nutzergruppe) sollen ausgeschlossen werden?',
            'optionscode'    => ($mybb->version_code >= 1800 ? 'groupselect' : 'text'),
            'value'            =>    '',
            'disporder' => 2
        ),'postingreminder_ice' => array(
            'title' => 'Eiszeit Profilfeld',
            'description' => 'Wie lautet die FID von deinem Eiszeit Profilfeld? (-1 = wird nicht genutzt)',
            'optionscode' => 'numeric',
            'value' => -1, 
            'disporder' => 3
        ),'postingreminder_banner' => array(
            'title' => 'Banner Erinnerung',
            'description' => 'Nach wie vielen Tagen soll der Banner dem User wieder angezeigt werden?',
            'optionscode' => 'numeric',
            'value' => 7, 
            'disporder' => 4
        )
    );

    foreach ($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }

    $templategroup = array(
        'prefix' => 'postingreminder',
        'title' => $db->escape_string('Posting-Erinnerung'),
    );

    $db->insert_query("templategroups", $templategroup);

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
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //Template postingreminderCharacters bauen
    $insert_array = array(
        'title'        => 'postingreminder_characters',
        'template'    => $db->escape_string('<table width="100%">
        <tr>
            <td class="thead" colspan="2">{$characterName}</td>
        </tr>
         {$openScenes}
        </table>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //Template postingreminderCharacters bauen
    $insert_array = array(
        'title'        => 'postingreminder_scenes',
        'template'    => $db->escape_string('<tr>
        <td width="70%">{$sceneLink}</td> 
        <td width="30%">{$lastPost}</td>
    </tr>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //Template postingreminderHeader bauen
    $insert_array = array(
        'title'        => 'postingreminder_header',
        'template'    => $db->escape_string('<div class="red_alert">{$lang->postingreminder_inactiveScenes}<a href="/postingreminder.php?seen=1" title="Nicht mehr anzeigen"><span style="font-size: 14px;margin-top: -2px;float:right;">✕</span></a></div>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);


    rebuild_settings();
}

function postingreminder_is_installed()
{
    global $mybb;
    if (isset($mybb->settings['postingreminder_day'])) {
        return true;
    }
    return false;
}

function postingreminder_uninstall()
{
    global $db;
    if($db->field_exists('postingreminder_hide_alert', 'users')) $db->drop_column('users', 'postingreminder_hide_alert');
    $db->delete_query('settings', "name LIKE 'postingreminder_%'");
    $db->delete_query('settinggroups', "name = 'postingreminder'");
    $db->delete_query("templates", "title LIKE 'postingreminder%'");
    rebuild_settings();
}

function postingreminder_activate()
{
}

function postingreminder_deactivate()
{
}

//Übersicht über inaktive Szenen
$plugins->add_hook('global_intermediate', 'postingreminder_notifications');
function postingreminder_notifications()
{
    global $mybb, $templates, $lang, $characterOpenScenes, $header_postingreminder;
    require_once "inc/datahandlers/postingreminder.php";
    $prHandler = new postingreminderHandler();
    $lang->load('postingreminder');

    if ($mybb->user['uid'] == 0 || $prHandler->hideBanner($mybb->user['uid'])) return;

    $characterOpenScenes = $lang->sprintf($lang->postingreminder_explanation, intval($mybb->settings['postingreminder_day']));
    $allCharacters = $prHandler->getAllCharacters($mybb->user['uid']);
    foreach ($allCharacters as $character) {
        $scenes = $prHandler->getInactiveScenesFrom($character);
        if (!empty($scenes)) {
            $openScenes = '';
            $characterName = get_user($character)['username'];
            foreach ($scenes as $scene) {
                $thread = get_thread($scene);
                $sceneLink = '<a href="https://test.beforestorm.de/test/showthread.php?tid=' . $thread['tid'] . '">' . $thread['subject'] . '</a>';
                $lastPost = date('d.m.Y', $thread['lastpost']);
                eval("\$openScenes .= \"" . $templates->get("postingreminder_scenes") . "\";");
            }
            if ($openScenes == '') $characterOpenScenes .= $lang->postingreminder_noOpenScenes;
            eval("\$characterOpenScenes .= \"" . $templates->get("postingreminder_characters") . "\";");
            eval("\$header_postingreminder = \"" . $templates->get("postingreminder_header") . "\";");
        }
    }

    if ($characterOpenScenes == $lang->sprintf($lang->postingreminder_explanation, intval($mybb->settings['postingreminder_day']))) {
        $characterOpenScenes .= '<center>' . $lang->postingreminder_noOpenScenes . '</center>';
    }
}

$plugins->add_hook("admin_tools_menu", "postingreminder_tools_menu");
function postingreminder_tools_menu($sub_menu)
{
    $ctr = 0;

    while (true) {
        if ($sub_menu[$ctr] == null) {
            $sub_menu[$ctr] = array(
                'id'    => 'postingreminder',
                'title'    => 'Posting-Erinnerung',
                'link'    => 'index.php?module=tools-postingreminder'
            );
            return $sub_menu;
        } else {
            $ctr++;
        }
    }
}

$plugins->add_hook("admin_tools_action_handler", "postingreminder_tools_action_handler");
function postingreminder_tools_action_handler($actions)
{
    $actions['postingreminder'] = array('active' => 'postingreminder', 'file' => 'postingreminder.php');
    return $actions;
}
