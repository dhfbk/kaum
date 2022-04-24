<?php

\ATDev\RocketChat\Chat::setUrl(RC_URL);
$result = \ATDev\RocketChat\Chat::login("admin", $rcPassword);
if (!$result) {
    $error = \ATDev\RocketChat\Chat::getError();
    dieWithError($error);
}

// $ret['log'] = [];
$group = null;
$groupName = "t" . $TaskID . "-" . $Info['type_info']['channel_name'];

try {
    $group = new \ATDev\RocketChat\Groups\Group();
    $group->setName($groupName);
    $result = $group->create();
    // $ret['log'][] = "Group {$groupName} created successfully";
} catch (Exception $e) {
    // $ret['log'][] = "Error in creating group {$groupName}: " . $e->getMessage();
    dieWithError($e->getMessage());
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
        // $ret['log'][] = "User {$row['username']} created successfully";
        $group->invite($user);
        // $ret['log'][] = "User {$row['username']} added to group {$groupName}";
        $r = $user->setActiveStatus(false);
        // if ($r) {
        //     $ret['log'][] = "User {$row['username']} deactivated";
        // }
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
$message->setText($Info['type_info']['description']);
$result = $message->postMessage();
