<?php
return [
    'field' => [
        'id' => [
            'name' => 'ID',
            'width' => 20,
            'fixed' => true
        ],
        "name" => [
            'name' => '角色名称',
            "order"=>true,
            'edit' => true,
            'title' => true
        ],
        'sort' => [
            'name' => '排序',
            'width' => 45,
            'fixed' => true,
            'edit' => true,
        ],
        'status' => [
            'name' => '状态',
            'status' => true,
            'width' => 45,
            'fixed' => true
        ],
    ],
    //编辑或增加
    'handle' => [
        'name',
        'sort' => [
            'value' => 255
        ],
        'status' => [
            'value' => '1'
        ],
        'json' => [
            'name' => '授权',
            'trees' => [
                "parent" => "parent_id",
                "child" => "id",
                "value" => 0,
                "table" => $GLOBALS['DOMAIN_CONFIG']['conn']."_menu",
                'name' => 'name',
                'sort' => ['sort', 'asc'],
                'where' => [
                    ['status', '=', '1']
                ],
                'end' => tpl("/sys/json/menu/add.data")
            ]
        ],
        'create_time',
        'update_time'
    ],
    'handleinfo' => [
        'width' => 500, //宽
        'height' => 300, //高
        'ismax' => true
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