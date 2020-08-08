<?php
/**
 * Created by PhpStorm.
 * User: yuxing
 * Date: 20-8-3
 * Time: 上午10:07
 * Remark: PostgreSql数据库字段同步脚本
 */

require_once "SqlClass.php";

class PgSql extends SqlClass{
    private $compare_field = ['pk', 'type', 'isnull', 'default', 'remark'];

    protected function getDatabaseDetail($db_info)
    {
        // TODO: Implement getDatabaseDetail() method.

        if(empty($db_info) || !is_array($db_info)) return [0, "配置为空"];
        $host = $db_info['host'];
        $database = $db_info['database'];
        $username = $db_info['username'];
        if(empty($host) || empty($database) || empty($username)) return [0, "配置错误"];
        $conn = $this->conn;
        list($status, $info) = $this->linkedTest($conn);
        if(!$status) return [0, "{$database}:{$info}"];

        $mod = DB::connection($conn);
        //遍历表
        $ret = $this->getListToKeyValue($mod->select(<<<EOF
SELECT
c.relname AS table,
a.attname AS field,
CASE WHEN pi.indrelid IS null THEN 0 ELSE 1 END AS pk,
format_type(a.atttypid, a.atttypmod) AS type,
CASE WHEN a.attnotnull THEN 0 ELSE 1 END AS isnull,
col.column_default AS default,
col_description(a.attrelid, a.attnum) AS remark
FROM
pg_class AS c
INNER JOIN pg_attribute AS a ON a.attrelid = c.oid
LEFT JOIN pg_index AS pi ON pi.indrelid = c.oid AND a.attnum = ANY (pi.indkey)
LEFT JOIN information_schema.columns col ON col.table_name=c.relname AND col.ordinal_position = a.attnum
WHERE
c.relname IN (
	SELECT
	tablename as relname
	FROM
	pg_tables
	WHERE
	pg_tables.tablename NOT LIKE 'pg_%'
	AND pg_tables.tablename NOT LIKE 'sql_%'
) AND
a.attrelid = c.oid AND
a.attnum > 0 AND
format_type(a.atttypid, a.atttypmod) <> '-'
ORDER BY c.relname, a.attnum
EOF
        ), 'table', 'field', $this->compare_field);
        return [1, 'ok', $ret];
    }

    protected function compareDatabase($new, $old)
    {
        // TODO: Implement compareDatabase() method.

        $diff = [
            'table' => [],
            'change' => []
        ];

        foreach ($old as $table_name => $table_detail){
            if (!isset($new[$table_name])){
                //删除表
                $diff['table']['drop'][$table_name] = $table_name;
            }
        }
        foreach ($new as $table_name => $new_detail){
            $old_detail = $old[$table_name];
            if (!isset($old_detail)){
                //创建表
                $diff['table']['create'][$table_name] = $new_detail;
                continue;
            }
            $field = [
                'add' => [],
                'delete' => [],
                'change' => [],
                'equal' => []
            ];
            foreach ($old_detail as $fkey=>$fval){
                $nd = $new_detail[$fkey];
                if(!isset($nd)){
                    $field['delete'][] = $fkey;
                    continue;
                }
                if($this->arrayIsEqual($nd, $fval, $this->compare_field)) {
                    $field['equal'][$fkey] = $nd;
                }else{
                    $field['change'][$fkey] = [$nd, $fval];
                }
            }
            foreach ($new_detail as $fkey=>$fval){
                $od = $old_detail[$fkey];
                if(!isset($od)){
                    $field['add'][$fkey] = $fval;
                }
            }
            if(empty($field['add'])){
                unset($field['add']);
            }
            if(empty($field['delete'])){
                unset($field['delete']);
            }
            if(empty($field['change'])){
                unset($field['change']);
            }
            if(empty($field['equal']) || (empty($field['add']) && empty($field['delete']) && empty($field['change']))){
                unset($field['equal']);
            }
            if(!empty($field)){
                $diff['change'][$table_name] = $field;
            }
        }
        return $diff;
    }

