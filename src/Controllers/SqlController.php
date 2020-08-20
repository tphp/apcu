<?php

namespace Tphp\Apcu\Controllers;

use DB;
use Illuminate\Support\Facades\Cache;

class SqlController{
    private static $sql_init = null;
    public static function getSqlInit(){
        if(empty(SqlController::$sql_init)){
            SqlController::$sql_init = new SqlController();
        }
        return SqlController::$sql_init;
    }

    function __construct() {
        $this->sqlconfig = $this->getSqlConfig();
//		dump($this->getDbConfig());
//		dump($this->getDbInfo());
//		dump($this->getDbInfo()['smchk']['tb']['t_cwdd1']);
        $this->last_sql = "";
        $this->db_cacheid = "apcu_sql_getDbInfoList";
    }

    /**
     * 执行数据库语句
     * @param $db
     * @param string $sqlstr
     * @return bool
     */
    public static function runDbExcute($db, $sqlstr=''){
        $ret = false;
        if(is_array($sqlstr)){
            foreach ($sqlstr as $s){
                $s = trim($s);
                if(empty($s)){
                    continue;
                }
                $ret = $db->statement($s);
            }
        }else{
            $ret = $db->statement($sqlstr);
        }
        return $ret;
    }

    /**
     * 获取MySql配置
     * @return array
     */
    private function getSqlConfig(){
        $config = config('database.connections');
        $retcof = [];
        foreach ($config as $key=>$val){
            if(in_array($val['driver'], ['mysql', 'sqlsrv', 'pgsql', 'sqlite'])){
                $retcof[$key] = $val;
            }
        }
        return $retcof;
    }

    /**
     * 获取数据库配置
     * @return array
     */
    private function getDbConfig(){
        $config = $this->sqlconfig;
        $ret = [];
        foreach ($config as $key=>$val){
            $db = strtolower(trim($val['database']));
            $keylower = strtolower(trim($key));
            $ret[$keylower] = [
                'name' => $db,
                'database' => $db,
                'key' => $key
            ];
        }
        return $ret;
    }

    /**
     * 列表转化为小写
     * @param $list
     * @return array
     */
    private function listToLower($list){
        $ret = [];
        foreach ($list as $key=>$val){
            foreach ($val as $k=>$v){
                $ret[$key][strtolower($k)] = $v;
            }
        }
        return $ret;
    }

    /**
     * 错误信息打印
     * @param null $e
     * @param string $name
     * @return array
     */
    private function errorMessage($e=null, $name='', $conn=''){
        $sql_error_pass = 'SQL_ERROR_PASS';
        if(getenv($sql_error_pass)){
            return [];
        }
        $smsg = "{$name} 查询错误： 可修改.env文件 {$sql_error_pass}=true 跳过";
        $dir_msg = "错误路径： database.connections.{$conn}";
        $msg = $e->getMessage();
        if(strpos($msg, "could not find driver") !== false){
            echo("<div>{$name} 扩展未安装</div>");
        }
        if(count($_POST) > 0){
            EXITJSON(0, $smsg."\n".$dir_msg."\n".$msg);
        }else{
            exit("<div>$smsg</div><div>$dir_msg</div><div>$msg</div>");
        }
    }

    /**
     * 获取MYSQL字段信息
     * @param $conn
     * @param $database
     * @return array
     */
    private function getDbMysql($conn, $database){
        try {
            $db = DB::connection($conn);
            $tablesqls = $this->listToLower($db->select("select table_name, table_comment from information_schema.tables where table_schema='{$database}'"));
            $fieldsqls = $this->listToLower($db->select("select table_name, column_name, column_comment, column_type, column_key from information_schema.columns where table_schema='{$database}'"));
            $dbdata = [];
            foreach ($tablesqls as $key => $val) {
                $dbdata[$val['table_name']]['name'] = $val['table_comment'];
            }

            foreach ($fieldsqls as $key => $val) {
                $cn = strtolower(trim($val['column_name']));
                $dbdata[$val['table_name']]['field'][$cn] = [
                    'name' => $val['column_comment'],
                    'key' => $val['column_key'],
                    'type' => $val['column_type'],
                ];
            }
            $newdbdata = [];
            foreach ($dbdata as $key => $val) {
                $key = strtolower(trim($key));
                $newdbdata[$key] = $val;
            }
            unset($tablesqls);
            unset($fieldsqls);
            unset($dbdata);
            return $newdbdata;
        }catch (\Exception $e){
            $this->errorMessage($e, 'Mysql', $conn);
        }
    }

    /**
     * 获取MSSQL字段信息， 版本MSSQL2012
     * @param $conn
     * @return array
     */
    private function getDbMssql($conn){
        try{
            $db = DB::connection($conn);
            //遍历表
            $tablenames = [];
            foreach($db->select("select name, object_id from sys.tables order by name asc") as $key=>$val){
                $tablenames[strtolower($val->name)] = $val->object_id;
            }

            //遍历字段类型
            $typenames = [];
            foreach($db->select("select user_type_id, name, max_length from sys.types") as $key=>$val){
                $typenames[$val->user_type_id] = $val->name;
            }

            //遍历注释
            $remarknames = [];
            foreach($db->select("select major_id, minor_id, value from sys.extended_properties") as $key=>$val){
                $remarknames[$val->major_id][$val->minor_id] = $val->value;
            }

            //遍历字段
            $fieldnames = [];
            $sqlstr =	"select user_type_id, name, object_id, column_id, COLUMNPROPERTY(object_id, name,'PRECISION') as max_length from sys.columns";
            foreach($db->select($sqlstr) as $key=>$val){
                $fieldnames[$val->object_id][$val->column_id] = [
                    'name' => $val->name,
                    'user_type_id' => $val->user_type_id,
                    'max_length' => $val->max_length,
                    'object_id' => $val->object_id
                ];
            }

            //查询主键
            $pknames = [];
            $sqlstr = "select a.table_name, b.column_name from information_schema.table_constraints a ";
            $sqlstr .= "inner join information_schema.constraint_column_usage b ";
            $sqlstr .= "on a.constraint_name = b.constraint_name ";
            $sqlstr .= "where a.constraint_type = 'PRIMARY KEY'";
            foreach($db->select($sqlstr) as $key=>$val){
                $t = strtolower(trim($val->table_name));
                $f = strtolower(trim($val->column_name));
                $pknames[$t][$f] = true;
            }

            $dbdata = [];
            foreach($tablenames as $key=>$val){
                $dbdata[$key]['name'] = $remarknames[$val][0];
                if(!empty($fieldnames[$val]) && is_array($fieldnames[$val])) {
                    foreach ($fieldnames[$val] as $k => $v) {
                        $cn = strtolower(trim($v['name']));
                        $pknames[$key][$cn] ? $keyname = "PRI" : $keyname = "";
                        $dbdata[$key]['field'][$cn] = [
                            'name' => $remarknames[$val][$k],
                            'key' => $keyname,
                            'type' => $typenames[$v['user_type_id']] . "(" . $v['max_length'] . ")",
                        ];
                    }
                }
            }

            $newdbdata = [];
            foreach ($dbdata as $key=>$val){
                $key = strtolower(trim($key));
                $newdbdata[$key] = $val;
            }
            unset($tablenames);
            unset($typenames);
            unset($remarknames);
            unset($fieldnames);
            unset($pknames);
            unset($dbdata);
            return $newdbdata;
        }catch (\Exception $e){
            $this->errorMessage($e, 'Sqlserver', $conn);
        }
    }

