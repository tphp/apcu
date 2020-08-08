<?php

return function ($data){
    $id = $_GET['id'];
    if($this->isEdit()) {
    }
    $php = "<?php";
    $url = $data['url'];
    $type = $data['type'];
    if($type == 'html') $type = "";
    if(empty($type)){
        $url_show = $url;
    }else{
        $url_show = $url.".".$type;
    }
    $info = [
        'init' => [
            'name' => '初始化',
            'file' => '_init.php',
            'type' => 'php'
        ],
        'data' => [
            'name' => '数据',
            'file' => 'data.php',
            'type' => 'php'
        ],
        'ini' => [
            'name' => '配置',
            'file' => 'ini.php',
            'type' => 'php'
        ],
        'set' => [
            'name' => '数据重设',
            'file' => 'set.php',
            'type' => 'php'
        ],
        'vim' => [
            'name' => '列表',
            'file' => 'vim.php',
            'type' => 'php'
        ],
        'vimh' => [
            'name' => '编辑',
            'file' => 'vimh.blade.php',
            'type' => 'html'
        ],
        'tpl' => [
            'name' => 'HTML',
            'file' => 'tpl.blade.php',
            'type' => 'html'
        ],
        'css' => [
            'name' => 'CSS',
            'file' => 'tpl.css',
            'type' => 'css'
        ],
        'js' => [
            'name' => 'JS',
            'file' => 'tpl.js',
            'type' => 'javascript'
        ]
    ];
    $bpath = base_path($this->config_tplbase.$this->getRealTplTop(true));
    $prodir = $bpath.$url."/";
    $xfile = import('XFile');
    if($this->isPost()){
        foreach($_POST as $key=>$val){
            $valtrim = strtolower(trim($val));
            if($valtrim == $php || empty($valtrim)){
                $val = "";
            }
            $file = $prodir.$info[$key]['file'];
            if(empty($val)){
                $xfile->delete($file);
            }else{
                $xfile->write($file, $val);
            }
        }

        //删除空目录
        $urlarr = explode("/", trim(trim($url, "/")));
        $urlx = [];
        $urlstr = "";
        foreach ($urlarr as $val){
            $urlstr .= "/{$val}";
            $urlx[] = $urlstr;
        }
        $urlx = array_reverse($urlx);
        foreach($urlx as $val){
            $tpath = $bpath.$val;
            //目录为空,=2是因为.和..存在
            if(count(scandir($tpath)) == 2){
                rmdir($tpath);
            }
        }
        if(count($_POST) > 1){
            EXITJSON(1, "保存成功（所有）！");
        }else{
            EXITJSON(1, "保存成功！");
        }
    }
    foreach ($info as $key=>$val){
        $value = $xfile->read($prodir.$val['file']);
        if(empty($value) && $val['type'] == 'php'){
            $info[$key]['value'] = $php."\n";
        }else{
            $info[$key]['value'] = $value;
        }
    }
    SEO([
        'title' => "TPL: {$url}"
    ]);
    $this->setView('url_show', $url_show);
    $this->setView('info', $info);
    $this->setView('id', $id);
    $this->setView('ini_url', $this->getRealUrl("../ini"));
    $this->setView("sys_function_path", "/".get_tphp_html_path()."sys/function");
    return $data;
};