    protected function buildQuery($diff)
    {
        // TODO: Implement buildQuery() method.

        if (empty($diff['table']) && empty($diff['change'])) {
            return [0, "数据相同，无需同步操作！"];
        }

        //主键约束
        $this->pk_kvs = $this->getListToKeyValue(
            DB::connection($this->conn)->select("SELECT relname AS name, oid AS id FROM pg_class WHERE relkind='S'"),
            'name',
            'id'
        );

        //表约束
        $pri_kvs = $this->getListToKeyValue(
            DB::connection($this->conn)->select("SELECT conname AS name, oid AS id FROM pg_constraint WHERE contype='p'"),
            'name',
            'id'
        );

        $sqls = [];
        $table = $diff['table'];
        if(!empty($table)){
            // 创建表
            $table_create = $table['create'];
            if(!empty($table_create)){
                foreach ($table_create as $table_name => $table_detail){
                    $sqls[$table_name]['__CREATE__'][] = $this->getCreateTableSql($table_name, $table_detail);
                }
            }

            // 删除表
            $table_drop = $table['drop'];
            if(!empty($table_drop)){
                foreach ($table_drop as $table_name => $table_detail){
                    $tn = str_replace('"', '""', $table_name);
                    $sqls[$table_name]['__DROP__'][] = "DROP TABLE \"{$tn}\"";
                }
            }
        }

        $field = $diff['change'];
        if(!empty($field)){
            foreach ($field as $table_name => $field_info) {
                $f_add = $field_info['add'];
                $f_change = $field_info['change'];
                $f_delete = $field_info['delete'];
                $f_equal = $field_info['equal'];
                if(!empty($f_delete)){
                    foreach ($f_delete as $f_del){
                        $sqls[$table_name][$f_del][] = "alter table \"{$table_name}\" drop column \"{$f_del}\"";
                    }
                }

                $is_key_set = false;
                $pk_list = [];
                if(!empty($f_add)){
                    foreach ($f_add as $fk=>$fa){
                        if($fa['pk'] == 1){
                            !$is_key_set && $is_key_set = true;
                            $pk_list[] = $fk;
                        }
                        list($pk_str, $add_str) = $this->getChangeSql($fa, $table_name, $fk);
                        if(!empty($pk_str)){
                            $sqls[$table_name][$fk][] = $pk_str;
                        }
                        $sqls[$table_name][$fk][] = $add_str;
                    }
                }

                if(!empty($f_change)){
                    foreach ($f_change as $fk=>list($new, $old)){
                        if($new['pk'] != $old['pk']){
                            !$is_key_set && $is_key_set = true;
                        }
                        if($new['pk'] == 1){
                            $pk_list[] = $fk;
                        }
                        list($pk_str, $add_str) = $this->getChangeSql($new, $table_name, $fk, false, $old);
                        if(!empty($pk_str)){
                            $sqls[$table_name][$fk][] = $pk_str;
                        }
                        $sqls[$table_name][$fk][] = $add_str;
                    }
                }

                if($is_key_set){
                    $pkey = $table_name."_pkey";
                    if(isset($pri_kvs[$pkey])){
                        $sqls[$table_name]['__PK__'][] = "alter table \"{$table_name}\" drop CONSTRAINT \"{$pkey}\"";
                    }
                    if(!empty($pk_list)){
                        $pk_list_rep = [];
                        foreach ($pk_list as $pl){
                            $pk_list_rep[] = str_replace('"', '""', $pl);
                        }
                        $field_str = implode(",", $pk_list_rep);
                        $sqls[$table_name]['__PK__'][] = "alter table \"{$table_name}\" add CONSTRAINT \"{$pkey}\" primary key({$field_str})";
                    }
                }
            }
        }
        $sqls = $this->getRealSqls($sqls);
        if (empty($sqls)) {
            return [0, "数据相同，无需同步操作！"];
        }
        return [1, $sqls];
    }

