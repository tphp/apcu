<?php

return function(){
    if($this->isPost()){
        $image = $_POST['image'];
        $btp = BASE_TPL_PATH_TOP;
        $userinfoid = $this->getCacheId("/{$btp}/sys/user/login/userinfo");
        $userinfo = Session::get($userinfoid);
        $userinfo['image'] = $image;
        Session::forget($userinfoid);
        Session::put($userinfoid, $userinfo, 24 * 60 * 60);
    }
};