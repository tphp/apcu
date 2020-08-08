<?php
$pk = $_GET['pk'];
$id = "";
$conn = $GLOBALS['DOMAIN_CONFIG']['user'];
empty($conn) && $conn = $this->config['config']['conn'];
empty($conn) && $conn = $GLOBALS['DOMAIN_CONFIG']['conn'];
if(!empty($pk)){
    $idinfo = json_decode($pk, true)[0];
    if(!empty($idinfo) && is_string($idinfo)){
        $id = json_decode($idinfo, true)['id'];
    }
}
if(empty($id)){
    $user_info = $this->db('admin', $conn)->orderBy('id', 'desc')->first();
    if(empty($user_info)){
        $id = 1;
    }else{
        $id = $user_info->id + 1;
    }
}
$b_path = $conn."/".$id;
return [
    'field' => [
        'id' => [
            'name' => 'ID',
            'width' => 20,
            'fixed' => true
        ],
        "username" => [
            'name' => '用户名',
            "order"=>true,
            'edit' => true,
            'title' => true,
            'search' => true
        ],
        'image' => ['name' => '个人头像', 'type' => 'image', 'thumbs' => [
            [200, 200],
            [100, 100]
        ], 'path' => 'sys/user/userinfo/'.$b_path, 'filename' => 'pic'],
//        "image" => [
//            'type' => 'field',
//            'field' => [
//                'name' => '名称',
//                'age' => '年龄'
//            ]
//        ],
        "role_id" => [
            'name' => '角色',
            "order"=>true,
            'title' => true,
            'search' => true,
            'from' => [
                [$GLOBALS['DOMAIN_CONFIG']['conn'].'_role', 'user'],
                'id',
                'name'
            ],
//            'list' => [
//                'ff' => 'bac',
//                'ds' => 'bsc',
//                '1' => 'iiu'
//            ],
            'edit' => true,
            'type' => 'selects'
        ],
        'status' => [
            'name' => '状态',
            'status' => true,
            'width' => 45,
            'fixed' => true,
            'search' => true
        ],
    ],
    //编辑或增加
    'handle' => [
        'username' => [
            'key' => true
        ],
        'role_id',
        'password' => [
            'name' => '密码',
            'type' => 'password',
            'md5' => true,
            'salt' => 'salt'
        ],
        'image',
        'status' => [
            'value' => '1',
            'batch' => "修改状态"
        ],
        'create_time',
        'update_time'
    ],
    'handleinfo' => [
        'width' => 800, //宽
        'height' => 500, //高
    ],
//    //'ispage' => true,
//    //'pagesize' => 5,
    'is' => [
        'checkbox' => true, //选择框
        'numbers' => true, //序列
        'delete' => true, //删除
        'add' => true,
    ],
];
