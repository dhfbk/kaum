<?php

use Rakit\Validation\Validator;

// Global stuff

$mysqli = @new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
if ($mysqli->connect_errno) {
    dieWithError("Error in DB connection: " . $mysqli->connect_error);
}

// $_SESSION['Options'] = loadOptions();
if (!isset($_SESSION['Options']) || !$_SESSION['Options']) {
    $_SESSION['Options'] = loadOptions();
}

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
    return isset($_SESSION['Admin']) && $_SESSION['Admin'];
}

function checkLogin() {
    if (!isset($_SESSION['Login']) || !$_SESSION['Login']) {
        dieWithError("User not logged in", 401);
    }
}

function checkAdmin() {
    if (!isAdmin()) {
        dieWithError("Only admin can do that", 401);
    }
}

function checkProject($id, $userID = 0) {
    if (!$userID) {
        $userID = $_SESSION['Login'];
    }

    $Row = find("projects", $id, "Unable to find project");
    if (isAdmin()) {
        return $Row;
    }

    $RowUser = find("users", $userID, "Unable to find user");
    if ($RowUser['project'] != $id) {
        dieWithError("Access denied", 401);
    }
    if ($RowUser['data']['disabled']) {
        dieWithError("User is disabled");
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

function checkField($var, $err_msg) {
    if (!$var) {
        dieWithError($err_msg);
    }
    return $var;
}

function dieWithError($text, $code = 400) {
    http_response_code($code);
    $ret = array();
    $ret['result'] = "ERR";
    $ret['error'] = $text;
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

function queryinsert($table, $a, $buff = 0) {
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
