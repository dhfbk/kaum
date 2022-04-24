<?php

if (!$Users) {
    exit();
}

$ret['log'] = [];

\ATDev\RocketChat\Chat::setUrl(RC_URL);
$result = \ATDev\RocketChat\Chat::login("admin", $rcPassword);
if (!$result) {
    $error = \ATDev\RocketChat\Chat::getError();
    dieWithError($error);
}

foreach ($Users as $row) {
    $user = new \ATDev\RocketChat\Users\User();
    $user->setName($row['username']);
    $user->setEmail($row['username'] . "@kidactions.eu");
    $user->setUsername($row['username']);
    $user->setPassword($row['password']);

    $user->create();
    $ret['log'][] = "User {$row['username']} created successfully";
    $r = $user->setActiveStatus(false);
    if ($r) {
        $ret['log'][] = "User {$row['username']} deactivated";
    }
    else {
        $ret['log'][] = "Error in deactivating {$row['username']}: " . $user->getError();
    }
}
