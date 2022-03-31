<?php

header("Content-Type: application/json; charset=UTF-8");

header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// CORS stuff
// https://stackoverflow.com/questions/53298478/has-been-blocked-by-cors-policy-response-to-preflight-request-doesn-t-pass-acce
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    http_response_code(200);
    exit();
}

ob_start();

$script_uri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}";

$request_body = file_get_contents('php://input');
if ($request_body) {
    $payload = json_decode($request_body, true);
    $_REQUEST = array_merge($payload, $_REQUEST);
}

if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) {
    session_id($_REQUEST['session_id']);
}
session_start();

require_once('vendor/autoload.php');
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
                dieWithError("Invalid login", 401);
            }
        }
        else {

        }
        break;

    case "userinfo":
        checkLogin();
        $ret['options'] = loadOptions(true);
        $ret['admin'] = $_SESSION['Admin'];

        break;

    case "projectList":
        checkAdmin();
        $query = "SELECT * FROM projects WHERE deleted = '0' ORDER BY id DESC";
        $result = $mysqli->query($query);
        $ret['records'] = [];
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $row['data'] = json_decode($row['data']);
            $ret['records'][] = $row;
        }
        break;

    case "projectAdd":
        checkAdmin();
        // missing validation
        $data = $_REQUEST['info'];

        validate($data, [
            'name' => 'required|min:' . $Options['project_name_minlength'],
            'educators' => 'required|numeric|min:1|max:' . $Options['project_max_educators'],
            'students' => 'required|numeric|min:1|max:' . $Options['project_max_students'],
            'passwords' => 'required|in:trivial,easy,difficult'
            // 'passwords' => 'required|in:a,b,c'
        ]);

        $data = ["name" => $data['name'], "data" => json_encode($data)];
        $query = queryinsert("projects", $data);
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        // $ret['data'] = $data;
        // $ret['query'] = $query;
        // $ret['result'] = print_r($result, true);
        break;

    case "cleanOptions":
        unset($_SESSION['Options']);
        break;

    case "logout":
        unset($_SESSION['Login']);
        unset($_SESSION['Admin']);
        break;

    default:
        dieWithError("Invalid action");
}

// $ret['login_info'] = ['user' => $_SESSION['Login'], 'admin' => $_SESSION['Admin']];
// $ret['_session'] = $_SESSION;
echo json_encode($ret);
