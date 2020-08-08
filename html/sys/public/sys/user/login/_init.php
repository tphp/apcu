<?php
use Tphp\Apcu\Controllers\SqlController;
return function(){
    $userinfoid = $this->getCacheId("userinfo");
    $userinfo = Session::get($userinfoid);
    $is_post = $this->isPost();
    if(!empty($userinfo) && !$is_post) redirect("/")->send();
    $rememberid = $this->getCacheId("remember");
    $usernameid = $this->getCacheId("username");
    $login_captcha = getenv('LOGIN_CAPTCHA');
    is_string($login_captcha) && $login_captcha = strtolower(trim($login_captcha));
    if($login_captcha === true || $login_captcha === 'true'){
        $login_captcha = true;
    }else{
        $login_captcha = false;
    }
    $db_config = config("database.connections");
    $conn = trim($GLOBALS['DOMAIN_CONFIG']['conn']);
    if(empty($conn)){
        if($this->isPost()){
            EXITJSON(0, "数据库链接未设置");
        }
        exit("数据库链接未设置");
    }elseif(!isset($db_config[$conn])){
        if($this->isPost()){
            EXITJSON(0, "无效数据源: {$conn}");
        }
        exit("无效数据源: {$conn}");
    }

    $rconn = 'user';
    if($conn !== $rconn){
        $dmenu = $conn."_menu";
        $dmenu_field = $conn."_menu_field";
        $drole = $conn."_role";
        $dbinfo = $this->dbInfo('user');
        $r_driver = $db_config[$rconn]['driver'];
        $clear_cache = false;
        //菜单目录表
        if(!isset($dbinfo[$dmenu])){
            if($r_driver == 'mysql'){
                $sqlstr = <<<EOF
CREATE TABLE if NOT EXISTS `{$dmenu}` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
    `parent_id` INT(11) NULL DEFAULT NULL COMMENT '父ID',
    `name` VARCHAR(20) NULL DEFAULT NULL COMMENT '菜单名称',
    `description` VARCHAR(50) NULL DEFAULT NULL COMMENT '描述',
    `icon` VARCHAR(50) NULL DEFAULT NULL COMMENT '文字图标样式',
    `url` VARCHAR(100) NULL DEFAULT NULL COMMENT '链接',
    `params` VARCHAR(100) NULL DEFAULT NULL COMMENT 'URL参数',
    `type` VARCHAR(20) NULL DEFAULT NULL COMMENT '链接类型',
    `sort` TINYINT(3) UNSIGNED NULL DEFAULT '255' COMMENT '排序',
    `status` TINYINT(1) NULL DEFAULT '1' COMMENT '是否启用：0禁用 1启用',
    `create_time` INT(11) NULL DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) NULL DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
ROW_FORMAT=DYNAMIC;
EOF;
            }elseif($r_driver == 'sqlsrv'){
                $sqlstr = <<<EOF
if OBJECT_ID(N'{$dmenu}',N'U') is null
	BEGIN
	CREATE TABLE [dbo].[{$dmenu}](
        [id] [int] identity(1,1) primary key,
        [parent_id] [int] DEFAULT 0,
        [name] [nvarchar](20) NULL,
        [description] [nvarchar](50) NULL,
        [icon] [nvarchar](50) NULL,
        [url] [nvarchar](100) NULL,
        [params] [nvarchar](100) NULL,
        [type] [nvarchar](20) NULL,
        [sort] [tinyint] DEFAULT 255,
        [status] [tinyint] DEFAULT 1,
        [create_time] [int] NULL,
        [update_time] [int] NULL
	);
	execute sp_addextendedproperty 'MS_Description','菜单ID','user','dbo','table','{$dmenu}','column','id';
	execute sp_addextendedproperty 'MS_Description','父ID','user','dbo','table','{$dmenu}','column','parent_id';
	execute sp_addextendedproperty 'MS_Description','菜单名称','user','dbo','table','{$dmenu}','column','name';
	execute sp_addextendedproperty 'MS_Description','描述','user','dbo','table','{$dmenu}','column','description';
	execute sp_addextendedproperty 'MS_Description','文字图标样式','user','dbo','table','{$dmenu}','column','icon';
	execute sp_addextendedproperty 'MS_Description','链接','user','dbo','table','{$dmenu}','column','url';
	execute sp_addextendedproperty 'MS_Description','URL参数','user','dbo','table','{$dmenu}','column','params';
	execute sp_addextendedproperty 'MS_Description','链接类型','user','dbo','table','{$dmenu}','column','type';
	execute sp_addextendedproperty 'MS_Description','排序','user','dbo','table','{$dmenu}','column','sort';
	execute sp_addextendedproperty 'MS_Description','是否启用：0禁用 1启用','user','dbo','table','{$dmenu}','column','status';
	execute sp_addextendedproperty 'MS_Description','创建时间','user','dbo','table','{$dmenu}','column','create_time';
	execute sp_addextendedproperty 'MS_Description','更新时间','user','dbo','table','{$dmenu}','column','update_time';
	END
EOF;
            }elseif($r_driver == 'pgsql'){
                $sqlstr = <<<EOF
CREATE TABLE IF NOT EXISTS "public"."{$dmenu}" (
  "id" serial primary key,
  "parent_id" int4,
  "name" varchar(20) COLLATE "pg_catalog"."default",
  "description" varchar(50) COLLATE "pg_catalog"."default",
  "icon" varchar(50) COLLATE "pg_catalog"."default",
  "url" varchar(100) COLLATE "pg_catalog"."default",
  "params" varchar(100) COLLATE "pg_catalog"."default",
  "type" varchar(20) COLLATE "pg_catalog"."default",
  "sort" int2 DEFAULT 255,
  "status" int2 DEFAULT 1,
  "create_time" int4,
  "update_time" int4
);
COMMENT ON COLUMN "public"."{$dmenu}"."id" IS '菜单ID';
COMMENT ON COLUMN "public"."{$dmenu}"."parent_id" IS '父ID';
COMMENT ON COLUMN "public"."{$dmenu}"."name" IS '菜单名称';
COMMENT ON COLUMN "public"."{$dmenu}"."description" IS '描述';
COMMENT ON COLUMN "public"."{$dmenu}"."icon" IS '文字图标样式';
COMMENT ON COLUMN "public"."{$dmenu}"."url" IS '链接';
COMMENT ON COLUMN "public"."{$dmenu}"."params" IS 'URL参数';
COMMENT ON COLUMN "public"."{$dmenu}"."type" IS '链接类型';
COMMENT ON COLUMN "public"."{$dmenu}"."sort" IS '排序';
COMMENT ON COLUMN "public"."{$dmenu}"."status" IS '是否启用：0禁用 1启用';
COMMENT ON COLUMN "public"."{$dmenu}"."create_time" IS '创建时间';
COMMENT ON COLUMN "public"."{$dmenu}"."update_time" IS '更新时间';
EOF;
                $sqlstr = explode(";", $sqlstr);
            }elseif($r_driver == 'sqlite'){
                $sqlstr = <<<EOF
CREATE TABLE IF NOT EXISTS "{$dmenu}" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "parent_id" integer,
  "name" text(20),
  "description" text(50),
  "icon" text(50),
  "url" text(100),
  "params" text(100),
  "type" text(20),
  "sort" integer DEFAULT 255,
  "status" integer(1) DEFAULT 1,
  "create_time" integer,
  "update_time" integer
);
EOF;
            }
            $clear_cache = SqlController::runDbExcute($this->db(false, $rconn), $sqlstr);
        }
        //菜单字段搜索表
        if(!isset($dbinfo[$dmenu_field])){
            if($r_driver == 'mysql') {
                $sqlstr = <<<EOF
CREATE TABLE if NOT EXISTS `{$dmenu_field}` (
	`menu_id` INT(11) NOT NULL COMMENT '菜单ID',
	`user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
	`field` VARCHAR(512) NULL DEFAULT NULL COMMENT '字段信息',
	PRIMARY KEY (`menu_id`, `user_id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOF;
            }elseif($r_driver == 'sqlsrv'){
                $sqlstr = <<<EOF
if OBJECT_ID(N'{$dmenu_field}',N'U') is null
	BEGIN
	CREATE TABLE [dbo].[{$dmenu_field}](
        [menu_id] [int] NOT NULL,
        [user_id] [int] NOT NULL,
        [field] [nvarchar](512) NULL,
        PRIMARY KEY CLUSTERED
        (
            [menu_id] ASC,
            [user_id] ASC
        )
	);
	execute sp_addextendedproperty 'MS_Description','菜单ID','user','dbo','table','{$dmenu_field}','column','menu_id';
	execute sp_addextendedproperty 'MS_Description','用户ID','user','dbo','table','{$dmenu_field}','column','user_id';
	execute sp_addextendedproperty 'MS_Description','字段信息','user','dbo','table','{$dmenu_field}','column','field';
	END
EOF;
            }elseif($r_driver == 'pgsql'){
                $sqlstr = <<<EOF
CREATE TABLE IF NOT EXISTS "public"."{$dmenu_field}" (
  "menu_id" int4 NOT NULL,
  "user_id" int4 NOT NULL,
  "field" varchar(512) COLLATE "pg_catalog"."default",
  CONSTRAINT "{$dmenu_field}_pkey" PRIMARY KEY ("menu_id", "user_id")
);
COMMENT ON COLUMN "public"."{$dmenu_field}"."menu_id" IS '菜单ID';
COMMENT ON COLUMN "public"."{$dmenu_field}"."user_id" IS '用户ID';
COMMENT ON COLUMN "public"."{$dmenu_field}"."field" IS '字段信息';
EOF;
                $sqlstr = explode(";", $sqlstr);
            }elseif($r_driver == 'sqlite'){
                $sqlstr = <<<EOF
CREATE TABLE IF NOT EXISTS "{$dmenu_field}" (
  "menu_id" integer NOT NULL,
  "user_id" integer NOT NULL,
  "field" text(512),
  PRIMARY KEY ("menu_id", "user_id")
);
EOF;
            }
            $clear_cache = SqlController::runDbExcute($this->db(false, $rconn), $sqlstr);
        }
        //菜单角色表
        if(!isset($dbinfo[$drole])){
            if($r_driver == 'mysql') {
                $sqlstr = <<<EOF
CREATE TABLE if NOT EXISTS `{$drole}` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
	`name` VARCHAR(20) NULL DEFAULT NULL COMMENT '角色名称',
	`sort` TINYINT(3) UNSIGNED NULL DEFAULT '255' COMMENT '排序',
	`status` TINYINT(3) NULL DEFAULT '1' COMMENT '状态',
	`json` VARCHAR(1024) NULL DEFAULT NULL COMMENT '角色目录',
	`create_time` INT(11) NULL DEFAULT NULL COMMENT '创建时间',
	`update_time` INT(11) NULL DEFAULT NULL COMMENT '更新时间',
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOF;
            }elseif($r_driver == 'sqlsrv'){
                $sqlstr = <<<EOF
if OBJECT_ID(N'{$drole}',N'U') is null
	BEGIN
	CREATE TABLE [dbo].[{$drole}](
        [id] [int] identity(1,1) primary key,
        [name] [nvarchar](20) NULL,
        [sort] [tinyint] DEFAULT 255,
        [json] [nvarchar](1024) NULL,
        [status] [tinyint] DEFAULT 1,
        [create_time] [int] NULL,
        [update_time] [int] NULL
	);
	execute sp_addextendedproperty 'MS_Description','ID','user','dbo','table','{$drole}','column','id';
	execute sp_addextendedproperty 'MS_Description','角色名称','user','dbo','table','{$drole}','column','name';
	execute sp_addextendedproperty 'MS_Description','排序','user','dbo','table','{$drole}','column','sort';
	execute sp_addextendedproperty 'MS_Description','状态','user','dbo','table','{$drole}','column','json';
	execute sp_addextendedproperty 'MS_Description','角色目录','user','dbo','table','{$drole}','column','status';
	execute sp_addextendedproperty 'MS_Description','创建时间','user','dbo','table','{$drole}','column','create_time';
	execute sp_addextendedproperty 'MS_Description','更新时间','user','dbo','table','{$drole}','column','update_time';
	END
EOF;
            }elseif($r_driver == 'pgsql'){
                $sqlstr = <<<EOF
CREATE TABLE IF NOT EXISTS "public"."{$drole}" (
  "id" serial primary key,
  "name" varchar(20) COLLATE "pg_catalog"."default",
  "sort" int2 DEFAULT 255,
  "status" int2 DEFAULT 1,
  "json" varchar(1024) COLLATE "pg_catalog"."default",
  "create_time" int4,
  "update_time" int4
);
COMMENT ON COLUMN "public"."{$drole}"."id" IS 'ID';
COMMENT ON COLUMN "public"."{$drole}"."name" IS '角色名称';
COMMENT ON COLUMN "public"."{$drole}"."sort" IS '排序';
COMMENT ON COLUMN "public"."{$drole}"."status" IS '状态';
COMMENT ON COLUMN "public"."{$drole}"."json" IS '角色目录';
COMMENT ON COLUMN "public"."{$drole}"."create_time" IS '创建时间';
COMMENT ON COLUMN "public"."{$drole}"."update_time" IS '更新时间';
EOF;
                $sqlstr = explode(";", $sqlstr);
            }elseif($r_driver == 'sqlite'){
                $sqlstr = <<<EOF
CREATE TABLE IF NOT EXISTS "{$drole}" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" text(20),
  "sort" integer DEFAULT 255,
  "status" integer DEFAULT 1,
  "json" text(1024),
  "create_time" integer,
  "update_time" integer
);
EOF;
            }
            $clear_cache = SqlController::runDbExcute($this->db(false, $rconn), $sqlstr);
        }
        if($clear_cache){
            Cache::flush();
        }
    }
    if($is_post) {
        if($login_captcha) {
            $captcha = $_POST['captcha'];
            $cc = import("CaptchaExt", [
                'imageH' => 36,
                'imageW' => 115,
                'length' => 4,
            ]);
            $status = $cc->check($captcha, $this->getCacheId("captcha"));
            if (!$status) EXITJSON(0, "验证码不正确");
        }

        $username = $_POST['username'];
        $password = $_POST['password'];
        if(empty($username)) {
            EXITJSON(0, "用户名不能为空");
        }elseif(empty($password)) {
            EXITJSON(0, "密码不能为空");
        }

        try{
            $userinfo = $this->db('admin', 'user')->where("username", "=", $username)->first();
        } catch (Exception $e){
            $this->flushCache();
            $this->checkDatabaseForUser();
            EXITJSON(0, "数据错误，请重试！");
        }
        if(empty($userinfo)) {
            EXITJSON(0, "用户或密码不正确");
        }
        if($userinfo->status."" != '1'){
            EXITJSON(0, "用户已禁用");
        }

        $salt = $userinfo->salt;
        $md5pwd = md5($password.$salt);
        if($userinfo->password != $md5pwd){
            EXITJSON(0, "用户或密码不正确");
        }

        $remember = $_POST['remember'];
        if($remember == 'on') {
            $this->setCookie($rememberid, $remember);
            $this->setCookie($usernameid, $username);
        }else{
            $this->forgetCookie($rememberid);
            $this->forgetCookie($usernameid);
        }

        $menu_ids = [];
        if($username != 'admin') {
            $role_id = $userinfo->role_id;
            if(!empty($role_id)) {
                $rinfo = $this->db($conn."_role", $rconn)->where("id", "=", $role_id)->first();
                $json = $rinfo->json;
                if(!empty($json)){
                    $menu_ids = explode(",", $json);
                }
            }
        }
        $menu_ida = [];
        if(!empty($menu_ids)) {
            $menu_ids_gt = [];
            $menu_ids_lt = [];
            foreach ($menu_ids as $val){
                if($val >= 0){
                    //数据库菜单ID
                    $menu_ids_gt[] = $val;
                }else{
                    //默认设置菜单ID
                    $menu_ids_lt[] = $val;
                }
            }
            if(!empty($menu_ids_gt)) {
                $menu_list = $this->db($conn."_menu", $rconn)->whereNotIn("id", $menu_ids)->Where("status", "=", "1")->select("id", "url", "name")->get();
                $menu_list = json_decode(json_encode($menu_list, true), true);
            }else{
                $menu_list = [];
            }
            if(!empty($menu_ids_lt)) {
                $def_list = tpl("/sys/public/sys/json/menu/add.data");
                if (!empty($def_list) && is_array($def_list)) {
                    foreach ($def_list as $key => $val) {
                        if (!in_array($val['id'], $menu_ids_lt)) {
                            $menu_list[] = $val;
                        }
                    }
                }
            }
            foreach ($menu_list as $key=>$val) {
                $url = $val['url'];
                $pos = strpos($url, "?");
                if($pos > 0){
                    $url = substr($url, 0, $pos);
                }
                $pos = strpos($url, "#");
                if($pos > 0){
                    $url = substr($url, 0, $pos);
                }
                $url = str_replace(".html", "", $url);
                $url = trim(trim($url), "/");
                $md5 = substr(md5($url), 8, 8);
                $menu_ida[$md5] = [
                    "id" => $val['id'],
                    "name" => $val['name'],
                    "url" => $val['url'],
                ];
            }
        }
        Session::put($userinfoid, [
            'id' => $userinfo->id,
            'username' => $username,
            'role_id' => $userinfo->role_id,
            'menu_ids' => $menu_ids,
            'menu_ida' => $menu_ida,
            'image' => $userinfo->image
        ], 24 * 60 * 60);
        EXITJSON(1, "登录成功", "", "/");
    }

    $this->setView("login_captcha", $login_captcha);
    $this->setView("remember", $this->getCookie($rememberid));
    $this->setView("username", $this->getCookie($usernameid));
};
