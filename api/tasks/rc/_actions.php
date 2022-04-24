<?php

ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

switch ($InputData['sub']) {
    case "test":
        \ATDev\RocketChat\Chat::setUrl(RC_URL);
        $result = \ATDev\RocketChat\Chat::login("admin", $rcPassword);
        if (!$result) {
            $error = \ATDev\RocketChat\Chat::getError();
            dieWithError($error);
        }

        $user = new \ATDev\RocketChat\Users\User("t6-user1");
        $i = $user->info();
        print_r($i);
        print_r($user);
        break;

    case "sos":
        $ret['input'] = $_REQUEST;
        $ret['message'] = "SOS received - " . print_r($_REQUEST, true);

        $username = addslashes($_REQUEST['username']);
        if (!$username) {
            $ret['message'] = "Invalid username - SOS *not* received by the system";
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
            break;
        }

        // $ret['user_data'] = $RowUser;
        // $ret['task_data'] = $TaskData;

        if ($TaskData['teacher_can_join']) {
            $ret['message'] = "In this task educators can already join the chat - SOS *not* received by the system";
            break;
        }

        if (!$TaskData['sos_info']) {
            $TaskData['sos_info'] = [];
        }

        $sos_info = [];
        $sos_info['username'] = $_REQUEST['username'];
        $sos_info['message'] = $_REQUEST['message'];
        $sos_info['roomname'] = $_REQUEST['roomname'];
        $sos_info['datetime'] = date("r");

        $TaskData['sos_info'][] = $sos_info;

        $dataJson = addslashes(json_encode($TaskData));
        $query = "UPDATE tasks SET data = '$dataJson' WHERE id = '{$RowUser['task']}'";
        $mysqli->query($query);

        break;

        $smtp_info = json_decode($Options['smtp'], true);
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtp_info['server'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_info['login'];
            $mail->Password   = $smtp_info['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtp_info['port'];

            $mail->setFrom('ziorufus57@gmail.com', 'Mailer');
            $mail->addAddress('alessio@apnetwork.it');

            $mail->isHTML(true);
            $mail->Subject = 'Here is the subject';
            $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            $ret['message'] .= ' - Message has been sent';
        } catch (Exception $e) {
            $ret['message'] .= " - Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }


        break;

        // \ATDev\RocketChat\Chat::setUrl(RC_URL);
        // $result = \ATDev\RocketChat\Chat::login("admin", $rcPassword);
        // if (!$result) {
        //     $error = \ATDev\RocketChat\Chat::getError();
        //     dieWithError($error);
        // }

        // // $listing = \ATDev\RocketChat\Users\User::listing();
        // // print_r($listing);

        // $user = new \ATDev\RocketChat\Users\User("pippo");
        // try {
        //     $user->info();
        // } catch (\Exception $e) {
            
        // }
        // print_r($user);

        // break;

        // // $channel = new \ATDev\RocketChat\Channels\Channel();
        // // $channel->setName("PLUTO");
        // // $result = $channel->create();
        // // print_r($result);

        // $channel = new \ATDev\RocketChat\Channels\Channel("GENERAL");
        // // print_r($channel);
        // // $result = $channel->info();
        // $result = $channel->delete();
        // print_r($result);
        // exit();

        // $listing = \ATDev\RocketChat\Channels\Channel::listing();
        // print_r($listing);
        // exit();

        // break;
        // try {
        //     $user = new \ATDev\RocketChat\Users\User();
        //     $user->setName("John Doe");
        //     $user->setEmail("john@example.com");
        //     $user->setUsername("jDoe");
        //     $user->setPassword("123456");

        //     $result = $user->create();
        // } catch (Exception $e) {
        //     echo $e->getMessage();
        //     exit();
        //     $ret['res'] = 'Caught exception: ' . $e->getMessage();
        // }


        // if (!$result) {
        //     dieWithError($user->getError());
        // }

        // // $ret['res'] = print_r($result, true);
        // break;
}
