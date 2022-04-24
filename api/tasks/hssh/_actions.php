<?php

switch ($InputData['sub']) {
    case "listDatasets":
        $projectInfo = checkProject($InputData['project_id']);
        $ret['datasets'] = hssh_listDatasets($projectInfo['project_id']);
        break;
}
