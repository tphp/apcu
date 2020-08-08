<?php
/**
 * Created by PhpStorm.
 * User: TPHP
 * Date: 19-2-11
 * Time: 下午12:05
 */

class XSQLite extends SQLite3 {
    function __construct($filename = '') {
        $this->filename = $filename;
    }

    public function execute($sql){
        try{
            $this->open($this->filename);
            $ret = $this->exec($sql);
            if(!$ret){
                $ret = [0, $this->lastErrorMsg()];
            }else{
                $ret = [1, $ret];
            }
        }catch (Exception $e){
            $ret = [0, $this->lastErrorMsg()];
        }
        $this->close();
        return $ret;
    }

    public function select($sql){
        try{
            $this->open($this->filename);
            $ret = $this->query($sql);
            if(!$ret){
                $retlist = [0, $this->lastErrorMsg()];
            }else {
                $list = [];
                while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
                    $lst = [];
                    foreach ($row as $key => $val) {
                        $lst[strtolower($key)] = $val;
                    }
                    $list[] = $lst;
                }
                $retlist = [1, $list];
            }
        }catch (Exception $e){
            $retlist = [0, $this->lastErrorMsg()];
        }
        $this->close();
        return $retlist;
    }
}