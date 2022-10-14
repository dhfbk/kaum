<?php

if (!$UserName) {
    exit();
}
// $UserID, $clearPassword

$ret['log'] = [];

$user = new \ATDev\RocketChat\Users\User();
$user->setName($UserName);
$user->setEmail($UserName . "@kidactions.eu");
$user->setUsername($UserName);
$user->setPassword($clearPassword);

$user->create();
$ret['log'][] = "User {$UserName} created successfully";
$r = $user->setActiveStatus(false);
if ($r) {
    $ret['log'][] = "User {$UserName} deactivated";
}
else {
    $ret['log'][] = "Error in deactivating {$UserName}: " . $user->getError();
}

rc_updateEducatorAvailability($UserID);

$query = "SELECT u.id user_id, t.id task_id, p.id project_id, t.data
    FROM users u
    LEFT JOIN projects p ON p.id = u.project
    RIGHT JOIN tasks t ON t.project_id = u.project
    WHERE u.id = '$UserID' AND t.tool = 'rc'";
$result = $mysqli->query($query);
while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
    $TaskData = json_decode($row['data'], true);
    $RoomID = $TaskData['type_info']['channel_id'];
    if ($TaskData['type_info']['teacher_can_join'] || count($TaskData['type_info']['sos_info'])) {
        $group = new \ATDev\RocketChat\Groups\Group($RoomID);
        $i = $group->info();
        $r = $group->invite($user);
        $ret['log'][] = "User {$UserName} added to group {$TaskData['type_info']['channel_name']}";
    }
}

