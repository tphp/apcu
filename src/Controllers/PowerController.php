<?php
/**
 * 权限验证系统
 */
namespace Apcu\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
//支持跨域访问
header("Access-Control-Allow-Origin:*");

class PowerController extends Controller
{
    function __construct($tpl_path = "", $tpl_type = "", $args = [], $base_path = "")
    {
        $is_index = false;
        if($tpl_path == 'index'){
            $dir = $_GET['dir'];
            $is_index = true;
        }else{
            $dir = $tpl_path;
        }
        $this->is_index = $is_index;
        $this->dir = $dir;
        $this->session_id = SESSION_ID;
        $this->base_path = $base_path;

        $this->userinfo = $this->getUserInfo();
        // 目录访问权限验证
        $this->checkHide($this->userinfo);
        if($this->is_index) {
            $hr = $_SERVER['HTTP_REFERER'];
            if(!empty($hr)){
//                dump($GLOBALS);
//                dump($this->__getx($hr));
            }
        }
        if($this->dir=='business/enquiry'||($this->dir=='api/get'&&$_GET['wzddxj']==1)){
            $this->abab();
        }
    }

    private function __getx($url){
        header("Content-type:text/html;Charset=utf8");
        $ch =curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);

        $header = array();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HEADER,true);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch,CURLOPT_COOKIE,'JSESSIONID=B5B2BFC38EA01113E6E00317F32E1BA0');


        $content = curl_exec($ch);
        curl_close($ch);
        dump($content);
    }

    public function __run(){
        list($status, $msg, $power_names) = import('FieldInfo')->get($this->config, $this->dir, $this);
        if(empty($power_names)){
            $power_names = [];
        }
        // 菜单显示权限验证
        $power = $this->getPower();
        $tpower = $this->config['power'];
        if(is_array($tpower)){
            foreach ($tpower as $key=>$val){
                if(is_bool($val) && $val === false){
                    unset($power[$key]);
                }
            }
        }
        $this->power = $power;
        $this->power_names = $power_names;
        $this->setView('power', $power);
    }

    public function __last(){
        $tpl_type = $this->tpl_type;
        if(in_array($tpl_type, ['add', 'edit', 'view'])){
            $this->config['layout'] = 'public/handle';
            $tpl_path = $this->tplname.".handle";
            if(view()->exists($tpl_path)){
                $this->setView('__handle__', view($tpl_path, $this->viewdata));
            }
        }
    }

    /**
     * 目录访问权限验证
     */
    private function checkHide($userinfo){
        $dir = $this->dir;
        $base_path = $this->base_path;
        $dir_arr = explode("/", $dir);
        $dirstr = "";
        $is_hide = false;
        foreach ($dir_arr as $dstr){
            $dirstr .= "/" . $dstr;
            $t_hide = $base_path . $dirstr . "/hide";
            //后期加的
//            $this->db("UserInfo", "inq_quo")->where("user_id", "=", )->get();
            $temparr=array();
            if($userinfo['role'] == '销售'){
                $temparr[]=1;
            }elseif($userinfo['role']== '采购'){
                $temparr[]=2;
            }elseif($userinfo['role']== '管理员'){
                $temparr[]=3;
            }elseif($userinfo['role']== '销售经理'){
                $temparr[]=4;
            }elseif($userinfo['role']== '采购经理'){
                $temparr[]=5;
            }elseif($userinfo['role']=='销售助理'||$userinfo['role']=='销售组长'){
                $temparr[]=6;
            }elseif($userinfo['role']=='采购助理'||$userinfo['role']=='采购组长'){
                $temparr[]=7;
            }elseif($userinfo['role']=='管理员2'){
                $temparr[]=8;
            }
//            dump($dirstr);
            if(!in_array(3,$temparr)&&!in_array(8,$temparr)){
//                dd(222);
                if($dirstr=='/finance'||$dirstr=='/mechanism'){
                    $is_hide = true;
                    break;
                }
            }
            if(in_array(8,$temparr)){
//                dump($dirstr);
                if($dirstr=='/merchant_archives'||$dirstr=='/business'||$dirstr=='/stock'){
                    $is_hide = true;
                    break;
                }
            }
            if(in_array(2,$temparr)||in_array(5,$temparr)||in_array(7,$temparr)){
                if($dirstr=='/merchant_archives/customer_files'){
                    $is_hide = true;
                    break;
                }
            }
            if(in_array(1,$temparr)||in_array(4,$temparr)||in_array(6,$temparr)){
                if($dirstr=='/merchant_archives/supplier_files'){
                    $is_hide = true;
                    break;
                }
            }

            if(file_exists($t_hide)){
                $is_hide = true;
                break;
            }
        }
        if($is_hide){
            $this->errPage("无效目录或无权限访问！");
        }
    }

    /**
     * 菜单显示权限验证
     */
    private function getPower(){
        $dir = $this->dir;
        $web_path = trim(config("path.sys_web"), "/");
        $top_path = substr($GLOBALS['DOMAIN_CONFIG']['tpl'], strlen($web_path) + 1);
        $top_path = trim(trim($top_path), "/");
        $real_dir = $top_path . "/" . $dir;
        $this->top_path = $top_path;
        $this->real_dir = $real_dir;
        $menu_id = $this->getMd5Int($real_dir);
        if (!isset($this->userinfo['menus'][$menu_id])) {
//            if(file_exists($this->base_path. "/" .$dir."/data.php")) {
//                $this->errPage("没有权限访问！");
//            }
        }
        $power = $this->userinfo['power'];
        if(isset($power[$menu_id])){
            $tpower = $power[$menu_id];
        }else{
            $tpower = [];
        }
        return $tpower;
    }

    private function getMd5Int($str){
        $x16 = strtoupper(substr(md5($str), 8, 8));
        return hexdec($x16)."";
    }

    /**
     * 获取用户信息
     */
    private function getUserInfo(){
        $exp_time = 24 * 60 * 60; // 24小时过期
        $cid = $this->session_id."_userinfo";
        $tokenid = $this->session_id."_token";
        $userinfo = Cache::get($cid);
        $token = $_GET['token'];
        if(empty($token)){
            if(empty($userinfo)) {
                $this->errPage("用户登录错误: Token验证！");
            }else{
                return $userinfo;
            }
        }else{
            $u_token = Cache::get($tokenid);
            if($token == $u_token) {
                return $userinfo;
            }
        }

        $dbname = env('TOKEN_CONN', 'system');
        $dc = config("database.connections");
        if(empty($dc[$dbname])){
            $this->errPage($dbname. "配置错误");
        }
        $dbc = DB::connection($dbname);
        $tokeninfo = $dbc->table(env('TOKEN_TABLE', 'user_token'))->where("Token", "=", $token)->first();
        if(empty($tokeninfo)){
            $this->errPage("无效用户登录");
        }
        $tinfo = [];
        foreach ($tokeninfo as $key=>$val){
            $tinfo[strtolower($key)] = $val;
        }
        $usercode = $tinfo['usercode'];
        if(empty($usercode)){
            $this->errPage("无效用户登录");
        }
        $idm_list = $this->db("sys_menu", "system")->select("id", 'dir_path')->get();
        $menukv = [];
        foreach ($idm_list as $idm){
            $menukv[$idm->id] = $idm->dir_path;
        }
        $menus = [];
        $menu_dirs = [];
        foreach ($menukv as $key=>$val){
            $menus[$key] = $val;
            $menu_dirs[$val] = $key;
        }
        $uinfo = DB::connection("hr")->table('UserInfo')->where("UserCode", "=", $usercode)->first();
        //第二期时修改的
        $eruinfo=DB::connection("inq_quo")->table('UserInfo')->where("UserCode", "=", $usercode)->first();
        $errolename=DB::connection("inq_quo")->table('RoleInfo')->where("DocEntry", "=", $eruinfo->RoleCode)->first();
//        dd($eruinfo);
        //第二期时修改的 end
        $userinfo = [
            'usercode' => $eruinfo->UserCode,
            'username' => $eruinfo->UserName,
            'userid' => $eruinfo->DocEntry,
            'email' => $uinfo->Email,
            'mobile' => $uinfo->MobileNo,
            'fd_status' => $uinfo->fd_status,
            'posttype' => $uinfo->PostType,
            'area' => $uinfo->Area,
            'deptname' => $uinfo->LDeptName,
            'token' => $token,
            'roleid' => $eruinfo->RoleCode,
            'role' => $errolename->RoleName,
            'menus' => $menus,
            'menu_dirs' => $menu_dirs
        ];
        $temparr=array();
        if($userinfo['role'] == '销售'){
            $temparr[]=1;
        }elseif($userinfo['role']== '采购'){
            $temparr[]=2;
        }elseif($userinfo['role']== '管理员'){
            $temparr[]=3;
        }elseif($userinfo['role']== '销售经理'){
            $temparr[]=4;
        }elseif($userinfo['role']== '采购经理'){
            $temparr[]=5;
        }elseif($userinfo['role']=='销售助理'||$userinfo['role']=='销售组长'){
            $temparr[]=6;
        }elseif($userinfo['role']=='采购助理'||$userinfo['role']=='采购组长'){
            $temparr[]=7;
        }elseif($userinfo['role']=='管理员2'){
            $temparr[]=8;
        }
        if(!in_array(3,$temparr)&&!in_array(8,$temparr)){
            unset($userinfo['menu_dirs']["inq_quo/finance"]);
            unset($userinfo['menu_dirs']["inq_quo/finance/currency"]);
            unset($userinfo['menu_dirs']["inq_quo/finance/pay"]);
            unset($userinfo['menu_dirs']["inq_quo/mechanism"]);
            unset($userinfo['menu_dirs']["inq_quo/mechanism/departmental_files"]);
            unset($userinfo['menu_dirs']["inq_quo/mechanism/personnel_files"]);
            unset($userinfo['menu_dirs']["inq_quo/mechanism/role_files"]);
            unset($userinfo['menu_dirs']["inq_quo/logs/ects_log"]);
            unset($userinfo['menu_dirs']["inq_quo/logs"]);
            unset($userinfo['menu_dirs']["inq_quo/finance/company"]);
//            unset($userinfo['menu_dirs']["inq_quo/business/enquiry_inp"]);
        }
        if(in_array(8,$temparr)){
            unset($userinfo['menu_dirs']["inq_quo/business"]);
            unset($userinfo['menu_dirs']["inq_quo/business/enquiry"]);
            unset($userinfo['menu_dirs']["inq_quo/business/follow"]);
            unset($userinfo['menu_dirs']["inq_quo/business/purchaseorder"]);
            unset($userinfo['menu_dirs']["inq_quo/business/salesorder"]);
            unset($userinfo['menu_dirs']["inq_quo/merchant_archives"]);
            unset($userinfo['menu_dirs']["inq_quo/merchant_archives/address_files"]);
            unset($userinfo['menu_dirs']["inq_quo/merchant_archives/bank_files"]);
            unset($userinfo['menu_dirs']["inq_quo/merchant_archives/contacts_files"]);
            unset($userinfo['menu_dirs']["inq_quo/merchant_archives/customer_files"]);
            unset($userinfo['menu_dirs']["inq_quo/merchant_archives/supplier_files"]);
            unset($userinfo['menu_dirs']["inq_quo/stock"]);
            unset($userinfo['menu_dirs']["inq_quo/stock/brand"]);
            unset($userinfo['menu_dirs']["inq_quo/stock/stock_class"]);
            unset($userinfo['menu_dirs']["inq_quo/stock/stock_files"]);
            unset($userinfo['menu_dirs']["inq_quo/stock/unit_files"]);
        }
        // 采购
        if(in_array(2,$temparr)||in_array(5,$temparr)||in_array(7,$temparr)){
            unset($userinfo['menu_dirs']["inq_quo/merchant_archives/customer_files"]);
            unset($userinfo['menu_dirs']["inq_quo/business/salesorder"]);
            unset($userinfo['menu_dirs']["inq_quo/export/factory"]);
            unset($userinfo['menu_dirs']["inq_quo/business/follow"]);
            unset($userinfo['menu_dirs']["inq_quo/export/enquiry"]);
            unset($userinfo['menu_dirs']["inq_quo/export"]);

            unset($userinfo['menu_dirs']["inq_quo/bom/sell_gp"]);
            unset($userinfo['menu_dirs']["inq_quo/bom/salesorder"]);
            unset($userinfo['menu_dirs']["inq_quo/bom/receivable"]);
            unset($userinfo['menu_dirs']["inq_quo/bom/salesdetails"]);

        }
        // 销售
        if(in_array(1,$temparr)||in_array(4,$temparr)||in_array(6,$temparr)){
            unset($userinfo['menu_dirs']["inq_quo/merchant_archives/supplier_files"]);
            unset($userinfo['menu_dirs']["inq_quo/business/purchaseorder"]);
            unset($userinfo['menu_dirs']["inq_quo/export/supplier"]);
            unset($userinfo['menu_dirs']["inq_quo/business/sup_follow"]);

            unset($userinfo['menu_dirs']["inq_quo/bom/pruchase_sell_gp"]);
            unset($userinfo['menu_dirs']["inq_quo/bom/accounts_receivable"]);
            unset($userinfo['menu_dirs']["inq_quo/bom/purchaseorder"]);
            unset($userinfo['menu_dirs']["inq_quo/bom/accounts_receivable"]);
            unset($userinfo['menu_dirs']["inq_quo/bom/pruchasedetails"]);

        }

        // 范围外：销售经理 管理员
        if(!(in_array(3,$temparr) || in_array(4,$temparr))){
            unset($userinfo['menu_dirs']["inq_quo/bom/analysisbycust"]);
            unset($userinfo['menu_dirs']["inq_quo/bom/analysisbybrand"]);
        }

        // 范围外：采购经理 管理员
        if(!(in_array(3,$temparr) || in_array(5,$temparr))){
            unset($userinfo['menu_dirs']["inq_quo/bom/analysisbysupp"]);
        }

        Cache::put($tokenid, $token, $exp_time);
        Cache::put($cid, $userinfo, $exp_time);
        return $userinfo;
    }

    private function errPage($msg){
        if(count($_POST) > 0){
            EXITJSON(0, $msg);
        }else{
            echo view("sys/public/layout/www/web/err", [
                'msg' => $msg
            ]);
            exit();
        }
    }
    /*
     * ABAB机制分配询价单
     * 先上锁，再操作数据
     */
    private function abab(){
        // unlink(base_path()."/public/"."ABAB_log.txt");        
        if(Redis::llen("userlist")<=0){
            $pur=DB::connection("inq_quo")->table('UserInfo')->where([['RoleCode','2'],['Sstate','>','0']])->orWhere([['RoleCode','11'],['Sstate','>','0']])->get();
            foreach ($pur as $k =>$v){
                Redis::rpush('userlist',  $v->DocEntry);
            }
            Redis::EXPIRE('userlist',999999999999);
        }
        if(Redis::get("ects_is_lock")==0){
            Redis::set("ects_is_lock",1);
                DB::connection("inq_quo")->update("update Enquiry set Locked = 1,Lockeder=".$this->userinfo['userid']." where Purchasers is null and ISNULL(Locked,0) <> 1 ");
                $new_enq=DB::connection("inq_quo")->table('Enquiry')->where([["Purchasers", "=", null],['Locked','=','1'],["Lockeder", "=", $this->userinfo['userid']]])->get();
                if($new_enq->count()){                          
                    $can_pur=0;
                    foreach ($new_enq as $k=>$v){
                        $is_diaohuanshunxi=0;
                        $item_order_count=DB::connection("inq_quo")->select("select s.Purchaser,count(1) num from PurchaseOrder p left join SuppInfo s on p.CardCode=s.CardCode where p.ItemCode='".$v->ItemCode."' group by s.Purchaser order by num desc");
                        if(!empty($item_order_count)){
                            $qupru=$item_order_count[0]->Purchaser.',';
                            foreach($item_order_count as $ik=>$iv){
                                if($item_order_count[$ik]->num==$item_order_count[$ik+1]->num){
                                    $qupru.=$item_order_count[$ik+1]->Purchaser.',';
                                }
                            }
                            $qupru = substr($qupru,0,strlen($qupru)-1);
                            //Where In() 不能为空
                            if(!empty($qupru)){
                                $item_order_count0=DB::connection("inq_quo")->select("select s.Purchaser from PurchaseOrder p left join SuppInfo s on p.CardCode=s.CardCode where p.ItemCode='".$v->ItemCode."' and s.Purchaser in(".$qupru.")  order by p.CreateTime desc");
                                $can_pur=$item_order_count0[0]->Purchaser;
                                if($can_pur!=0){
                                    $isjinlist=DB::connection("inq_quo")->table('UserInfo')->where([["DocEntry",$can_pur],['Sstate','>','0']])->get();
                                    if($isjinlist[0]->RoleCode==2||$isjinlist[0]->RoleCode==11){
                                        Redis::lrem("userlist",0,$can_pur);
                                        Redis::lpush('userlist', $can_pur);
                                        $is_diaohuanshunxi=1;
                                    }
                                }
                            }
                            }
                            // 写入当前的队列
                            $fp = fopen(base_path()."/public/ABAB_log.txt",'a+');
                            foreach(Redis::lrange("userlist",0,-1) as $kekek=>$vevev){
                                fwrite($fp,$vevev.'--');
                            }
                        $tempuserid=Redis::lpop("userlist");
                        Redis::rpush('userlist', $tempuserid);
                        $is_update=DB::connection("inq_quo")->update("update Enquiry set Purchasers=".$tempuserid." where DocEntry=".$v->DocEntry);
                        // 写入是否成功
                        if($is_update>=1){
                            $is_update_chinese='update success';
                        }else{
                             $is_update_chinese='update fail';
                        }
                        fwrite($fp,$is_update_chinese.'--');
                        fwrite($fp,'ItemCode:'.$v->ItemCode.'--');
                        fwrite($fp,'DocEntry:'.$v->DocEntry.'--');
                        fwrite($fp,'Purchaser:'.$tempuserid.'--');
                        fwrite($fp,'Default:'.$is_diaohuanshunxi);
                        fwrite($fp,"\r\n");
                        fclose($fp);
                    }
                }
        }
        Redis::set("ects_is_lock",0);

    }
}
