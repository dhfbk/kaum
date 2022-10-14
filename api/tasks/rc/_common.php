<?php

define('RC_ADMIN_FILE', "/var/run/secrets/rocketchat_secret");
define('RC_URL', "http://rocketchat:3000/chat");

$rcPassword = file_get_contents(RC_ADMIN_FILE);
$rcPassword = trim($rcPassword);

\ATDev\RocketChat\Chat::setUrl(RC_URL);
$result = \ATDev\RocketChat\Chat::login("admin", $rcPassword);
if (!$result) {
    $error = \ATDev\RocketChat\Chat::getError();
    dieWithError($error);
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