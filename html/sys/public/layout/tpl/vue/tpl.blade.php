<!DOCTYPE HTML>
@php
    $static_tphp = config('path.static_tphp');
    $static = config('path.static');
    $conf_url = rtrim(trim(getenv('URL_CONF')), '\\/') . "/";
    $css = $data['css'];
    empty($css) && $css = [];
    $js = $data['js'];
    empty($js) && $js = [];
    list($borwser_name, $borwser_ext) = _get_browser();
@endphp
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@if(!empty($title)) {{$title}} - @endif 用户测试</title>
@foreach($css as $val)    <link rel="stylesheet" href="{{$conf_url.$val.".css"}}" />
@endforeach
    <link rel="stylesheet" href="{{$static_tphp}}js/vue/element/index.css" />
    <link rel="stylesheet" href="{{$static_tphp}}sys/css/style.css" />
    @if($borwser_name === 'IE')<script src="{{$static_tphp}}js/IE/polyfill.min.js"></script>@endif
    <script language="javascript" src="{{$static_tphp}}js/vue/vue.min.js"></script>
    <script language="javascript" src="{{$static_tphp}}js/vue/axios.min.js"></script>
    <script language="javascript" src="{{$static_tphp}}js/vue/element/index.js"></script>
    <script language="javascript" src="{{$static_tphp}}js/tinymce/tinymce.min.js"></script>
    <script language="javascript" src="{{$static_tphp}}js/plugins/base64.min.js"></script>
    <script language="javascript" src="{{$static_tphp}}js/plugins/md5.min.js"></script>

    <script language="javascript" src="{{$static_tphp}}js/vue.index.js"></script>
</head>
<body @if($is_menu) style="display: none;" @endif>
@if($borwser_name === 'IE' && $borwser_ext <= 8) <div style="
text-align: center;
color: #F33;
padding: 10px;
background-color: #FEE;
border-bottom: 1px #CCC solid;
">您的浏览器不支持，请更换或升级浏览器！</div> @endif
@if($is_menu)
<div id="app_body_top" class="app_body_top">
    <div class="left">
        {!! $GLOBALS['DOMAIN_CONFIG']['title'] !!}
    </div>
    <div class="right">
        <div class="title">@empty($child_title) @else {{$child_title}} @endif</div>
        <div class="oper">
            <el-popover placement="bottom-start" title="用户信息" width="200" trigger="click">
                <div>
                    <div>账号：{{$userinfo['usercode']}}</div>
                    @if(!empty($userinfo['mobile'])) <div>手机：{{$userinfo['mobile']}}</div> @endif
                    @if(!empty($userinfo['email'])) <div>邮箱：{{$userinfo['email']}}</div> @endif
                </div>
                <el-button class="user" slot="reference" type="text">{{$userinfo['username']}}</el-button>
            </el-popover>
            <el-button class="exit" type="text" @click="exit" title="退出"><span class="el-icon-circle-close in"></span></el-button>
        </div>
    </div>
</div>

<div id="app_menu" class="app_body_left">
    <el-menu
            default-active="{{$menu_index}}"
            class="el-menu-vertical-demo"
            :collapse="false"
            background-color="#F8F8F8"
    >
        <el-menu-item index="1" @click="select('/')">
            <i class="el-icon-s-home"></i>
            <span slot="title">首页</span>
        </el-menu-item>
        @foreach($menu_trees as $key=>$val)
            @if(empty($val['child']))
                <el-menu-item index="{{$val['index']}}" @click="select('{{$val['dir']}}')">
                    @if(!empty($val['icon'])) <i class="el-icon-{{$val['icon']}}"></i> @endif
                    <span slot="title">{{$val['name']}}</span>
                </el-menu-item>
            @else
                <el-submenu index="{{$val['index']}}">
                    <template slot="title">
                        @if(!empty($val['icon'])) <i class="el-icon-{{$val['icon']}}"></i> @endif
                        <span>{{$val['name']}}</span>
                    </template>
                    @foreach($val['child'] as $k=>$v)
                        @if(empty($v['child']))
                            <el-menu-item index="{{$v['index']}}" @click="select('{{$v['dir']}}')">
                                @if(!empty($v['icon'])) <i class="el-icon-{{$v['icon']}}"></i> @endif
                                <span slot="title">{{$v['name']}}</span>
                            </el-menu-item>
                        @else
                            <el-submenu index="{{$v['index']}}">
                                <template slot="title">
                                    @if(!empty($v['icon'])) <i class="el-icon-{{$v['icon']}}"></i> @endif
                                    <span>{{$v['name']}}</span>
                                </template>
                                @foreach($v['child'] as $kk=>$vv)
                                    <el-menu-item index="{{$vv['index']}}" @click="select('{{$vv['dir']}}')">
                                        @if(!empty($vv['icon'])) <i class="el-icon-{{$vv['icon']}}"></i> @endif
                                        <span slot="title">{{$vv['name']}}</span>
                                    </el-menu-item>
                                @endforeach
                            </el-submenu>
                        @endif
                    @endforeach
                </el-submenu>
            @endif
        @endforeach
    </el-menu>
</div>
<div class="app_body_right">
    @if($is_tpl)
        {!! $__tpl__ !!}
    @elseif($is_tpl_error)
        <div class="error">{{$error_msg}}</div>
    @elseif($is_flow)
        @include("sys.public.layout.tpl.vue.flow.tpl")
    @else
        @include("sys.public.layout.tpl.vue.search")
        @include("sys.public.layout.tpl.vue.list.tpl")
    @endif
</div>
<script>
    document.body.style.display = 'inline';
    var menu_dir = '{{$menu_dir}}';
</script>
<script language="javascript" src="{{$static_tphp}}sys/js/menu.js"></script>
@elseif($is_flow)
    @include("sys.public.layout.tpl.vue.flow.tpl")
@else
    @include("sys.public.layout.tpl.vue.search")
    @include("sys.public.layout.tpl.vue.list.tpl")
@endif
@if($is_flow)
    @include("sys.public.layout.tpl.vue.flow.js")
@else
    @include("sys.public.layout.tpl.vue.js")
@endif
@foreach($js as $val)<script language="javascript" src="{{$conf_url.$val.".js"}}"></script>
@endforeach
</body>
</html>
