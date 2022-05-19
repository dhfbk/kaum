<?php

switch ($InputData['sub']) {
    case "listDatasets":
        $RowProject = checkProject($InputData['project_id']);
        $ret['datasets'] = hssh_listDatasets($RowProject['id']);
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
                   r.content,
                   GROUP_CONCAT(a.session_id) annotations
            FROM   `hssh_ds_task_cluster` c
                   LEFT JOIN hssh_rows r
                          ON r.id = c.row
                   LEFT JOIN hssh_datasets d
                          ON d.id = r.dataset_id
                   LEFT JOIN hssh_annotations a
                          ON a.sentence = c.id AND a.deleted = '0'
            WHERE  c.task = '{$RowTask['id']}'
                   AND c.cluster = '{$cluster}'
                   AND d.type = '{$set}'
            GROUP  BY c.id,
                      r.content";
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

        $ret['sentence'] = [
                "id" => $FinalRow['id'],
                "tokens" => hssh_getTokens($FinalRow['content']),
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
                   AND d.type = '{$set}'
                   AND c.id NOT IN (SELECT sentence
                                    FROM   hssh_annotations
                                    WHERE  user = '{$RowTask['user_info']['id']}'
                                           AND deleted = '0'
                                           AND session_id != '{$session_id}')
            GROUP  BY c.id,
                      r.content";
        // $ret['query'] = str_replace("\n", " ", $query);
        $result = $mysqli->query($query);
        $offset = $offset % $result->num_rows;
        $total = $offset + $limit;

        // $ret['total'] = $total;
        // $ret['offset'] = $offset;

        $thisIndex = 0;
        $ret['sentences'] = [];
        for ($i = $offset; $i < $total; $i++) {
            $query_id = $i % $result->num_rows;
            $result->data_seek($query_id);
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $ret['sentences'][] = [
                "id" => $row['id'],
                "tokens" => hssh_getTokens($row['content']),
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
