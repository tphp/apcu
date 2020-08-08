<?php
class FieldInfo{
    public function get($data_info, $dir, $obj){
        $web_path = trim(config("path.sys_web"), "/");
        //自定义权限
        $cpower = [];
        $ret_cpower = [];
        $power_is_only = false;
        $di_power = [];
        if(is_array($data_info)){
            if(isset($data_info['#power'] )){
                $power_is_only = true;
                if(is_array($data_info['#power'])) {
                    $di_power = $data_info['#power'];
                }else{
                    $di_power = [];
                }
            }elseif(!empty($data_info['power'] ) && is_array($data_info['power'])){
                $di_power = $data_info['power'];
            }
        }
        if(!empty($di_power)){
            foreach ($di_power as $key=>$val){
                if(is_int($key)){
                    if(is_string($val)){
                        $cpower[$val] = [];
                    }
                }else{
                    if(!($power_is_only && !is_string($val))) {
                        $cpower[$key] = $val;
                    }
                }
            }

            foreach ($cpower as $key=>$val){
                if(is_string($val)){
                    $ret_cpower[$key] = $val;
                }elseif(empty($val) && !is_bool($val)){
                    $cpower[$key] = [];
                }
            }
        }

        $config = $data_info['config'];
        $type = $data_info['type'];
        $table = $config['table'];
        if(!(!empty($config) && is_array($config) && in_array($type, ['sql', 'sqlfind']) && !empty($table) && is_string($table))){
            if(empty($ret_cpower)) {
                return [0, "无效字段配置"];
            }
            return [1, "css_ret_power", $ret_cpower];
        }

        //系统默认权限
        $conn = $config['conn'];
        $dir_conns = [];
        $domains = config("domains");
        if(empty($conn)) {
            $def_conn = config('database.default_src');
            $dbkv = config('database.connections');
            foreach ($domains as $domain) {
                $dconn = $domain['conn'];
                empty($dconn) && $dconn = $def_conn;
                if (is_array($dbkv[$dconn])) {
                    $dir_conns[strtolower($domain['tpl'])] = $domain['conn'];
                }
            }
            $fpath = $web_path . "/" . $dir;
            $fpatharr = explode("/", $fpath);
            if (count($fpatharr) > 0) {
                $fpstr = $fpatharr[0];
                unset($fpatharr[0]);
                $fpathlist = [$fpstr];
                foreach ($fpatharr as $fpa) {
                    $fpstr .= "/" . $fpa;
                    $fpathlist[] = $fpstr;
                }

                $fpathlist = array_reverse($fpathlist);
                foreach ($fpathlist as $fpval) {
                    if (isset($dir_conns[$fpval])) {
                        $conn = $dir_conns[$fpval];
                        break;
                    }
                }
            }
        }
        $dbinfo = $obj->dbInfo($conn, $table);
        if(empty($dbinfo)){
            if(empty($ret_cpower)) {
                return [0, "{$conn} -> {$table} 未找到"];
            }
            return [1, "css_ret_powerv", $ret_cpower];
        }
        $dbkv = [];
        $dballkv = [];
        foreach ($dbinfo as $key=>$val){
            $name = $val['name'];
            empty($name) && $name = $key;
            if($val['key'] != 'PRI'){
                $dbkv[$key] = $name;
            }
            $dballkv[$conn][$table][$key] = $name;
        }
        $field = $config['field'];
        $power = [];
        if(empty($field) || is_string($field)){
            $power = $dbkv;
        } else {
            foreach ($field as $key=>$val) {
                if (is_string($key)) {
                    if (is_string($val)) {
                        $power[$key] = $val;
                    }
                } else {
                    if (is_string($val)) {
                        $vname = $dbkv[$val];
                        if (!empty($vname)) {
                            $power[$val] = $vname;
                        }
                    } elseif (is_array($val)) {
                        $old_conn = $conn;
                        $old_table = $table;
                        foreach ($val as $v) {
                            if (!is_array($v)) {
                                break;
                            }

                            list($v_table, $v_this, $v_parent, $v_field) = $v;
                            // 判断设置是否正确，一旦不正确则退出循环
                            if (empty($v_table) || empty($v_this) || !is_string($v_this) || empty($v_parent) || !is_string($v_parent) || empty($v_field)) {
                                break;
                            }
                            $v_parent = strtolower($v_parent);
                            if (!isset($dballkv[$old_conn][$old_table][$v_parent])) {
                                break;
                            }
                            if (is_array($v_table)) {
                                list($v_table, $v_conn) = $v_table;
                                empty($v_conn) && $v_conn = $conn;
                                $v_conn = strtolower($v_conn);
                            } else {
                                $v_conn = $conn;
                            }
                            if (!is_string($v_table)) {
                                break;
                            }
                            $v_table = strtolower($v_table);
                            $v_this = strtolower($v_this);
                            if (!isset($dballkv[$v_conn][$v_table])) {
                                $v_dbinfo = $obj->dbInfo($v_conn, $v_table);
                                if (!isset($v_dbinfo[$v_this])) {
                                    break;
                                }
                                foreach ($v_dbinfo as $kk => $vv) {
                                    $vv_name = $vv['name'];
                                    empty($vv_name) && $vv_name = $kk;
                                    $dballkv[$v_conn][$v_table][$kk] = $vv_name;
                                }
                                if (is_string($v_field)) {
                                    $v_field = strtolower($v_field);
                                    if (isset($dballkv[$v_conn][$v_table][$v_field])) {
                                        $power[$v_field] = $dballkv[$v_conn][$v_table][$v_field];
                                    }
                                } elseif (is_array($v_field)) {
                                    foreach ($v_field as $kk => $vv) {
                                        if (!is_string($vv)) {
                                            continue;
                                        }
                                        $vv = strtolower($vv);
                                        if (is_int($kk)) {
                                            $_key = $vv;
                                        } else {
                                            $_key = strtolower($kk);
                                        }
                                        if (isset($dballkv[$v_conn][$v_table][$_key])) {
                                            $power[$vv] = $dballkv[$v_conn][$v_table][$_key];
                                        }
                                    }
                                }
                            }
                            $old_conn = $v_conn;
                            $old_table = $v_table;
                        }
                    }
                }
            }
        }
        if(empty($cpower)){
            $class = "css_ret_field";
            if($power_is_only){
                return [0, "无效字段配置"];
            }
        }else {
            $class = "css_ret_field_power";
            if($power_is_only){
                $pw = [];
                foreach ($cpower as $key => $val) {
                    if(is_string($val)) {
                        $pw[$key] = $val;
                    }elseif(is_array($val)){
                        if(isset($power[$key])){
                            $pw[$key] = $power[$key];
                        }else{
                            $pw[$key] = $key;
                        }
                    }
                }
                return [1, $class, $pw];
            }

            foreach ($cpower as $key => $val) {
                if ($val === false) {
                    unset($power[$key]);
                } elseif (!empty($val) && is_string($val)) {
                    $power[$key] = $val;
                }
            }
        }
        return [1, $class, $power];
    }
}