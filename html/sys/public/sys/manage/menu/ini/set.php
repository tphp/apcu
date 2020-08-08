<?php

return function (){
    $dir = $_GET['dir'];
    $sysmenu = apcu_fetch('_sysmenu_');
    if(!empty($dir)){
        $dir = trim(trim($dir), '/');
        $dir_arr = explode("/", $dir);
        foreach ($dir_arr as $da){
            $sysmenu = $sysmenu[$da];
            if(!is_array($sysmenu) || empty($sysmenu['_next_'])){
                $sysmenu = [];
                break;
            }
            $sysmenu = $sysmenu['_next_'];
        }
    }
    $sysnote = apcu_fetch('_sysnote_');
    $this->setView("code_url", $this->getRealUrl("code"));
    $this->setView("sysnote", $sysnote);
    return $sysmenu;
};