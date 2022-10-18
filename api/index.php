<?php

ini_set("session.use_cookies", 0);

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

$ret = [];
$ret['result'] = "OK";

$TaskTypes = loadSimpleTsv($Options['task_types']);
foreach ($TaskTypes as $index => $taskName) {
    @include("tasks/" . $index . "/_common.php");
}

// $ret = $Options;

// $pwList = explode("\n", $Options['nouns_for_passwords']);
// $pwList = array_map("trim", $pwList);
// $password_indexes = array_rand($pwList, 10);

// print_r($pwList);
// print_r($password_indexes);
// exit();

switch ($Action) {
    case "userLogin":
        $username = @checkField($_REQUEST['username'], "Missing username parameter");
        $password = @checkField($_REQUEST['password'], "Missing password parameter");
        $username = addslashes($username);

        $query = "SELECT * FROM users u
            WHERE u.username = '{$username}' AND u.deleted = 0";
        $result = $mysqli->query($query);
        if (!$result->num_rows) {
            dieWithError("User " . $username . " does not exist", 401);
        }
        $RowUser = $result->fetch_array(MYSQLI_ASSOC);
        if ($RowUser['password'] != $password) {
            dieWithError("Invalid password", 401);
        }
        $RowProject = checkProject($RowUser['project'], $RowUser['id']);
        $RowTask = checkTaskAvailability($RowUser['task']);

        $_SESSION['Admin'] = false;
        $_SESSION['Login'] = 0;
        $_SESSION['StudentLogin'] = $RowUser['id'];

        @include("tasks/" . $RowTask['tool'] . "/_login.php");

        $ret['session_id'] = session_id();
        break;

    case "login":
        $username = @checkField($_REQUEST['username'], "Missing username parameter");
        $password = @checkField($_REQUEST['password'], "Missing password parameter");

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
            $RowUser = $result->fetch_array(MYSQLI_ASSOC);
            if ($RowUser['password'] != md5($password)) {
                dieWithError("Invalid password", 401);
            }
            checkProject($RowUser['project'], $RowUser['id']);
            $_SESSION['Admin'] = false;
            $_SESSION['Login'] = $RowUser['id'];

            foreach ($TaskTypes as $index => $taskName) {
                @include("tasks/" . $index . "/_educatorLogin.php");
            }

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

    case "userToggleAvailability":
        checkLogin();
        $RowUser = find("users", $_REQUEST['id'], "Unable to find user");
        $continue = false;
        if (isAdmin()) {
            $continue = true;
        }
        else {
            if (!$RowUser['educator']) {
                $info = checkProject($RowUser['project']);
                $continue = true;
            }
        }

        if (!$continue) {
            dieWithError("Unauthorized operation", 401);
        }

        $RowUser['data']['disabled'] = !$RowUser['data']['disabled'];
        $dataJson = addslashes(json_encode($RowUser['data']));
        $query = "UPDATE users SET data = '$dataJson' WHERE id = '{$RowUser['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        if ($RowUser['task']) {
            $RowTask = checkTask($RowUser['task']);
            $TaskID = $RowTask['id'];
            @include("tasks/" . $RowTask['tool'] . "/_taskToggleAvailability.php");
        }
        else {
            foreach ($TaskTypes as $index => $taskName) {
                $UserID = $RowUser['id'];
                @include("tasks/" . $index . "/_userToggleAvailability.php");
            }
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
        if (empty($Row['data']['downloadedPasswords'])) {
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
        $query = "UPDATE projects SET data = '$dataJson' WHERE id = '{$Row['id']}'";
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

    case "projectDelete":
        checkAdmin();
        $Row = find("projects", $_REQUEST['id'], "Unable to find project");
        if (!$Row['disabled']) {
            dieWithError("Only disabled projects can be deleted");
        }
        $query = "UPDATE projects SET deleted = '1' WHERE id = '{$Row['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        break;

    case "projectToggleAvailability":
        checkAdmin();
        $Row = find("projects", $_REQUEST['id'], "Unable to find project");
        $query = "UPDATE projects SET disabled = NOT disabled WHERE id = '{$Row['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        $tasks = getTasks($Row['id']);
        foreach($tasks as $task) {
            $TaskID = $task['id'];
            @include("tasks/" . $task['tool'] . "/_taskToggleAvailability.php");
        }

        $Users = [];
        $query = "SELECT * FROM users WHERE project = '{$Row['id']}' AND educator = '1'";
        $resIndex = $mysqli->query($query);
        while ($row = $resIndex->fetch_array(MYSQLI_ASSOC)) {
            $Users[] = $row['id'];
        }
        foreach ($TaskTypes as $index => $taskName) {
            foreach ($Users as $UserID) {
                @include("tasks/" . $index . "/_userToggleAvailability.php");
            }
        }

        break;

    case "educatorResetPassword":
        checkAdmin();
        $RowUser = find("users", $_REQUEST['id'], "Unable to find user");
        if ($RowUser['project'] != $_REQUEST['project_id']) {
            dieWithError("Unable to perform the operation");
        }

        $newPassword = password_generate(8);
        $ret['password'] = $newPassword;

        $newPassword = md5($newPassword);
        $query = "UPDATE users SET password = '$newPassword' WHERE id = '{$RowUser['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        $clearPassword = $ret['password'];
        foreach ($TaskTypes as $index => $taskName) {
            @include("tasks/" . $index . "/_educatorResetPassword.php");
        }
        break;

    case "educatorAdd":
        checkAdmin();
        $Row = find("projects", $_REQUEST['id'], "Unable to find project");
        $query = "SELECT * FROM users WHERE project = '{$Row['id']}' AND educator = '1'";
        $result = $mysqli->query($query);
        $lastID = 0;
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            if (preg_match("/educator([0-9]+)/", $row['username'], $matches)) {
                $thisID = $matches[1];
                if ($thisID > $lastID) {
                    $lastID = $thisID;
                }
            }
        }

        $UserName = educatorName($lastID + 1, $Row['id']);
        $data = [];
        $data['project'] = $Row['id'];
        $data['username'] = $UserName;
        $password = password_generate(8);
        $clearPassword = $password;
        if ($Row['confirmed']) {
            $password = md5($password);
        }
        $data['password'] = $password;
        $data['educator'] = 1;
        $data['data'] = json_encode([
            "name" => "",
            "email" => "",
            "disabled" => false
        ]);
        $query = queryinsert("users", $data);
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        $UserID = $mysqli->insert_id;

        $query = "SELECT * FROM users
            WHERE educator = '1' AND project = '{$Row['id']}' AND deleted = '0'";
        $result = $mysqli->query($query);

        $Info = $Row['data'];
        $Info['educators'] = $result->num_rows;
        $dataJson = addslashes(json_encode($Info));
        $query = "UPDATE projects SET data = '$dataJson' WHERE id = '{$Row['id']}'";
        $mysqli->query($query);

        $ret['username'] = $UserName;
        $ret['password'] = $clearPassword;

        foreach ($TaskTypes as $index => $taskName) {
            @include("tasks/" . $index . "/_educatorAdd.php");
        }
        break;

    case "projectAdd":
        checkAdmin();
        // missing validation
        $orig_data = $_REQUEST['info'];

        validate($orig_data, [
            'name' => 'required|min:' . $Options['project_name_minlength'],
            'educators' => 'required|numeric|min:1|max:' . $Options['project_max_educators'],
            'language' => 'required|in:' . $Options['languages']
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
            $data['username'] = educatorName($i + 1, $ProjectID);
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
        unset($_SESSION['StudentLogin']);
        unset($_SESSION['Admin']);
        unset($_SESSION['TaskInfo']);
        break;

    case "taskTypes":
        $ret['types'] = $TaskTypes;
        break;

    case "taskDelete":
        $RowTask = checkTask($_REQUEST['id']);

        // e.disabled || e.closed || !e.confirmed
        if (!$RowTask['disabled'] && !$RowTask['closed'] && $RowTask['confirmed']) {
            dieWithError("A task must be inactive to be deleted");
        }
        $query = "UPDATE tasks SET deleted = '1' WHERE id = '{$RowTask['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        break;

    case "taskToggleAvailability":
        $RowTask = checkTask($_REQUEST['id']);
        if (!$RowTask['confirmed']) {
            dieWithError("The task is not confirmed");
        }

        $query = "UPDATE tasks SET disabled = NOT disabled WHERE id = '{$RowTask['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        // $RowTask = checkTask($_REQUEST['id']);
        $TaskID = $RowTask['id'];
        @include("tasks/" . $RowTask['tool'] . "/_taskToggleAvailability.php");
        break;

    case "confirmTask":
        $RowTask = checkTask($_REQUEST['id']);

        if ($RowTask['confirmed']) {
            dieWithError("This task is already confirmed");
        }

        $Info = $RowTask['data'];
        $passwords_task = [];
        if ($Info['passwords'] == "duplicate") {
            $Row = find("tasks", $Info['duplicateTask'], "Unable to find task");
            if (!$Row['confirmed']) {
                dieWithError("Project is not confirmed");
            }
            if ($Row['project_id'] != $RowTask['project_id']) {
                dieWithError("Project IDs must be identical");
            }

            $query = "SELECT * FROM users WHERE task = '{$Info['duplicateTask']}' ORDER BY id";
            $result = $mysqli->query($query);
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $passwords_task[] = $row['password'];
            }
            if (count($passwords_task) != $Info['students']) {
                dieWithError("Students number mismatch");
            }
        }

        $query = "UPDATE tasks SET confirmed = '1' WHERE id = '{$RowTask['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        $TaskID = $RowTask['id'];
        $ProjectID = $RowTask['project_id'];

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

                case "duplicate":
                    $password = $passwords_task[$i];
                    break;
            }

            $data = [
                "project" => $ProjectID,
                "task" => $TaskID,
                "username" => $username,
                "password" => $password,
                "educator" => 0,
                "data" => json_encode(["name" => "", "disabled" => !!$Info['disabledStatus']])
            ];

            $query = queryinsert("users", $data);
            $result = $mysqli->query($query);
        }

        $TaskID = $RowTask['id'];
        @include("tasks/" . $RowTask['tool'] . "/_confirmTask.php");
        break;

    case "closeTask":
        $RowTask = checkTask($_REQUEST['id']);

        // This is important!
        if (!$RowTask['disabled']) {
            dieWithError("Only disabled tasks can be closed");
        }

        $query = "UPDATE tasks SET closed = '1' WHERE id = '{$RowTask['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
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
                // $ret['username'] = $Username;
                // break;
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

                if (!$_REQUEST['name']) {
                    dieWithError("Missing field");
                }
                $pieces = preg_split('/_/', $_REQUEST['name']);
                $Field = $pieces[count($pieces) - 1];
                validate(["field" => $Field], [
                    'field' => 'required|in:name,email',
                ]);

                if (!$RowUser['educator'] && $Field != "name") {
                    dieWithError("Only educators can have fields different than 'name'");
                }
                if ($Field == "email") {
                    validate(["email" => $NewValue], [
                        'email' => 'email',
                    ]);
                }
                // $ret['field'] = $Field;

                $UserData[$Field] = $NewValue;
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

                if ($ret['info']['data']['passwords'] == "duplicate") {
                    $query = "SELECT * FROM tasks WHERE id = '{$ret['info']['data']['duplicateTask']}'";
                    $result_dp = $mysqli->query($query);
                    while ($row_dp = $result_dp->fetch_array(MYSQLI_ASSOC)) {
                        $ret['info']['data']['duplicateTaskInfo'] = $row_dp;
                        $ret['info']['data']['duplicateTaskInfo']['data'] = json_decode($ret['info']['data']['duplicateTaskInfo']['data'], true);
                    }
                }

                $ret['info']['students'] = [];
                $query = "SELECT * FROM users
                    WHERE task = '{$Row['id']}' AND educator = '0' AND deleted = '0'";
                $result = $mysqli->query($query);
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $row['data'] = json_decode($row['data'], true);
                    $ret['info']['students'][] = $row;
                }
                @include("tasks/" . $Row['tool'] . "/_info.php");
                // $ret['project_info'] = checkProject($)
                break;

            case "add":

                $EditID = 0;
                if (!empty($_REQUEST['edit_id'])) {
                    checkTask($_REQUEST['edit_id']);
                    $EditID = $_REQUEST['edit_id'];
                }

                validate($InputData, [
                    'type' => 'required|in:' . implode(",", array_keys($TaskTypes)),
                ]);

                $RowProject = checkProject($_REQUEST['project_id']);
                $ProjectID = $RowProject['id'];
                $Info = json_decode($InputData['info'], true);
                validate($Info, [
                    'name' => 'required|min:' . $Options['task_name_minlength'],
                    'students' => 'required|numeric|min:1|max:' . $Options['task_max_students'],
                    'passwords' => 'required|in:trivial,easy,difficult,duplicate'
                ]);
                if ($Info['passwords'] == "duplicate") {
                    $taskInfo = checkTaskAvailability($Info['duplicateTask'], false, false);
                    if ($taskInfo['data']['students'] != $Info['students']) {
                        dieWithError("Invalid student number, should be " . $taskInfo['data']['students']);
                    }
                }

                // TIME STUFF
                $Info['time']['start_date_s'] = date("d/m/Y", strtotime($Info['time']['start_date']));
                $Info['time']['end_date_s'] = date("d/m/Y", strtotime($Info['time']['end_date']));
                if (!empty($Info['automatic_timing'])) {
                    validate($Info['time'], [
                        'afternoon_from' => 'required|date:H:i',
                        'morning_from' => 'required|date:H:i',
                        'afternoon_to' => 'required|date:H:i',
                        'morning_to' => 'required|date:H:i',
                        'start_date_s' => 'required|date:d/m/Y',
                        'end_date_s' => 'required|date:d/m/Y',
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

                    $start_date = DateTime::createFromFormat('d/m/Y', $Info['time']['start_date_s']);
                    $end_date = DateTime::createFromFormat('d/m/Y', $Info['time']['end_date_s']);
                    // $diff = $end_date->diff($start_date);
                    $diff = $start_date->diff($end_date);
                    if ($diff->invert) {
                        dieWithError("Wrong date order");
                    }
                    // $ret['diff'] = print_r($diff, true);
                    // check dates
                }
                else {
                    unset($Info['time']);
                }

                // break;

                // if ($CheckOnly) {
                //     $ret['co'] = true;
                // }

                //$Info['orig_info'] = $Info['type_info'];

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

                if ($EditID) {
                    $query = queryupdate("tasks", $data, ["id" => $EditID]);
                }
                else {
                    $query = queryinsert("tasks", $data);
                }
                
                $result = $mysqli->query($query);
                if (!$result) {
                    dieWithError($mysqli->error);
                }

                if ($EditID) {
                    $TaskID = $EditID;
                }
                else {
                    $TaskID = $mysqli->insert_id;
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

    case "resetAll":
        checkAdmin();
        $Reset = true;

        foreach ($TaskTypes as $index => $taskName) {
            @include("tasks/" . $index . "/_resetAll.php");
        }
        foreach (["projects", "tasks", "users"] as $table) {
            $table = addslashes($table);
            $query = "TRUNCATE `{$table}`";
            $mysqli->query($query);
        }
        
        break;

    default:
        dieWithError("Invalid action");
}

// $ret['login_info'] = ['user' => $_SESSION['Login'], 'admin' => $_SESSION['Admin']];
// $ret['_session'] = $_SESSION;
http_response_code(200);
echo json_encode($ret);