    /**
     * 获取Pgsql字段信息， 版本 PostgreSQL 12.2
     * @param $conn
     * @return array
     */
    private function getDbPgsql($conn){
        try{
            $db = DB::connection($conn);
            $table_list = $db->select(<<<EOF
select
relname as table,
cast(obj_description(relfilenode,'pg_class') as varchar) as name
from pg_class c
where
relkind = 'r'
and relname not like 'pg_%'
and relname not like 'sql_%'
order by relname
EOF
            );
            $newdbdata = [];
            if(empty($table_list)){
                return $newdbdata;
            }
            $t_list = [];
            foreach ($table_list as $key=>$val){
                $name = $val->name;
                empty($name) && $name = '';
                $table = strtolower($val->table);
                if(strpos($table, "'")){
                    continue;
                }
                $t_list[] = "'{$table}'";
                $newdbdata[$table] = [
                    'name' => $name
                ];
            }
            if(empty($newdbdata)){
                return $newdbdata;
            }
            $table_str = implode(",", $t_list);
            $pk_list = $db->select(<<<EOF
select
pg_attribute.attname as field,
pg_class.relname as table
from
pg_constraint
inner join pg_class
on pg_constraint.conrelid = pg_class.oid
inner join pg_attribute on pg_attribute.attrelid = pg_class.oid
and  pg_attribute.attnum = pg_constraint.conkey[1]
where pg_class.relname in ({$table_str})
and pg_constraint.contype='p'
EOF
            );
            $pks = [];
            foreach ($pk_list as $key=>$val){
                $pks[strtolower($val->table)][strtolower($val->field)] = true;
            }

            $field_list = $db->select(<<<EOF
select
col_description(a.attrelid, a.attnum) as name,
format_type(a.atttypid, a.atttypmod) as type,
a.attname as field,
c.relname as table
from pg_class as c, pg_attribute as a
where
c.relname in({$table_str})
AND
a.attrelid = c.oid
AND a.attnum>0
EOF
            );
            foreach ($field_list as $key=>$val){
                $table = strtolower($val->table);
                if(!isset($newdbdata[$table])){
                    $newdbdata[$table] = [];
                }
                if(!isset($newdbdata[$table]['field'])){
                    $newdbdata[$table]['field'] = [];
                }
                $k = strtolower($val->field);
                $pkey = '';
                if(isset($pks[$table]) && $pks[$table][$k]){
                    $pkey = 'PRI';
                }
                $newdbdata[$table]['field'][$k] = [
                    'name' => $val->name,
                    'key' => $pkey,
                    'type' => $val->type
                ];
            }
            unset($table_list);
            unset($t_list);
            unset($pk_list);
            unset($pks);
            unset($field_list);
            return $newdbdata;
        }catch (\Exception $e){
            $this->errorMessage($e, 'PostgreSql', $conn);
        }
    }

    /**
     * 获取Sqlite字段信息， 版本 Sqlite3
     * @param $conn
     * @return array
     */
    private function getDbSqlite($conn){
        try{
            $db_file = $this->sqlconfig[$conn]['database'];
            $newdbdata = [];
            if(!is_file($db_file)){
                return $newdbdata;
            }
            $db = DB::connection($conn);
            $table_list = $db->select("select name as 'table' from sqlite_master where type='table' and name<>'sqlite_sequence' order by name");
            if(empty($table_list)){
                return $newdbdata;
            }
            foreach ($table_list as $key=>$val){
                $table = strtolower($val->table);
                if(strpos($table, "'")){
                    continue;
                }
                $newdbdata[$table] = [
                    'name' => ''
                ];
            }
            if(empty($newdbdata)){
                return $newdbdata;
            }
            foreach ($newdbdata as $key=>$val){
                $field_list = $db->select("PRAGMA table_info('{$key}')");
                $field_info = [];
                foreach ($field_list as $k=>$v){
                    if($v->pk == '1'){
                        $pkey = 'PRI';
                    }else{
                        $pkey = '';
                    }
                    $field_info[strtolower($v->name)] = [
                        'name' => '',
                        'key' => $pkey,
                        'type' => $v->type
                    ];
                }
                $newdbdata[$key]['field'] = $field_info;
            }
            unset($table_list);
            return $newdbdata;
        }catch (\Exception $e){
            $this->errorMessage($e, 'Sqlite3', $conn);
        }
    }

    /**
     * 获取数据库字段命名信息
     * @param $conn
     * @param $database
     * @return array
     */
    private function getDb($conn, $database){
        $type = $this->sqlconfig[$conn]['driver'];

        try {
            if ($type == 'mysql') {
                return $this->getDbMysql($conn, $database);
            } elseif ($type == 'sqlsrv') {
                return $this->getDbMssql($conn, $database);
            } elseif ($type == 'pgsql') {
                return $this->getDbPgsql($conn);
            } elseif ($type == 'sqlite') {
                return $this->getDbSqlite($conn);
            }
        }catch (\Exception $e){
            return [];
        }

    }

