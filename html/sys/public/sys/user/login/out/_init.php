<?php

return function(){
    $userinfoid = $this->getCacheId("../userinfo");
    Session::forget($userinfoid);
    redirect("/sys/user/login")->send();
};