<?php

ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

switch ($InputData['sub']) {
    case "chat":
        $Row = checkTask($_REQUEST['id'], 0, $_REQUEST['project_id']);
        if (!$Row['closed'] && !isAdmin()) {
            dieWithError("Only admins can read messages in running tasks", 401);
        }
        $ret['row'] = $Row;
        $TaskData = $Row['data'];
        if (isset($TaskData['type_info']['rc_groups']) && $TaskData['type_info']['rc_groups'] > 1) {
            $ret['groups'] = [];
            foreach ($TaskData['type_info']['rc_groups_info'] as $groupInfo) {
                $channelID = $groupInfo['channel_id'];
                $messages = [];
                $group = new \ATDev\RocketChat\Groups\Group($channelID);
                $result = $group->messages();
                foreach ($result as $message) {
                    $t = $message->getT();
                    if ($t) {
                        continue;
                    }
                    $msg = [];
                    $msg['ts'] = $message->getTs();
                    $msg['username'] = $message->getUsername();
                    $msg['text'] = $message->getMsg();
                    $messages[] = $msg;
                    // $ret['messages'][] = $message;
                    // echo $message->getUsername() . " - ";
                    // echo $message->getMsg() . "\n";
                    // print_r($message);
                }
                $messages = array_reverse($messages);
                $ret['groups'][] = $messages;
            }

        }
        else {
            $channelID = $Row['data']['type_info']['channel_id'];

            $ret['messages'] = [];
            $group = new \ATDev\RocketChat\Groups\Group($channelID);
            $result = $group->messages();
            foreach ($result as $message) {
                $t = $message->getT();
                if ($t) {
                    continue;
                }
                $msg = [];
                $msg['ts'] = $message->getTs();
                $msg['username'] = $message->getUsername();
                $msg['text'] = $message->getMsg();
                $ret['messages'][] = $msg;
                // $ret['messages'][] = $message;
                // echo $message->getUsername() . " - ";
                // echo $message->getMsg() . "\n";
                // print_r($message);
            }
            $ret['messages'] = array_reverse($ret['messages']);

        }
        // $ret['id'] = $channelID;
        break;

    // case "test":
    //     $user = new \ATDev\RocketChat\Users\User("t5-user1");
    //     $i = $user->info();
    //     $ret['info'] = $i;
    //     break;

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
