<!DOCTYPE html>
@php
    $static_tphp = config('path.static_tphp');
    $verify_file = dirname($static_tphp)."/tphp_ext/verify.js";
    $static = config('path.static');
    $staticadmin = $static_tphp."admin/";
    $md5_css = tpl_css();
    $md5_js = tpl_js();
@endphp
<html lang="zh-CN">
<head>
    <meta name="keywords" content="{{$keywords}}" />
    <meta name="description" content="{{$description}}" />
    <title>{{$title}}</title>

    <link rel="stylesheet" href="{{url($static_tphp.'layui/css/layui.css')}}"  media="all">
    <link rel="stylesheet" href="{{url($staticadmin.'css/font-awesome.min.css')}}"/>
    <link rel="stylesheet" href="{{url($staticadmin.'js/sql/style.css')}}"/>
    @include("sys.public.layout.public.style")
    <script src="{{url($static_tphp.'layui/layui.js')}}" charset="utf-8"></script>
    <script src="{{url($static_tphp.'js/jquery/jquery.js')}}"></script>
    <script src="{{url($static_tphp.'js/jquery.index.js')}}"></script>
    @if(!empty($md5_css))<link rel="stylesheet" href="{{ url($static.'tpl/css/'.$md5_css.'.css')}}" />@endif
</head>
<body data-tpl-type="{{$tpl_type}}" data-tpl-base="{{trim($tpl_base, '/')}}" data-base-url="/{{$tpl_path}}" data-static-path="{{$static_tphp}}" data-field='{{ empty($field) ? '' : json__encode($field) }}'>

<form style="display: none" class="layui-form layui-form-main" action="" lay-filter="main">
    <input name="title" id="title">
    <input name="database_id" id="database_id">
    <input name="flag" id="flag">
    <input name="type" id="type">
    <input name="type_id" id="type_id">
    <input name="args" id="args" value='{{empty($sql_handle->args) ? '{}' : trim($sql_handle->args)}}'>
    <textarea name="code" id="code"></textarea>
    <input name="fieldset" id="fieldset">
    <input name="fieldrun" id="fieldrun" value="{{ $sql_handle->fieldrun }}">
    <input name="remark" id="remark">
    <button class="layui-btn layui-btn-submit" lay-submit="" lay-filter="submit">立即提交</button><button type="reset" class="layui-btn  layui-btn-reset">重置</button>
</form>
<div class="layui-form js_from_top">
    <div class="layui-form-item">
        <label class="layui-form-label">数据源</label>
        <div class="layui-input-inline" style="width: 100px">
            <select class="js_set_input_value" input="database_id">
                @foreach($dblist as $key=>$val)<option value="{{$val->id}}" @if($sql_handle->database_id == $val->id) selected="" @endif>{{$val->flag}}</option>@endforeach
            </select>
        </div>
        <label class="layui-form-label">执行</label>
        <div class="layui-input-inline" style="width: 90px">
            <select class="js_set_input_value" input="type">
                <option value="select" @if($sql_handle->type == 'select' || empty($sql_handle->type)) selected="" @endif>仅查询</option>
                <option value="selectc" @if($sql_handle->type == 'selectc') selected="" @endif>查询</option>
                <option value="statement" @if($sql_handle->type == 'statement') selected="" @endif>更新</option>
            </select>
        </div>
        <label class="layui-form-label">类型</label>
        <div class="layui-input-inline" style="width: 80px">
            <select class="js_set_input_value" input="type_id">
                @if(!empty($typelist))
                    @foreach($typelist as $key=>$val)
                        <option value="{{$key}}" @if($sql_handle->type_id == $key) selected="" @endif>{!! $val !!}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <label class="layui-form-label">命令标识</label>
        <div class="layui-input-inline" style="width: 200px">
            <input type="text" class="layui-input js_set_input_value" input="flag" value="{{ $sql_handle->flag }}">
        </div>
        <label class="layui-form-label js_input_title_label">标题</label>
        <div class="layui-input-inline js_input_title">
            <input type="text" class="layui-input js_set_input_value" input="title" value="{{ $sql_handle->title }}">
        </div>
    </div>
