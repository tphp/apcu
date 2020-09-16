<!DOCTYPE HTML>
@php
    $static_tphp = config('path.static_tphp');
    $conf_url = rtrim(trim(getenv('URL_CONF')), '\\/') . "/";
    $css = $data['css'];
    empty($css) && $css = [];
    $js = $data['js'];
    empty($js) && $js = [];
    list($borwser_name, $borwser_ext) = _get_browser();
    $ext_tab_name = '';
    if(is_array($ext)){
        $ext_total = count($ext);
        $ext_total > 0 && $ext_tab_name = array_keys($ext)[0];
    }else{
        $ext_total = 0;
    }
    $key_json_str = base64_decode($_GET['key']);
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
    @include("sys.public.layout.tpl.error.page.div.style.tpl")
    <script>
        var list_exts = {};
        var list_ext_keys = {};
        var ext_tab_name = '{{$ext_tab_name}}';
    </script>
</head>
<body>
@if($borwser_name === 'IE' && $borwser_ext <= 8) <div style="
text-align: center;
color: #F33;
padding: 10px;
background-color: #FEE;
border-bottom: 1px #CCC solid;
">您的浏览器不支持，请更换或升级浏览器！</div> @endif
<div id="{{$app_list_name}}_edit_top" class="css_app_list_edit" style="display: none;">
    <div v-show="show === 2">
        <el-form size="small" class="css_more_button">
            <el-form-item class="left">
                <el-popover
                        placement="right"
                        trigger="click"
                        content="{{$key_json_str}}">
                    <span slot="reference">{{$sub_title}}</span>
                </el-popover>
            </el-form-item>
            <el-form-item class="right">
                @if($form_info['view'] > 0)
                    <el-button type="text" @click="show_edit = !show_edit;">
                        <template v-if="show_edit">收起 <span class="el-icon-arrow-up"></span></template>
                        <template v-else>展开 <span class="el-icon-arrow-down"></span></template>
                    </el-button>
                    @if($form_info['edit'] > 0)
                    <el-button type="primary" @click="save" :disabled="disabled">保存</el-button>
                    <el-button @click="reset">还原</el-button>
                    @endif
                @endif
                <el-button @click="flush">刷新</el-button>
                <el-button @click="close">关闭</el-button>
            </el-form-item>
        </el-form>
    </div>
    <div v-show="show === 1">@{{show_msg}}</div>
    <div v-show="show === 0">{!! $err_page !!}</div>
</div>
<div class="clear"></div>
<div class="css_more_button_nav"></div>
<div class="css_more_body">
<div id="{{$app_list_name}}_edit" class="css_app_list_edit" v-show="show === 2">
    @if(!empty($form))
    <transition name="edit_more">
        <div v-show="show_edit">
            @include("sys.public.layout.tpl.vue.list.edit")
        </div>
    </transition>
    @endif
    @if($ext_total > 0)
        <el-tabs class="css_more_list" type="card" @tab-click="ext_change">
            @foreach($ext as $key=>$val)
                <el-tab-pane label="{{$val['name']}}" tag="{{$key}}"></el-tab-pane>
            @endforeach
        </el-tabs>
    @endif
</div>
@if($ext_total > 0)
    @foreach($ext as $key=>$val)
        {!! tpl($tpl_ext, ['get' => ['dir' => $val['dir'], 'ext_key' => $key, 'json' => $val['json'], 'url_init' => $url_init]]) !!}
    @endforeach
@endif
</div>
<script>
    var list_edit = {
        url: '{!! $url !!}',
        key: {!! $keystr !!},
        show: 1,
        form_cot: {{count($form)}}
    }
</script>
@foreach($js as $val)<script language="javascript" src="{{$conf_url.$val.".js"}}"></script>
@endforeach
@if($ext_total > 0)
    <script language="javascript" src="{{$static_tphp}}sys/js/list.js"></script>
    <script>
        var list_vues = {};
        for(var i in list_exts){
            var iv_id = "app_list_" + i;
            list_vues[i] = config_vue_list(iv_id, list_exts[i], i);
            if(ext_tab_name === i) {
                list_vues[i].set_list_height();
            }else{
                list_vues[i].show = false;
            }
        }
    </script>
@endif
<script language="javascript" src="{{$static_tphp}}sys/js/list_edit.js"></script>
</body>
</html>