    protected function updateDatabase()
    {
        // TODO: Implement updateDatabase() method.

        $db_info = $this->db_info;
        $cot_ok = 0;
        $cot_no = 0;
        $sqls = $this->sqls;
        $errors = [];
        $oks = [];
        $mod = DB::connection($this->conn);
        foreach ($sqls as $key=>$val) {
            foreach ($val as $k=>$v) {
                try {
                    $v_str_init = trim(implode(";", $v), ";");
                    if(strpos($v_str_init, ";") === false){
                        $v_str = $v_str_init;
                    }else{
                        $v_str = <<<EOF
DO $$
DECLARE
BEGIN
    {$v_str_init};
END
$$;
EOF;
                    }
                    $ret = $mod->statement($v_str);
                    if($ret > 0){
                        $cot_ok += $ret;
                        $oks[] = $v_str_init;
                    }else{
                        $cot_no++;
                        $errors[] = $v_str_init;
                    }
                } catch (Exception $e) {
                    $cot_no++;
                    $errors[] = $e->getMessage();
                }
            }
        }

        $this->saveLogs($db_info['driver']."/".$this->conn, $oks, $errors);

        if($cot_ok > 0){
            $str = "操作成功数: {$cot_ok}";
            if($cot_no > 0){
                $str .= "<BR>操作失败数: {$cot_no}";
                return [1, $str, $errors];
            }
            return [1, $str];
        }
        return [0, "操作失败数: {$cot_no}", $errors];
    }

    /**
     * 获取创建表语句
     * @param $table_name
     * @param $table_detail
     * @return string
     */
    private function getCreateTableSql($table_name, $table_detail){
        $pk_kvs = $this->pk_kvs;
        $tn = str_replace('"', '""', $table_name);
        $lst = [];
        $pks = [];
        $has_flag = false;
        $remarks = [];
        $l_str = "nextval('";
        $r_str = "'::";
        $l_str_len = strlen($l_str);
        $head_pks = [];
        foreach ($table_detail as $fk=>$fv){
            $fk = str_replace('"', '""', $fk);
            $s = "\"{$fk}\" {$fv['type']} ";
            if($fv['isnull'] == '0'){
                $s .= "NOT NULL ";
            }
            if(!empty($fv['default'])) {
                $fv_def = $fv['default'];
                $l_pos = strpos($fv_def, $l_str);
                $r_pos = strpos($fv_def, $r_str);
                if($l_pos >= 0 && $r_pos > $l_pos){
                    $pk_name = substr($fv_def, $l_pos + $l_str_len, $r_pos - $l_pos - $l_str_len);
                    if(!isset($pk_kvs[$pk_name])){
                        $head_pks[] = "CREATE SEQUENCE {$pk_name} START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1";
                    }
                }
                $s .= "DEFAULT {$fv['default']} ";
            }
            if ($fv['pk'] == '1') {
                $pks[] = "\"{$fk}\"";
            }
            $lst[] = trim($s);

            $remark = $fv['remark'];
            if(!empty($remark)){
                $remark = str_replace("'", "''", $remark);
                $remarks[] = "COMMENT ON COLUMN \"public\".\"{$tn}\".\"{$fk}\" IS '{$remark}'";
            }
        }
        if(count($pks) > 0 && !$has_flag){
            $pk_str = implode(",", $pks);
            $lst[] = "CONSTRAINT \"{$tn}_pkey\" PRIMARY KEY ({$pk_str})";
        }
        $lst_str = implode(",", $lst);
        $ret_str = "CREATE TABLE \"public\".\"{$tn}\" ({$lst_str});";
        if(count($remarks) > 0){
            $ret_str .= implode(";", $remarks).";";
        }
        if(!empty($head_pks)){
            $ret_str = implode(";", $head_pks).";".$ret_str;
        }
        return $ret_str;
    }

    /**
     * 字段类型转换
     * @param $type
     * @param $otype
     */
    private function getUsingStr($type, $otype){
        $ret = "";
        if($type == $otype){
            return $ret;
        }

        if($type == 'smallint'){
            $ret = "integer";
        }elseif($type == 'integer'){
            if($otype == 'money'){
                $ret = "numeric";
            }else{
                $ret = "integer";
            }
        }elseif($type == 'money'){
            $ret = "numeric";
        }
        return $ret;
    }