</div>
<pre id="sqlcode">{{$sql_handle->code}}</pre>
<div class="js_sql_oper">
    <div class="js_field">
        <div class="title">
            <button class="layui-btn layui-btn-primary layui-btn-xs js_field_title_clear_button">删除配置</button>
            <button class="layui-btn layui-btn-primary layui-btn-xs js_field_title_button">字段配置</button>
            <button class="layui-btn layui-btn-primary layui-btn-xs js_field_args_button">参数传递</button>
        </div>
        <div class="list js_field_list" data-json='{!! json__encode($sql_handle->fieldset) !!}'></div>
    </div>
    <div class="js_run">
        <div class="title" id="ControlsRow">
            <input class="layui-btn layui-btn-xs js_run_sql" type="Button" value="运行SQL"/>
            <input class="layui-btn layui-btn-primary layui-btn-xs js_run_sql_copy" type="Button" value="复制运行数据"/>
            <input class="layui-btn layui-btn-primary layui-btn-xs js_run_sql_view" type="Button" value="查看生成SQL"/>
            <span id="TabSizeHolder">
                缩进量
                <select id="TabSize" onchange="TabSizeChanged()">
                    <option value="1">1</option>
                    <option value="2" selected="true">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                </select>
            </span>
            <label for="QuoteKeys"><input type="checkbox" id="QuoteKeys" onclick="QuoteKeysClicked()" checked="true"/>引号</label>&nbsp;
            <a href="javascript:void(0);" onclick="SelectAllClicked()">全选</a>&nbsp;
            <span id="CollapsibleViewHolder">
                <label for="CollapsibleView">
                    <input type="checkbox" id="CollapsibleView" onclick="CollapsibleViewClicked()" checked="true"/>显示控制
                </label>
            </span>
            <span id="CollapsibleViewDetail">
                <a href="javascript:void(0);" onclick="ExpandAllClicked()">展开</a>
                <a href="javascript:void(0);" onclick="CollapseAllClicked()">叠起</a>
                <a href="javascript:void(0);" onclick="CollapseLevel(3)">2级</a>
                <a href="javascript:void(0);" onclick="CollapseLevel(4)">3级</a>
            </span>
            <span class="js_control">
                <i class="fa fa-chevron-up" title="上隐藏" ></i>
                <i class="fa fa-circle-o" title="中间" ></i>
                <i class="fa fa-chevron-down" title="下隐藏" ></i>
            </span>
        </div>
        <div class="list js_run_list">
            <div class="HeadersRow"><textarea id="RawJson"></textarea><textarea id="RawView"></textarea></div>
            <pre id="Canvas_msg" style="margin:10px 15px 0px 15px;"></pre>
            <div id="Canvas" class="Canvas"></div>
            <a id="gotop" href="#"><span>▲</span></a>
        </div>
    </div>
</div>

<script type="text/javascript">
    var baseuri = "{{$static_tphp}}api/";
</script>
<script src="{{url($static_tphp.'api/js/json.js')}}"></script>
<script src="{{url($static_tphp.'js/ace/ace.js')}}"></script>
<script src="{{url($static_tphp.'js/ace/ext-language_tools.js')}}"></script>
@if(is_file(public_path($verify_file)))<script src="{{url($verify_file)}}" charset="utf-8"></script>
@endif
<script src="{{url($staticadmin.'vim/js/edit.js')}}" charset="utf-8"></script>

@if(!empty($md5_js))<script src="{{url($static.'tpl/js/'.$md5_js.'.js')}}"></script>@endif
<script src="{{url($staticadmin.'js/sql/code.js')}}"></script>
</body>
</html>
