<?php

use App\Http\Controllers\Controller;
use Tphp\Apcu\Controllers\SqlController;

class InitController extends Controller {
    function __construct($tpl, $type) {
        $this->checkDatabaseForUser();
        $btpt = BASE_TPL_PATH_TOP;
        $btpt = trim(trim($btpt), "/");
        $btpt = str_replace("/", "_", $btpt);
        $userinfoid = $btpt."_sys_user_login_userinfo";
        $userinfo = Session::get($userinfoid);
        if(
            empty($userinfo) &&
            !in_array($tpl, [
                'sys/user/login',
                'sys/user/login/captcha',
                'info/warning/sql/send',
                'info/warning/sql/send/to',
                'manage/code/dev/sql/run',
                'manage/code/tree',
                'manage/code/dev/sql/fieldset'
            ])
        ){
            if(count($_POST) > 0){
                EXITJSON(0, "登录超时， 请重新登录！");
            }else{
                redirect("/sys/user/login")->send();
            }
        }
        $this->userinfo = $userinfo;
        $GLOBALS['USERINFO'] = $userinfo;

        if(empty($type) || $type == 'html'){
            $url = $tpl;
        }else{
            $url = $tpl.".".$type;
        }

        $menu_ida = $userinfo['menu_ida'];
        $md5 = substr(md5($url), 8, 8);
        if(!empty($menu_ida && isset($menu_ida[$md5]))){
            if(count($_POST) > 0){
                $id = $menu_ida[$md5]['id'];
                $name = $menu_ida[$md5]['name'];
                $url = $menu_ida[$md5]['url'];
                EXITJSON(0, "无权限操作！<BR><BR>菜单ID： {$id}<BR>菜单名称： {$name}<BR>链接： {$url}");
            }else {
                exit(tpl("/sys/public/sys/err.html", ["layout" => false, "view" => [
                    'info' => $menu_ida[$md5]
                ]]));
            }
        }
    }