    /**
     * 获取更改字段或新增字段语句
     * @param $info
     * @param bool $is_add
     * @return string
     */
    private function getChangeSql($info, $table_name, $field_name, $is_add=true, $old_info=[]){
        $type = $info['type'];
        if($is_add){
            $f_change_str = "ALTER TABLE \"public\".\"{$table_name}\" ADD \"{$field_name}\" {$type} ";
        }else{
            $otype = $old_info['type'];
            if($type !== $otype){
                $f_change_str = "ALTER TABLE \"public\".\"{$table_name}\" ALTER COLUMN \"{$field_name}\" TYPE {$type} ";
                $using = $this->getUsingStr($type, $otype);
                if(!empty($using)){
                    $f_change_str .= "USING  \"{$field_name}\"::{$using} ";
                }
            }
        }
        $isnull = $info['isnull'];
        $isnull_sql = '';
        if($is_add){
            if ($isnull == '0') {
                $f_change_str .= "NOT NULL ";
            }
        }elseif($isnull !== $old_info['isnull']){
            if ($isnull == '0') {
                $isnull_sql = "ALTER TABLE \"public\".\"{$table_name}\" ALTER COLUMN \"{$field_name}\" SET NOT NULL";
            }else{
                $isnull_sql = "ALTER TABLE \"public\".\"{$table_name}\" ALTER COLUMN \"{$field_name}\" DROP NOT NULL";
            }
        }
        $pk_str = "";
        if(empty($info['default'])) {
            if(!$is_add && !empty($old_info)){
                // 删除默认值
                $f_change_str = trim($f_change_str).";ALTER TABLE \"public\".\"{$table_name}\" ALTER COLUMN \"{$field_name}\" DROP DEFAULT";
            }
        }else{
            $pk_kvs = $this->pk_kvs;
            $l_str = "nextval('";
            $r_str = "'::";
            $l_str_len = strlen($l_str);
            $info_def = $info['default'];
            $l_pos = strpos($info_def, $l_str);
            $r_pos = strpos($info_def, $r_str);
            if($l_pos >= 0 && $r_pos > $l_pos){
                $pk_name = substr($info_def, $l_pos + $l_str_len, $r_pos - $l_pos - $l_str_len);
                if(!isset($pk_kvs[$pk_name])){
                    $pk_str = "CREATE SEQUENCE {$pk_name} START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1";
                }
            }
            if($is_add || $info_def != $old_info['default']){
                // 设置默认值
                $f_change_str = trim($f_change_str).";ALTER TABLE \"public\".\"{$table_name}\" ALTER COLUMN \"{$field_name}\" SET DEFAULT {$info_def}";
            }
        }
        $remark = $info['remark'];
        !empty($remark) && $remark = trim($remark);
        $is_set_remark = false;
        if($is_add){
            if(!empty($remark)){
                $is_set_remark = true;
            }
        }else{
            $oremark = $old_info['remark'];
            !empty($oremark) && $oremark = trim($oremark);
            empty($remark) && $remark = '';
            empty($oremark) && $oremark = '';
            if($remark !== $oremark){
                $is_set_remark = true;
            }
        }
        if($is_set_remark){
            $remark = str_replace("'", "''", $remark);
            $f_change_str = trim($f_change_str).";COMMENT ON COLUMN \"public\".\"{$table_name}\".\"{$field_name}\" IS '{$remark}'";
        }
        if(!empty($isnull_sql)){
            $f_change_str = trim($f_change_str).";".$isnull_sql;
        }

        return [$pk_str, trim(trim($f_change_str), ";")];
    }

    /**
     * 获取真实数据库语句
     * @param $sqls
     * @return array
     */
    private function getRealSqls($sqls){
        $retsqls = [];
        if(empty($sqls)){
            return $retsqls;
        }
        foreach ($sqls as $key=>$val) {
            foreach ($val as $k=>$v) {
                if(count($v) > 0){
                    $retsqls[$key][$k][] = implode(";", $v);
                }
            }
        }
        return $retsqls;
    }
}
