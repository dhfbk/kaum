<?php

$rcPassword = file_get_contents(RC_ADMIN_FILE);
$rcPassword = trim($rcPassword);

\ATDev\RocketChat\Chat::setUrl(RC_URL);
$result = \ATDev\RocketChat\Chat::login("admin", $rcPassword);
if (!$result) {
    $error = \ATDev\RocketChat\Chat::getError();
    dieWithError($error);
}

function rc_confirmTask($TaskID, &$Info) {

    $group = null;
    $groupName = "t" . $TaskID . "-" . $Info['type_info']['channel_name'];

    try {
        $group = new \ATDev\RocketChat\Groups\Group();
        $group->setName($groupName);
        $result = $group->create();
        $Info['type_info']['channel_id'] = $group->getGroupId();
        $ret['log'][] = date("H:i:s")." - Group {$groupName} created successfully";
    } catch (Exception $e) {
        // $ret['log'][] = "Error in creating group {$groupName}: " . $e->getMessage();
        return $e->getMessage();
    }

    if ($Info['type_info']['teacher_can_join']) {
        $query = "SELECT * FROM users
            WHERE project = '{$ProjectID}'
                AND educator = '1'
                AND deleted = '0'";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $user = new \ATDev\RocketChat\Users\User($row['username']);

            // This needs to be here
            // TODO: try/catch
            $user->info();
            $group->invite($user);
            // $ret['log'][] = "User {$row['username']} added to group {$groupName}";
        }
    }

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

    if (!$Info['type_info']['teacher_can_join']) {
        $message = new \ATDev\RocketChat\Messages\Message();
        $message->setRoomId($groupName);
        // $message->setAlias("Kid Actions Admin");
        $message->setEmoji(":sos:");
        $message->setText("Educators cannot join this conversation. If you send */sos* (slash + sos) in the chat, the educators are notified and are allowed to the chat. Other users are notified that someone asks for help, but the caller's identity is not revealed.");
        $result = $message->postMessage();
        $ret['log'][] = date("H:i:s")." - Initial message sent";
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