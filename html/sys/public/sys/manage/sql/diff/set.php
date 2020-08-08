<?php

return function (){
    $connections = config("database.connections");
    unset($connections['user']);
    $dblist = [];
    foreach ($connections as $key=>$val){
        in_array($val['driver'], ['mysql', 'sqlsrv', 'pgsql', 'sqlite']) && $dblist[] = $key;
    }
    $this->setView("dblist", $dblist);
    if(count($dblist) <= 0) return false;
    $conn = $_GET["conn"];
    if(empty($conn)){
        $conn = config("database.default");
    }
    !in_array($conn, $dblist) && $conn = $dblist[0];
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
    list($status, $msg, $sqls) = $msd->getDiff(true);
    $sqlarr = [];
    if(!empty($sqls)) {
        foreach ($sqls as $key => $val) {
            foreach ($val as $k=>$v) {
                $sqlarr[] = [
                    'table' => $key,
                    'field' => $k,
                    'sql' => $v
                ];
            }
        }
    }
    $this->setView("status", $status);
    $this->setView("msg", $msg);
    $this->setView("sqls", $sqlarr);
    $this->setView("conn", $conn);
};
