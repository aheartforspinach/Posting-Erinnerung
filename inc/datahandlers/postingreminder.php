<?php

if (!defined("IN_MYBB")) die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

global $plugins;

class postingreminderHandler
{
    /**
     *
     * @return array with all UID from one User
     */
    public function getAllCharacters($uid)
    {
        global $db;
        $returnArray = array();
        $user = get_user($uid);
        $mainUid = $user['as_uid'] != 0 ? $user['as_uid'] : $uid;

        $query = $db->simple_select('users', 'uid', 'as_uid = ' . $mainUid . ' or uid = ' . $mainUid, array('order_by' => 'username', 'order_dir' => 'ASC'));
        while ($result = $db->fetch_array($query)) array_push($returnArray, (int)$result['uid']);

        return $returnArray;
    }

    /**
     *
     * @return array with all inactive Scenes from one User
     */
    public function getInactiveScenesFrom($uid)
    {
        global $mybb;
        $returnArray = array();
        $groups = explode(',', $mybb->settings['postingreminder_groups']);
        $ice = intval($mybb->settings['postingreminder_ice']);
        $user = get_user($uid);
        if (in_array($user['usergroup'], $groups)) return array();

        foreach ($this->getAllInactiveScenes() as $tid => $nextInRow) {
            if ($nextInRow != $uid || $user['fid' . $ice] == 'Ja') continue;
            array_push($returnArray, $tid);
        }
        return $returnArray;
    }

    /**
     *
     * @return array with key -> tid and value -> uid
     */
    public function getAllInactiveScenes()
    {
        global $db, $mybb;
        $returnArray = array();
        $inplayIDs = $mybb->settings['postingreminder_inplayID'];
        $dayDifference = intval($mybb->settings['postingreminder_day']);
        $date = new DateTime(date("Y-m-d", time())); // heute
        date_sub($date, date_interval_create_from_date_string($dayDifference . 'days'));

        $scenes = $db->simple_select(
            'ipt_scenes s join ' . TABLE_PREFIX . 'threads t on s.tid = t.tid',
            't.tid, lastposteruid, lastpost',
            'visible = 1 and ' . $date->getTimestamp() . ' > lastpost and find_in_set(fid, "' . $inplayIDs . '")'
        );
        while ($scene = $db->fetch_array($scenes)) $returnArray[$scene['tid']] = $this->getNextInRow($scene['tid']);

        return $returnArray;
    }

    public function setUnseenBanner($uid)
    {
        global $db, $mybb;
        $expirationDate = new DateTime(date("Y-m-d", time())); // heute
        $expirationDate->add(date_interval_create_from_date_string(intval($mybb->settings['postingreminder_banner']) . 'days'));
        $update = array('postingreminder_hide_alert' => $expirationDate->format('Y-m-d'));
        return $db->update_query('users', $update, 'find_in_set(uid, "' . implode(",", $this->getAllCharacters($uid)) . '")');
    }

    /**
     *
     * @return boolean true -> hide; false -> don't hide
     */
    public function hideBanner($uid)
    {
        global $db;
        $today = date('Y-m-d', time());
        $expirationDate = $db->fetch_array($db->simple_select('users', 'postingreminder_hide_alert', 'uid = ' . $uid))['postingreminder_hide_alert'];
        return $today <= $expirationDate ? true : false;
    }

    /**
     *
     * @return int with uid form user who must post next
     */
    private function getNextInRow($tid)
    {
        global $db;
        $lastPosterUid = get_thread($tid)['lastposteruid'];
        $next = $db->fetch_array($db->simple_select('ipt_scenes_partners', 'spid', 'tid = ' . $tid . ' and uid = ' . $lastPosterUid))['spid'] + 1;
        $next_uid = $db->fetch_field($db->simple_select('ipt_scenes_partners', 'uid', 'spid = ' . $next), 'uid');
        if (empty($next_uid)) {
            $next_uid = $db->fetch_field($db->simple_select('ipt_scenes_partners', 'uid', 'tid = ' . $tid, ["order_by" => 'spid', "order_dir" => 'ASC', 'limit' => 1]), "uid");
        }
        return $next_uid;
    }
}