    /**
     * 获取数据库配置详情
     * @param $dbinfo
     * @return mixed
     */
    private function getDbInfoListDetail($dbinfo){
        foreach ($dbinfo as $key=>$val){
            $tb = $dbinfo[$key]['tb'];
            $dbinfo[$key]['tb'] = Cache::get($tb);
        }
        return $dbinfo;
    }
    /**
     * 获取数据库配置
     * @return array
     */
    private function getDbInfoList(){
        $cacheid = $this->db_cacheid;
        $dbinfo = Cache::get($cacheid);
        if(!empty($dbinfo)) return $this->getDbInfoListDetail($dbinfo);
        $ttl = 60 * 60;
        $ttl_sub = $ttl + 60;
        $dbinfo = $this->getDbConfig();
        foreach ($dbinfo as $key=>$val){
            $tb_info = $this->getDb($val['key'], $val['database']);
            $cacheid_sub = $cacheid."_{$key}";
            $dbinfo[$key]['tb'] = $cacheid_sub;
            Cache::put($cacheid_sub, $tb_info, $ttl_sub);
        }
        Cache::put($cacheid, $dbinfo, $ttl);
        return $this->getDbInfoListDetail($dbinfo);
    }

    /**
     * 获取数据库配置
     * @return array
     */
    private function getDbInfo($name = ""){
        $dbinfo = $this->getDbInfoList();
        if(empty($name)) return $dbinfo;
        return $dbinfo[$name];
    }

    /**
     * 验证数据库是否可用
     * @param $data
     * @return array
     */
    private function checkDb(&$data){
        $table = strtolower($data['table']);
        if(empty($table)) return [0, '表不能为空'];
        $data['table'] = $table;

        $conn = $data['conn'];
        empty($conn) && $conn = config('database.default');

        $db = $this->getDbInfo($conn);
        if(empty($db)) return [0, $conn.'数据库未找到'];
        $data['conn'] = $conn;

        $prefix = strtolower(trim($this->sqlconfig[$conn]['prefix']));

        $dbtb = $db['tb'];
        $tb = $dbtb[$prefix.$table];
        if(empty($tb)) return [0, $conn."->".$prefix.$table.'表不存在'];

        $field = $data['field'];
        $tbfield = $tb['field'];
        if(empty($field)) return [1, [
            'set' => $tbfield,
            'show' => $tbfield,
            'all' => $tbfield
        ]];

        /**处理设置字段,字段信息如下：
        'field' => ['id', 'id', 'name',
        [
        ['order', 'id', 'sourceid', ['type'=>'order_type']],
        ['member', 'id', 'mid', 'account']
        ]...
        ]*/

        $fieldstr = []; //字符串字段
        $fieldarr = []; //数组字段（高级处理字段）
        foreach ($field as $val) {
            if(is_string($val)){
                $fieldstr[] = $val;
            }elseif(is_array($val) && count($val) > 0){
                $fieldarr[] = $val;
            }
        }
        $fieldstr = array_unique($fieldstr);

        $fieldshow = [];
        $fieldset = [];
        foreach ($fieldstr as $val){
            $val = strtolower(trim($val));
            if(!empty($tbfield[$val])){
                $fieldshow[$val] = $fieldset[$val] = $tbfield[$val];
            }
        }

        $fieldnext = [];
        $fieldadds = [];
        foreach ($fieldarr as $key=>$val){
            $ttb = $tb;
            $ttb['table'] = $table;

            $vallen = count($val) - 1;
            for($i = $vallen; $i >= 0; $i --){
                if(empty($val[$i][3])){
                    unset($val[$i]);
                }else{
                    break;
                }
            }
            if(empty($val)) continue;

            foreach ($val as $k=>$v) {
                if(!is_array($v)) return [0, $conn."->{$table} 配置有误: {$k}=>{$v}"];
                $v[1] = strtolower(trim($v[1]));
                $v[2] = strtolower(trim($v[2]));
                $vtbs = $v[0];
                $vconn = $conn;
                if(is_array($vtbs)){
                    if(empty($vtbs[1])){
                        $vtb = strtolower(trim($vtbs[0]));
                        $vprefix = $prefix;
                        $vdbtb = $dbtb;
                    }else{
                        $vtb = strtolower(trim($vtbs[0]));
                        $vconn = $vtbs[1];
                        empty($vconn) && $vconn = config('database.default');
                        $vdb = $this->getDbInfo($vconn);
                        if(empty($vdb)) return [0, $vconn.'数据库未找到'];
                        $vprefix = strtolower(trim($this->sqlconfig[$vconn]['prefix']));

                        $vdbtb = $vdb['tb'];
                        $vvtb = $vdbtb[$vprefix.$vtb];
                        if(empty($vvtb)) return [0, $vconn."中".$vprefix.$vtb.'表不存在'];
                    }
                }else{
                    $vtb = strtolower(trim($vtbs));
                    $vprefix = $prefix;
                    $vdbtb = $dbtb;
                }
                $ttbnext = $vdbtb[$vprefix . $vtb];
                if(!isset($ttbnext['field'])) return [0, $vconn."->{$vtb} 不存在"];
                if(!isset($ttbnext['field'][$v[1]])) return [0, $vconn."->{$vtb} 中的字段 {$v[1]} 不存在"];
                if(!isset($ttb['field'][$v[2]])) return [0, $vconn."->{$ttb['table']} 中的字段 {$v[2]} 不存在"];
                if(!empty($v[3])) {
                    $tas = [];
                    if (is_string($v[3])) {
                        $vas = strtolower(trim($v[3]));
                        $tas[$vas] = $vas;
                    } else {
                        foreach ($v[3] as $kk => $vv) {
                            if (is_string($vv) || is_numeric($vv)) {
                                if (is_string($kk)) {
                                    $tas[strtolower(trim($kk))] = strtolower(trim($vv));
                                } else {
                                    $vas = strtolower(trim($vv));
                                    $tas[$vas] = $vas;
                                }
                            }
                        }
                    }

                    foreach ($tas as $kk => $vv){
                        if(!isset($ttbnext['field'][$kk])) return [0, $vconn."->{$vtb} 中的字段 {$kk} 不存在"];
                        if(isset($fieldshow[$vv])) return [0, $vconn."->{$vtb} 中字段 {$vv} 与主字段重复"];
                        if(isset($fieldadds[$vv])) return [0, $vconn."->{$vtb} 中字段 {$vv} 与其他字段重复"];
                        $fieldadds[$vv] = $ttbnext['field'][$kk];
                    }
                    $v[3] = $tas;
                }
                if($k == 0 && !empty($tbfield[$v[2]])){
                    empty($fieldset[$v[2]]) && $fieldset[$v[2]] = $tbfield[$v[2]];
                }
                $ttb = $ttbnext;
                $ttb['table'] = $vtb;

                $val[$k] = $v;
            }
            $fieldnext[$key] = $val;
        }

        foreach ($fieldadds as $key=>$val){
            $fieldshow[$key] = $val;
        }

        return [
            1, [
                'set' => $fieldset,
                'show' => $fieldshow,
                'next' => $fieldnext,
                'all' => $tbfield
            ]
        ];

    }

