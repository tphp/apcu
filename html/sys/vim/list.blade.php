<!DOCTYPE html>
@php
    $static_tphp = "/".config('path.static_tphp');
    $static = "/".config('path.static');
    $staticadmin = $static_tphp."admin/";
    $includepath = $tpl_base.$tpl_path."/viml";
    $is_ie = is_ie();
    $color = $_DC_['color'];
    if(!empty($pageinfo)){
        $undershow = true;
        $undershowstr = "true";
    }else{
        $undershow = false;
        $undershowstr = "false";
    }
    $xstring = import('XString');
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>TPL : {{$tpl_path}}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="{{url($static_tphp.'layui/css/layui.css')}}"  media="all">
    <link rel="stylesheet" href="{{url($static_tphp.'layui/css/modules/formselects/v4/formselects-v4.css')}}"  media="all">
    <link rel="stylesheet" href="{{url($staticadmin.'vim/css/list.css')}}"  media="all">
    <link rel="stylesheet" href="{{url($staticadmin.'css/font-awesome.min.css')}}"/>
    @include("sys.public.layout.public.style")
    <script src="{{url($static_tphp.'layui/layui.js')}}" charset="utf-8"></script>
    <script src="{{url($static_tphp.'js/jquery/jquery.js')}}"></script>
    <script src="{{url($static_tphp.'js/jquery.index.js')}}"></script>
    <script>var layer_is_list = "yes"; </script>
</head>
<body data-tpl-type="{{$tpl_type}}" data-base-url="/{{$tpl_path}}" data-isfixed="{{$vim['isfixed']}}" data-oper-title="{{$oper_title}}" data-handle-width="{{$vim['handleinfo']['width']}}" data-handle-height="{{$vim['handleinfo']['height']}}" data-handle-fixed="{{$vim['handleinfo']['fixed']?'true':'false'}}" data-handle-ismax="{{$vim['handleinfo']['ismax']?'true':'false'}}" data-istree="{{$is_tree?'true':'false'}}" data-fd='{{json__encode($field)}}' data-tree='{{json__encode($tree)}}' data-menu-id='{{$_GET['_mid_']}}'>
<div class="web_loadding" style="color:#999">正在加载中...</div>
@if(!empty($search))
<form class="layui-form" action="">
    <div class="layui-form-item">
        @foreach($search as $key=>$val)
        @php
            $fk = $field[$key];
            if(isset($fk['search_name'])){
                $fk_name = $fk['search_name'];
            }else{
                $fk_name = $fk['name'];
            }
            $tp = $val['type'];
            if(in_array($tp, ['radio', 'checkbox', 'selects'])){
                $search_width = "auto";
            } elseif ($tp == 'select') {
                $search_width = "150px";
            }else{
                $search_width = $val['width'] . "px";
            }
        @endphp
        <div class="lfi-in">
            <label class="layui-form-label">{{strlen($fk_name) > 20 ? $key : $fk_name}}</label>
            @if($tp == 'time' || $tp == 'between')
                <div class="layui-input-inline layui-input-between" style="margin-right: 0px;"><input type="text" class="layui-input {{ $tp == 'time' ? "js_search_time" : "" }}" name="{{$key}}" value="{{$_GET[$key]}}" id="search_{{$key}}"></div>
                <div class="layui-input-inline" style="width:auto;margin: 3px;padding: 0px;">-</div>
                <div class="layui-input-inline layui-input-between"><input type="text" class="layui-input {{ $tp == 'time' ? "js_search_time" : "" }}" name="{{$key}}__" value="{{$_GET[$key."__"]}}" id="search_{{$key}}__"></div>
            @else
                <div class="layui-input-inline" style="width: {{$search_width}};">
                @if($tp == 'status')
                <select name="{{$key}}">
                    <option value=""></option>
                    @foreach($val['list'] as $k=>$v) <option value="{{$k}}" @if(isset($_GET[$key]) && $_GET[$key] == $k) selected="" @endif>{!!$v!!}</option> @endforeach
                </select>
                @elseif($tp == 'select')
                <select name="{{$key}}">
                    <option value=""></option>
                    @foreach($val['list'] as $k=>$v) <option value="{{$k}}" @if(isset($_GET[$key]) && $_GET[$key] == $k) selected="" @endif>{!!$v!!}</option> @endforeach
                </select>
                @elseif($tp == 'selects' || $tp == 'checkbox')
                <select name="{{$key}}" xm-select="{{$key}}" xm-select-skin="default" xm-select-search="">
                    @define $gv = explode(",", $_GET[$key])
                    <option value=""></option>
                    @foreach($val['list'] as $k=>$v) <option value="{{$k}}" @if(in_array($k, $gv)) selected="" @endif>{!!$v!!}</option> @endforeach
                </select>
                @elseif($tp == 'radio')
                    <input type="{{$tp}}" name="{{$key}}" value="" title="不选" @if(empty($_GET[$key])) checked="" @endif >
                    @foreach($val['list'] as $k=>$v) <input type="{{$tp}}" name="{{$key}}" value="{{$k}}" title="{{$v}}" @if(isset($_GET[$key]) && $_GET[$key] == $k) checked="" @endif > @endforeach
                @else
                    <input type="text" name="{{$key}}" autocomplete="off" class="layui-input" value="{{$_GET[$key]}}" @if(isset($fk_size)) size="{{$fk_size}}" @endif>
                @endif
                </div>
            @endif
        </div>
        @endforeach
        <div class="layui-inline">
            <button class="layui-btn layui-btn-primary layui-btn-xs" lay-submit="" lay-filter="search">搜索</button>
            <button class="layui-btn layui-btn-primary layui-btn-xs" lay-submit="" lay-filter="reset">重置</button>
        </div>
    </div>
