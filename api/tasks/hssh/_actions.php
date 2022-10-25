<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

switch ($InputData['sub']) {
    case "listDatasets":
        $RowProject = checkProject($InputData['project_id']);
        $ret['datasets'] = hssh_listDatasets($RowProject['id']);
        break;

    case "taskResults":
        checkLogin();
        $Row = checkTask($_REQUEST['id']);

        $ClusterInfo = ["ch" => [], "gr" => []];
        $query = "SELECT c.cluster, d.type, COUNT(*) num
            FROM hssh_ds_task_cluster c
            LEFT JOIN hssh_rows r ON c.row = r.id
            LEFT JOIN hssh_datasets d ON d.id = r.dataset_id
            WHERE c.task = '{$Row['id']}'
            GROUP BY c.cluster, d.type";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $ClusterInfo[$row['type']][$row['cluster']] = $row['num'];
        }

        $UserInfo = [];
        $query = "SELECT * FROM users
            WHERE task = '{$Row['id']}' AND educator = '0' AND deleted = '0'";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $row['data'] = json_decode($row['data'], true);
            $thisUserInfo = [];
            $thisUserInfo['cluster'] = $row['data']['rc_cluster'];
            $thisUserInfo['annotations'] = ["ch" => [], "gr" => []];
            $thisUserInfo['annotations']["ch"]['annotated'] = 0;
            $thisUserInfo['annotations']["gr"]['annotated'] = 0;
            $thisUserInfo['annotations']["ch"]['total'] = $ClusterInfo["ch"][$row['data']['rc_cluster']];
            $thisUserInfo['annotations']["gr"]['total'] = $ClusterInfo["gr"][$row['data']['rc_cluster']];
            $UserInfo[$row['id']] = $thisUserInfo;
        }

        $query = "SELECT a.user, d.type, COUNT(*) num
            FROM hssh_annotations a
            LEFT JOIN users u ON u.id = a.user
            LEFT JOIN hssh_ds_task_cluster dtc ON a.sentence = dtc.id
            LEFT JOIN hssh_rows r ON r.id = dtc.row
            LEFT JOIN hssh_datasets d ON d.id = r.dataset_id
            WHERE a.deleted = '0' AND u.task = '{$Row['id']}'
                AND u.deleted = '0' AND u.educator = '0'
            GROUP BY a.user, d.type";
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $UserInfo[$row['user']]['annotations'][$row['type']]['annotated'] = $row['num'];
        }

        $ret['info'] = $UserInfo;
        break;

    case "exportResults":
        checkLogin();
        $Row = checkTask($_REQUEST['id']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $query = "SELECT a.id annotation_id, a.data, a.created_at, u.username,
                r.id sentence_id, r.content, d.name, d.type
            FROM hssh_annotations a
            LEFT JOIN users u ON u.id = a.user
            LEFT JOIN hssh_ds_task_cluster dtc ON a.sentence = dtc.id
            LEFT JOIN hssh_rows r ON r.id = dtc.row
            LEFT JOIN hssh_datasets d ON d.id = r.dataset_id
            WHERE u.task = '{$Row['id']}' AND a.deleted = '0'
            ORDER BY u.id";
        $result = $mysqli->query($query);

        $sheet->setCellValue('A1', "Annotation ID");
        $sheet->setCellValue('B1', "Username");
        $sheet->setCellValue('C1', "Sentence ID");
        $sheet->setCellValue('D1', "Type");
        $sheet->setCellValue('E1', "Dataset name");
        $sheet->setCellValue('F1', "Sentence");
        $sheet->setCellValue('G1', "Actions");
        $sheet->setCellValue('H1', "Annotation time");
        $sheet->setCellValue('I1', "Date/time");

        $i = 2;
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $row['data'] = json_decode($row['data'], true);
            $tokens = hssh_getTokens($row['content']);

            $actions = [];
            if ($row['type'] == "gr") {
                foreach ($row['data']['tokens'] as $index => $t) {
                    if ($t == HSSH_True) {
                        $actions[] = "Deleted: ({$index}) {$tokens[$index]}";
                    }
                }
            }
            else { // "ch"
                foreach ($row['data']['tokens'] as $index => $t) {
                    if ($t != HSSH_False) {
                        $actions[] = "Replaced: ({$index}) {$tokens[$index]} => {$t}";
                    }
                }
            }
            $annotationTime = 0.0;
            if (isset($row['data']['annotationTime'])) {
                $annotationTime = $row['data']['annotationTime'];
            }

            $sheet->setCellValue('A' . $i, intval($row['annotation_id']));
            $sheet->setCellValue('B' . $i, $row['username']);
            $sheet->setCellValue('C' . $i, intval($row['sentence_id']));
            $sheet->setCellValue('D' . $i, $row['type']);
            $sheet->setCellValue('E' . $i, $row['name']);
            $sheet->setCellValue('F' . $i, $row['content']);
            $sheet->setCellValue('G' . $i, implode("\n", $actions));
            $sheet->setCellValue('H' . $i, floatval($annotationTime));
            $sheet->setCellValue('I' . $i, $row['created_at']);
            $sheet->getStyle('G' . $i)->getAlignment()->setWrapText(true);

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

        $filename = "hssh-results-t" . $Row['id'];
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
        http_response_code(200);
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
        break;

    case "saveAnnotation":
        checkStudentLogin();
        $RowTask = checkTask();
        validate($_REQUEST, [
            'sentence_id' => 'required|integer',
            'tokens' => 'required'
        ]);
        $id = addslashes($_REQUEST['sentence_id']);
        $UserData = $RowTask['user_info']['data'];
        $cluster = addslashes($UserData['rc_cluster']);
        $query = "SELECT c.id,
                   r.content,
                   GROUP_CONCAT(a.session_id) annotations
            FROM   `hssh_ds_task_cluster` c
                   LEFT JOIN hssh_rows r
                          ON r.id = c.row
                   LEFT JOIN hssh_datasets d
                          ON d.id = r.dataset_id
                   LEFT JOIN hssh_annotations a
                          ON a.sentence = c.id
            WHERE  c.task = '{$RowTask['id']}'
                   AND c.cluster = '{$cluster}'
                   AND c.id = '{$id}'
            GROUP  BY c.id,
                      r.content";
        // $ret['query'] = str_replace("\n", " ", $query);
        $result = $mysqli->query($query);
        if (!$result->num_rows) {
            dieWithError("Wrong sentence ID");
        }

        // $tokens = $tokens['tokens'];

        // TODO: Check length?
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $orig_tokens = hssh_getTokens($row['content']);
        $tokens = json_decode($_REQUEST['tokens'], true);

        if (count($orig_tokens) != count($tokens['tokens'])) {
            dieWithError("Token count mismatch");
        }

        $data = [
            "sentence" => $id,
            "user" => $_SESSION['StudentLogin'],
            "session_id" => session_id(),
            "data" => json_encode($tokens)
        ];
        $query = queryinsert("hssh_annotations", $data);
        // $ret['query'] = $query;
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }

        // TODO: Check tokens

        // $ret['tokens'] = $tokens;
        // $ret['request'] = $tokens['tokens'];

        break;

    case "nextSentence":
        checkStudentLogin();
        $RowTask = checkTask();
        validate($_REQUEST, [
            'set' => 'required|in:ch,gr',
            'last_id' => 'integer',
        ]);
        $UserData = $RowTask['user_info']['data'];
        $cluster = addslashes($UserData['rc_cluster']);
        $set = addslashes($_REQUEST['set']);
        $query = "SELECT c.id,
                   r.content, r.goldLabel, r.goldTokens,
                   GROUP_CONCAT(a.session_id) annotations
            FROM   `hssh_ds_task_cluster` c
                   LEFT JOIN hssh_rows r
                          ON r.id = c.row
                   LEFT JOIN hssh_datasets d
                          ON d.id = r.dataset_id
                   LEFT JOIN hssh_annotations a
                          ON a.sentence = c.id AND a.deleted = '0' AND user = '{$RowTask['user_info']['id']}'
            WHERE  c.task = '{$RowTask['id']}'
                   AND c.cluster = '{$cluster}'
                   AND d.type = '{$set}'
            GROUP  BY c.id, r.content, r.goldLabel, r.goldTokens";
        // $ret['query'] = str_replace("\n", " ", $query);
        $result = $mysqli->query($query);
        if (!$result->num_rows) {
            dieWithError("No available sentences");
        }
        $found = $_REQUEST['last_id'] ? false : true;
        $FirstRow = false;
        $FirstRowWithoutAnnotations = false;
        $FinalRow = false;
        $FollowingRow = false;
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            if (!$FirstRow) {
                $FirstRow = $row;
            }
            if (!$FirstRowWithoutAnnotations && !$row['annotations']) {
                $FirstRowWithoutAnnotations = $row;
            }
            if ($found && !$row['annotations']) {
                $FinalRow = $row;
                break;
            }
            if ($found && !$FollowingRow) {
                $FollowingRow = $row;
            }
            if ($_REQUEST['last_id'] && $row['id'] == $_REQUEST['last_id']) {
                $found = true;
            }
        }
        if (!$FinalRow) {
            if ($FollowingRow) {
                $FinalRow = $FollowingRow;
            }
            else if ($FirstRowWithoutAnnotations) {
                $FinalRow = $FirstRowWithoutAnnotations;
            }
            else {
                $FinalRow = $FirstRow;
            }
        }

        $goldIDs = [];
        foreach (hssh_getTokens($FinalRow['goldTokens']) as $token) {
            $token = trim($token);
            if (!strlen($token)) {
                continue;
            }
            $goldIDs[] = intval($token);
        }
        $ret['sentence'] = [
                "id" => $FinalRow['id'],
                "tokens" => hssh_getTokens($FinalRow['content']),
                "goldLabel" => $FinalRow['goldLabel'],
                "goldTokens" => $goldIDs,
                "annotated" => $FinalRow['annotations'] ? true : false
            ];
        break;

    case "sentences":
        checkStudentLogin();
        $RowTask = checkTask();
        validate($_REQUEST, [
            'set' => 'required|in:ch,gr',
            'limit' => 'integer|min:1',
            'offset' => 'integer|min:0',
        ]);
        $UserData = $RowTask['user_info']['data'];
        $cluster = addslashes($UserData['rc_cluster']);
        $set = addslashes($_REQUEST['set']);
        $limit = $_REQUEST['limit'] ? $_REQUEST['limit'] : 100;
        $offset = $_REQUEST['offset'] ? $_REQUEST['offset'] : 0;
        $session_id = session_id();
        $query = "SELECT c.id,
                   r.content, r.goldLabel, r.goldTokens,
                   GROUP_CONCAT(a.session_id) annotations
            FROM   `hssh_ds_task_cluster` c
                   LEFT JOIN hssh_rows r
                          ON r.id = c.row
                   LEFT JOIN hssh_datasets d
                          ON d.id = r.dataset_id
                   LEFT JOIN hssh_annotations a
                          ON a.sentence = c.id AND a.deleted = '0' AND user = '{$RowTask['user_info']['id']}'
            WHERE  c.task = '{$RowTask['id']}'
                   AND c.cluster = '{$cluster}'
                   AND d.type = '{$set}'
                   AND c.id NOT IN (SELECT sentence
                                    FROM   hssh_annotations
                                    WHERE  user = '{$RowTask['user_info']['id']}'
                                           AND deleted = '0'
                                           AND session_id != '{$session_id}')
            GROUP  BY c.id, r.content, r.goldLabel, r.goldTokens";
        // $ret['query'] = str_replace("\n", " ", $query);
        $result = $mysqli->query($query);
        if (!$result->num_rows) {
            dieWithError("No available sentences");
        }
        $offset = $offset % $result->num_rows;
        $total = $offset + $limit;

        // $ret['total'] = $total;
        // $ret['offset'] = $offset;

        $thisIndex = 0;
        // $ret['userInfo'] = $RowTask['user_info'];
        $ret['sentences'] = [];
        for ($i = $offset; $i < $total; $i++) {
            $query_id = $i % $result->num_rows;
            $result->data_seek($query_id);
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $goldIDs = [];
            foreach (hssh_getTokens($row['goldTokens']) as $token) {
                $token = trim($token);
                if (!strlen($token)) {
                    continue;
                }
                $goldIDs[] = intval($token);
            }
            $ret['sentences'][] = [
                "id" => $row['id'],
                "tokens" => hssh_getTokens($row['content']),
                "goldLabel" => $row['goldLabel'],
                "goldTokens" => $goldIDs,
                "annotated" => $row['annotations'] ? true : false
            ];
        }
        break;

    case "saveGame":
        checkStudentLogin();
        // $ret['request'] = $_REQUEST;
        $RowTask = checkTask();
        $data = $RowTask['data'];
        if (!$_REQUEST['save_game']) {
            dieWithError("Missing variable save_game");
        }

        if (!$data['type_info']['save_game']) {
            $data['type_info']['save_game'] = [];
        }
        $data['type_info']['save_game'][$_SESSION['StudentLogin']] = $_REQUEST['save_game'];

        $dataJson = addslashes(json_encode($data));
        $query = "UPDATE tasks SET data = '$dataJson' WHERE id = '${RowTask['id']}'";
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        break;
}
