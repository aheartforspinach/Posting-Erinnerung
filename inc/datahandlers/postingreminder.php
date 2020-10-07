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

        array_push($returnArray, $mainUid);
        $query = $db->simple_select('users', 'uid', 'as_uid = ' . $mainUid);
        while ($result = $db->fetch_array($query)) {
            array_push($returnArray, (int)$result['uid']);
        }
        arsort($returnArray);
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
            if ($nextInRow != $uid || $user['fid'. $ice] == 'Ja') continue;
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

        $scenes = $db->simple_select('threads', 'tid, partners, lastposteruid, lastpost', 'find_in_set(fid, "' . $inplayIDs . '")', array('order_by' => 'lastpost', 'order_dir' => 'ASC'));
        while ($scene = $db->fetch_array($scenes)) {
            if ($date->getTimestamp() > $scene['lastpost'])
                $returnArray[$scene['tid']] = $this->getNextInRow(explode(',', $scene['partners']), $scene['lastposteruid']);
        }

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
    public function hideBanner($uid) {
        global $db;
        $today = date('Y-m-d', time());
        $expirationDate = $db->fetch_array($db->simple_select('users', 'postingreminder_hide_alert', 'uid = '. $uid))['postingreminder_hide_alert'];
        return $today <= $expirationDate ? true : false;
    }

    /**
     *
     * @return int with uid form user who must post next
     */
    private function getNextInRow($participants, $lastPosterUid)
    {
        $ctr = 0;
        $nextInRow = '';
        foreach ($participants as $participant) {
            if ($participant == $lastPosterUid) {
                if ($participants[$ctr + 1] == null) {
                    $nextInRow = $participants[0];
                } else {
                    $nextInRow = $participants[$ctr + 1];
                }
            }
            $ctr++;
        }
        return $nextInRow;
    }
}
