<?php

validate($Info['type_info'], [
    'rc_groups' => 'required|min:1|max:' + $Info['students']
]);

if ($Info['type_info']['rc_groups'] > 1 && $Info['type_info']['rc_uniqueScenario'] != 1) {
    unset($Info['type_info']['channel_name']);
    unset($Info['type_info']['description']);
    unset($Info['type_info']['teacher_can_join']);
    for ($i = 1; $i <= $Info['type_info']['rc_groups']; $i++) {
        validate($Info['type_info']['rc_scenario_groups'][$i], [
            'channel_name' => 'required|min:3',
            'description' => 'required'
        ]);

        $err = rc_channelNameIsWrong($Info['type_info']['rc_scenario_groups'][$i]['channel_name']);
        if ($err) {
            dieWithError($err);
        }
    }
}

if ($Info['type_info']['rc_uniqueScenario'] == 1) {
    unset($Info['type_info']['rc_scenario_groups']);
    validate($Info['type_info'], [
        'channel_name' => 'required|min:3',
        'description' => 'required'
    ]);

    $err = rc_channelNameIsWrong($Info['type_info']['channel_name']);
    if ($err) {
        dieWithError($err);
    }
}

if ($Info['type_info']['rc_groups'] > 1) {
    $groups = [];
    for ($i = 1; $i <= $Info['students']; $i++) {
        if (!isset($Info['type_info']['rc_user_groups'][$i])) {
            dieWithError("Missing value for user $i");
        }
        if ($Info['type_info']['rc_user_groups'][$i] === "") {
            dieWithError("Empty value for user $i");
        }
        if (!isset($groups[$Info['type_info']['rc_user_groups'][$i]])) {
            $groups[$Info['type_info']['rc_user_groups'][$i]] = [];
        }
        $groups[$Info['type_info']['rc_user_groups'][$i]][$i] = true;
    }
    for ($i = 1; $i <= $Info['type_info']['rc_groups']; $i++) {
        if (!isset($groups[$i]) || count($groups[$i]) <= 1) {
            dieWithError("Group $i has less than 2 users");
        }
        unset($groups[$i]);
    }
    unset($groups[0]);
    if (count($groups)) {
        dieWithError("Unknown group(s): " . implode(", ", array_keys($groups)));
    }
}

// dieWithError(print_r($Info['type_info'], true));