    /**
     * 设置KEY为小写
     * @param $data
     * @param $notin
     * @return array
     */
    private function setKeyLower($data) {
        if(empty($data) || is_string($data)) return $data;
        $newdata = array();
        foreach($data as $key=>$val){
            $newdata[strtolower($key)] = $val;
        }
        return $newdata;
    }

    /**
     * 条件语句处理
     * @param $where
     */
    public function getWhere($where, $fieldall){
        if(empty($where)) return [1, $where];
        if(is_string($where[0])) $where = [$where];
        $flag = 0;
        $child_i = 0;
        $wherenew = [];
        ksort($where);
        foreach ($where as $key=>$val){
            if(is_string($val)){
                if(strtolower(trim($val)) == "or"){
                    $flag ++;
                    $wherenew[$flag] = 'or';
                    $flag ++;
                }
            }elseif(is_array($val) && count($val) >= 2){
                $is_child = true;
                if(is_string($val[0]) && is_string($val[1]) && strtolower(trim($val[1])) != 'or'){
                    $is_child = false;
                }
                if($is_child){
                    if (!empty($val) && is_array($val)) {
                        list($status, $vwhere) = $this->getWhere($val, $fieldall);
                        if ($status) {
                            $wherenew[$flag]['child']["_#c#_{$child_i}"] = $vwhere;
                            $child_i ++;
                        }
                    }
                }else {
                    $f = strtolower(trim($val[1]));
                    $n = strtolower(trim($val[0]));
                    isset($val[2]) ? $v = $val[2] : $v = [];
                    if (is_string($v) || is_numeric($v)) {
                        $wherenew[$flag][$f][$n][] = "{$v}";
                    } else {
                        if ($f == 'between' || $f == 'notbetween') {
                            if (count($v) == 2 && $v[1] > $v[0]) {
                                $wherenew[$flag][$f][$n][] = $v;
                            } else {
                                return [0, "字段 {$n} 条件范围出错"];
                            }
                        } elseif ($f == 'null' || $f == 'notnull') {
                            $wherenew[$flag][$f][$n] = 'null';
                        } else {
                            foreach ($v as $vv) {
                                (is_string($vv) || is_numeric($vv)) && $wherenew[$flag][$f][$n][] = "{$vv}";
                            }
                        }
                    }
                }
            }
        }

        $fields = [];
        $whereret = [];
        foreach ($wherenew as $key=>$val){
            if(is_string($val)){
                $whereret[$key] = $val;
                continue;
            }
            foreach ($val as $k=>$v){
                foreach ($v as $kk=>$vv){
                    if($k == 'child'){
                        $whereret[$key][$k][$kk] = $vv;
                    }else {
                        $fields[] = $kk;
                        if ($k == 'between' || $k == 'notbetween') {
                            $mins = [];
                            $maxs = [];
                            foreach ($vv as $vvv) {
                                $mins[] = $vvv[0];
                                $maxs[] = $vvv[1];
                            }
                            if ($k == 'between') {
                                $min = max($mins);
                                $max = min($maxs);
                            } else {
                                $min = min($mins);
                                $max = max($maxs);
                            }
                            if ($min <= $max) {
                                $whereret[$key][$k][$kk] = [$min, $max];
                            } else {
                                return [1, -1];
                            }
                        } elseif ($k == 'column') {
                            $tp = strtolower(trim($vv[0]));
                            $tf = strtolower(trim($vv[1]));
                            if (!empty($tp) && !empty($tf)) {
                                if (!isset($fieldall[$tf])) return [0, "条件字段 {$tf} 不存在"];
                                if (!in_array($tp, ['=', '<>', '>', '>=', '<', '<='])) return [0, "条件字段 {$tf} 判断语句错误"];
                                $whereret[$key][$k][$kk] = [$tp, $tf];
                            }
                        } else {
                            if(is_array($vv)){
                                $whereret[$key][$k][$kk] = array_unique($vv);
                            }else{
                                $whereret[$key][$k][$kk] = $vv;
                            }
                        }
                    }
                }
            }
        }

        $fields = array_unique($fields);
        foreach ($fields as $val){
            if(!isset($fieldall[$val])) return [0, "条件字段 {$val} 错误"];
        }
        return [1, $whereret];
    }

    /**
     * 排序处理
     * @param $order
     * @param $fieldall
     * @return array
     */
    public function getOrder($order, $fieldall){
        if(!is_array($order)) return [0, "数据库排序错误"];
        $orderret = [];
        foreach($order as $key=>$val){
            $key = strtolower(trim($key));
            if(!isset($fieldall[$key])) return [0, "排序字段 {$key} 不存在"];
            $val = strtolower(trim($val));
            if(!in_array($val, ["asc", "desc"])){
                $val = "asc";
            }
            $orderret[$key] = $val;
        }
        return [1, $orderret];
    }

