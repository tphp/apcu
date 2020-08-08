<?php

return function (){
    if($this->flushCache()){
        EXITJSON(1, "清除成功".$msg);
    }else{
        EXITJSON(0, "清除失败".$msg);
    }
};