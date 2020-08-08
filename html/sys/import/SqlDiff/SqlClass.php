<?php
/**
 * Created by PhpStorm.
 * User: TPHP
 * Date: 20-7-28
 * Time: 上午11:10
 */

abstract class SqlClass {
    /**
     * 获取数据库详情
     * @param $db_info
     * @return mixed
     */
    abstract protected function getDatabaseDetail($db_info);

    /**
     * 数据库字段比较
     * @param $new
     * @param $old
     * @return mixed
     */
    abstract protected function compareDatabase($new, $old);

    /**
     * 建立数据库语句信息
     * @param $diff
     * @return mixed
     */
    abstract protected function buildQuery($diff);

    /**
     * 同步数据库
     * @return mixed
     */
    abstract protected function updateDatabase();

    /**
     * 初始化
     * SqlClass constructor.
     * @param array $db_info
     * @param string $conn
     */
    public function __construct($conn='')
    {
        $this->db_info = config("database.connections.{$conn}");
        $this->conn = $conn;
        $this->xfile = import("XFile");
        $this->sqls = [];
        $this->db_path = "html/sys/db/";
        $this->base_path = base_path($this->db_path);
    }

    /**
     * 保持到文件
     * @param $filename
     */
    public function save(){
        // TODO: Implement save() method.

        $filename = $this->conn;
        if(empty($filename)) return [0, "未指定文件名！"];
        list($status, $msg, $data_new) = $this->getDatabaseDetail($this->db_info);
        if(!$status) return [0, $msg];
        $filepath = $this->base_path.$filename.".json";
        $this->xfile->write($filepath, json_encode($data_new, true));
    }

    /**
     * 获取信息比较
     * @param bool $is_rest
     * @return array
     */
    public function getDiff($is_rest = false){
        // TODO: Implement getDiff() method.

        list($status, $data_old) = $this->getJsonToFile();
        if(!$status){
            return [0, $data_old];
        }

        list($status, $msg, $data_new) = $this->getDatabaseDetail($this->db_info);
        if($status == 0) return [$status, $msg];
        if($is_rest){ //如果是还原则对调对比
            $diff = $this->compareDatabase($data_old, $data_new);
        }else {
            $diff = $this->compareDatabase($data_new, $data_old);
        }
        if(empty($diff)) {
            return [0, "数据相同，无需同步操作！"];
        }

        list($status, $sqls) = $this->buildQuery($diff);
        if(!$status){
            return [0, $sqls];
        }
        $this->sqls = $sqls;
        return [1, "同步列表", $sqls];
    }

    /**
     * 开始同步
     * @param array $diff
     */
    public function run(){
        // TODO: Implement run() method.

        if(empty($this->sqls)) return [0, "数据相同，无需同步操作！"];
        return $this->updateDatabase();
    }

    /**
     * 设置同步数据源
     * @param array $db_info
     */
    public function setSrcDb($db_info = []){
        $this->db_old = $db_info;
    }

    /**
     * 获取文件
     * @param $filename
     */
    protected function getJsonToFile(){
        $filepath = $this->base_path.$this->conn.".json";
        if(!is_file($filepath)){
            return [false, '未生成备份字段信息文件：'. $this->db_path.$this->conn.".json"];
        }
        $str = $this->xfile->read($filepath);
        if(empty($str)) return [true, []];
        $json = json_decode($str, true);
        empty($json) && $json = [];
        return [true, $json];
    }

