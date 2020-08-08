<?php
return function(){
    function get_sys_linux_info(){
        $str = shell_exec('more /proc/meminfo');
        $pattern = "/(.+):\s*([0-9]+)/";
        preg_match_all($pattern, $str, $out);
        $infostr = "<div>物理内存总量：<span>".intval($out[2][0] / 1024)."MB</span></div>";
        $infostr .= "<div>内存使用率：<span>".bcdiv(100 * ($out[2][0] - $out[2][1]), $out[2][0], 2)."%</span></div>";

        $mode = "/(cpu)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)[\s]+([0-9]+)/";
        $string=shell_exec("more /proc/stat");
        preg_match_all($mode, $string, $arr);
        $total = $arr[2][0] + $arr[3][0] + $arr[4][0] + $arr[5][0] + $arr[6][0] + $arr[7][0] + $arr[8][0] + $arr[9][0];
        $time = $arr[2][0] + $arr[3][0] + $arr[4][0] + $arr[6][0] + $arr[7][0] + $arr[8][0] + $arr[9][0];
        $percent = bcdiv($time, $total, 3);
        $percent = $percent * 100;
        $infostr .= "<div>CPU使用率:<span>".$percent."%</span></div>";

        $fp = popen('df -lh | grep -E "^(/)"',"r");
        $rs = fread($fp,1024);
        pclose($fp);
        $rs = preg_replace("/\s{2,}/",' ',$rs);  //把多个空格换成 “_”
        $hds = explode("\n", trim($rs));
        if(!empty($hds)) {
            $infostr .= "<table class=\"layui-table\" lay-size=\"sm\" style='margin-top: 20px; width: 600px;'><thead><tr><th>硬盘路径</th><th>总容量</th><th>已使用</th><th>剩余</th><th>使用率</th></tr></thead>";
            foreach ($hds as $hd) {
                $hs = explode(" ", trim($hd));
                $infostr .= "<tr><td>{$hs[5]}</td><td>$hs[1]</td><td>$hs[2]</td><td>$hs[3]</td><td>$hs[4]</td></tr>";
            }
            $infostr .= "</table>";
        }
        return $infostr;
    }
    $list = [
        'http://admin.demo.tphp.com' => ['TPHP框架', 'btn btn-medium btn-orange  btn-radius'],
        'http://admin.api.tphp.com' => ['TPHP接口系统', 'btn btn-medium btn-red btn-radius'],
        'http://help.tphp.com' => ['TPHP帮助文档', 'btn btn-medium btn-gray btn-radius'],
        'https://packagist.org/packages/tphp/apcu' => ['Composer依赖管理', 'btn btn-medium btn-blue btn-radius'],
    ];
    $this->setView('list', $list);
    $version = "<div>Laravel版本：<span>".app()::VERSION."</span></div>";
    if(strtoupper(substr(PHP_OS,0,3)) === 'WIN'){
        $this->setView('sys_info', "{$version}<div style='color:#666;'>".php_uname()."</div>");
    }else{
        $this->setView('sys_info', $version.get_sys_linux_info());
    }
};