    private function setWhereQuery(&$query, $where, $is_or=false){
        if($is_or){
            $cmd_w      = 'orWhere';
            $cmd_wi     = 'orWhereIn';
            $cmd_wni    = 'orWhereNotIn';
            $cmd_wbt    = 'orWhereBetween';
            $cmd_wnbt   = 'orWhereNotBetween';
            $cmd_wn     = 'orWhereNull';
            $cmd_wnn    = 'orWhereNotNull';
            $cmd_wc     = 'orWhereColumn';
        }else{
            $cmd_w      = 'where';
            $cmd_wi     = 'whereIn';
            $cmd_wni    = 'whereNotIn';
            $cmd_wbt    = 'whereBetween';
            $cmd_wnbt   = 'whereNotBetween';
            $cmd_wn     = 'whereNull';
            $cmd_wnn    = 'whereNotNull';
            $cmd_wc     = 'whereColumn';
        }

        $keyflags = [
            '=' => $cmd_wi,
            '<>' => $cmd_wni,
            'between' => $cmd_wbt,
            'notbetween' => $cmd_wnbt,
            'null' => $cmd_wn,
            'notnull' => $cmd_wnn,
            'column' => $cmd_wc,
        ];
        foreach ($where as $key=>$val){
            $cmd = $keyflags[$key];
            foreach ($val as $k => $v) {
                if (in_array($key, ['=', '<>', 'between', 'notbetween'])) {
                    //等于、不等于、区间之内、区间之外
                    $query->$cmd($k, $v);
                } elseif (in_array($key, ['null', 'notnull'])) {
                    //值为空、值不为空
                    $query->$cmd($k);
                } elseif ($key == 'column') { //判断两个字段是否符合条件$v[0]为=,>和<
                    $query->$cmd($k, $v[0], $v[1]);
                } elseif ($key == 'like') { //模糊查询
                    if (count($v) > 1) {
                        $query->$cmd_w(function ($q) use ($v, $k, $key) {
                            foreach ($v as $vv) $q->where($k, $key, $vv);
                        });
                    } else {
                        foreach ($v as $vv) $query->$cmd_w($k, $key, $vv);
                    }
                } elseif ($key == 'child') { //子查询
                    $ws = $v;
                    if (count($ws) > 1) {
                        $query->$cmd_w(function ($q) use ($ws) {
                            $this->setWhereMod($q, $ws);
                        });
                    } else {
                        $this->setWhereMod($query, $ws, $is_or);
                    }
                } else {
                    if ($key == '>' || $key == '>=') {
                        $query->$cmd_w($k, $key, max($v));
                    } elseif ($key == '<' || $key == '<=') {
                        $query->$cmd_w($k, $key, min($v));
                    }
                }
            }
        }
    }
    /**
     * 条件构造查询
     * @param $mod 数据库构造器
     * @param $where 条件查询
     */
    public function setWhereMod(&$mod, $where, $is_or = false){
        if(empty($where) || !is_array($where)){
            return;
        }

        foreach ($where as $w){
            if($w == 'or'){
                $is_or = true;
                continue;
            }
            if($is_or){
                $cmd = 'orWhere';
            }else{
                $cmd = 'where';
            }
            if(count($w) > 1) {
                $mod->$cmd(function ($q) use ($w) {
                    $this->setWhereQuery($q, $w);
                });
            }else{
                $this->setWhereQuery($mod, $w, $is_or);
            }
            $is_or = false;
        }
    }

    /**
     * 获取分页信息
     * @param $page
     * @param $mod
     */
    private function selectGetPageInfo($page, &$mod){
        $pagesize = $page['pagesize'];
        $p = $_GET['p'];
        $p <= 0 && $p =1;
        $cot = 0;
        if($p > 0){
            $cot = $mod->count();
            $pmaxf = $cot / $pagesize;
            $pmax = intval($pmaxf);
            $pmaxf > $pmax && $pmax++;
            $p > $pmax && $p = $pmax;
        }
        $pages = $mod->paginate($pagesize, ['*'], 'p', $p);
        $pages->page = $p;
        $pages->pagesize = $pagesize;
        $pages->pagesizedef = $page['pagesizedef'];
        $pages->count = $cot;
        return $pages;
    }

    /**
     * 查找数据库
     * @param $data
     */
    private function _select($data, $field, $where, $whereAdd = [], $page = [], $order = []){
        $is_query = $this->is_query;
        $pk_name = '';
        if(!$is_query) {
            $field_arr = [];
            foreach ($field as $key => $val) {
                $field_arr[] = $key;
                if(empty($pk_name) && $val['key'] == 'PRI'){
                    $pk_name = $key;
                }
            }
            if (empty($field_arr)) return [0, "查询字段不能为空"];
        }
        $table = $data['table'];
        $conn = $data['conn'];
        if($is_query){
            $mod = DB::connection($conn)->table(DB::raw("(".$data['query'].") as query_table"));
            $pages = $this->selectGetPageInfo($page, $mod);
            $mod_list = $mod->get();
            $this->last_sql = $mod->toSql();
            return [1, json_decode(json_encode($mod_list), true), $pages];
        }else {
            $mod = DB::connection($conn)->table($table);
            !empty($where) && $this->setWhereMod($mod, $where);
            !empty($whereAdd) && $this->setWhereMod($mod, $whereAdd);

            $cdata = [];
            $isadd = false;
            if (!empty($data['edit'])) {
                //编辑操作
                $cdata = $data['edit'];
            } elseif (!empty($data['add'])) {
                //增加操作
                $cdata = $data['add'];
                $isadd = true;
            }
            if (!empty($cdata)) {
                $newdata = [];
                foreach ($cdata as $key => $val) {
                    if (is_string($key)) {
                        if (is_array($val)) {
                            $v = json_encode($val, true);
                        } elseif (is_numeric($val) || is_string($val)) {
                            $v = $val;
                        } else {
                            continue;
                        }
                        $newdata[$key] = $v;
                    }
                }
                if (!empty($newdata)) {
                    try {
                        if ($isadd) {
                            empty($pk_name) && $pk_name = 'id';
                            $pk_value = $mod->insertGetId($newdata);
                            if ($pk_value > 0) {
                                $this->setWhereMod($mod, [['=' => [$pk_name => [$pk_value]]]]);
                                return [1, json_decode(json_encode($mod->get()), true)];
                            } else {
                                return [0, "增加失败！"];
                            }
                        } else {
                            !empty($where) && $mod->update($newdata);
                        }
                    } catch (\Exception $e) {
                        return [0, "ERROR: " . $e->getMessage() . "<BR>File: " . $e->getFile() . "<BR>Line: " . $e->getLine()];
                    }
                }
            }
            $mod->select($field_arr);
        }

        $ispage = $page['ispage'];
        $export_type = $_GET['_@export@_'];
        $pages = [];
        if($export_type === 'all'){
            $sql_limit = env('SQL_LIMIT', 10000);
            !is_numeric($sql_limit) && $sql_limit = 10000;
            $mod->limit($sql_limit);
        }elseif($ispage){
            $pages = $this->selectGetPageInfo($page, $mod);
        }else {
            $limit = $data['limit'];
            $offset = $data['offset'];
            (empty($offset) || $offset <= 0) && $offset = 0;
            if ($limit != -1) {
                (empty($limit) || $limit <= 0) && $limit = 100;
                $mod->limit($limit)->offset($offset);
            }
        }
        if(!empty($order)){
            foreach($order as $key=>$val){
                $mod->orderBy($key, $val);
            }
        }
        $this->last_sql = $mod->toSql();
//        dump($this->last_sql );
        return [1, json_decode(json_encode($mod->get()), true), $pages];
    }

