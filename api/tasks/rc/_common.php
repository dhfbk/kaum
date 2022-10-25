<?php

$rcPassword = file_get_contents(RC_ADMIN_FILE);
$rcPassword = trim($rcPassword);

\ATDev\RocketChat\Chat::setUrl(RC_URL);
$result = \ATDev\RocketChat\Chat::login("admin", $rcPassword);
if (!$result) {
    $error = \ATDev\RocketChat\Chat::getError();
    dieWithError($error);
}

function rc_addEducatorsToChannel($ProjectID, $RoomID) {
    global $mysqli;

    $query = "SELECT * FROM users
        WHERE educator = '1' AND deleted = '0' AND project = '{$ProjectID}'";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $data = json_decode($row['data'], true);
        if ($data['disabled']) {
            continue;
        }

        $user = new \ATDev\RocketChat\Users\User($row['username']);
        $user->info();

        // $channelName = $data['type_info']['channel_name'];
        $group = new \ATDev\RocketChat\Groups\Group($RoomID);
        $i = $group->info();

        $r = $group->invite($user);
    }
}


function rc_setTeacherCanJoin($ProjectID, $groupID, &$ret) {
    global $mysqli;

    $query = "SELECT * FROM users
        WHERE project = '{$ProjectID}'
            AND educator = '1'
            AND deleted = '0'";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $user = new \ATDev\RocketChat\Users\User($row['username']);

        $group = new \ATDev\RocketChat\Groups\Group($groupID);
        $group->info();
        // TODO: try/catch
        $user->info();
        $group->invite($user);
        // $ret['log'][] = "User {$row['username']} added to group {$groupName}";
    }
}

function rc_createGroup($groupName, &$err) {
    try {
        $group = new \ATDev\RocketChat\Groups\Group();
        $group->setName($groupName);
        $result = $group->create();
        return $group->getGroupId();
    } catch (Exception $e) {
        // $ret['log'][] = "Error in creating group {$groupName}: " . $e->getMessage();
        $err = $e->getMessage();
    }
    return false;
}

