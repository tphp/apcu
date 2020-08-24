<?php
/**
 * Created by PhpStorm.
 * User: TPHP
 * Date: 20-7-28
 * Time: 上午11:16
 * Remark: Sqlite数据库字段同步脚本
 */

require_once "SqlClass.php";

class Sqlite extends SqlClass{
    protected function getDatabaseDetail($db_info)
    {
        // TODO: Implement getDatabaseDetail() method.

        $file_name = $db_info['database'];
        $ret = [];
        if(empty($file_name) || !is_file($file_name)){
            return [1, '数据库不存在', $ret];
        }
        $conn = $this->conn;
        $db = DB::connection($conn);
        $table_list = $db->select("select name as 'table' from sqlite_master where type='table' and name<>'sqlite_sequence' order by name");
        if(empty($table_list)){
            return [1, 'ok', $ret];
        }
        foreach ($table_list as $key=>$val){
            $table = $val->table;
            if(strpos($table, "'")){
                continue;
            }
            $field_list = $db->select("PRAGMA table_info('{$table}')");
            $ret[$table] = [];
            foreach ($field_list as $fd){
                $ret[$table][$fd->name] = [
                    'cid' => $fd->cid,
                    'type' => $fd->type,
                    'notnull' => $fd->notnull,
                    'dflt_value' => $fd->dflt_value,
                    'pk' => $fd->pk
                ];
            }
        }
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
            $field = [];
            $is_delete = false;
            foreach ($old_detail as $fkey=>$fval){
                $nd = $new_detail[$fkey];
                if(!isset($nd)){
                    $is_delete = true;
                    continue;
                }
                if(!$this->arrayIsEqual($nd, $fval, ['type', 'notnull', 'dflt_value', 'pk'])){
                    $field[] = $fkey;
                }
            }
            if(!empty($field) || $is_delete){
                $save_field = [];
                foreach ($old_detail as $fkey=>$fval){
                    if(isset($new_detail[$fkey])){
                        $save_field[] = $fkey;
                    }
                }
                $diff['change'][$table_name] = [
                    $new_detail,
                    $save_field
                ];
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

        $sqls = [];
        $table = $diff['table'];
        if(!empty($table)){
            $table_create = $table['create'];
            if(!empty($table_create)){
                foreach ($table_create as $table_name => $table_detail){
                    $sqls[$table_name]['__CREATE__'][] = $this->getCreateTableSql($table_name, $table_detail);
                }
            }
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
            foreach ($field as $table_name => list($create, $field)){
                $create_sql = $this->getCreateTableSql($table_name, $create);
                if(empty($field)){
                    // 当同步字段为空时
                    // 删除原表
                    $sqls[$table_name]['__DROP__'][] = "DROP TABLE \"{$table_name}\"";
                    // 创建新表
                    $sqls[$table_name]['__CREATE__'][] = $create_sql;
                }else{
                    $f = [];
                    foreach ($field as $fv){
                        $f[] = '"'.str_replace('"', '""', $fv).'"';
                    }
                    $f_str = implode(",", $f);
                    $r_table_name = "__{$table_name}";
                    // 先重命名表
                    $sqls[$table_name]['__RENAME__'][] = "ALTER TABLE \"{$table_name}\" RENAME TO \"{$r_table_name}\";";
                    // 创建新表
                    $sqls[$table_name]['__CREATE__'][] = $create_sql;
                    // 把原有的列表数据复制到新表
                    $sqls[$table_name]['__INSERT__'][] = "INSERT INTO \"{$table_name}\" ({$f_str}) SELECT {$f_str} FROM \"{$r_table_name}\";";
                    // 删除重命名表
                    $sqls[$table_name]['__DROP__'][] = "DROP TABLE \"{$r_table_name}\";";
                }
            }
        }
        return [1, $sqls];
    }

    protected function updateDatabase()
    {
        // TODO: Implement updateDatabase() method.

        $db_info = $this->db_info;
        $db_file = $db_info['database'];
        if(!is_file($db_file)){
            import('XSQLite', $db_file)->select("select 'add file' as msg");
        }

        $cot_ok = 0;
        $cot_no = 0;
        $sqls = $this->sqls;
        $errors = [];
        $oks = [];
        $db = DB::connection($this->conn);
        foreach ($sqls as $key=>$val) {
            foreach ($val as $k=>$v) {
                foreach ($v as $vv){
                    try {
                        $ret = $db->statement($vv);
                        if($ret > 0){
                            $cot_ok += $ret;
                            $oks[] = $vv;
                        }else{
                            $cot_no++;
                            $errors[] = $vv;
                        }
                    } catch (Exception $e) {
                        $cot_no++;
                        $errors[] = $e->getMessage();
                    }
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
        $tn = str_replace('"', '""', $table_name);
        $lst = [];
        $pks = [];
        foreach ($table_detail as $fk=>$fv){
            $fk = str_replace('"', '""', $fk);
            $s = "\"{$fk}\" {$fv['type']} ";
            if($fv['notnull'] == '1'){
                $s .= "NOT NULL ";
            }
            if($fv['dflt_value'] !== null){
                $dv = $fv['dflt_value'];
                $s .= "DEFAULT {$dv} ";
            }
            if($fv['pk'] == '1'){
                $pks[] = "\"{$fk}\"";
            }
            $lst[] = trim($s);
        }
        if(count($pks) > 0){
            $pk_str = implode(",", $pks);
            $lst[] = "PRIMARY KEY({$pk_str})";
        }
        $lst_str = implode(",", $lst);
        return "CREATE TABLE \"{$tn}\" ({$lst_str});";
    }
}
