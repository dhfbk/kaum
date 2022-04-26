<?php

header("Content-Type: application/json; charset=UTF-8");

header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

use Ds\Map;
use Ds\Set;

// CORS stuff
// https://stackoverflow.com/questions/53298478/has-been-blocked-by-cors-policy-response-to-preflight-request-doesn-t-pass-acce
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    http_response_code(200);
    exit();
}

http_response_code(500);
date_default_timezone_set("Europe/Rome");

ob_start();

$script_uri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}";

$request_body = file_get_contents('php://input');
if ($request_body) {
    // print_r(substr($request_body, 0, 1000));
    $payload = json_decode($request_body, true);
    if ($payload) {
        $_REQUEST = array_merge($payload, $_REQUEST);
    }
}

// if ($_REQUEST['sub'] == "add") {
//     echo strlen($request_body)."\n";
//     print_r($_REQUEST);
//     print_r($_POST);
//     print_r($_FILES);
//     // print_r($request_body);
//     exit();
// }

if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) {
    session_id($_REQUEST['session_id']);
}
session_start();

require_once('vendor/autoload.php');
require_once("config.php");
require_once("include.php");

use Fpdf\Fpdf;

$Action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
$CheckOnly = isset($_REQUEST['check_only']) && $_REQUEST['check_only'] ? true : false;
$Options = $_SESSION['Options'];

$TaskTypes = loadSimpleTsv($Options['task_types']);
foreach ($TaskTypes as $index => $taskName) {
    @include("tasks/" . $index . "/_common.php");
}

$ret = [];
$ret['result'] = "OK";
// $ret = $Options;

// $pwList = explode("\n", $Options['nouns_for_passwords']);
// $pwList = array_map("trim", $pwList);
// $password_indexes = array_rand($pwList, 10);

// print_r($pwList);
// print_r($password_indexes);
// exit();

