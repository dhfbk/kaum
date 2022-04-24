<?php

define('RC_ADMIN_FILE', "/var/run/secrets/rocketchat_secret");
define('RC_URL', "http://rocketchat:3000/chat");

$rcPassword = file_get_contents(RC_ADMIN_FILE);
$rcPassword = trim($rcPassword);
