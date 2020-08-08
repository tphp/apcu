<?php

return function (){
    if($this->isPost()) {
        $code = "";
        $name = $_POST['name'];
        if (!empty($name)) {
            $sysnote = apcu_fetch('_sysnote_');
            $xfile = import("XFile");
            $filepath = TPHP_TPL_PATH . "/sys/function/" . $sysnote[$name]['path'] . "/func.php";
            if(!is_file($filepath)){
                $filepath = TPHP_PATH."/function/" . $sysnote[$name]['path'] . "/func.php";
            }
            $code = $xfile->read($filepath);
        }
        EXITJSON(1, htmlspecialchars($code));
    }
    EXITJSON(0, '404错误');
};