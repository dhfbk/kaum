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

// Functions

function validate($data, $rules) {
    $validator = new Validator;
    $validation = $validator->validate($data, $rules);
    if ($validation->fails()) {
        $errors = $validation->errors();
        $errorList = $errors->firstOfAll();
        dieWithError(implode(", ", $errorList));
    }
}

function checkLogin() {
    if (!isset($_SESSION['Login'])) {
        dieWithError("User not logged in", 401);
    }
}

function checkAdmin() {
    if (!isset($_SESSION['Admin'])) {
        dieWithError("Only admin can do that", 401);
    }
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
        return $r->fetch_assoc();
    }

    dieWithError($text);
}

function generaPassword() {
    $parole = array(
        "topolino",
        "pippo",
        "paperino",
        "gambadilegno",
        "macchianera",
        "bandabassotti",
        "nonnapapera",
        "paperina",
        "minnie",
        "pluto",
        "archimede",
        "clarabella",
        "orazio",
        "ziopaperone",
        "gastone",
        "paperoga",
        "paperina",
        "battista"
        );
    if (file_exists("random_words.txt")) {
        $fn = fopen("random_words.txt", "r");
        $parole = array();

        while(!feof($fn))  {
            $result = fgets($fn);
            $result = trim($result);
            if (strlen($result) > 0) {
                $parole[] = $result;
            }
        }

        fclose($fn);
    }
    $n = $parole[rand(0, count($parole) - 1)];
    $n .= str_pad(rand(1, 999), 3, "0", STR_PAD_LEFT);
    $n .= randomPassword();
    return $n;
}

function randomPassword($len = 1) {
    $alphabet = '!@#?%$';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $len; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
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
