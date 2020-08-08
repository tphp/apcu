<?php

$btpt = BASE_TPL_PATH_TOP;
$btpt = trim(trim($btpt), "/");
$btpt = str_replace("/", "_", $btpt);
return [
    //编辑或增加
    'handle' => [
        'old_password' => ['name' => '旧密码', 'type' => 'password'],
        'password' => ['name' => '密码', 'type' => 'password', 'md5' => true, 'salt' => 'salt'],
        'create_time',
        'update_time'
    ],
    'handleinit' => [
        'id' => '_%'.$btpt.'_sys_user_login_userinfo.id%_'
    ]
];