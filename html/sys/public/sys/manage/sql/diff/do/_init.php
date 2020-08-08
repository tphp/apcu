<?php

return function (){
    if($this->isPost()) {
        $conn = $_POST['conn'];
        $type = $_POST['type'];
        if(empty($conn) || empty($type)){
            if(!in_array($type, ['save', 'rest'])) EXITJSON(0, "参数不正确");
        }
        $connections = config("database.connections");
        $dblist = [];
        foreach ($connections as $key => $val) {
            in_array($val['driver'], ['mysql', 'sqlsrv', 'pgsql', 'sqlite']) && $dblist[] = $key;
        }
        if (count($dblist) <= 0) EXITJSON(0, "数据库配置无效");
        if(!in_array($conn, $dblist)) EXITJSON(0, "数据库配置无效");
        $db_info = $connections[$conn];
        $driver = $db_info['driver'];
        if($driver == 'sqlsrv'){
            $import_name = 'SqlSrv';
        }elseif($driver == 'pgsql'){
            $import_name = 'PgSql';
        }elseif($driver == 'sqlite'){
            $import_name = 'Sqlite';
        }else{
            $import_name = 'MySql';
        }
        $msd = import("SqlDiff.{$import_name}", $conn);
        if($type == 'save'){
            $msd->save();
            EXITJSON(1, "字段保存成功");
        }else {
            list($status, $msg) = $msd->getDiff(true);
            if($status){
                list($status, $msg) = $msd->run();
                EXITJSON($status, $msg);
            }else{
                EXITJSON(0, $msg);
            }
        }
    }
    EXITJSON(0, "404");
};