</form>
@endif
<div class="layui-form-batch">
    <div class="js_batch">
        <div class="layui-table-tool">
            @if(!empty($field_src))<div class="layui-inline js_tools_ext" title="筛选列"><i class="layui-icon layui-icon-cols"></i></div>@endif
            <div class="layui-inline js_tools_export" title="导出"><i class="layui-icon layui-icon-export"></i></div>
            <div class="layui-inline js_tools_print" title="打印"><i class="layui-icon layui-icon-print"></i></div>
        </div>
        @if(!empty($field_src))
        <div class="js_tools_ext_box">
            <select name="_field_set_args_" xm-select="_field_set_args_" xm-select-skin="default" xm-select-search="" style="position: absolute">
                @foreach($field_src as $key=>$val)
                <option value="{{$key}}" @if($val['disabled']) disabled="" selected="" @elseif($val['selected']) selected="" @endif>{{$val['name']}}</option>
                @endforeach
            </select>
        </div>
        @endif
        <ul class="js_tools_export_box">
            @if($vim['is']['checkbox'])<li class="js_batch_export_checked">导出所选</li>@endif
            <li class="js_batch_export_this" style="color:#08F;">导出本页</li>
            <li class="js_batch_export_all" style="color:#080;">导出 {{$sql_limit}} 行</li>
        </ul>
        @if(!empty($vim['batch']))
        @foreach($vim['batch'] as $key=>$val)
            @if($key == 'handle')
                @foreach($vim['handles'] as $k=>$v)<button class="layui-btn layui-btn-primary layui-btn-xs js_batch_{{$key}}" data-hdkey="{{$k}}" lay-batch="" lay-filter="{{$key}}">{{$v['key']}}</button>@endforeach
            @else
            <button class="layui-btn layui-btn-primary layui-btn-xs js_batch_{{$key}}" lay-batch="" lay-filter="{{$key}}">{{$val}}</button>
            @endif
        @endforeach
        @endif
        <button class="layui-btn layui-btn-primary layui-btn-xs js_flush">刷新</button>
    </div>