    /**
     * 判断是否有user数据库
     */
    protected function checkDatabaseForUser(){
        $domain_config = $GLOBALS['DOMAIN_CONFIG'];
        $dc_user = $domain_config['user'];
        if(empty($dc_user)){
            $dc_user = 'user';
        }

        $dc = config('database.connections');
        $err_msg = "用户数据源未配置：文件config/database.php配置connections.{$dc_user}";
        $dc_info = $dc[$dc_user];
        if(!isset($dc_info)){
            exit($err_msg);
        }
        $sql = SqlController::getSqlInit();
        $dbinfo = $sql->dbInfoCache();
        if(empty($dbinfo) || !is_array($dbinfo)){
            exit($err_msg);
        }
        if(empty($dbinfo[$dc_user])){
            exit($err_msg);
        }
        $db_user = $dbinfo[$dc_user];
        $db_user_tb = $db_user['tb'];
        if(isset($db_user_tb['admin'])){
            return;
        }
        // 如果admin表不存在则创建
        $r_driver = $dc_info['driver'];
        if($r_driver == 'mysql') {
            $sqlstr = <<<EOF
CREATE TABLE if NOT EXISTS `admin` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
	`username` VARCHAR(20) NULL DEFAULT NULL COMMENT '用户名',
	`password` VARCHAR(64) NULL DEFAULT NULL COMMENT '密码',
	`salt` VARCHAR(10) NULL DEFAULT NULL COMMENT '密码盐值',
	`role_id` INT(11) NULL DEFAULT NULL COMMENT '角色ID',
	`status` TINYINT(3) NULL DEFAULT '1' COMMENT '状态',
	`image` VARCHAR(200) NULL DEFAULT NULL COMMENT '头像',
	`image_big` VARCHAR(200) NULL DEFAULT NULL COMMENT '高清头像',
	`create_time` INT(11) NULL DEFAULT NULL COMMENT '创建时间',
	`update_time` INT(11) NULL DEFAULT NULL COMMENT '更新时间',
	`login_time` INT(11) NULL DEFAULT NULL COMMENT '登录时间',
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOF;
        }elseif($r_driver == 'sqlsrv') {
            $sqlstr = <<<EOF
if OBJECT_ID(N'admin',N'U') is null
	BEGIN
	CREATE TABLE [dbo].[admin](
		[id] [int] identity(1,1) primary key,
		[username] [nvarchar](20) NULL,
		[password] [nvarchar](64) NULL,
		[salt] [nvarchar](10) NULL,
		[role_id] [int] NULL,
		[status] [tinyint] DEFAULT 1,
		[image] [nvarchar](200) NULL,
		[image_big] [nvarchar](200) NULL,
		[create_time] [int] NULL,
		[update_time] [int] NULL,
		[login_time] [int] NULL
	);
	execute sp_addextendedproperty 'MS_Description','用户ID','user','dbo','table','admin','column','id';
	execute sp_addextendedproperty 'MS_Description','用户名','user','dbo','table','admin','column','username';
	execute sp_addextendedproperty 'MS_Description','密码','user','dbo','table','admin','column','password';
	execute sp_addextendedproperty 'MS_Description','密码盐值','user','dbo','table','admin','column','salt';
	execute sp_addextendedproperty 'MS_Description','角色ID','user','dbo','table','admin','column','role_id';
	execute sp_addextendedproperty 'MS_Description','状态','user','dbo','table','admin','column','status';
	execute sp_addextendedproperty 'MS_Description','头像','user','dbo','table','admin','column','image';
	execute sp_addextendedproperty 'MS_Description','高清头像','user','dbo','table','admin','column','image_big';
	execute sp_addextendedproperty 'MS_Description','创建时间','user','dbo','table','admin','column','create_time';
	execute sp_addextendedproperty 'MS_Description','更新时间','user','dbo','table','admin','column','update_time';
	execute sp_addextendedproperty 'MS_Description','登录时间','user','dbo','table','admin','column','login_time';
	END
EOF;
        }elseif($r_driver == 'pgsql'){
            $sqlstr = <<<EOF
CREATE TABLE IF NOT EXISTS "public"."admin" (
    "id" serial primary key,
    "username" varchar(20) COLLATE "pg_catalog"."default",
    "password" varchar(64) COLLATE "pg_catalog"."default",
    "salt" varchar(10) COLLATE "pg_catalog"."default",
    "role_id" int4,
    "status" int2 DEFAULT 1,
    "image" varchar(200) COLLATE "pg_catalog"."default",
    "image_big" varchar(200) COLLATE "pg_catalog"."default",
    "create_time" int4,
    "update_time" int4,
    "login_time" int4
);
COMMENT ON COLUMN "public"."admin"."id" IS '用户ID';
COMMENT ON COLUMN "public"."admin"."username" IS '用户名';
COMMENT ON COLUMN "public"."admin"."password" IS '密码';
COMMENT ON COLUMN "public"."admin"."salt" IS '密码盐值';
COMMENT ON COLUMN "public"."admin"."role_id" IS '角色ID';
COMMENT ON COLUMN "public"."admin"."status" IS '状态';
COMMENT ON COLUMN "public"."admin"."image" IS '头像';
COMMENT ON COLUMN "public"."admin"."image_big" IS '高清头像';
COMMENT ON COLUMN "public"."admin"."create_time" IS '创建时间';
COMMENT ON COLUMN "public"."admin"."update_time" IS '更新时间';
COMMENT ON COLUMN "public"."admin"."login_time" IS '登录时间';
EOF;
            $sqlstr = explode(";", $sqlstr);
        }elseif($r_driver == 'sqlite'){
            $sqlstr = <<<EOF
CREATE TABLE IF NOT EXISTS "admin" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "username" text(20),
  "password" text(64),
  "salt" text(10),
  "role_id" integer,
  "status" integer DEFAULT 1,
  "image" text(200),
  "image_big" text(200),
  "create_time" integer,
  "update_time" integer,
  "login_time" integer
);
EOF;
        }else{
            return;
        }

        try{
            $db = DB::connection($dc_user);
            SqlController::runDbExcute($db, $sqlstr);
        }catch (Exception $e){
            exit("<div>用户表创建错误，请检查是否有权限创建表</div><div>".$e->getMessage()."</div>");
        }
        try{
            $username = 'admin';
            $admin_cot = DB::connection($dc_user)->table('admin')->where('username', $username)->count();
            if($admin_cot <= 0){
                $salt = $this->strRand();
                $password = md5('admin'.$salt);
                $time = time();
                DB::connection($dc_user)->table('admin')->insert([
                    'username' => $username,
                    'password' => $password,
                    'salt' => $salt,
                    'status' => '1',
                    'create_time' => $time,
                    'update_time' => $time,
                ]);
            }
        }catch (Exception $e){
            exit("<div>用户表创建admin错误</div><div>".$e->getMessage()."</div>");
        }
        $this->flushCache();
        if(count($_POST) > 0){
            EXITJSON(0, "初始账号：admin， 密码： admin，再单击登录。");
        }
        exit("初始账号：admin， 密码： admin，刷新该页面后可以登录。");
    }

    /**
     * 获取随机字符串
     * @param int $length
     * @param string $char
     * @return bool|string
     */
    private function strRand($length = 5, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        $string = '';
        for($i = $length; $i > 0; $i--) {
            $string .= $char[mt_rand(0, strlen($char) - 1)];
        }
        return $string;
    }

    /**
     * 清空缓存
     */
    public function flushCache(){
        $output = trim(shell_exec('redis-cli flushall'));
        $msg = "";
        if(!empty($output) && $output !== 'OK'){
            $msg = $output;
        }
        return Cache::flush();
    }
}
