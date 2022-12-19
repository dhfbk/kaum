<?php

ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

switch ($InputData['sub']) {

    case "getScenarios":
        checkLogin();
        $query = "SELECT * FROM rc_scenarios WHERE deleted = '0' ORDER BY id";
        $result = $mysqli->query($query);
        $ret['data'] = [];
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $ret['data'][] = $row;
        }
        $query = "SELECT * FROM rc_schools WHERE deleted = '0' ORDER BY id";
        $result = $mysqli->query($query);
        $ret['schools'] = [];
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $ret['schools'][] = $row;
        }
        break;

    case "deleteScenario":
        checkAdmin();
        $Row = find("rc_scenarios", $_REQUEST['id'], "Scenario not found");
        $query = "UPDATE rc_scenarios SET deleted = '1' WHERE id = '{$Row['id']}'";
        $result = $mysqli->query($query);
        break;

    case "addScenario":
        checkAdmin();
        $ret['debug'] = $_REQUEST;
        validate($_REQUEST['data'], [
            'channel_name' => 'required|min:3',
            'description' => 'required',
            'title' => 'required',
            'school' => 'required',
            'lang' => 'required' // TODO: check lang
        ]);
        $err = rc_channelNameIsWrong($_REQUEST['data']['channel_name']);
        if ($err) {
            dieWithError($err);
        }
        $data = [
            "name" => $_REQUEST['data']['title'],
            "lang" => $_REQUEST['data']['lang'],
            "label" => $_REQUEST['data']['channel_name'],
            "school" => $_REQUEST['data']['school'],
            "description" => $_REQUEST['data']['description']
        ];
        $query = queryinsert("rc_scenarios", $data);
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        break;

    case "chat":
        $Row = checkTask($_REQUEST['id'], 0, $_REQUEST['project_id']);
        if (!$Row['closed'] && !isAdmin()) {
            dieWithError("Only admins can read messages in running tasks", 401);
        }
        $ret['row'] = $Row;
        $TaskData = $Row['data'];

        $UserMap = loadUserMap($Row['id']);

        if (isset($TaskData['type_info']['rc_groups']) && $TaskData['type_info']['rc_groups'] > 1) {
            $ret['groups'] = [];
            foreach ($TaskData['type_info']['rc_groups_info'] as $groupInfo) {
                $channelID = $groupInfo['channel_id'];
                $messages = rc_getMessages($channelID, $UserMap);
                $ret['groups'][] = $messages;
            }
        }
        else {
            $channelID = $Row['data']['type_info']['channel_id'];
            $ret['messages'] = rc_getMessages($channelID, $UserMap);
        }
        // $ret['id'] = $channelID;
        break;

    // case "test":
    //     $user = new \ATDev\RocketChat\Users\User("t5-user1");
    //     $i = $user->info();
    //     $ret['info'] = $i;
    //     break;

    case "exportResults":
        $Row = checkTask($_REQUEST['id'], 0, $_REQUEST['project_id']);
        if (!$Row['closed'] && !isAdmin()) {
            dieWithError("Only admins can read messages in running tasks", 401);
        }
        $ret['row'] = $Row;
        $TaskData = $Row['data'];

        $UserMap = loadUserMap($Row['id']);

        $ret['groups'] = [];
        if (isset($TaskData['type_info']['rc_groups']) && $TaskData['type_info']['rc_groups'] > 1) {
            foreach ($TaskData['type_info']['rc_groups_info'] as $groupInfo) {
                $channelID = $groupInfo['channel_id'];
                $messages = rc_getMessages($channelID, $UserMap);
                $ret['groups'][] = $messages;
            }

        }
        else {
            $channelID = $Row['data']['type_info']['channel_id'];
            $messages = rc_getMessages($channelID, $UserMap);
            $ret['groups'][] = $messages;
        }

        $sos = [];
        if (isset($ret['row']['data']['type_info']['rc_groups_info'])) {
            foreach ($ret['row']['data']['type_info']['rc_groups_info'] as $groupInfo) {
                if (isset($groupInfo['sos_info'])) {
                    foreach ($groupInfo['sos_info'] as $s) {
                        $sos[] = $s;
                    }
                }
            }
        }
        if (isset($ret['row']['data']['type_info']['sos_info'])) {
            foreach ($ret['row']['data']['type_info']['sos_info'] as $s) {
                $sos[] = $s;
            }
        }

        $spreadsheet = new Spreadsheet();

        $messageCount = [];
        $messageCountUser = [];
        foreach ($UserMap as $username => $name) {
            $messageCountUser[$username] = 0;
        }
        foreach ($ret['groups'] as $index => $group) {
            $count = 0;

            $sheetName = 'Group '.($index + 1);
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetName);

            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setWidth(500, 'pt');

            $sheet->setCellValue('A1', "Message ID");
            $sheet->setCellValue('B1', "Time");
            $sheet->setCellValue('C1', "Username");
            $sheet->setCellValue('D1', "Name");
            $sheet->setCellValue('E1', "Text");

            $i = 2;
            foreach ($group as $message) {
                $sheet->setCellValue('A'.$i, $message['id']);
                $sheet->setCellValue('B'.$i, $message['ts']);
                $sheet->setCellValue('C'.$i, $message['username']);
                $sheet->setCellValue('D'.$i, $message['name']);
                $sheet->setCellValue('E'.$i, $message['text']);

                if ($message['username'] != "admin") {
                    $count++;
                    if (!isset($messageCountUser[$message['username']])) {
                        $messageCountUser[$message['username']] = 0;
                    }
                    $messageCountUser[$message['username']]++;
                }

                $i++;
            }

            $messageCount[] = $count;

            $spreadsheet->addSheet($sheet);
            $spreadsheet->getSheetByName($sheetName)->getStyle('E')->getAlignment()->setWrapText(true);
            $spreadsheet->getSheetByName($sheetName)->getStyle('E')->getNumberFormat()->setFormatCode(PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }

        $spreadsheet->setActiveSheetIndex(0)->setTitle("Statistics");
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', "Statistics");
        $sheet->setCellValue('A3', "Groups");
        $sheet->setCellValue('B3', count($ret['groups']));

        $sheet->setCellValue('A5', "Messages");
        $i = 6;
        foreach ($messageCount as $index => $count) {
            $sheet->setCellValue('A'.$i, "Group ".($index + 1));
            $sheet->setCellValue('B'.$i, $count);
            $i++;
        }
        $i++;

        if (count($sos)) {
            $sheet->setCellValue('A'.$i, "SOS");
            $i++;
            foreach ($sos as $s) {
                $sheet->setCellValue('A'.$i, $s['datetime']);
                $sheet->setCellValue('B'.$i, $s['roomname']);
                $sheet->setCellValue('C'.$i, $s['username']);
                $sheet->setCellValue('D'.$i, $s['message']);
                $i++;
            }
        }
        $i++;

        $sheet->setCellValue('A'.$i, "Users");
        $i++;
        foreach ($messageCountUser as $user => $count) {
            $sheet->setCellValue('A'.$i, $user);
            if (isset($UserMap[$user])) {
                $sheet->setCellValue('B'.$i, $UserMap[$user]);
            }
            $sheet->setCellValue('C'.$i, $count);
            $i++;
        }
        

        // $sheet = $spreadsheet->createSheet();
        // $sheet = $spreadsheet->getActiveSheet();

        // $query = "SELECT a.id annotation_id, a.data, a.created_at, u.username,
        //         r.id sentence_id, r.content, d.name, d.type
        //     FROM hssh_annotations a
        //     LEFT JOIN users u ON u.id = a.user
        //     LEFT JOIN hssh_ds_task_cluster dtc ON a.sentence = dtc.id
        //     LEFT JOIN hssh_rows r ON r.id = dtc.row
        //     LEFT JOIN hssh_datasets d ON d.id = r.dataset_id
        //     WHERE u.task = '{$Row['id']}' AND a.deleted = '0'
        //     ORDER BY u.id";
        // $result = $mysqli->query($query);

        // $sheet->setCellValue('A1', "Annotation ID");
        // $sheet->setCellValue('B1', "Username");
        // $sheet->setCellValue('C1', "Sentence ID");
        // $sheet->setCellValue('D1', "Type");
        // $sheet->setCellValue('E1', "Dataset name");
        // $sheet->setCellValue('F1', "Sentence");
        // $sheet->setCellValue('G1', "Actions");
        // $sheet->setCellValue('H1', "Annotation time");
        // $sheet->setCellValue('I1', "Date/time");

        // $i = 2;
        // while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        //     $row['data'] = json_decode($row['data'], true);
        //     $tokens = hssh_getTokens($row['content']);

        //     $actions = [];
        //     if ($row['type'] == "gr") {
        //         foreach ($row['data']['tokens'] as $index => $t) {
        //             if ($t == HSSH_True) {
        //                 $actions[] = "Deleted: ({$index}) {$tokens[$index]}";
        //             }
        //         }
        //     }
        //     else { // "ch"
        //         foreach ($row['data']['tokens'] as $index => $t) {
        //             if ($t != HSSH_False) {
        //                 $actions[] = "Replaced: ({$index}) {$tokens[$index]} => {$t}";
        //             }
        //         }
        //     }
        //     $annotationTime = 0.0;
        //     if (isset($row['data']['annotationTime'])) {
        //         $annotationTime = $row['data']['annotationTime'];
        //     }

        //     $sheet->setCellValue('A' . $i, intval($row['annotation_id']));
        //     $sheet->setCellValue('B' . $i, $row['username']);
        //     $sheet->setCellValue('C' . $i, intval($row['sentence_id']));
        //     $sheet->setCellValue('D' . $i, $row['type']);
        //     $sheet->setCellValue('E' . $i, $row['name']);
        //     $sheet->setCellValue('F' . $i, $row['content']);
        //     $sheet->setCellValue('G' . $i, implode("\n", $actions));
        //     $sheet->setCellValue('H' . $i, floatval($annotationTime));
        //     $sheet->setCellValue('I' . $i, $row['created_at']);
        //     $sheet->getStyle('G' . $i)->getAlignment()->setWrapText(true);

        //     $i++;
        //     // print_r($row);
        // }

        // $sheet->getColumnDimension('A')->setAutoSize(true);
        // $sheet->getColumnDimension('B')->setAutoSize(true);
        // $sheet->getColumnDimension('C')->setAutoSize(true);
        // $sheet->getColumnDimension('D')->setAutoSize(true);
        // $sheet->getColumnDimension('E')->setAutoSize(true);
        // $sheet->getColumnDimension('F')->setAutoSize(true);
        // $sheet->getColumnDimension('G')->setAutoSize(true);

        $filename = "p" . $Row['project_id'] . "-rc-results-t" . $Row['id'];
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
        http_response_code(200);
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        exit();
        break;

    case "sos":
        $ret['input'] = $_REQUEST;
        $ret['message'] = "SOS received";
        $ret['avatar'] = ":sos:";

        $username = addslashes($_REQUEST['username']);
        if (!$username) {
            $ret['message'] = "Invalid username - SOS *not* received by the system";
            $ret['avatar'] = ":warning:";
            break;
        }

        $RowUser = [];
        $TaskData = [];

        $query = "SELECT u.*, t.data
            FROM users u
            LEFT JOIN tasks t ON t.id = u.task
            WHERE u.username = '{$username}'
                AND u.educator = '0'
                AND u.deleted = '0'
                AND t.tool = 'rc'";
        $result = $mysqli->query($query);
        if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $RowUser = $row;
            $TaskData = json_decode($row['data'], true);
        }
        else {
            $ret['message'] = "Unable to find user {$username} - SOS *not* received by the system";
            $ret['avatar'] = ":warning:";
            break;
        }

        // $ret['user_data'] = $RowUser;
        // $ret['task_data'] = $TaskData;

        if (isset($TaskData['type_info']['rc_groups']) && $TaskData['type_info']['rc_groups'] > 1) {
            if (!isset($TaskData['type_info']['rc_user_channels'][$username])) {
                $ret['message'] = "User {$username} is not able to ask for SOS here";
                $ret['avatar'] = ":warning:";
                break;
            }
            $RoomID = $TaskData['type_info']['rc_user_channels'][$username]['channel_id'];
            $groupIndex = $TaskData['type_info']['rc_user_channels'][$username]['group_index'];
            $groupInfo = $TaskData['type_info']['rc_groups_info'][$groupIndex];

            if ($groupInfo['teacher_can_join']) {
                $ret['message'] = "In this task educators can already join the chat - SOS *not* received by the system";
                $ret['avatar'] = ":warning:";
                break;
            }
            if (!$groupInfo['sos_info']) {
                $TaskData['type_info']['rc_groups_info'][$groupIndex]['sos_info'] = [];
            }

            rc_addEducatorsToChannel($RowUser['project'], $RoomID);

            if (!count($TaskData['type_info']['rc_groups_info'][$groupIndex]['sos_info'])) {
                $message = new \ATDev\RocketChat\Messages\Message();
                $message->setRoomId($RoomID);
                $message->setEmoji(":sos:");
                $message->setText("Someone has called the SOS command, an educator will join the room soon");
                $result = $message->postMessage();
            }

            $sos_info = [];
            $sos_info['username'] = $_REQUEST['username'];
            $sos_info['message'] = $_REQUEST['message'];
            $sos_info['roomname'] = $_REQUEST['roomname'];
            $sos_info['datetime'] = date("r");

            $TaskData['type_info']['rc_groups_info'][$groupIndex]['sos_info'][] = $sos_info;
        }
        else {
            if ($TaskData['type_info']['teacher_can_join']) {
                $ret['message'] = "In this task educators can already join the chat - SOS *not* received by the system";
                $ret['avatar'] = ":warning:";
                break;
            }

            if (!$TaskData['type_info']['sos_info']) {
                $TaskData['type_info']['sos_info'] = [];
            }

            $RoomID = $TaskData['type_info']['channel_id'];

            rc_addEducatorsToChannel($RowUser['project'], $RoomID);

            if (!count($TaskData['type_info']['sos_info'])) {
                $message = new \ATDev\RocketChat\Messages\Message();
                $message->setRoomId($RoomID);
                $message->setEmoji(":sos:");
                $message->setText("Someone has called the SOS command, an educator will join the room soon");
                $result = $message->postMessage();
            }
            
            $sos_info = [];
            $sos_info['username'] = $_REQUEST['username'];
            $sos_info['message'] = $_REQUEST['message'];
            $sos_info['roomname'] = $_REQUEST['roomname'];
            $sos_info['datetime'] = date("r");

            $TaskData['type_info']['sos_info'][] = $sos_info;
        }

        $dataJson = addslashes(json_encode($TaskData));
        $query = "UPDATE tasks SET data = '$dataJson' WHERE id = '{$RowUser['task']}'";
        $mysqli->query($query);

        break;

        // $smtp_info = json_decode($Options['smtp'], true);
        // $mail = new PHPMailer(true);
        // try {
        //     $mail->isSMTP();
        //     $mail->Host       = $smtp_info['server'];
        //     $mail->SMTPAuth   = true;
        //     $mail->Username   = $smtp_info['login'];
        //     $mail->Password   = $smtp_info['password'];
        //     $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        //     $mail->Port       = $smtp_info['port'];

        //     $mail->setFrom('ziorufus57@gmail.com', 'Mailer');
        //     $mail->addAddress('alessio@apnetwork.it');

        //     $mail->isHTML(true);
        //     $mail->Subject = 'Here is the subject';
        //     $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
        //     $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        //     $mail->send();
        //     $ret['message'] .= ' - Message has been sent';
        // } catch (Exception $e) {
        //     $ret['message'] .= " - Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        // }

        // break;

}
