<!DOCTYPE html>
@php
    $static_tphp = config('path.static_tphp');
    $static = $static_tphp."admin/";
    $md5_css = tpl_css();
    $md5_js = tpl_js();
    $title = $_DC_['title'];
    $color = $_DC_['color'];

    function color($color, $op){
        $clen = strlen($color);
        if($clen == 3){
            $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
        }
        $clen = strlen($color);
        if($clen != 6 || !is_numeric($op)) return $color;
        $c1 = hexdec($color[0].$color[1]) + $op;
        $c2 = hexdec($color[2].$color[3]) + $op;
        $c3 = hexdec($color[4].$color[5]) + $op;
        $c1 < 0 && $c1 = 0;
        $c2 < 0 && $c2 = 0;
        $c3 < 0 && $c3 = 0;
        $c1 > 255 && $c1 = 255;
        $c2 > 255 && $c2 = 255;
        $c3 > 255 && $c3 = 255;

        $c1 = str_pad(dechex($c1), 2, '0');
        $c2 = str_pad(dechex($c2), 2, '0');
        $c3 = str_pad(dechex($c3), 2, '0');
        return $c1.$c2.$c3;
    }
    if(empty($title)){
        $title = "PHP后台系统";
    }
@endphp
<html lang="zh-CN">
<head>
    <meta name="format-detection" content="telephone=no" />
    <meta name="format-detection" content="email=no" />
    <meta name="viewport" content="viewport-fit=cover,width=device-width,height=device-height,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="renderer" content="webkit|ie-comp|ie-stand"/>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge">
    <title>{{preg_replace("/<[^>]*>/is", "", $title)}}</title>
    <link href="{{ url($static.'css/font-awesome.min.css') }}" rel="stylesheet"/>
    <link href="{{ url($static.'css/bootstrap.min.css') }}" rel="stylesheet"/>
    <link href="{{ url($static.'css/jquery.mCustomScrollbar.min.css') }}" rel="stylesheet"/>
    <link href="{{ url($static.'css/layer.css') }}" rel="stylesheet"/>
    <link href="{{ url($static.'css/style.css') }}" rel="stylesheet"/>
    @if(!empty($color) && is_array($color) && count($color) >= 2)
        @define $c1 = $color[0]
        @define $c2 = $color[1]
        <style>
            .frame-top {
                background: #{{$c1}};
                filter: progid:DXImageTransform.Microsoft.Gradient(startColorStr='#{{$c1}}', endColorStr='#{{$c2}}', gradientType='1');
                -ms-filter: progid:DXImageTransform.Microsoft.Gradient(startColorStr='#{{$c1}}', endColorStr='#{{$c2}}', gradientType='1');
                background: -webkit-linear-gradient(left bottom, #{{$c1}}, #{{$c2}});
                background: -moz-linear-gradient(left bottom, #{{$c1}}, #{{$c2}});
                background: -o-linear-gradient(left bottom, #{{$c1}}, #{{$c2}});
                background-size: cover
            }
            .frame-tabItem.active:after {
                background-color: #{{color($c1, 50)}}
            }
            .first-menu-list > li > .menu-item.active:after {
                background-color: #{{color($c2, 30)}}
            }
            .logo-title span{
                font-size: 12px;
                color: #333;
                background-color: #FFF;
                padding: 2px;
                border-radius: 5px;
            }
        </style>
    @endif
    <script src="{{ url($static.'js/pace.min.js') }}"></script>
    <script src="{{ url($static.'js/jquery.min.js') }}"></script>
    <!--[if lt IE 9]>
    <script src="{{ url($static.'js/IE9/html5shiv.min.js') }}"></script>
    <script src="{{ url($static.'js/IE9/respond.min.js') }}"></script>
    <![endif]-->

</head>
<body>
<div class="loadbg" id="loadbg"></div>
<div class="frame-top">
    <a class="logo-title">{!!$title!!}</a>
</div>
<div class="frame-menu-btn" id="frame_menu_btn"><i class="fa fa-fw fa-dedent"></i></div>
<div class="second-menu-wrap" id="second_menu_wrap"></div>

<div class="frame-menu">
    <div class="frame-menu-wrap" id="frame_menu"></div>
</div>
<div class="frame-tabs">
    <div class="frame-tabs-wrap">
        <ul id="frame_tabs_ul"></ul>
    </div>
</div>
<div class="frame-main" id="frame_main"></div>


<div class="frame_fullscreen">
    <a href="javascript:void(0);" id="flush_btn" title="刷新页面">
        <i class="fa fa-rotate-right"></i>
    </a>
    <a href="javascript:void(0);" id="refresh_btn" data-uri="/sys/cache/refresh" title="清除缓存">
        <i class="fa fa-recycle"></i>
    </a>
    <a href="javascript:void(0);" id="fullscreen_btn" title="全屏">
        <i class="fa fa-arrows-alt"></i>
    </a>
</div>
<div class="frame-personCenter"><a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown"><img
                id="userhead" src="{{ empty($image) ? url($static.'img/on-boy.jpg') : $image }}"><span>{{$username}}</span></a>
    <ul class="dropdown-menu pull-right">
        <li><a href="javascript:void(0);" id="set_userinfo"><i class="fa fa-user-o"></i>个人中心</a></li>
        <li><a href="javascript:void(0);" id="set_password"><i class="fa fa-edit"></i>修改密码</a></li>
        <li><a href="javascript:void(0);" id="loginout_btn"><i class="fa fa-power-off"></i>安全退出</a></li>
    </ul>
</div>
<script src="{{ url($static.'js/bootstrap.min.js') }}"></script>
<script src="{{ url($static.'js/jquery.cookie.min.js') }}"></script>
<script src="{{ url($static.'js/jquery.md5.min.js') }}"></script>
<script src="{{ url($static.'js/jquery.mCustomScrollbar.concat.min.js') }}"></script>
<script src="{{ url($static.'js/toastr.min.js') }}"></script>
<script src="{{ url($static.'js/layer.js') }}"></script>
<script src="{{ url($static.'js/index.js') }}"></script>
</body>
</html>
