<?php

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
global $plugins;

class postingreminderHandler {

    /**
     *
     * @return array with all UID from one User
     */
    function getAllCharacters($uid) {
        global $db;
        $returnArray = array();
        $user = get_user($uid);
        if ($user['as_uid'] != 0) {
            $mainUid = $user['as_uid'];
        } else {
            $mainUid = $uid;
        }

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
    function getInactiveScenesFrom($uid){
        global $db, $mybb;
        $returnArray = array();
        $inplayID = intval($mybb->settings['postingreminder_inplayID']);
        $dayDifference = intval($mybb->settings['postingreminder_day']);

        $date = new DateTime(date("Y-m-d", time())); //heute
        date_sub($date, date_interval_create_from_date_string($dayDifference.'days'));

        $scenes = $db->simple_select('threads t join ' . TABLE_PREFIX . 'forums f on f.fid = t.fid', 't.lastpost, t.tid, t.subject', "find_in_set($inplayID, f.parentlist) and find_in_set($uid, t.partners)");
        while($scene = $db->fetch_array($scenes)){
            if($date->getTimestamp() > $scene['lastpost']){
                $user = get_user($uid);
                $allowed = true;
                $groups = explode(',', $mybb->settings['postingreminder_groups']);

                foreach($groups as $group){
                    if($group == $user['usergroup']){
                        $allowed = false;
                    }
                }
                if($allowed){
                    array_push($returnArray, $scene['tid']);
                }
            }
        }
        asort($returnArray);
        return $returnArray;
    }

    /**
     *
     * @return array with key -> tid and value -> uid
     */
    function getAllInactiveScenes(){
        global $db, $mybb;
        $returnArray = array();
        $inplayID = intval($mybb->settings['postingreminder_inplayID']);
        $dayDifference = intval($mybb->settings['postingreminder_day']);
        $date = new DateTime(date("Y-m-d", time())); //heute
        date_sub($date, date_interval_create_from_date_string($dayDifference.'days'));

        $scenes = $db->simple_select('threads t join ' . TABLE_PREFIX . 'forums f on f.fid = t.fid', 't.tid, t.partners, t.subject, t.lastposteruid, t.lastpost', "find_in_set($inplayID, f.parentlist)", array("order_by" => "lastpost","order_dir" => 'ASC'));
        while($scene = $db->fetch_array($scenes)){
            if($date->getTimestamp() > $scene['lastpost']){
                $nextInRow = '';
                $participants = explode(',', $scene['partners']);
                $ctr = 0;
                foreach($participants as $participant){
                    if($participant == $scene['lastposteruid']){
                        if($participants[$ctr + 1] == null){
                            $nextInRow = $participants[0];
                        }else{
                            $nextInRow = $participants[$ctr + 1];
                        }
                    }
                    $ctr++;
                }
                $returnArray[$scene['tid']] = $nextInRow;
            }
        }

        return $returnArray;
    }
}