function rc_confirmTask($TaskID, $ProjectID, &$Info, &$ret) {
    global $mysqli;

    if (isset($Info['type_info']['rc_groups']) && $Info['type_info']['rc_groups'] > 1) {
        $groupsInfo = [];

        if ($Info['type_info']['rc_uniqueScenario']) {
            for ($i = 1; $i <= $Info['type_info']['rc_groups']; $i++) {
                $thisGroup = [
                    "channel_name" => "t" . $TaskID . "-" . $Info['type_info']['channel_name'] . "-" . $i,
                    "description" => $Info['type_info']['description'],
                    "teacher_can_join" => !!$Info['type_info']['teacher_can_join']
                ];
                $groupsInfo[] = $thisGroup;
            }
        }
        else {
            foreach ($Info['type_info']['rc_scenario_groups'] as $scenarioGroup) {
                $groupNames[] = 
                $thisGroup = [
                    "channel_name" => "t" . $TaskID . "-" . $scenarioGroup['channel_name'],
                    "description" => $scenarioGroup['description'],
                    "teacher_can_join" => !!$scenarioGroup['teacher_can_join']
                ];
                $groupsInfo[] = $thisGroup;
            }
        }

        foreach ($groupsInfo as $key => $thisGroup) {

            // Create channel
            $groupID = rc_createGroup($thisGroup['channel_name'], $err);
            if ($err) return $err;
            $groupsInfo[$key]['channel_id'] = $groupID;
            $ret['log'][] = date("H:i:s")." - Group {$thisGroup['channel_name']} created successfully";

            // Add educators
            if ($thisGroup['teacher_can_join']) {
                rc_setTeacherCanJoin($ProjectID, $groupID, $ret);
            }
            else {
                $message = new \ATDev\RocketChat\Messages\Message();
                $message->setRoomId($thisGroup['channel_name']);
                $message->setEmoji(":sos:");
                $message->setText("Educators cannot join this conversation. If you send */sos* (slash + sos) in the chat, the educators are notified and are allowed to the chat. Other users are notified that someone asks for help, but the caller's identity is not revealed.");
                $result = $message->postMessage();
                $ret['log'][] = date("H:i:s")." - Initial message sent";
            }

        }

        $query = "SELECT * FROM users WHERE task = '$TaskID'";
        $result = $mysqli->query($query);
        $userGroups = [];
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            if (preg_match('/user([0-9]+)/', $row['username'], $ris)) {
                $groupIndex = $Info['type_info']['rc_user_groups'][$ris[1]] - 1;
                if ($groupIndex < 0) {
                    continue;
                }
                $groupID = $groupsInfo[$groupIndex]['channel_id'];
                $userGroups[$row['username']] = [
                    "channel_id" => $groupID,
                    "group_index" => $groupIndex
                ];
                $group = new \ATDev\RocketChat\Groups\Group($groupID);
                $group->info();

                $user = new \ATDev\RocketChat\Users\User();
                $user->setName($row['username']);
                $user->setEmail($row['username'] . "@kidactions.eu");
                $user->setUsername($row['username']);
                $user->setPassword($row['password']);

                $user->create();
                $ret['log'][] = date("H:i:s")." - User {$row['username']} created successfully";
                $group->invite($user);
                $ret['log'][] = date("H:i:s")." - User {$row['username']} added to group {$groupName}";
                $r = $user->setActiveStatus(false);
                if ($r) {
                    $ret['log'][] = date("H:i:s")." - User {$row['username']} deactivated";
                }
            }
        }

        // Send welcome message
        foreach ($groupsInfo as $key => $thisGroup) {
            $message = new \ATDev\RocketChat\Messages\Message();
            $message->setRoomId($thisGroup['channel_name']);
            // $message->setAlias("Kid Actions Admin");
            // $message->setEmoji(":house:");
            $message->setText($thisGroup['description']);
            $result = $message->postMessage();
        }

        $Info['type_info']['rc_groups_info'] = $groupsInfo;
        $Info['type_info']['rc_user_channels'] = $userGroups;
    }
    else {
        $groupName = "t" . $TaskID . "-" . $Info['type_info']['channel_name'];

        $groupID = rc_createGroup($groupName, $err);
        if ($err) return $err;
        $Info['type_info']['channel_id'] = $groupID;
        $ret['log'][] = date("H:i:s")." - Group {$groupName} created successfully";

        if ($Info['type_info']['teacher_can_join']) {
            rc_setTeacherCanJoin($ProjectID, $groupID, $ret);
        }
        else {
            $message = new \ATDev\RocketChat\Messages\Message();
            $message->setRoomId($groupName);
            $message->setEmoji(":sos:");
            $message->setText("Educators cannot join this conversation. If you send */sos* (slash + sos) in the chat, the educators are notified and are allowed to the chat. Other users are notified that someone asks for help, but the caller's identity is not revealed.");
            $result = $message->postMessage();
            $ret['log'][] = date("H:i:s")." - Initial message sent";
        }

        $group = new \ATDev\RocketChat\Groups\Group($groupID);
        $group->info();

        $query = "SELECT * FROM users WHERE task = '$TaskID'";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            try {
                $user = new \ATDev\RocketChat\Users\User();
                $user->setName($row['username']);
                $user->setEmail($row['username'] . "@kidactions.eu");
                $user->setUsername($row['username']);
                $user->setPassword($row['password']);

                $user->create();
                $ret['log'][] = date("H:i:s")." - User {$row['username']} created successfully";
                $group->invite($user);
                $ret['log'][] = date("H:i:s")." - User {$row['username']} added to group {$groupName}";
                $r = $user->setActiveStatus(false);
                if ($r) {
                    $ret['log'][] = date("H:i:s")." - User {$row['username']} deactivated";
                }
                // else {
                //     $ret['log'][] = "Error in deactivating {$row['username']}: " . $user->getError();
                // }
            } catch (Exception $e) {
                // echo $e->getMessage();
                // $ret['log'][] = "Error in creating user {$row['username']}: " . $e->getMessage();
            }
        }

        $message = new \ATDev\RocketChat\Messages\Message();
        $message->setRoomId($groupName);
        // $message->setAlias("Kid Actions Admin");
        // $message->setEmoji(":house:");
        $message->setText($Info['type_info']['description']);
        $result = $message->postMessage();

    }

    $dataJson = addslashes(json_encode($Info));
    $query = "UPDATE tasks SET data = '$dataJson' WHERE id = '{$TaskID}'";
    $mysqli->query($query);

}

function rc_channelNameIsWrong($channelName) {
    if (!preg_match('/^[a-z0-9-]+$/', $channelName)) {
        return "The channel name must include only lowercase letters, numbers and dashes ($channelName)";
    }

    if (strpos($channelName, "--") !== false) {
        return "The channel name cannot contain two consecutive dashes ($channelName)";
    }

    if ($channelName[0] == "-") {
        return "The channel name cannot begin with a dash ($channelName)";
    }

    if ($channelName[strlen($channelName) - 1] == "-") {
        return "The channel name cannot end with a dash ($channelName)";
    }

    return false;
}

function rc_updateEducatorAvailability($UserID) {
    global $mysqli;

    $query = "SELECT u.id, u.username, u.deleted, u.data,
        p.disabled project_disabled, p.deleted project_deleted, p.confirmed project_confirmed
    FROM `users` u
    LEFT JOIN projects p ON p.id = u.project
    WHERE educator = '1' AND u.id = '{$UserID}'";
    $result = $mysqli->query($query);
    if (!$result->num_rows) {
        dieWithError("User not found");
    }
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $data = json_decode($row['data'], true);
        $disabled = $data['disabled'] ? 1 : 0;
        $active = 1;
        $active = $active * (1 - $row['deleted']);
        $active = $active * (1 - $disabled);
        $active = $active * (1 - $row['project_disabled']);
        $active = $active * (1 - $row['project_deleted']);
        $active = $active * $row['project_confirmed'];

        $data['rc_enabled'] = $active ? true : false;

        $user = new \ATDev\RocketChat\Users\User($row['username']);
        $i = $user->info();
        $user->setActiveStatus($data['rc_enabled']);

        $dataJson = addslashes(json_encode($data));
        $query = "UPDATE users SET data = '$dataJson' WHERE id = '${row['id']}'";
        $mysqli->query($query);
    }
}