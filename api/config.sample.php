<?php

$DB_HOST = "";
$DB_USERNAME = "";
$DB_PASSWORD = "";
$DB_NAME = "";

define('MYSQL_ADMIN_FILE', "/var/run/secrets/mysql_secret");
define('RC_ADMIN_FILE', "/var/run/secrets/rocketchat_secret");
define('RC_URL', "http://rocketchat:3000/chat");

/*

CREATE USER 'kaum'@'%' IDENTIFIED VIA mysql_native_password USING '***';
GRANT USAGE ON *.* TO 'kaum'@'%' REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;
CREATE DATABASE IF NOT EXISTS `kaum`;
GRANT ALL PRIVILEGES ON `kaum`.* TO 'kaum'@'%';

*/

