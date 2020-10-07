<?php
if (!defined("IN_MYBB")) die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

require_once MYBB_ROOT . "/inc/datahandlers/postingreminder.php";

// Navigation erstellen
$page->output_header("Posting-Erinnerung Administration");

$sub_tabs['postingreminder'] = array(
    'title' => 'Inaktive Szenen',
    'link' => 'index.php?module=tools-postingreminder',
    'description' => 'Schau dir die inaktiven Szenen an'
);

$sub_tabs['postingreminder_update'] = array(
    'title' => 'Update',
    'link' => 'index.php?module=tools-postingreminder&action=update',
    'description' => 'Update das Plugin'
);

if (!$mybb->input['action']) {
    $page->output_nav_tabs($sub_tabs, 'postingreminder');

    // format Table
    $form_container = new FormContainer('Inaktive Szenen');
    $form_container->output_row_header('Szenenname');
    $form_container->output_row_header('User, der posten muss');
    $form_container->output_row_header('letzter Post');

    $prHandler = new postingreminderHandler();
    $scenes = $prHandler->getAllInactiveScenes();

    foreach ($scenes as $tid => $uid) {
        $user = get_user($uid);
        $thread = get_thread($tid);
        $form_container->output_cell("<a href=\"{$mybb->settings['bburl']}/showthread.php?tid={$thread['tid']}\" target=\"blank\">{$thread['subject']}</a>");
        $form_container->output_cell($user['username']);
        $form_container->output_cell(date('d.m.Y', $thread['lastpost']));
        $form_container->construct_row();
    }

    // Keine inaktiven Szenen?
    if (empty($scenes)) {
        $form_container->output_cell("<center>Keine inaktiven Szenen gefunden!</center>", array("colspan" => 3));
        $form_container->construct_row();
    }
    $form_container->end();
    $page->output_footer();
}

// Update
if ($mybb->input['action'] == 'update') {
    $page->output_nav_tabs($sub_tabs, 'postingreminder_update');

    if ($_POST['postingreminder_update'] == 'Update') {
        verify_post_check($mybb->get_input('my_post_key'));

        // update old settings
        $db->update_query('settings', array('optionscode' => 'forumselect', 'description' => 'Wähle deine Inplay-Bereiche aus'), 'title = "Inplay-ID"');

        // new userfield
        $db->add_column('users', 'postingreminder_hide_alert', 'date default NULL');

        // template
        $db->update_query('templates', array('template' => '<div class="red_alert">{$lang->postingreminder_inactiveScenes}<a href="/postingreminder.php?seen=1" title="Nicht mehr anzeigen"><span style="font-size: 14px;margin-top: -2px;float:right;">✕</span></a></div>'), 'title = "postingreminderHeader"');

        // add new settings
        $setting_array = array(
            'postingreminder_ice' => array(
                'title' => 'Eiszeit Profilfeld',
                'description' => 'Wie lautet die FID von deinem Eiszeit Profilfeld? (-1 = wird nicht genutzt)',
                'optionscode' => 'numeric',
                'value' => '-1', // Default
                'disporder' => 4
            ),'postingreminder_banner' => array(
                'title' => 'Banner Erinnerung',
                'description' => 'Nach wie vielen Tagen soll der Banner dem User wieder angezeigt werden?',
                'optionscode' => 'numeric',
                'value' => '7', // Default
                'disporder' => 5
            )
        );
        foreach ($setting_array as $name => $setting) {
            $setting['name'] = $name;
            $setting['gid'] = $db->fetch_array($db->simple_select('settinggroups', 'gid', 'name = "postingreminder"'))['gid'];

            $db->insert_query('settings', $setting);
        }
        flash_message('Das Update wurde erfolgreich durchgeführt!', 'success');
    }

    // format Table
    $form = new Form('index.php?module=tools-postingreminder&action=update', 'post');
    $form_container = new FormContainer('Plugin aktualisieren');
    $form_container->output_row_header('Plugin');
    $form_container->output_row_header('Update');
    $form_container->output_cell('Posting-Reminder Plugin');
    if ($db->field_exists('postingreminder_hide_alert', 'users')) { //update durchgeführt
        $form_container->output_cell('Du bist bereits auf den aktuellen Stand');
    } else {
    $form_container->output_cell($form->generate_submit_button('Update', array('name' => 'postingreminder_update')));
    }
    $form_container->construct_row();
    $form_container->end();
    $form->end();
    $page->output_footer();
}