switch ($Action) {
    case "userLogin":
        $username = checkField($_REQUEST['username'], "Missing username parameter");
        $password = checkField($_REQUEST['password'], "Missing password parameter");
        $username = addslashes($username);

        $query = "SELECT * FROM users u
            WHERE u.username = '{$username}' AND u.deleted = 0";
        $result = $mysqli->query($query);
        if (!$result->num_rows) {
            dieWithError("User " . $username . " does not exist", 401);
        }
        $row = $result->fetch_array(MYSQLI_ASSOC);
        if ($row['password'] != $password) {
            dieWithError("Invalid password", 401);
        }
        checkProject($row['project'], $row['id']);
        checkTaskAvailability($row['task']);
        $_SESSION['Admin'] = false;
        $_SESSION['StudentLogin'] = $row['id'];

        @include("tasks/" . $InputData['type'] . "/_login.php");

        $ret['session_id'] = session_id();
        break;

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
                WHERE u.username = '{$username}' AND u.educator = '1'
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
        $ret['options']['max_size'] = file_upload_max_size();
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
        $ProjectID = $Row['id'];
        $query = "UPDATE users SET password = MD5(password)
            WHERE project = '{$ProjectID}' AND educator = '1'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        $query = "UPDATE projects SET confirmed = '1' WHERE id = '{$ProjectID}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        foreach ($TaskTypes as $index => $taskName) {
            @include("tasks/" . $index . "/_confirmProject.php");
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
        $pdf->Image('img/logo_kidactions4horizontal.png', null, null, 50);
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

    case "taskPasswords":
        // print_r($_REQUEST);
        $Row = checkTask($_REQUEST['id'], 0, $_REQUEST['project_id']);

        $pdf = new Fpdf();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Image('img/logo_kidactions4horizontal.png', null, null, 50);
        $pdf->Ln();
        $pdf->Cell(40, 15, "Passwords for task: " . $Row['name']);
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(60, 10, "Username", 1, 0, 'L');
        $pdf->Cell(60, 10, "Name", 1, 0, 'L');
        $pdf->Cell(50, 10, "Password", 1, 0, 'L');
        $pdf->Ln();
        $query = "SELECT *
            FROM users
            WHERE task = '{$Row['id']}' AND educator = '0' AND deleted = '0'";
        // echo $query;
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

    case "taskToggleAvailability":
        $RowTask = checkTask($_REQUEST['id']);
        $query = "UPDATE tasks SET disabled = NOT disabled WHERE id = '${RowTask['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        $RowTask = checkTask($_REQUEST['id']);
        @include("tasks/" . $RowTask['tool'] . "/_taskToggleAvailability.php");
        break;

    case "projectToggleAvailability":
        checkAdmin();
        $Row = find("projects", $_REQUEST['id'], "Unable to find project");
        $query = "UPDATE projects SET disabled = NOT disabled WHERE id = '${Row['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        $tasks = getTasks($Row['id']);
        foreach($tasks as $task) {
            $RowTask = $task;
            @include("tasks/" . $task['tool'] . "/_taskToggleAvailability.php");
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

        $ProjectID = $mysqli->insert_id;

        // needed for tasks inclusions
        $Users = [];

        for ($i = 0; $i < $orig_data['educators']; $i++) {
            $data = [];
            $data['project'] = $ProjectID;
            $data['username'] = "pr" . $ProjectID . "-educator" . ($i + 1);
            $data['password'] = password_generate(8);
            $data['educator'] = 1;
            $data['data'] = json_encode([
                "name" => "",
                "email" => "",
                "disabled" => false
            ]);
            $Users[] = $data;

            $query = queryinsert("users", $data);
            $result = $mysqli->query($query);
            if (!$result) {
                dieWithError($mysqli->error);
            }
        }

        foreach ($TaskTypes as $index => $taskName) {
            @include("tasks/" . $index . "/_addProject.php");
        }
        // $ret['data'] = $data;
        // $ret['query'] = $query;
        // $ret['result'] = print_r($result, true);
        break;

    case "projectInfo":
        checkLogin();
        $info = checkProject($_REQUEST['id']);

        $educators = [];
        $students = [];
        $query = "SELECT * FROM users
            WHERE project = '{$info['id']}' AND deleted = '0'";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $user = json_decode($row['data'], true);
            $user['username'] = $row['username'];
            $user['id'] = $row['id'];
            if ($row['educator']) {
                $educators[] = $user;
            }
            else {
                if (!array_key_exists($row['task'], $students)) {
                    $students[$row['task']] = 0;
                }
                $students[$row['task']]++;
            }
        }

        $info['educators'] = $educators;
        $info['tasks'] = getTasks($info['id'], $students);

        $ret['info'] = $info;
        break;

    case "cleanOptions":
        unset($_SESSION['Options']);
        break;

    case "logout":
        unset($_SESSION['Login']);
        unset($_SESSION['Admin']);
        break;

    case "taskTypes":
        $ret['types'] = $TaskTypes;
        break;

    case "task":
        $InputData = $_REQUEST;
        validate($InputData, [
            'sub' => 'required|alpha_num',
        ]);

        // @include("tasks/" . $InputData['type'] . "/_common.php");

        switch ($InputData['sub']) {
            case "changeUserName":
                $Username = addslashes($_REQUEST['pk']);
                $query = "SELECT * FROM users
                    WHERE username = '{$Username}' AND deleted = '0'";
                $result = $mysqli->query($query);
                $RowUser = $result->fetch_array(MYSQLI_ASSOC);
                $UserData = json_decode($RowUser['data'], true);
                if (!$RowUser) {
                    dieWithError("User {$Username} does not exist");
                }

                $Row = [];
                if ($RowUser['educator']) {
                    $Row = checkProject($RowUser['project']);
                    if (!isAdmin() && $RowUser['id'] != $_SESSION['Login']) {
                        dieWithError("Unauthorized operation", 401);
                    }
                }
                else {
                    $Row = checkTask($RowUser['task'], 0, $RowUser['project']);
                }

                $NewValue = $_REQUEST['value'];
                if (strlen($NewValue) > $Options['max_user_name_len']) {
                    dieWithError("Name length cannot exceed {$Options['max_user_name_len']} chars");
                }

                $UserData['name'] = $NewValue;
                $dataJson = addslashes(json_encode($UserData));
                $query = "UPDATE users SET data = '$dataJson' WHERE id = '{$RowUser['id']}'";
                $mysqli->query($query);

                if ($RowUser['educator']) {
                    foreach ($TaskTypes as $index => $taskName) {
                        @include("tasks/" . $index . "/_changeUserName.php");
                    }                    
                }
                else {
                    @include("tasks/" . $Row['tool'] . "/_changeUserName.php");
                }

                // $ret['username'] = $_REQUEST['pk'];
                // $Row = checkTask($_REQUEST['id'], 0, $_REQUEST['project_id']);
                // dieWithError("Cacca");
                // print_r($_REQUEST);
                // $ret['ret'] = $_REQUEST;
                break;

            case "info":
                $Row = checkTask($_REQUEST['id'], 0, $_REQUEST['project_id']);
                $ret['info'] = $Row;
                $ret['info']['students'] = [];
                $query = "SELECT * FROM users
                    WHERE task = '{$Row['id']}' AND educator = '0' AND deleted = '0'";
                $result = $mysqli->query($query);
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $row['data'] = json_decode($row['data'], true);
                    $ret['info']['students'][] = $row;
                }
                // $ret['project_info'] = checkProject($)
                break;

            case "add":

                // TODO: depending on project, check that the type is active

                validate($InputData, [
                    'type' => 'required|in:' . implode(",", array_keys($TaskTypes)),
                ]);

                $projectInfo = checkProject($_REQUEST['project_id']);
                $ProjectID = $projectInfo['id'];
                $Info = json_decode($InputData['info'], true);
                validate($Info, [
                    'name' => 'required|min:' . $Options['task_name_minlength'],
                    'students' => 'required|numeric|min:1|max:' . $Options['task_max_students'],
                    'passwords' => 'required|in:trivial,easy,difficult'
                ]);
                $Info['time']['start_date'] = date("d/m/Y", strtotime($Info['time']['start_date']));
                $Info['time']['end_date'] = date("d/m/Y", strtotime($Info['time']['end_date']));
                if ($Info['automatic_timing']) {
                    validate($Info['time'], [
                        'afternoon_from' => 'required|date:H:i',
                        'morning_from' => 'required|date:H:i',
                        'afternoon_to' => 'required|date:H:i',
                        'morning_to' => 'required|date:H:i',
                        'start_date' => 'required|date:d/m/Y',
                        'end_date' => 'required|date:d/m/Y',
                        'days' => 'required|min:1'
                    ]);
                    if (!$Info['time']['use_morning'] && !$Info['time']['use_afternoon']) {
                        dieWithError("You must select at least a time interval");
                    }
                    if ($Info['time']['use_morning']) {
                        if (
                            intval(str_replace(":", "", $Info['time']['morning_from']))
                            >=
                            intval(str_replace(":", "", $Info['time']['morning_to']))
                        ) {
                            dieWithError("Incompatible morning times");
                        }
                    }
                    if ($Info['time']['use_afternoon']) {
                        if (
                            intval(str_replace(":", "", $Info['time']['afternoon_from']))
                            >=
                            intval(str_replace(":", "", $Info['time']['afternoon_to']))
                        ) {
                            dieWithError("Incompatible afternoon times");
                        }
                    }
                    if ($Info['time']['use_morning'] && $Info['time']['use_afternoon']) {
                        if (
                            intval(str_replace(":", "", $Info['time']['morning_to']))
                            >
                            intval(str_replace(":", "", $Info['time']['afternoon_from']))
                        ) {
                            dieWithError("Incompatible times between intervals");
                        }
                    }

                    $start_date = DateTime::createFromFormat('d/m/Y', $Info['time']['start_date']);
                    $end_date = DateTime::createFromFormat('d/m/Y', $Info['time']['end_date']);
                    // $diff = $end_date->diff($start_date);
                    $diff = $start_date->diff($end_date);
                    if ($diff->invert) {
                        dieWithError("Wrong date order");
                    }
                    // $ret['diff'] = print_r($diff, true);
                    // check dates
                }

                // break;

                // if ($CheckOnly) {
                //     $ret['co'] = true;
                // }

                @include("tasks/" . $InputData['type'] . "/_validation.php");
                if ($CheckOnly) {
                    break;
                }

                $data = [
                    "project_id" => $ProjectID,
                    "name" => $Info['name'],
                    "tool" => $Info['type'],
                    "data" => json_encode($Info)
                ];
                $query = queryinsert("tasks", $data);
                $result = $mysqli->query($query);
                if (!$result) {
                    dieWithError($mysqli->error);
                }

                $TaskID = $mysqli->insert_id;

                $pwList = explode("\n", $Options['nouns_for_passwords']);
                $pwList = array_map("trim", $pwList);
                $password_indexes = array_rand($pwList, $Info['students']);
                for ($i = 0; $i < $Info['students']; $i++) {
                    $username = "t" . $TaskID . "-user" . ($i + 1);
                    $password = $username;
                    switch ($Info['passwords']) {
                        case "easy":
                            $password = $pwList[$password_indexes[$i]];
                            $n = mt_rand(0, 999);
                            $password .= str_pad($n, 3, '0', STR_PAD_LEFT);
                            break;

                        case "difficult":
                            $password = password_generate();
                            break;
                    }

                    $data = [
                        "project" => $ProjectID,
                        "task" => $TaskID,
                        "username" => $username,
                        "password" => $password,
                        "educator" => 0,
                        "data" => json_encode(["name" => "", "disabled" => false])
                    ];

                    $query = queryinsert("users", $data);
                    $result = $mysqli->query($query);
                }

                @include("tasks/" . $InputData['type'] . "/_add.php");

                $ret['id'] = $TaskID;
                // $ret['data'] = $Info;
                // $ret['files'] = $_FILES;
                break;

            default:
                validate($InputData, [
                    'type' => 'required|in:' . implode(",", array_keys($TaskTypes)),
                ]);
                @include("tasks/" . $InputData['type'] . "/_actions.php");
                break;
        }

        break;

    default:
        dieWithError("Invalid action");
}

// $ret['login_info'] = ['user' => $_SESSION['Login'], 'admin' => $_SESSION['Admin']];
// $ret['_session'] = $_SESSION;
http_response_code(200);
echo json_encode($ret);
