<?php


return function(){
    $cc = import("CaptchaExt", [
        'imageH'   => 36,
        'imageW'   => 115,
        'length'   => 4,
    ]);
    $cc->entry($this->cacheid);
    return false;
};