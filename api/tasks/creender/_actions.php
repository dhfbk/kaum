<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    case "enterDemo":
        $RowTask = checkTaskAvailability($_REQUEST['id']);
        if (empty($RowTask['data']['type_info']['enable_demo_mode'])) {
            dieWithError("This task is not available in demo mode");
        }
        if ($RowTask['data']['type_info']['demo_password'] != $_REQUEST['password']) {
            dieWithError("Wrong login");
        }

        if (!isset($_SESSION['TaskInfo'])) {
            $_SESSION['TaskInfo'] = [];
        }
        $_SESSION['TaskInfo']['creender_demo'] = $_REQUEST['id'];
        $ret['session_id'] = session_id();
        break;

    case "listDemo":
        $query = "SELECT t.*
            FROM `tasks` t
            LEFT JOIN projects p ON t.project_id = p.id
            WHERE p.deleted = '0' AND p.disabled = '0' AND p.confirmed = '1'
                AND t.deleted = '0' AND t.closed = '0' AND t.confirmed = '1'
                AND t.tool = 'creender'";
        $result = $mysqli->query($query);
        $ret['data'] = [];
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $row['data'] = json_decode($row['data'], true);
            if (empty($row['data']['type_info']['enable_demo_mode'])) {
                continue;
            }
            $name = "T{$row['id']} - {$row['name']}";
            $toPass = ["id" => $row['id'], "name" => $name];
            $ret['data'][] = $toPass;
        }
        break;

    case "getInfo":
        $ret['demo'] = false;
        if (!isStudentLogged() && !empty($_SESSION['TaskInfo']['creender_demo'])) {
            $RowTask = checkTaskAvailability($_SESSION['TaskInfo']['creender_demo']);
            $ret['demo'] = true;
        }
        else {
            checkStudentLogin();
            $RowTask = checkTask();
        }
        require("_login.php");
        break;

    case "nextPhoto":
        $ret['next'] = null;
        if (!isStudentLogged() && !empty($_SESSION['TaskInfo']['creender_demo'])) {
            $ret['next'] = ["row" => -1];
            return;
        }
        checkStudentLogin();
        $list = creender_getPictureList();
        if (count($list)) {
            $ret['next'] = $list[0];
        }
        break;

    case "saveAnnotation":
        if (!isStudentLogged() && !empty($_SESSION['TaskInfo']['creender_demo'])) {
            return;
        }
        checkStudentLogin();
        $list = creender_getPictureList(false, true);
        $PictureID = addslashes($_REQUEST['id']);
        if (!isset($list[$PictureID])) {
            dieWithError("Unable to find record");
        }
        $TaskInfo = checkTask($list[$PictureID]['task']);
        $Data = $_REQUEST['data'];
        $dataToSave = [];
        $dataToSave['report'] = $Data['value'] == -2;
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
        if (!isStudentLogged() && !empty($_SESSION['TaskInfo']['creender_demo'])) {
            // todo: query for test photos (photos from datasets with test flag not included in the task)
            $filePath = "img/demo-picture.png";
        }
        else {
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
            $FileInfo['content'] = json_decode($FileInfo['content'], true);
        }
        $mime = mime_content_type($filePath);
        // $parts = pathinfo($FileInfo['content']['basename']);

        http_response_code(200);
        // header("Content-Type: " . $creenderAllowedExtensions[$parts['extension']]);
        header("Content-Type: " . $mime);
        $content = file_get_contents($filePath);
        echo $content;
        exit();
        break;

    case "exportResults":
        checkLogin();
        $Row = checkTask($_REQUEST['id']);
        $choices = $Row['data']['type_info']['choices'];

        // print_r($Row);
        $isYesWithComment = empty($Row['data']['type_info']['no_show_question']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $query = "SELECT a.id annotation_id, a.data, a.created_at, u.username, r.id picture_id, r.content, d.name
            FROM `creender_annotations` a
            LEFT JOIN users u ON u.id = a.user
            LEFT JOIN creender_ds_task_cluster dtc ON a.dtc_id = dtc.id
            LEFT JOIN creender_rows r ON r.id = dtc.row
            LEFT JOIN creender_datasets d ON d.id = r.dataset_id
            WHERE u.task = '{$Row['id']}' AND a.deleted = '0'";
        $result = $mysqli->query($query);

        $sheet->setCellValue('A1', "Annotation ID");
        $sheet->setCellValue('B1', "Username");
        $sheet->setCellValue('C1', "Picture ID");
        $sheet->setCellValue('D1', "Dataset name");
        $sheet->setCellValue('E1', "File name");
        $sheet->setCellValue('F1', "Answer");
        $sheet->setCellValue('G1', "Types");
        $sheet->setCellValue('H1', "Comment");
        $sheet->setCellValue('I1', "Date/time");

        $i = 2;
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $row['data'] = json_decode($row['data'], true);
            $row['content'] = json_decode($row['content'], true);
            $answer = "No";
            if ($row['data']['report']) {
                $answer = "Report";
            }
            if ($isYesWithComment xor !$row['data']['needComment']) {
                $answer = "Yes";
            }

            $types = [];
            if (!empty($row['data']['values'])) {
                foreach ($row['data']['values'] as $value) {
                    $types[] = $choices[$value];
                }
            }

            $comment = $row['data']['comment'];
            // $comment = str_replace("\n", "\n\r", $comment);

            $sheet->setCellValue('A' . $i, intval($row['annotation_id']));
            $sheet->setCellValue('B' . $i, $row['username']);
            $sheet->setCellValue('C' . $i, intval($row['picture_id']));
            $sheet->setCellValue('D' . $i, $row['name']);
            $sheet->setCellValue('E' . $i, $row['content']['basename']);
            $sheet->setCellValue('F' . $i, $answer);
            $sheet->setCellValue('G' . $i, implode(", ", $types));
            $sheet->setCellValue('H' . $i, $comment);
            $sheet->setCellValue('I' . $i, $row['created_at']);
            $sheet->getStyle('H' . $i)->getAlignment()->setWrapText(true);
            
            $i++;
            // print_r($row);
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);

        $filename = "creender-results-t" . $Row['id'];
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
        http_response_code(200);
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
        break;

    case "taskResults":
        checkLogin();
        $Row = checkTask($_REQUEST['id']);

        $ClusterInfo = [];
        $query = "SELECT cluster, COUNT(*) num
            FROM `creender_ds_task_cluster`
            WHERE task = '{$Row['id']}'
            GROUP BY cluster";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $ClusterInfo[$row['cluster']] = $row['num'];
        }

        $UserInfo = [];
        $query = "SELECT * FROM users
            WHERE task = '{$Row['id']}' AND educator = '0' AND deleted = '0'";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $row['data'] = json_decode($row['data'], true);
            $thisUserInfo = [];
            $thisUserInfo['cluster'] = $row['data']['rc_cluster'];
            $thisUserInfo['annotated'] = 0;
            $thisUserInfo['total'] = $ClusterInfo[$row['data']['rc_cluster']];
            $UserInfo[$row['id']] = $thisUserInfo;
        }

        $query = "SELECT a.user, COUNT(*) num
            FROM `creender_annotations` a
            LEFT JOIN users u ON u.id = a.user
            WHERE a.deleted = '0' AND u.task = '{$Row['id']}'
                AND u.deleted = '0' AND u.educator = '0'
            GROUP BY a.user;";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $UserInfo[$row['user']]['annotated'] = $row['num'];
        }

        $ret['info'] = $UserInfo;
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