    /**
     * 设置字段高级关联值
     * @param $list
     * @param $fieldnext
     */
    private function setFieldList($config, &$list, $fieldnext){
        $fields = [];
        foreach ($fieldnext as $val){
            !empty($val[0][2]) && $fields[] = $val[0][2];
        }
        $fields = array_unique($fields);

        $data = [];
        foreach ($list as $key=>$val){
            foreach ($fields as $v){
                $data[$v][] = $val[$v];
            }
        }

        foreach ($data as $key=>$val){
            $data[$key] = array_unique($val);
        }
        $conn = $config['conn'];
        $field_tops = [];
        foreach ($fieldnext as $val){
            foreach ($val as $v){
                if(is_string($v[2]) && !isset($field_tops[$v[2]])){
                    $field_tops[$v[2]] = true;
                }
                break;
            }
        }
        $field_sets = [];
        foreach ($list as $val){
            foreach ($field_tops as $fkey=>$fval){
                $field_sets[$fkey][] = $val[$fkey];
            }
        }
        foreach ($field_sets as $key=>$val){
            $field_sets[$key] = array_unique($val);
        }
        $listadds = [];
        $next_kv_names = [];
        foreach ($fieldnext as $val){
            $i = 0;
            $top_field = '';
            foreach ($val as $v){
                if($i > 0){
                    $val[$i - 1][] =  $v[2];
                }else{
                    $top_field = $v[2];
                }
                $i ++;
            }
            $fsets = $field_sets;
            $next = [];
            foreach ($list as $lk=>$lv){
                $next[$lv[$top_field]] = $lv[$top_field];
            }
            foreach ($val as $v){
                $v1 = $v[1];
                $v2 = $v[2];
                $fkvs = $fsets[$v2];
                if(!isset($fkvs)){
                    continue;
                }
                $vtable = $v[0];
                $vconn = $conn;
                if(is_array($vtable)){
                    list($vtable, $vconn) = $vtable;
                    empty($vconn) && $vconn = $conn;
                }
                $v3 = $v[3];
                $tfkv = [];
                $tfvals = [];
                if(is_string($v3)){
                    $tfkv[$v3] = $v3;
                    $tfvals[] = $v3;
                }elseif(is_array($v3)){
                    foreach ($v3 as $v3k=>$v3v){
                        if(is_int($v3k)){
                            $tfkv[$v3v] = $v3v;
                            $tfvals[] = $v3v;
                        }elseif(is_string($v3v)){
                            $tfkv[$v3k] = $v3v;
                            $tfvals[] = $v3k;
                        }
                    }
                }
                foreach ($tfkv as $_v){
                    $next_kv_names[] = $_v;
                }
                $tfvals_select = $tfvals;
                $tfvals_select[] = $v1;
                $v4 = $v[4];
                if(!empty($v4)){
                    $tfvals_select[] = $v4;
                }
                try{
                    $vlist = DB::connection($vconn)->table($vtable)->whereIn($v1, $fkvs)->select($tfvals_select)->get();
                } catch (\Exception $e){
                    // 如果$fkvs中不支持字符串，则使用数字或小数类型搜索
                    $num_fkvs = [];
                    foreach ($fkvs as $fkv){
                        if(is_numeric($fkv)){
                            $num_fkvs[] = $fkv;
                        }
                    }
                    if(empty($num_fkvs)){
                        $vlist = [];
                    }else{
                        try {
                            $vlist = DB::connection($vconn)->table($vtable)->whereIn($v1, $num_fkvs)->select($tfvals_select)->get();
                        } catch (\Exception $e){
                            // 如果$fkvs中不支持小数，则使用数字类型搜索
                            $int_fkvs = [];
                            foreach ($num_fkvs as $num){
                                if(strpos($num, ".") === false){
                                    $int_fkvs[] = $num;
                                }
                            }
                            if(empty($int_fkvs)){
                                $vlist = [];
                            }else{
                                $vlist = DB::connection($vconn)->table($vtable)->whereIn($v1, $int_fkvs)->select($tfvals_select)->get();
                            }
                        }
                    }
                }
                $nnext = [];
                if(!empty($v4)){
                    $fsets = [];
                    foreach ($vlist as $vv){
                        $fsets[$v4][] = $vv->$v4;
                        $nnext[$vv->$v1] = $vv->$v4;
                    }
                }
                $dnext = [];
                foreach ($next as $nk=>$nv){
                    $dnext[$nv] = $nk;
                }
                foreach ($vlist as $vv){
                    foreach ($tfkv as $tkk=>$tvv) {
                        if(isset($dnext[$vv->$v1])) {
                            $listadds[$top_field][$tvv][$dnext[$vv->$v1]] = $vv->$tkk;
                        }
                    }
                }
                foreach ($next as $nk=>$nv){
                    $next[$nk] = $nnext[$nv];
                }
            }
        }
        foreach ($list as $key=>$val){
            foreach ($listadds as $k=>$v){
                foreach ($v as $kk=>$vv){
                    $list[$key][$kk] = $vv[$val[$k]];
                }
            }
        }
        $next_kv_names = array_unique($next_kv_names);
        foreach ($list as $key=>$val){
            foreach ($next_kv_names as $v){
                if(!isset($val[$v])){
                    $list[$key][$v] = '';
                }
            }
        }
    }

