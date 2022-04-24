<?php

if (!$ProjectID) {
    exit();
}

$ret['log'] = [];

\ATDev\RocketChat\Chat::setUrl(RC_URL);
$result = \ATDev\RocketChat\Chat::login("admin", $rcPassword);
if (!$result) {
    $error = \ATDev\RocketChat\Chat::getError();
    dieWithError($error);
}

$query = "SELECT * FROM users
    WHERE project = '{$ProjectID}'
        AND educator = '1'
        AND deleted = '0'";
$result = $mysqli->query($query);
while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
    $data = json_decode($row['data'], true);
    if ($data['disabled']) {
        continue;
    }
    $user = new \ATDev\RocketChat\Users\User($row['username']);

    // This needs to be here
    // TODO: try/catch
    $user->info();
    
    $r = $user->setActiveStatus(true);
    if ($r) {
        $ret['log'][] = "User {$row['username']} activated";
    }
    else {
        $ret['log'][] = "Error in activating {$row['username']}: " . $user->getError();
    }
}
