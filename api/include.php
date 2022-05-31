<?php

use Rakit\Validation\Validator;

// Global stuff

$mysqli = @new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
if ($mysqli->connect_errno) {
    dieWithError("Error in DB connection: " . $mysqli->connect_error);
}

$_SESSION['Options'] = loadOptions();
// if (!isset($_SESSION['Options']) || !$_SESSION['Options']) {
//     $_SESSION['Options'] = loadOptions();
// }

mysqli_options($mysqli, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);

// Functions

// https://stackoverflow.com/questions/6101956/generating-a-random-password-in-php
function password_generate($length=8, $min_lowercases=1, $min_uppercases=1, $min_numbers=1, $min_specials=0) {

    $lowercases = 'abcdefghjkmnpqrstuvwxyz';
    $uppercases = 'ABCDEFGHJKMNPQRSTUVWXYZ';
    $numbers = '23456789';
    $specials = '!#%&?$*-+';

    $absolutes = '';
    if ($min_lowercases && !is_bool($min_lowercases)) $absolutes .= substr(str_shuffle(str_repeat($lowercases, $min_lowercases)), 0, $min_lowercases);
    if ($min_uppercases && !is_bool($min_uppercases)) $absolutes .= substr(str_shuffle(str_repeat($uppercases, $min_uppercases)), 0, $min_uppercases);
    if ($min_numbers && !is_bool($min_numbers)) $absolutes .= substr(str_shuffle(str_repeat($numbers, $min_numbers)), 0, $min_numbers);
    if ($min_specials && !is_bool($min_specials)) $absolutes .= substr(str_shuffle(str_repeat($specials, $min_specials)), 0, $min_specials);

    $remaining = $length - strlen($absolutes);

    $characters = '';
    if ($min_lowercases !== false) $characters .= substr(str_shuffle(str_repeat($lowercases, $remaining)), 0, $remaining);
    if ($min_uppercases !== false) $characters .= substr(str_shuffle(str_repeat($uppercases, $remaining)), 0, $remaining);
    if ($min_numbers !== false) $characters .= substr(str_shuffle(str_repeat($numbers, $remaining)), 0, $remaining);
    if ($min_specials !== false) $characters .= substr(str_shuffle(str_repeat($specials, $remaining)), 0, $remaining);

    $password = str_shuffle($absolutes . substr($characters, 0, $remaining));

    return $password;
}

// https://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size
// Returns a file size limit in bytes based on the PHP upload_max_filesize
// and post_max_size
function file_upload_max_size() {
  static $max_size = -1;

  if ($max_size < 0) {
    // Start with post_max_size.
    $post_max_size = parse_size(ini_get('post_max_size'));
    if ($post_max_size > 0) {
      $max_size = $post_max_size;
    }

    // If upload_max_size is less, then reduce. Except if upload_max_size is
    // zero, which indicates no limit.
    $upload_max = parse_size(ini_get('upload_max_filesize'));
    if ($upload_max > 0 && $upload_max < $max_size) {
      $max_size = $upload_max;
    }

    $memory_limit = parse_size(ini_get('memory_limit'));
    if ($memory_limit < $max_size) {
      $max_size = $memory_limit;
    }
  }
  return $max_size;
}

function educatorName($id, $project_id) {
    return "pr" . $project_id . "-educator" . $id;
}

function parse_size($size) {
  $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
  $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
  if ($unit) {
    // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
    return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
  }
  else {
    return round($size);
  }
}


function loadSimpleTsv($data) {
    $tt = explode("\n", $data);
    $ret = [];
    foreach ($tt as $key => $value) {
        $value = trim($value);
        if (!$value) {
            break;
        }
        $parts = explode("\t", $value);
        $value = $parts[0];
        $value = trim($value);
        if (!$value) {
            break;
        }
        $ret[$value] = $parts[1];
    }
    return $ret;
}

function validate($data, $rules) {
    $validator = new Validator;
    $validation = $validator->validate($data, $rules);
    if ($validation->fails()) {
        $errors = $validation->errors();
        $errorList = $errors->firstOfAll();
        dieWithError(implode(", ", $errorList));
    }
}

function isAdmin() {
    return !empty($_SESSION['Admin']);
}

function isLogged() {
    return !empty($_SESSION['Login']);
}

function isStudentLogged() {
    return !empty($_SESSION['StudentLogin']);
}

function checkLogin() {
    if (!isLogged()) {
        dieWithError("User not logged in", 401);
    }
}

function checkStudentLogin() {
    if (!isStudentLogged()) {
        dieWithError("User not logged in", 401);
    }
}

function checkAdmin() {
    if (!isAdmin()) {
        dieWithError("Only admin can do that", 401);
    }
}

function getTasks($project_id, $getStudents = false) {
    global $mysqli;

    $tasks = [];
    $query = "SELECT * FROM tasks
        WHERE project_id = '{$project_id}' AND deleted = '0'
        ORDER BY id DESC";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $row['data'] = json_decode($row['data'], true);
        $row['students'] = $row['data']['students'];
        if ($getStudents !== false) {
            if (array_key_exists($row['id'], $getStudents) && $getStudents[$row['id']]) {
                $row['students'] = 0;
                $row['students'] = $getStudents[$row['id']];
            }
        }
        $tasks[] = $row;
    }
    return $tasks;
}