    /**
     * 查找表数据
     * @param $config
     * @return array
     */
    private function getSelectData($config, $tplwhere = [], $page = []){
        $query = $config['query'];
        $is_query = false;
        if (!empty($query) && is_string($query)) {
            $query = trim($query);
            !empty($query) && $is_query = true;
        }
        $this->is_query = $is_query;

        if (!$is_query) {
            if (isset($config['where']) && !is_array($config['where'])) {
                return [1, []];
            }
            //验证数据库是否正确
            list($status, $field) = $this->checkDb($config);
            if (!$status) return [$status, $field];
            list($status, $where) = $this->getWhere($config['where'], $field['all']);
            if (!$status) return [$status, $where];
            if ($where == -1) return [1, null];
            if (!empty($tplwhere)) {
                list($status, $whereAdd) = $this->getWhere($tplwhere, $field['all']);
                if (!$status) return [$status, $whereAdd];
            }

            if (!empty($config['order'])) {
                list($status, $order) = $this->getOrder($config['order'], $field['all']);
                if (!$status) return [$status, $order];
            }
        }
        list($status, $list, $pageinfo) = $this->_select($config, $field['set'], $where, $whereAdd, $page, $order);
        if (!$status) return [$status, $list];
        if (empty($list)) return [1, $list];

        $fieldnext = $field['next'];
        $fieldshow = $field['show'];
        if (empty($fieldnext)) return [1, $list, $fieldshow, $pageinfo, $this->last_sql];

        $this->setFieldList($config, $list, $fieldnext);
        return [1, $list, $fieldshow, $pageinfo, $this->last_sql];
    }

    /**
     * 查找数据库列表
     * @param $config
     * @return array
     */
    public function select($config, $where = [], $page = []){
        $config = $this->setKeyLower($config);
        return $this->getSelectData($config, $where, $page);
    }

    /**
     * 查找数据库一个列表
     * @param $config
     * @return array
     */
    public function find($config, $where = []){
        $config = $this->setKeyLower($config);
        if(empty($config['offset']) || $config['offset'] < 0) $config['offset'] = 0;
        $config['limit'] = 1;
        list($status, $list, $fieldshow) = $this->getSelectData($config, $where);
        if(is_string($list)){
            $retlist = $list;
        }else{
            $retlist = $list[0];
        }
        return [$status, $retlist, $fieldshow, [], $this->last_sql];
    }

    /**
     * 获取数据下标，以 "." 隔开
     * @param array $data
     * @param string $index
     * @return array|mixed|null
     */
    private function getApiIndex($data=[], $index=""){
        $ret = $data;
        $index_arr = explode(".", $index);
        foreach ($index_arr as $ia){
            if(!is_array($ret)){
                $ret = null;
                break;
            }
            $ret = $ret[trim($ia)];
        }
        return $ret;
    }

