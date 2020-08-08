<?php

$btpt = BASE_TPL_PATH_TOP;
$btpt = trim(trim($btpt), "/");
$btpt = str_replace("/", "_", $btpt);
$conn = $GLOBALS['DOMAIN_CONFIG']['user'];
empty($conn) && $conn = $this->config['config']['conn'];
empty($conn) && $conn = $GLOBALS['DOMAIN_CONFIG']['conn'];
return [
    //编辑或增加
    'handle' => [
        'image' => ['name' => '个人头像', 'type' => 'image', 'thumbs' => [
                [200, 200],
                'image_big' => [400, 400]
            ],
            'path' => 'sys/user/userinfo/'.$conn.'/_%'.$btpt.'_sys_user_login_userinfo.id%_', 'filename' => 'pic'
        ],
        'create_time',
        'update_time'
    ],
    'handleinit' => [
        'id' => '_%'.$btpt.'_sys_user_login_userinfo.id%_'
    ]
];