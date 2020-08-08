<?php

return function(){
    if($this->isPost()){
        $old_password = $_POST['old_password'];
        unset($_POST['old_password']);
        $new_password = $_POST['password'];
        if(empty($old_password)){
            EXITJSON(0, "旧密码不能为空！");
        }
        if(empty($new_password)){
            EXITJSON(0, "新密码不能为空！");
        }

        $btp = BASE_TPL_PATH_TOP;
        $userinfoid = $this->getCacheId("/{$btp}/sys/user/login/userinfo");
        $userinfo = Session::get($userinfoid);
        $userid = $userinfo['id'];
        $info = $this->db('admin')->where("id", "=", $userid)->first();
        if(empty($info)){
            EXITJSON(0, "用户数据错误");
        }
        $salt = $info->salt;
        $pwd = $info->password;
        $salt_passwrod = md5($old_password.$salt);
        if($salt_passwrod != $pwd){
            EXITJSON(0, "旧密码不正确！");
        }

        if(md5($new_password.$salt) == $pwd){
            EXITJSON(0, "新密码不能和旧密码相同！");
        }
    }
};