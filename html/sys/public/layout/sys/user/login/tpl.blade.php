<!DOCTYPE html>
@php
    $static_tphp = config('path.static_tphp');
    $static = $static_tphp."admin/";
    $staticlayui = $static_tphp."layui/";
    $title = $_DC_['title'];
    $color = $_DC_['color'];
    if(empty($color[1])){
        $c = "2e99d4";
        $cd = "4176e7";
    }else{
        $c = $color[1];
        $cd = $color[0];
    }
@endphp
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{{preg_replace("/<[^>]*>/is", "", $title)}}</title>
    <meta name="renderer" content="webkit" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <link href="{{ url($staticlayui.'css/layui.css') }}" rel="stylesheet" />
    <link id="layuicss-layer" rel="stylesheet" href="{{ url($static.'css/layer.css') }}" media="all" />
    <link id="layuicss-layuiAdmin" rel="stylesheet" href="{{ url($static.'css/admin.css') }}" media="all" />
    <link href="{{ url($static.'css/login.css') }}" rel="stylesheet" />
    <link href="{{ url($static.'css/font-awesome.min.css') }}" rel="stylesheet" />
    <style>
        .layadmin-user-login-header h2 span{
            color: #F33;
            margin-left: 10px;
        }
    </style>
    <script src="{{ url($staticlayui.'layui.js') }}"></script>
    <script src="{{ url($static.'js/jquery.min.js') }}"></script>
</head>
<body class="layui-layout-body">
<div id="LAY_app">
    <div class="layadmin-user-login" id="LAY-user-login" style="display: none;">

        <div class="layadmin-user-login-main">
            <div class="layadmin-user-login-box layadmin-user-login-header">
                <h2 style="color:#{{$cd}}">{!!$title!!}</h2>
                <p>推荐使用谷歌浏览器</p>
            </div>
            <div class="layadmin-user-login-box layadmin-user-login-body layui-form">
                <form class="layui-form" id="login">
                    <div class="layui-form-item">
                        <label class="layadmin-user-login-icon layui-icon layui-icon-username" for="LAY-user-login-username"></label>
                        <input type="text" name="username" lay-verify="required" autocomplete="off" placeholder="用户名" class="layui-input" value="{{$username}}">
                    </div>
                    <div class="layui-form-item">
                        <label class="layadmin-user-login-icon layui-icon layui-icon-password" for="LAY-user-login-password"></label>
                        <input type="password" name="password" lay-verify="required" autocomplete="off" placeholder="密码" class="layui-input">
                    </div>
                    @if($login_captcha)
                    <div class="layui-form-item">
                        <label class="layadmin-user-login-icon" for="LAY-user-login-password"><i class="fa fa-code"></i></label>
                        <input type="text" name="captcha" lay-verify="required" autocomplete="off" placeholder="验证码" class="layui-input" style="width:62%;float: left;margin-right:11px;">
                        <img src="/sys/user/login/captcha" alt="captcha" onclick="this.src='/sys/user/login/captcha?seed='+Math.random()" height="36" id="captcha" style="margin-top: 1px">
                    </div>
                    @endif
                    <div class="layui-form-item">
                        <button class="layui-btn layui-btn-fluid" lay-submit="" lay-filter="login" style="background-color: #{{$c}}">登 入</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>

<script>
    //如果是子窗口则从父窗口跳转到登录页面
    if(parent.layer != undefined){
        parent.window.location.href = window.location.href;
    }
    layui.use(['layer', 'form'], function () {
        var layer = layui.layer,
            $ = layui.jquery,
            form = layui.form;
        $(window).on('load', function () {
            form.on('submit(login)', function () {
                $.ajax({
                    url: "/sys/user/login",
                    data: $('#login').serialize(),
                    type: 'post',
                    async: false,
                    success: function (res) {
                        layer.msg(res.msg, {offset: '50px', anim: 1});
                        if (res.code == 1) {
                            setTimeout(function () {
                                location.href = res.url;
                            }, 1500);
                        } else {
                            $('#captcha').click();
                        }
                    }
                })
                return false;
            });
            var uname = $("input[name='username']");
            if(uname.val() == ""){
                uname.focus();
            }else{
                $("input[name='password']").focus();
            }
        });
    });
</script>

<div class="layui-layer-move"></div>
</body>
</html>
