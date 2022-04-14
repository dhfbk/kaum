<?php

header("Content-Type: application/json; charset=UTF-8");

header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

use Ds\Map;

// CORS stuff
// https://stackoverflow.com/questions/53298478/has-been-blocked-by-cors-policy-response-to-preflight-request-doesn-t-pass-acce
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    http_response_code(200);
    exit();
}

http_response_code(500);

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

use Fpdf\Fpdf;

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
                $ret['session_id'] = session_id();
                break;
            }
            else {
                dieWithError("Invalid admin login", 401);
            }
        }
        else {
            $username = addslashes($username);
            $query = "SELECT u.*, p.disabled
                FROM users u
                LEFT JOIN projects p ON u.project = p.id
                WHERE u.username = '$username' AND u.educator = '1'
                    AND p.deleted = '0' AND u.deleted = '0'";
            $result = $mysqli->query($query);
            if (!$result->num_rows) {
                dieWithError("User " . $username . " does not exist", 401);
            }
            $row = $result->fetch_array(MYSQLI_ASSOC);
            if ($row['password'] != md5($password)) {
                dieWithError("Invalid password", 401);
            }
            checkProject($row['project'], $row['id']);
            $_SESSION['Admin'] = false;
            $_SESSION['Login'] = $row['id'];
            $ret['session_id'] = session_id();
        }
        break;

    case "userinfo":
        checkLogin();
        $ret['options'] = loadOptions(true);
        $ret['data'] = ["id" => $_SESSION['Login']];
        $ret['admin'] = false;
        if (isAdmin()) {
            $ret['admin'] = true;
        }
        else {
            $Row = find("users", $_SESSION['Login'], "Unable to find user");
            $ret['data']['project'] = $Row['project'];
        }
        break;

    // PROJECTS

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

    case "projectConfirm":
        checkAdmin();
        $Row = find("projects", $_REQUEST['id'], "Unable to find project");
        if (!$Row['data']['downloadedPasswords']) {
            dieWithError("You need to download the passwords before confirming a project");
        }
        $query = "UPDATE users SET password = MD5(password)
            WHERE project = '{$Row['id']}' AND educator = '1'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        $query = "UPDATE projects SET confirmed = '1' WHERE id = '${Row['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        break;

    case "projectPasswords":
        checkAdmin();
        $Row = find("projects", $_REQUEST['id'], "Unable to find project");
        if ($Row['confirmed']) {
            dieWithError("Project already confirmed, you cannot download the passwords anymore");
        }

        $Row['data']['downloadedPasswords'] = true;
        $dataJson = addslashes(json_encode($Row['data']));
        $query = "UPDATE projects SET data = '$dataJson' WHERE id = '${Row['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }


        $pdf = new Fpdf();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Image('http://dh-hetzner.fbk.eu:8080/img/logo_kidactions4horizontal.png', null, null, 50);
        $pdf->Ln();
        $pdf->Cell(40, 15, "Passwords for project: " . $Row['name']);
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(60, 10, "Username", 1, 0, 'L');
        $pdf->Cell(60, 10, "Name", 1, 0, 'L');
        $pdf->Cell(50, 10, "Password", 1, 0, 'L');
        $pdf->Ln();
        $query = "SELECT *
            FROM users
            WHERE project = '{$Row['id']}' AND educator = '1' AND deleted = '0'";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $data = json_decode($row['data'], true);
            $pdf->Cell(60, 10, $row['username'], 1, 0, 'L');
            $pdf->Cell(60, 10, $data['name'], 1, 0, 'L');
            $pdf->Cell(50, 10, $row['password'], 1, 0, 'L');
            $pdf->Ln();
        }
        $pdf->Output();
        exit();
        break;

    case "projectToggleAvailability":
        checkAdmin();
        $Row = find("projects", $_REQUEST['id'], "Unable to find project");
        $query = "UPDATE projects SET disabled = NOT disabled WHERE id = '${Row['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        break;

    case "projectAdd":
        checkAdmin();
        // missing validation
        $orig_data = $_REQUEST['info'];

        validate($orig_data, [
            'name' => 'required|min:' . $Options['project_name_minlength'],
            'educators' => 'required|numeric|min:1|max:' . $Options['project_max_educators'],
            // 'students' => 'required|numeric|min:1|max:' . $Options['project_max_students'],
            // 'passwords' => 'required|in:trivial,easy,difficult'
        ]);

        $data = ["name" => $orig_data['name'], "data" => json_encode($orig_data)];
        $query = queryinsert("projects", $data);
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        $projectID = $mysqli->insert_id;
        for ($i = 0; $i < $orig_data['educators']; $i++) {
            $data = [];
            $data['project'] = $projectID;
            $data['username'] = "pr" . $projectID . "-educator" . ($i + 1);
            $data['password'] = password_generate(8);
            $data['educator'] = 1;
            $data['data'] = json_encode(["name" => "", "disabled" => false]);

            $query = queryinsert("users", $data);
            $result = $mysqli->query($query);
            if (!$result) {
                dieWithError($mysqli->error);
            }
        }
        // $ret['data'] = $data;
        // $ret['query'] = $query;
        // $ret['result'] = print_r($result, true);
        break;

    case "projectInfo":
        checkLogin();
        $info = checkProject($_REQUEST['id']);

        $educators = [];
        $query = "SELECT * FROM users
            WHERE project = '{$info['id']}' AND deleted = '0' AND educator = '1'";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $educator = json_decode($row['data'], true);
            $educator['username'] = $row['username'];
            $educator['id'] = $row['id'];
            $educators[] = $educator;
        }

        $info['educators'] = $educators;

        $tasks = [];
        $info['tasks'] = $tasks;

        $ret['info'] = $info;
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
http_response_code(200);
echo json_encode($ret);
