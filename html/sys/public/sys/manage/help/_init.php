<?php
use Tphp\Apcu\Controllers\SqlController;
return function (){

    $sql = SqlController::getSqlInit();
    $dbinfo = $sql->dbInfoCache();
    $db_user = $dbinfo['user'];
    if(!empty($db_user) && empty($db_user['tb']['help'])){
        $r_driver = config('database.connections.user.driver');
        $table_name = 'help';
        if($r_driver == 'mysql'){
            $sqlstr = <<<EOF
CREATE TABLE if NOT EXISTS `{$table_name}` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `parent_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '父ID',
    `name` varchar(50) DEFAULT NULL COMMENT '文档名称',
    `remark` longtext DEFAULT NULL COMMENT '文档说明',
    `sort` tinyint(3) unsigned NOT NULL DEFAULT 255 COMMENT '排序',
    `status` tinyint(1) unsigned DEFAULT 1 COMMENT '状态',
    `seo` varchar(1024) DEFAULT '1' COMMENT 'SEO',
    `create_time` int(11) unsigned DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) unsigned DEFAULT 0 COMMENT '修改时间',
    PRIMARY KEY (`id`)
)
ENGINE=InnoDB
COLLATE='utf8_general_ci'
DEFAULT CHARSET=utf8 COMMENT='帮助文档'
EOF;
        }elseif($r_driver == 'sqlsrv'){
            $sqlstr = <<<EOF
if OBJECT_ID(N'{$table_name}',N'U') is null
	BEGIN
	CREATE TABLE [dbo].[{$table_name}](
        [id] [int] identity(1,1) primary key,
        [parent_id] [int] DEFAULT 0,
        [name] [nvarchar](50) NULL,
        [remark] [nvarchar](max) NULL,
        [sort] [tinyint] DEFAULT 255,
        [status] [tinyint] DEFAULT 1,
        [seo] [nvarchar](1024) NULL,
        [create_time] [int] DEFAULT 0,
        [update_time] [int] DEFAULT 0
	);
	execute sp_addextendedproperty 'MS_Description','ID','user','dbo','table','{$table_name}','column','id';
	execute sp_addextendedproperty 'MS_Description','父ID','user','dbo','table','{$table_name}','column','parent_id';
	execute sp_addextendedproperty 'MS_Description','文档名称','user','dbo','table','{$table_name}','column','name';
	execute sp_addextendedproperty 'MS_Description','文档说明','user','dbo','table','{$table_name}','column','remark';
	execute sp_addextendedproperty 'MS_Description','排序','user','dbo','table','{$table_name}','column','sort';
	execute sp_addextendedproperty 'MS_Description','状态','user','dbo','table','{$table_name}','column','status';
	execute sp_addextendedproperty 'MS_Description','SEO','user','dbo','table','{$table_name}','column','seo';
	execute sp_addextendedproperty 'MS_Description','创建时间','user','dbo','table','{$table_name}','column','create_time';
	execute sp_addextendedproperty 'MS_Description','修改时间','user','dbo','table','{$table_name}','column','update_time';
	END
EOF;
        }elseif($r_driver == 'pgsql'){
            $sqlstr = <<<EOF
CREATE TABLE IF NOT EXISTS "public"."{$table_name}" (
  "id" serial primary key,
  "parent_id" int4 NOT NULL,
  "name" varchar(50) COLLATE "pg_catalog"."default",
  "remark" text COLLATE "pg_catalog"."default",
  "sort" int2 DEFAULT 255,
  "status" int2 DEFAULT 1,
  "seo" varchar(1024) COLLATE "pg_catalog"."default",
  "create_time" int4,
  "update_time" int4
);
COMMENT ON COLUMN "public"."{$table_name}"."id" IS 'ID';
COMMENT ON COLUMN "public"."{$table_name}"."parent_id" IS '父ID';
COMMENT ON COLUMN "public"."{$table_name}"."name" IS '文档名称';
COMMENT ON COLUMN "public"."{$table_name}"."remark" IS '文档说明';
COMMENT ON COLUMN "public"."{$table_name}"."sort" IS '排序';
COMMENT ON COLUMN "public"."{$table_name}"."status" IS '状态';
COMMENT ON COLUMN "public"."{$table_name}"."seo" IS 'SEO';
COMMENT ON COLUMN "public"."{$table_name}"."create_time" IS '创建时间';
COMMENT ON COLUMN "public"."{$table_name}"."update_time" IS '修改时间';
COMMENT ON TABLE "public"."{$table_name}" IS '帮助文档';
EOF;
            $sqlstr = explode(";", $sqlstr);
        }elseif($r_driver == 'sqlite'){
            $sqlstr = <<<EOF
CREATE TABLE IF NOT EXISTS "{$table_name}" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "parent_id" integer NOT NULL,
  "name" text(50),
  "remark" text,
  "sort" integer NOT NULL DEFAULT 255,
  "status" integer DEFAULT 1,
  "seo" text(1024),
  "create_time" integer,
  "update_time" integer
);
EOF;
        }
        if(!empty($sqlstr)){
            try{
                SqlController::runDbExcute($this->db(false), $sqlstr);
            }catch (Exception $e){
                exit("<div>用户表创建错误，请检查是否有权限创建表</div><div>".$e->getMessage()."</div>");
            }
            if($this->db('help')->count() <= 0){
                $time = time();
                $this->db('help')->insert([
                    'parent_id' => '0',
                    'name' => '简介',
                    'remark' => '[TOC]
##TPHP框架介绍
TPHP是基于Laravel框架的基础上进行整合的一套便捷性框架，原Laravel框架未做任何更改。该框架具有高配置型代码设计，使得程序员更快速的开发项目，并减少大量BUG调试时间，当你使用TPHP开发后台时开发速度将大幅度提升。

##框架特性
####智能路由
- 域名关联绑定，一次设置无需修改。
- 路由自动关联到对应目录，开发过程中无需配置路由。

####模块化
- 每个目录对应一个模块，MVC合并为一个目录并相互独立，除非一个模块需调用另一个模块。
- 关联的JS、CSS、PHP、HTML都在一个目录中进行，大量减少代码查看或调用的复杂性。
- SCSS自动生成CSS
- 如果一个页面使用多个模块：CSS、 SCSS代码合并到一个css文件当中、JS合并到一个JS文件当中。
- 合并的CSS或JS可存储于Memcache或文件缓存中。',
                    'sort' => '255',
                    'status' => '1',
                    'seo' => '{"title":"","keywords":"","description":""}',
                    'create_time' => $time,
                    'update_time' => $time,
                ]);
            }
            $this->flushCache();
            exit("初始化完成，重新刷新该页面后正常访问。");
        }
    }
};
