<?php

$creenderAllowedExtensions = [
    "jpeg" => "image/jpg",
    "jpg" => "image/jpg",
    "png" => "image/png"
];

function creender_getPictureList($onlyID = false, $replaceID = false) {
    global $mysqli;

    $ret = [];
    if (isLogged()) {
        $RowTask = checkTask($_SESSION['creenderTask']);
        $RowUser = find("users", $_SESSION['Login'], "Unable to find user");
        $cluster = 0;
    }
    else {
        checkStudentLogin();
        $RowTask = checkTask();
        checkTaskAvailability($RowTask['id']);
        $RowUser = find("users", $_SESSION['StudentLogin'], "Unable to find user");
        $cluster = $RowUser['data']['rc_cluster'];
    }
    $query = "SELECT c.*
        FROM creender_ds_task_cluster c
        WHERE c.task = '{$RowTask['id']}' AND c.cluster = '{$cluster}'
            AND c.id NOT IN (
                SELECT dtc_id FROM creender_annotations
                    WHERE user = '{$RowUser['id']}' AND deleted = '0'
            )
        ORDER BY c.id";
    $result = $mysqli->query($query);
    if (!$result) {
        dieWithError("Error in querying database");
    }
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        if ($onlyID) {
            if ($replaceID) {
                $ret[$row['row']] = $row[$onlyID];
            }
            else {
                $ret[] = $row[$onlyID];
            }
        }
        else {
            if ($replaceID) {
                $ret[$row['row']] = $row;
            }
            else {
                $ret[] = $row;
            }
        }
    }
    return $ret;
}

function creender_getTasksByProject($projectID) {
    global $mysqli;

    $ret = [];
    $query = "SELECT * FROM tasks
        WHERE tool = 'creender'
            AND closed = '0' AND deleted = '0'
            AND project_id = '{$projectID}'";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $ret[$row['id']] = $row['name'];
    }
    return $ret;
}

function creender_getPartsInfo($longID) {
    $first_part = substr($longID, 0, strlen($longID) - 3);
    $second_part = substr($longID, -3);
    return ["f" => $first_part, "s" => $second_part];
}

function creender_listChoices($projectID = 0) {
    global $mysqli;

    $ret = [];
    $query = "SELECT *
        FROM creender_choices
        WHERE (
            project_id = '{$projectID}' OR
            project_id IS NULL
        )
        AND name IS NOT NULL
        AND deleted = '0'
        ORDER BY name";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $row['data'] = json_decode($row['data']);
        $ret[$row['id']] = $row;
    }

    return $ret;
}

function creender_listDatasets($projectID = 0, $userID = 0) {
	global $mysqli;

    $ret = [];
    $orUser = "";
    if ($userID) {
        $orUser = " OR user_id = '{$userID}' ";
    }
    else {
        $orUser = " OR user_id = '{$_SESSION['Login']}' ";
    }
    $query = "SELECT d.id, d.name, d.test, COUNT(r.id) num
        FROM creender_datasets d
        LEFT JOIN creender_rows r ON r.dataset_id = d.id
        WHERE (
            project_id = '{$projectID}' OR
            (project_id IS NULL AND task_id IS NULL AND user_id IS NULL)
            {$orUser}
        )
        AND name IS NOT NULL
        AND deleted = '0'
        GROUP BY d.id, d.task_id, d.name
        ORDER BY name";
    // dieWithError("Error", 400, ["query" => $query]);
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        // $name = "{$row['name']} ({$row['num']})";
        // if ($row['task_id']) {
        //     $name = "T{$row['task_id']} - $name";
        // }
        $ret[$row['id']] = $row;
    }

    return $ret;
}

function creender_addChoiceList($Data) {
    global $Options, $mysqli;

    $Data['choices'] = array_map("trim", $Data['choices']);
    $validation_rules = [];
    $validation_rules['choices'] = "required|min:2";
    $validation_rules['name'] = "required|min:" . $Options['creender_choicelist_name_minlength'];
    validate($Data, $validation_rules);
    $insertInfo = [];
    $insertInfo['name'] = $Data['name'];
    $insertInfo['data'] = json_encode($Data['choices']);
    if (!isAdmin()) {
        $UserID = $_SESSION['Login'];
        $RowUser = find("users", $UserID, "Unable to find user");
        $RowProject = checkProject($RowUser['project'], $RowUser['id']);
        $insertInfo['project_id'] = $RowProject['id'];
    }
    $query = queryinsert("creender_choices", $insertInfo);
    $result = $mysqli->query($query);
    if (!$result) {
        dieWithError($mysqli->error);
    }
}
