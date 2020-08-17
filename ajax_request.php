<?php

function getParam($param) {
    return isset($_REQUEST[$param]) ? $_REQUEST[$param] : null;
}
$cmd = getParam('cmd');

switch ($cmd) {
    case 'task_2':
        $add = getParam('add');
        if ($add = getParam('add')) {
            $add = ' ('.$add.')';
        }
        $data = [
            0 => ['id' => 1, 'name' => 'Load name 1'.$add],
            1 => ['id' => 2, 'name' => 'Load name 2'.$add],
            2 => ['id' => 3, 'name' => 'Load name 3'.$add],
            3 => ['id' => 4, 'name' => 'Load name 4'.$add],
        ];
        break;

    case 'task_5':
        $data = [
            0 => ['name' => 'Александр', 'sex' => 'муж', 'status' => 1],
            1 => ['name' => 'Игорь', 'sex' => 'муж', 'status' => 1],
            2 => ['name' => 'Таня', 'sex' => 'жен', 'status' => 1],
            3 => ['name' => 'Вова', 'sex' => 'муж', 'status' => 0],
            4 => ['name' => 'Ира', 'sex' => 'жен', 'status' => 1],
            5 => ['name' => 'Сергей', 'sex' => 'муж', 'status' => 1],
            6 => ['name' => 'Аня', 'sex' => 'жен', 'status' => 0],
        ];
        break;
}

echo json_encode($data);