<?php

switch ($InputData['sub']) {
    // case "getTasks":
    //     checkLogin();
    //     $RowUser = find("users", $_SESSION['Login'], "Unable to find user");
    //     $ret['creender_tasks'] = creender_getTasksByProject($RowUser['project']);
    //     break;

    // case "setTask":
    //     checkLogin();
    //     $RowUser = find("users", $_SESSION['Login'], "Unable to find user");
    //     $tasks = creender_getTasksByProject($RowUser['project']);
    //     if (!isset($tasks[$_REQUEST['task']])) {
    //         dieWithError("Unable to find task");
    //     }
    //     $_SESSION['creenderTask'] = $_REQUEST['task'];
    //     break;

    case "getInfo":
        checkStudentLogin();
        $RowTask = checkTask();
        require("_login.php");
        break;

    case "nextPhoto":
        checkStudentLogin();
        $list = creender_getPictureList();
        $ret['next'] = null;
        if (count($list)) {
            $ret['next'] = $list[0];
        }
        break;

    case "saveAnnotation":
        checkStudentLogin();
        $list = creender_getPictureList(false, true);
        $PictureID = addslashes($_REQUEST['id']);
        if (!isset($list[$PictureID])) {
            dieWithError("Unable to find record");
        }
        $TaskInfo = checkTask($list[$PictureID]['task']);
        $Data = $_REQUEST['data'];
        $dataToSave = [];
        $dataToSave['needComment'] = $Data['needComment'];
        $dataToSave['comment'] = $Data['comment'];

        if ($Data['needComment']) {
            if ($TaskInfo['data']['type_info']['allow_multiple_choices']) {
                if (empty($Data['values'])) {
                    dieWithError("No value selected");
                }
                $dataToSave['values'] = $Data['values'];
            }
            else {
                if ($Data['value'] == -1) {
                    dieWithError("No value selected");
                }
                $dataToSave['values'] = [$Data['value']];
            }

            if (!$TaskInfo['data']['type_info']['do_not_ask_for_comment']
                && $TaskInfo['data']['type_info']['comment_is_mandatory']
                && empty(trim($Data['comment']))) {
                dieWithError("Comment is mandatory");
            }
        }

        $data = [];
        $data['dtc_id'] = $list[$PictureID]['id'];
        $data['user'] = $_SESSION['StudentLogin'];
        $data['data'] = json_encode($dataToSave);
        $query = queryinsert("creender_annotations", $data);
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        // $ret['list'] = $list[$PictureID];
        // $ret['task'] = $TaskInfo['data']['type_info'];
        // $ret['data'] = $_REQUEST['data'];
        break;

    case "getPhoto":
        checkStudentLogin();
        $list = creender_getPictureList("row");
        $PictureID = addslashes($_REQUEST['id']);
        if (!in_array($PictureID, $list)) {
            dieWithError("Unable to load image");
        }
        $baseFolder = $Options['creender_images_path'];
        $FileInfo = find("creender_rows", $PictureID, "Unable to find picture");

        $longID = str_pad($PictureID, 4, "0", STR_PAD_LEFT);
        $partInfo = creender_getPartsInfo($longID);
        $filePath = $baseFolder . "/" . $FileInfo['dataset_id'] . "/" . $partInfo['f'] . "/" . $partInfo['s'];
        $ret['path'] = $FileInfo;
        $FileInfo['content'] = json_decode($FileInfo['content'], true);
        $mime = mime_content_type($filePath);
        // $parts = pathinfo($FileInfo['content']['basename']);

        http_response_code(200);
        // header("Content-Type: " . $creenderAllowedExtensions[$parts['extension']]);
        header("Content-Type: " . $mime);
        $content = file_get_contents($filePath);
        echo $content;
        exit();
        break;

    case "listChoices":
        checkLogin();
        $projectID = 0;
        if ($InputData['project_id']) {
            $RowProject = checkProject($InputData['project_id']);
            $projectID = $RowProject['id'];
        }
        $ret['datasets'] = creender_listChoices($projectID);
        break;

    case "addChoice":
        checkLogin();
        $Data = $_REQUEST['data'];
        creender_addChoiceList($Data);
        break;

    case "listDatasets":
        checkLogin();
        $projectID = 0;
        if ($InputData['project_id']) {
            $RowProject = checkProject($InputData['project_id']);
            $projectID = $RowProject['id'];
        }
        $ret['datasets'] = creender_listDatasets($projectID);
        break;

    case "addDataset":
        checkLogin();
        $insertInfo = [];
        if (isAdmin()) {
            if (empty($_REQUEST['save'])) {
                $insertInfo['user_id'] = -1;
            }
        }
        else {
            $UserID = $_SESSION['Login'];
            if (!empty($_REQUEST['save'])) {
                $RowUser = find("users", $UserID, "Unable to find user");
                $RowProject = checkProject($RowUser['project'], $RowUser['id']);
                $insertInfo['project_id'] = $RowProject['id'];
            }
            else {
                $insertInfo['user_id'] = $UserID;
            }
        }
        // checkAdmin();
        $baseFolder = $Options['creender_images_path'];

        $testFile = $baseFolder . "/.creender_test_file";
        $res = touch($testFile);
        if (!$res) {
            dieWithError("Unable to write to folder " . $baseFolder);
        }
        unlink($testFile);

        $Info = json_decode($_REQUEST['info'], true);
        if (!$_FILES['f'] || !count($_FILES['f']['name'])) {
            dieWithError("No files found");
        }
        if (count($_FILES['f']['name']) != 1) {
            dieWithError("Only one file can be uploaded");
        }
        validate($Info, [
            'name' => 'required|min:' . $Options['creender_dataset_name_minlength'],
        ]);
        if ($_FILES['f']['type'][0] != "application/zip") {
            dieWithError("Uploaded file is not a zip archive");
        }

        $zip = new ZipArchive;
        $res = $zip->open($_FILES['f']['tmp_name'][0], ZipArchive::RDONLY);
        if ($res !== true) {
            dieWithError("Unable to open file");
        }
        $fileList = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $parts = pathinfo($filename);
            $ext = strtolower($parts['extension']);
            $basename = $parts['basename'];
            if ($basename[0] == ".") {
                continue;
            }
            if (!isset($creenderAllowedExtensions[$ext])) {
                continue;
            }
            $fileList[] = ['name' => $filename, 'basename' => $basename];
        }
        if (!count($fileList)) {
            dieWithError("Unable to find images in ZIP file");
        }

        $insertInfo['name'] = $Info['name'];
        $query = queryinsert("creender_datasets", $insertInfo);
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        $DatasetID = $mysqli->insert_id;

        $thisFolder = $baseFolder . "/" . $DatasetID;
        mkdir($thisFolder);
        $ret['parts'] = [];

        foreach ($fileList as $filePath) {
            $content = json_encode($filePath);
            $query = queryinsert("creender_rows", ["dataset_id" => $DatasetID, "content" => $content]);
            $result = $mysqli->query($query);
            $FileID = $mysqli->insert_id;

            $longID = str_pad($FileID, 4, "0", STR_PAD_LEFT);
            $partInfo = creender_getPartsInfo($longID);
            $ret['parts'][$FileID] = $partInfo;

            $firstFileFolder = $thisFolder . "/" . $partInfo['f'];

            if (!file_exists($firstFileFolder)) {
                mkdir($firstFileFolder);
            }

            $thisFileFolder = $firstFileFolder . "/" . $partInfo['s'];
            $content = $zip->getFromName($filePath['name']);
            file_put_contents($thisFileFolder, $content);
        }

        $ret['files'] = $fileList;
        $ret['folder'] = $baseFolder;

        // touch($baseFolder . "/prova");

        // $content = $zip->getFromName($filename);

        $ret['req'] = $_REQUEST;
        break;
}