    /**
     * 超时判断，设置未500毫秒
     * @param $ip
     * @param $port
     */
    protected function isLinked($ip, $database, $port, $driver){
        $url = $ip;
        !empty($port) && $url .= ":{$port}";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
        curl_exec($ch);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_errno > 0 && $curl_error != "Empty reply from server") {
            if(strpos($curl_error, "timed") > 0){
                $curl_error = "数据库 {$database} 链接超时";
            }elseif(strpos($curl_error, "port") > 0){
                $curl_error = "数据库 {$database} IP或端口配置错误";
            }elseif($driver == 'pgsql'){
                if(strpos($curl_error, "Recv failure") !== false){
                    return [1, "链接成功"];
                }
            }
            return [0, $curl_error];
        }
        return [1, "链接成功"];
    }

    /**
     * 链接测试
     */
    protected function linkedTest(){
        $conn = $this->conn;
        empty($conn) && $conn = 'testdb';
        $db_config = config("database.connections.{$conn}");
        if(empty($db_config)) return [0, "数据库配置错误"];

        $driver     = $db_config['driver'];
        $ip         = $db_config['host'];
        $database   = $db_config['database'];
        $port       = $db_config['port'];

        if(empty($ip) || empty($database)) return [0, "链接失败"];
        list($status, $msg) = $this->isLinked($ip, $database, $port, $driver);
        if(!$status) return [0, $msg];
        $db = \DB::connection($conn);
        try {
            if($driver == 'mysql'){
                $sqlstr = "show tables";
            } elseif ($driver == 'sqlsrv'){
                $sqlstr = "select top 1 name, object_id from sys.tables order by name asc";
            } elseif ($driver == 'pgsql'){
                $sqlstr = "select count(1) from pg_tables";
            }
            $db->select($sqlstr);
        }catch (\Exception $e){
            $einfo = $e->getMessage();
            if($driver == 'mysql'){
                if (strpos($einfo, "Unknown database") > 0) {
                    $einfo = "数据库不存在";
                } elseif (strpos($einfo, "Access denied for user") > 0) {
                    $einfo = "用户或密码不正确";
                }
            } elseif($driver == 'sqlsrv') {
                if (strpos($einfo, "General SQL Server error") > 0) {
                    $einfo = "数据库不存在";
                } elseif (strpos($einfo, "Adaptive Server connection failed") > 0) {
                    $einfo = "用户或密码不正确";
                }
            }
            return [0, $einfo];
        }
        return [1, "链接成功"];
    }

    /**
     * 获取键值类型的数据格式
     * @param $list
     * @param null $key_name
     * @param null $value_name
     * @param null $last_name
     * @return array
     */
    protected function getListToKeyValue($list, $key_name=null, $value_name=null, $last_name=null){
        $ret = [];
        if(empty($list)){
            return $ret;
        }
        if(empty($key_name) || !is_string($key_name)){
            return $ret;
        }

        $new_list = [];
        foreach ($list as $detail){
            $new_list[] = json_decode(json_encode($detail), true);;
        }
        if(empty($value_name)){
            foreach ($new_list as $detail){
                $kn = $detail[$key_name];
                unset($detail[$key_name]);
                $ret[$kn] = $detail;
            }
        }elseif(is_string($value_name)){
            if(empty($last_name)){
                foreach ($new_list as $detail){
                    $ret[$detail[$key_name]] = $detail[$value_name];
                }
            }elseif(is_string($last_name)){
                foreach ($new_list as $detail){
                    empty($ret[$detail[$key_name]]) && $ret[$detail[$key_name]] = [];
                    $ret[$detail[$key_name]][$detail[$value_name]] = $detail[$last_name];
                }
            }elseif(is_array($last_name)){
                foreach ($new_list as $detail){
                    empty($ret[$detail[$key_name]]) && $ret[$detail[$key_name]] = [];
                    $ret[$detail[$key_name]][$detail[$value_name]] = [];
                    $ret_addr = &$ret[$detail[$key_name]][$detail[$value_name]];
                    foreach ($last_name as $ln){
                        $ret_addr[$ln] = $detail[$ln];
                    }
                }
            }
        }elseif(is_array($value_name)){
            foreach ($new_list as $detail){
                $kn = $detail[$key_name];
                $ret[$kn] = [];
                foreach ($value_name as $vn){
                    $ret[$kn][$vn] = $detail[$vn];
                }
            }
        }
        return $ret;
    }

    /**
     * 判断两个数组是否相等
     * @param array $arr1
     * @param array $arr2
     * @param array $keys
     * @return bool
     */
    protected function arrayIsEqual($arr1=[], $arr2=[], $keys=[]){
        $is_equal = true;
        foreach ($keys as $key){
            if($arr1[$key] !== $arr2[$key]){
                $is_equal = false;
                break;
            }
        }
        return $is_equal;
    }

    /**
     * 保存日志
     * @param $name
     * @param $oks
     * @param $errs
     */
    protected function saveLogs($name, $oks, $errors){
        $datestr = date("Y/m/d_His");
        $path = storage_path('framework/cache/sql/')."{$name}/{$datestr}";
        if(count($oks) > 0){
            $this->xfile->write($path."_ok.sql", implode("\r\n", $oks));
        }
        if(count($errors) > 0){
            $this->xfile->write($path."_no.sql", implode("\r\n", $errors));
        }
    }
}
