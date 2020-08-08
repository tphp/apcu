<?php
return function(){
    return [
        [
            "id" => -1,
            "parent_id" => 0,
            "name" => "系统设置",
            "icon" => "cog",
            "url" => "/sys/manage"
        ],

        //菜单管理
        [
            "id" => -2,
            "parent_id" => -1,
            "name" => "菜单管理",
            "icon" => "navicon",
            "url" => "/sys/manage/menu.list"
        ],

        //用户管理
        [
            "id" => -3,
            "parent_id" => -1,
            "name" => "用户管理",
            "icon" => "user",
            "url" => "/sys/manage/user"
        ],
        [
            "id" => -31,
            "parent_id" => -3,
            "name" => "用户列表",
            "icon" => "user-o",
            "url" => "/sys/manage/user/list.list"
        ],
        [
            "id" => -32,
            "parent_id" => -3,
            "name" => "角色管理",
            "icon" => "male",
            "url" => "/sys/manage/user/role.list"
        ],
        [
            "id" => -5,
            "parent_id" => -1,
            "name" => "配置函数",
            "icon" => "code",
            "url" => "/sys/manage/menu/ini"
        ],
        //数据库
        [
            "id" => -4,
            "parent_id" => -1,
            "name" => "数据库同步",
            "icon" => "table",
            "url" => "/sys/manage/sql/diff"
        ],
        //GIT管理
        [
            "id" => -6,
            "parent_id" => -1,
            "name" => "GIT管理",
            "icon" => "git",
            "url" => "/sys/manage/git"
        ],
        //帮助中心
        [
            "id" => -7,
            "parent_id" => -1,
            "name" => "帮助中心",
            "icon" => "question",
            "url" => "/sys/manage/help.list"
        ]
    ];
};