function checkTask($id = 0, $userID = 0, $projectID = 0) {
    if (!$userID) {
        if ($_SESSION['Login']) {
            $userID = $_SESSION['Login'];
        }
        else {
            $userID = $_SESSION['StudentLogin'];
        }
    }

    if (!$id) {
        if (isAdmin()) {
            dieWithError("Unable to get task ID (admin)");
        }
        $RowUser = find("users", $userID, "Unable to find user");
        if (!$RowUser['task']) {
            dieWithError("Unable to get task ID");
        }
        $Row = find("tasks", $RowUser['task'], "Unable to find task");
    }
    else {
        $Row = find("tasks", $id, "Unable to find task");
        if (isAdmin()) {
            $Row['project_info'] = find("projects", $Row['project_id'], "Unable to find project");
            return $Row;
        }
        $RowUser = find("users", $userID, "Unable to find user");
    }

    if ($RowUser['data']['disabled']) {
        dieWithError("User is disabled");
    }

    if ($Row['deleted']) {
        dieWithError("Unable to find task");
    }

    if ($RowUser['project'] != $Row['project_id']) {
        dieWithError("Access denied", 401);
    }
    if ($projectID && $projectID != $RowUser['project']) {
        dieWithError("Wrong project ID");
    }
    $RowProject = checkProjectAvailability($Row['project_id']);
    $Row['project_info'] = $RowProject;
    $Row['user_info'] = $RowUser;

    return $Row;
}

function rawCheckTaskAvailability($Row) {
    if ($Row['deleted']) {
        dieWithError("Unable to find task");
    }
    if (!$Row['confirmed']) {
        dieWithError("Task is unconfirmed");
    }
    if ($Row['disabled']) {
        dieWithError("Task is disabled");
    }
    if ($Row['closed']) {
        dieWithError("Task is closed");
    }
    if (!empty($Row['data']['automatic_timing'])) {
        // check if time is ok
    }
    return $Row;
}

function checkTaskAvailability($id) {
    $Row = find("tasks", $id, "Unable to find task");
    $RowProject = checkProjectAvailability($Row['project_id']);
    $Row['project_info'] = $RowProject;
    return rawCheckTaskAvailability($Row);
}

function checkProjectAvailability($id, $Row = []) {
    if (empty($Row)) {
        $Row = find("projects", $id, "Unable to find project");
    }

    if ($Row['deleted']) {
        dieWithError("Unable to find project");
    }
    if ($Row['disabled']) {
        dieWithError("Project is disabled");
    }
    if (!$Row['confirmed']) {
        dieWithError("Project needs to be confirmed by an administrator");
    }

    return $Row;
}

function checkProject($id, $userID = 0) {
    $Row = find("projects", $id, "Unable to find project");
    if (isAdmin()) {
        return $Row;
    }

    if (!$userID) {
        if ($_SESSION['Login']) {
            $userID = $_SESSION['Login'];
        }
        else {
            $userID = $_SESSION['StudentLogin'];
        }
    }
    $RowUser = find("users", $userID, "Unable to find user");
    if ($RowUser['project'] != $id) {
        dieWithError("Access denied", 401);
    }
    if ($RowUser['data']['disabled']) {
        dieWithError("User is disabled");
    }

    checkProjectAvailability($id, $Row);
    return $Row;
}

function checkField($var, $err_msg) {
    if (!$var) {
        dieWithError($err_msg);
    }
    return $var;
}

function dieWithError($text, $code = 400, $addenda = []) {
    http_response_code($code);
    $ret = array();
    $ret['result'] = "ERR";
    $ret['error'] = $text;
    $ret = array_merge($ret, $addenda);
    echo json_encode($ret);
    exit();
}

function loadOptions($api = false) {
    global $mysqli;
    
    $Options = array();
    if ($api) {
        $query = "SELECT * FROM options WHERE api = '1'";
    }
    else {
        $query = "SELECT * FROM options";
    }
    
    $result = $mysqli->query($query);
    while ($obj = $result->fetch_object()) {
        $Options[$obj->id] = $obj->value;
    }
    return $Options;
}

function find($table, $id, $text) {
    global $mysqli;
    
    $stmt_up = $mysqli->prepare("SELECT * FROM {$table} WHERE id = ?");
    $stmt_up->bind_param("s", $id);
    $stmt_up->execute();
    $r = $stmt_up->get_result();
    if ($r->num_rows) {
        $row = $r->fetch_assoc();
        if ($row['data']) {
            $row['data'] = json_decode($row['data'], true);
        }
        return $row;
    }

    dieWithError($text);
}

function queryinsert($table, $a) {
    $array1 = $array2 = array();
    foreach ($a as $i => $v) {
        $array1[] = "`".$i."`";
        $array2[] = "'".addslashes($v)."'";
    }
    $query = "INSERT INTO ".$table." (".
        implode(", ", $array1).") VALUES (".
        implode(", ", $array2).")";
    return $query;
}

function queryupdate($table, $a, $where) {
    $updatearray = array();
    foreach ($a as $indice => $valore) {
        $updatearray[] = "`".$indice."` = '".addslashes($valore)."'";
    }
    if (is_array($where)) {
        $wherearray = array();
        foreach ($where as $indice => $valore) {
            $wherearray[] = "`".$indice."` = '".addslashes($valore)."'";
        }
        $nwhere = implode(" AND ", $wherearray);
    }
    else {
        $nwhere = $where;
    }
    $query = "UPDATE ".$table." SET ".
        implode(", ", $updatearray)." WHERE ".$nwhere;
    return $query;
}