</div>
<div class="layui-table-view" style="margin: 10px; display: none; overflow: hidden;" data-click-find="thead tr th .layui-edge" layui-height-ful="0" layui-isunder="{{$undershowstr}}">
    <div class="layui-table-header">
        @if(!empty($field))
        <table class="layui-table" lay-size="sm">
            <thead>
            <tr>
                @if($vim['is']['numbers'])<th data-type="numbers"></th>@endif
                @if($vim['is']['checkbox'])<th data-type="checkbox"></th>@endif
                @foreach($field as $key=>$val){{~$addstr = ""}}@if ($val['order']) @if ($_GET['_sort'] == $key) {{~$addclass = $_GET['_order']}} @else {{~$addclass = "noset"}} @endif {{~$addstr = 'lay-sort="'.$addclass.'"'}} @endif @if(!($val['hidden']))
                        <th width="{{$val['width']}}" @if(isset($val['min-width'])) style="min-width: {{$val['min-width']}}px;" @endif
                            data-field="{{$key}}" {!!$addstr!!}
                            @if($val['status']) data-type="status" data-text="{{$val['text']}}"
                            @elseif($val['edit']) data-edit="true"
                            @elseif(!empty($val['click'])) data-click='{{json__encode($val['click'])}}'
                            @endif>{!! $val['name'] !!}</th>
                @endif @endforeach
                @if(!empty($vim['oper']))<th data-type="oper" width="{{$vim['operwidth']}}" data-json='{{json__encode($vim['oper'])}}'>操作</th>@endif
            </tr>
            </thead>
        </table>
        @endif
    </div>
    <div class="layui-table-body layui-table-main layui-form" lay-filter="table-body">
        @if(is_array($list))
        <table class="layui-table" lay-size="sm">
            <tbody>
            @foreach($list as $key=>$val)
                <tr pk='{!! $pklist[$key] !!}' pkmd5='{!! substr(md5($pklist[$key]), 8, 8) !!}' @if(isset($srclist[$key]['@child'])) child="{{$srclist[$key]['@child']}}" vchild="{{$srclist[$key][$tree['child']]}}" pkparent="" level="0" @endif >
                    @foreach($field as $k=>$v)@php empty($field[$k]['length']) ? $__len = 200 : $__len = $field[$k]['length']; @endphp
                    @if(!($v['hidden']))
                        <td>
                            @if(isset($srclist[$key][$k]))
                                <s>{{ mb_strlen($srclist[$key][$k]) > $__len ? mb_substr_chg($srclist[$key][$k], $__len) : $srclist[$key][$k] }}</s>
                            @endif
                            @if(isset($val[$k]))
                                <v>@php
                                    strlen($val[$k]) > $__len ? $_vv = mb_substr_chg($val[$k], $__len, true) : $_vv = $val[$k];
                                    $_vg = $_GET[$k];
                                    if($v['type'] == 'tree'){
                                        $_vg = str_replace("/", " ", $_vg);
                                        $_vgs = explode(' ', $_vg); $_vg_kvs = [];
                                        foreach ($_vgs as $__k => $__v){
                                            $__v = trim($__v);
                                            if(!empty($__v)){
                                                $__v_lower = strtolower($__v);
                                                $_vv = str_replace($__v, "_#{$__v}#_", $_vv);
                                                $_vg_kvs[$__v] = "_#{$__v}#_";
                                            }
                                        }
                                        foreach ($_vg_kvs as $__k => $__v){
                                            $_vv = $xstring->replaceStrToHtml($__v, "<span style='color:#F33'>{$__k}</span>", $_vv);
                                        }
                                    } else {
                                        $v['type'] != 'between' && !empty($_vg) && $_vv = $xstring->replaceStrToHtml($_vg, "<span style='color:#F33'>{$_vg}</span>", $_vv);
                                    }
                                @endphp {!! $_vv !!}</v>@endif
                        </td>@endif
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        @else
            <div style="margin: 20px;color:#F00">错误信息：{{$list}}</div>
        @endif
    </div>
    @if($undershow)
    <div class="layui-table-page">
        @if(!empty($pageinfo))<div id="pageinfo" style="float: right; margin-right: 15px; margin-bottom: 50px;" data-count="{!! $pageinfo['total'] !!}" data-page="{!! $pageinfo['now'] !!}" data-pagesize="{!! $pageinfo['size'] !!}" data-pagesizedef="{!! $pageinfo['sizedef'] !!}" data-color="{{$color[1]}}"></div>@endif
    </div>
    @endif
</div>
<div style="width: 1px; height: 1px;overflow: hidden;"><textarea id="WindowCopy" style="border: none;"></textarea></div>
<script src="{{url($staticadmin.'vim/js/list.js')}}" charset="utf-8"></script>
<script src="{{url($static_tphp.'layui/lay/modules/formselects-v4.js')}}" @if($is_ie) type="text/babel" @else type="text/javascript" charset="utf-8" @endif></script>
@php
    $md5_css = tpl_css();
    $md5_js = tpl_js();
@endphp
@if(!empty($md5_css))<link rel="stylesheet" href="{{ url($static.'tpl/css/'.$md5_css.'.css')}}">
@endif
@if(!empty($md5_js))<script src="{{ url($static.'tpl/js/'.tpl_js().'.js')}}"></script>
@endif
@if(view()->exists($includepath))@include($includepath)
@endif
</body>
</html>