    /**
     * 外部接口数据
     * @param $config
     * @param array $c_page
     * @param array $field
     * @return array
     */
    public function api($config, $c_page=[], $field=[], $obj=null){
        $url = $config['url'];
        list($url, $params) = HttpController::getUrlParams($url);
        $c_type = $config['get']['__'];
        if($c_type == 'list'){
            $page = $config['page'];
        }else{
            $page = false;
        }
        $gets = $_GET;
        $pk = $gets['pk'];
        $is_exp = false;
        if($page === false) {
            // 非分页初始化设置
            $is_page = false;
            if(empty($config['list'])){
                $config['list'] = 'data';
            }
        }else{
            // 分页初始化设置
            $is_page = true;
            if(!is_array($page)){
                $page = [];
            }

            // 分页参数传递
            if(empty($page['params'])){
                $page['params'] = [];
            }
            if(empty($page['params']['page'])){
                $page['params']['page'] = 'p';
            }
            if(empty($page['params']['pagesize'])){
                $page['params']['pagesize'] = 'psize';
            }

            $p_name = $page['params']['page'];
            $p = $gets['p'];
            unset($gets['p']);
            empty($p) && $p = 1;
            $gets[$p_name] = $p;
            $psize = $gets['psize'];
            // 如果是列表并且是导出状态
            if($obj->tpl_type == 'list'){
                $exp = $_GET['_@export@_'];
                if(in_array($exp, ['this', 'all'])){
                    // 导出全部
                    if($exp == 'all'){
                        $psize = env('SQL_LIMIT', 10000);
                        !is_numeric($psize) && $psize = 10000;
                    }
                    $is_exp = true;
                }
            }
            $psize_name = $page['params']['pagesize'];
            if(empty($psize)){
                $psize = $c_page['pagesize'];
            }
            if(empty($psize)){
                $psize = $c_page['pagesizedef'];
            }
            if(!empty($psize)){
                unset($gets['psize']);
                $gets[$psize_name] = $psize;
            }

            // 分页信息获取
            if(empty($page['info'])){
                $page['info'] = [];
            }
            if(empty($page['info']['page'])){
                $page['info']['page'] = 'data.p';
            }
            if(empty($page['info']['pagesize'])){
                $page['info']['pagesize'] = 'data.psize';
            }
            if(empty($page['info']['total'])){
                $page['info']['total'] = 'data.total';
            }
            $config['page'] = $page;

            // 分页数据获取
            if(empty($config['list'])){
                $config['list'] = 'data.list';
            }
        }

        foreach($gets as $key=>$val){
            $params[$key] = $val;
        }
        $page_get = $config['get'];
        if(is_array($page_get)){
            foreach($page_get as $key=>$val){
                $params[$key] = $val;
            }
        }
        if(!empty($pk)){
            $pk_json = json_decode($pk, true);
            if(!empty($pk_json)){
                $pk_json0 = $pk_json[0];
                if(!empty($pk_json0)){
                    $pk0 = json_decode($pk_json0, true);
                    if(is_array($pk0)){
                        foreach ($pk0 as $key=>$val){
                            $params[$key] = $val;
                        }
                    }
                }
            }
        }

        $posts = $_POST;
        $page_post = $config['post'];
        if(is_array($page_post)){
            foreach($page_post as $key=>$val){
                $posts[$key] = $val;
            }
        }
        $headers = [];
        $page_header = $config['header'];
        if(is_array($page_header)){
            foreach($page_header as $key=>$val){
                $headers[$key] = $val;
            }
        }
        $method = $config['method'];

        $param_str = http_build_query($params);
        $url .= "?{$param_str}";

        if($c_type == 'delete'){
            $p_data = $posts['data'];
            unset($posts['data']);
            $deletes = [];
            foreach ($p_data as $p_d){
                $p_d_arr = json_decode($p_d, true);
                !empty($p_d_arr) && is_array($p_d_arr) && $deletes[] = $p_d_arr;
            }
            if(count($deletes) > 0){
                if(count($deletes[0]) > 1){
                    foreach ($deletes[0] as $k=>$v){
                        $posts[$k] = $v;
                    }
                }else{
                    foreach ($deletes as $del){
                        foreach ($del as $k=>$v){
                            $v = trim($v);
                            if(empty($v)){
                                continue;
                            }
                            $v = str_replace("'", "''", $v);
                            if(empty($posts[$k])){
                                $posts[$k] = [];
                            }
                            $posts[$k][] = $v;
                        }
                    }
                    foreach ($posts as $key=>$val){
                        if(is_array($val)){
                            $posts[$key] = json_encode($val, true);
                        }
                    }
                }
            }
        }
        if($is_exp){
            $posts = [];
        }
        $html = HttpController::getHttpData($url, $posts, $method, $headers);
        $html = trim($html);
        if(empty($html)){
            EXITJSON(0, "获取数据错误： {$url}");
        }
        $html_data = json_decode($html, true);
        if(empty($html_data)){
            EXITJSON(0, $html);
        }

        // 返回状态设置
        if(!isset($config['code']) || !is_string($config['code'])) {
            $code = false;
        }elseif(empty($config['code'])){
            $code = 'code';
            $code_ok = 1;
        }else{
            $code = $config['code'];
            if(is_string($code)){
                $code_ok = 1;
            }elseif(is_array($code)){
                list($code, $code_ok) = $code;
                empty($code) && $code = 'code';
                empty($code_ok) && $code_ok !== 0 && $code_ok = 1;
            }else{
                $code = 'code';
                $code_ok = 1;
            }
        }

        $msg = $config['msg'];
        if(empty($msg) || !is_string($msg)){
            $msg = 'msg';
        }

        $msg_value = $this->getApiIndex($html_data, $msg);

        if($code !== false){
            // 错误提醒
            $t_code = $this->getApiIndex($html_data, $code);
            if($t_code != $code_ok){
                EXITJSON(0, $msg_value);
            }
        }

        $list = $this->getApiIndex($html_data, $config['list']);
        if(!is_array($list)){
            if(count($_POST) > 0){
                EXITJSON(1, $msg_value);
            }
            $list = [];
        }
        // 新增时直接返回成功
        if(empty($list) && count($_POST) > 0 && $c_type == 'add'){
            EXITJSON(1, $msg_value);
        }
        // 删除直接返回信息
        if($c_type == 'delete'){
            EXITJSON(1, $msg_value);
        }

        if($is_page){
            $cot = $this->getApiIndex($html_data, $page['info']['total']);
            $pagesize = $c_page['pagesize'];
            $current_page = $gets['p'];
            $current_page <= 0 && $current_page = 1;
            $item = array_slice($list, ($current_page - 1) * $pagesize, $pagesize);
            $hr_url = $_SERVER['HTTP_REFERER'];
            $pos = strpos($hr_url, '?');
            if($pos > 0){
                $hr_url = substr($hr_url, 0, $pos);
            }
            $pages = new \Illuminate\Pagination\LengthAwarePaginator($item, $cot, $pagesize, $current_page,[
                'path' => $hr_url,
                'pageName' => 'p'
            ]);
            $pages->page = $p;
            $pages->pagesize = $pagesize;
            $pages->pagesizedef = $c_page['pagesizedef'];
            $pages->count = $cot;
        }else{
            $pages = [];
        }

        $fieldshow = [];
        $has_title = false;
        foreach ($field as $key=>$val){
            $val['title'] && $has_title = true;
            $fieldshow[$key] = [
                'name' => $val['name'],
                'key' => $val['title'] ? 'PRI' : '',
                'type' => 'text'
            ];
        }
        // 如果不存在主键则默认使用第一个字段
        if(!$has_title){
            foreach ($field as $key=>$val){
                $fieldshow[$key]['key'] = 'PRI';
                break;
            }
        }

        return [1, $list, $fieldshow, $pages, $this->last_sql];
    }

    /**
     * 获取数据库表字段信息
     * @param string $connname 数据库配置名称
     * @param string $tablename 表名称
     * @param string $fieldname 字段名称
     * @return array
     */
    public function dbInfo($connname="", $tablename="", $fieldname=""){
        $connname = strtolower(trim($connname));
        $info = $this->getDbInfo($connname);

        $tablename = strtolower(trim($tablename));
        if(empty($tablename)) return $info['tb'];

        $prefix = strtolower(trim($this->sqlconfig[$connname]['prefix']));

        $fieldname = strtolower(trim($fieldname));
        $tb = $info['tb'][$prefix.$tablename];
        if(empty($tb)) return [];
        if(empty($fieldname)) return $tb['field'];

        $f = $tb['field'][$fieldname];
        if(empty($f)){
            return [];
        }
        return $f;
    }

    /**
     * 获取数据库配置
     * @return array
     */
    public function dbInfoCache(){
        $cacheid = $this->db_cacheid;
        $info = Cache::get($cacheid);
        if($info == null){
            $this->dbInfo();
            $info = Cache::get($cacheid);
        }
        return $this->getDbInfoListDetail($info);
    }
}
