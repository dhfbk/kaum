<?php

if (!$TaskID) {
    exit();
}

// $ret['dis'][$TaskID] = [];
// $ret['dis'][$TaskID]['users'] = [];
// $ret['dis'][$TaskID]['rc_users'] = [];

$query = "SELECT u.id, u.username, u.deleted, u.data,
    t.disabled task_disabled, t.deleted task_deleted,
    t.closed task_closed, t.confirmed task_confirmed,
    p.disabled project_disabled, p.deleted project_deleted, p.confirmed project_confirmed
FROM `users` u
LEFT JOIN tasks t ON t.id = u.task
LEFT JOIN projects p ON p.id = u.project
WHERE educator = '0' AND t.id = '{$TaskID}'";
// $ret['query'] = $query;
$result = $mysqli->query($query);
while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
    $data = json_decode($row['data'], true);
    $disabled = $data['disabled'] ? 1 : 0;
    $active = 1;
    $active = $active * (1 - $row['deleted']);
    $active = $active * (1 - $disabled);
    $active = $active * ($row['task_confirmed']);
    $active = $active * (1 - $row['task_disabled']);
    $active = $active * (1 - $row['task_closed']);
    $active = $active * (1 - $row['task_deleted']);
    $active = $active * (1 - $row['project_disabled']);
    $active = $active * (1 - $row['project_deleted']);
    $active = $active * $row['project_confirmed'];

    $data['rc_enabled'] = $active ? true : false;

    $user = new \ATDev\RocketChat\Users\User($row['username']);
    $i = $user->info();
    $user->setActiveStatus($data['rc_enabled']);

    // $ret['dis'][$TaskID]['rc_users'][$row['username']] = $i;

    $dataJson = addslashes(json_encode($data));
    $query = "UPDATE users SET data = '$dataJson' WHERE id = '${row['id']}'";
    $mysqli->query($query);

    // $ret['dis'][$TaskID]['users'][$row['username']] = $data;
}

// $ret['task_' . $RowTask['id']] = $RowTask;
// $ret['query_' . $RowTask['id']] = $query;
