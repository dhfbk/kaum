<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$script_uri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}";

if (isset($_REQUEST['session_id'])) {
    session_id($_REQUEST['session_id']);
}
session_start();

require_once("config.php");
require_once("include.php");

$Action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
$Options = $_SESSION['Options'];

$ret = [];
$ret['result'] = "OK";
// $ret = $Options;

switch ($Action) {
    case "login":
        $username = checkField($_REQUEST['username'], "Missing username parameter");
        $password = checkField($_REQUEST['password'], "Missing password parameter");

        if ($username == "admin") {
            if (isset($Options['admin_password']) && $Options['admin_password'] === md5($password)) {
                $_SESSION['Admin'] = true;
                $_SESSION['Login'] = -1;
                $ret['result'] = "OK";
                $ret['session_id'] = session_id();
                break;
            }
            else {
                dieWithError("Invalid login");
            }
        }
        else {

        }
        break;

    case "userinfo":
        checkLogin();

        break;

    case "logout":
        unset($_SESSION['Login']);
        unset($_SESSION['Admin']);
        break;

    default:
        dieWithError("Invalid action");
}

// $ret['login_info'] = ['user' => $_SESSION['Login'], 'admin' => $_SESSION['Admin']];
echo json_encode($ret);
