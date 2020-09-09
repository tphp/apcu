<!DOCTYPE html>
@php
    $static_tphp = config('path.static_tphp');
    $staticadmin = $static_tphp."admin/";
    $includepath = $tpl_base.$tpl_path."/vimh";
    $is_ie = is_ie();
    $pk_str = $_GET['pk'];
    !empty($pk_str) && $pk_str = "&pk={$pk_str}";
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>TPL : {{$tpl_path}}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="{{url($static_tphp.'layui/css/layui.css')}}"  media="all">
    <link rel="stylesheet" href="{{url($staticadmin.'vim/css/edit.css')}}"  media="all">
    <link rel="stylesheet" href="{{url($staticadmin.'css/font-awesome.min.css')}}"/>
    @if($types['selects'] && !$is_ie)<link rel="stylesheet" href="{{url($static_tphp.'layui/css/modules/formselects/v4/formselects-v4.css')}}"  media="all">@endif
    @include("sys.public.layout.public.style")
    <script src="{{url($static_tphp.'layui/layui.js')}}" charset="utf-8"></script>
    <script src="{{url($static_tphp.'js/jquery/jquery.js')}}"></script>
    <script src="{{url($static_tphp.'js/jquery.index.js')}}"></script>
    @if($types['article'])
    <script src="{{url($static_tphp.'js/ueditor/ueditor.config.js')}}"></script>
    @endif
    @if($types['markdown'])
    <link rel="stylesheet" href="{{url($static_tphp.'js/markdown/css/editormd.min.css')}}"/>
    <style>
        .editormd-fullscreen{
            border-bottom: 1px #DDD solid;
        }
    </style>
    @endif
</head>
<body data-tpl-type="{{$tpl_type}}" data-tpl-base="{{trim($tpl_base, '/')}}" data-base-url="/{{$tpl_path}}" data-static-path="{{$static_tphp}}" data-field='{{ empty($field) ? '' : json__encode($field) }}'>
@if(!empty($handle_group) && is_array($handle_group))
@php
    $handle_group_count = count($handle_group);
@endphp
<form class="layui-form @if($handle_group_count <= 1) layui-form-main @endif" action="" lay-filter="main">
    @if($handle_group_count > 1)
    <div class="layui-tab layui-tab-brief">
        <ul class="layui-tab-title">
            @foreach($handle_group as $gkey=>$handle)
            <li data-tab="{{$gkey}}" @if($gkey == $handle_group_checked) class="layui-this" @endif>{{empty($gkey) ? '基本设置' : $gkey}}</li>
            @endforeach
        </ul>
        <div class="layui-tab-content @if($handle_group_count > 1) layui-form-main @endif">
    @endif
    @foreach($handle_group as $gkey=>$handle)
        @if($handle_group_count > 1) <div class="layui-tab-item  @if($gkey == $handle_group_checked) layui-show @endif"> @endif
        @foreach($handle as $key=>$val)
            @php
                $type = $val['type'];
                empty($val['list']) && $val['list'] = [];
                $_verify = $val['verify'];
                $_verify_str = "";
                if(!empty($_verify)){
                    $_verify_str .= "lay-verify=\"{$_verify}\"";
                    $_verify[0] != '@' && $_verify_str .= " placeholder=\"请输入{$val['src_name']}\"";
                }
                if($is_view || $val['view']){
                    $view = 'disabled=""';
                }else{
                    $view = '';
                }
            @endphp
            @if($type == 'hidden')
                <input type="hidden" @if($val['md5']) data-name="{{$key}}" @else name="{{$key}}" @endif>
            @elseif($type == 'segment')
            <fieldset class="layui-elem-field layui-field-title"><legend>{!!$val['name']!!}</legend></fieldset>
            @elseif($type == 'password')
            <div class="layui-form-item">
                <label class="layui-form-label">{!!$val['name']!!}</label>
                <div class="layui-input-block">
                    <input type="{{$type}}" @if($val['md5']) data-name="{{$key}}" @else name="{{$key}}" @endif {!!$_verify_str!!} class="layui-input js_password_readonly" {!!$view!!} readonly>
                </div>
            </div>
                @if($val['md5'])
                <div class="layui-form-item">
                    <label class="layui-form-label">确认{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <input type="{{$type}}" name="{{$key}}" lay-verify="md5" class="layui-input" {!!$view!!}>
                    </div>
                </div>
                @endif
            @elseif($type == 'article')
            <div class="layui-form-item">
                <label class="layui-form-label">{!!$val['name']!!}</label>
                <div class="layui-input-block" style="z-index:100;">
                    @php
                        empty($val['height']) ? $h = 300 : $h = $val['height'];
                        is_numeric($h) ? $hstr = "{$h}px" : $hstr = $h;
                    @endphp
                    <textarea class="js_ueditor" name="{{$key}}" id="editor_{{$key}}" style="width:100%;height:{{$hstr}};"></textarea>
                </div>
            </div>
            @elseif($type == 'markdown')
                <div class="layui-form-item">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <div class="js_markdown" id="markdown_{{$key}}" style="width:100%; z-index: 801;">
                            <textarea name="{{$key}}" style="display: none;"></textarea>
                        </div>
                    </div>
                </div>
            @elseif($type == 'select')
                <div class="layui-form-item">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <select name="{{$key}}">
                            @if(!isset($val['istop']) || $val['istop']) <option value="" @if(empty($field[$key])) selected="" @endif></option> @endif
                            @foreach($val['list'] as $k=>$v) <option value="{{$k}}">{!!$v!!}</option> @endforeach
                        </select>
                    </div>
                </div>
            @elseif($type == 'selects' || $type == 'checkbox')
                <div class="layui-form-item">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    @php
                        empty($field[$key]) ? $gv = [] : $gv = explode(",", $field[$key]);
                        $is_checkbox = $is_ie || $type == 'checkbox';
                        $afd_is_int = strpos($allfield[$key]['type'], 'int');
                    @endphp
                    <div @if($is_checkbox) class="layui-input-block js_checkbox" data-key="{{$key}}" @else class="layui-input-block" @endif>
                        @if($is_checkbox)
                            @foreach($val['list'] as $k=>$v)<input type="checkbox" name="{{$key}}[]" lay-skin="primary" value="{{$k}}" title="{{$v}}" @if(in_array($k, $gv)) checked="" @endif>@endforeach
                        @else
                        <select name="{{$key}}" @if($afd_is_int === false) xm-select="{{$key}}" xm-select-skin="default" xm-select-search="" @endif>
                            @if($afd_is_int === false)
                                <option value=""></option>
                            @else
                                @if(!isset($val['istop']) || $val['istop']) <option value="" @if(empty($field[$key])) selected="" @endif></option> @endif
                            @endif
                            @foreach($val['list'] as $k=>$v) <option value="{{$k}}" @if(in_array($k, $gv)) selected="" @endif>{!!$v!!}</option> @endforeach
                        </select>
                        @endif
                    </div>
                </div>
            @elseif($type == 'radio')
                <div class="layui-form-item">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        @php
                            $vv=$field[$key];
                        @endphp
                        @if($val['top'] !== false) <input type="{{$type}}" name="{{$key}}" value="" title="不选" @if(empty($vv)) checked="" @endif> @endif
                        @foreach($val['list'] as $k=>$v) <input type="{{$type}}" name="{{$key}}" value="{{$k}}" title="{{$v}}" @if($k == $vv) checked="" @endif> @endforeach
                    </div>
                </div>
            @elseif($type == 'image' || $type == 'file')
                @php
                    $type == 'image' ? $typename = '上传图片' : $typename = '上传文件';
                @endphp
                <div class="layui-form-item">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <input class="file_upload" type="file" data-name="{{$key}}" name="_file_{{$key}}" data-url="/{{$tpl_path}}.upload?field={{$key}}{{$pk_str}}">
                        <input class="layui-input js_image_or_file" type="text" name="{{$key}}">
                        <button class="layui-btn layui-btn-primary js_btn_image_or_file" data-type="{{$type}}">{{$typename}}</button>
                    </div>
                    @if($type == 'image')
                        @php
                            $thumbs = $val['thumbs'];
                        @endphp
                        @if(!empty($thumbs) && is_array($thumbs))
                            @foreach($thumbs as $k=>$v) @if(!isset($handle[$k]) && isset($allfield[$k])) <input name="{{$k}}" id="{{$k}}" type="hidden" value="{{$handle[$k]}}"> @endif @endforeach
                        @endif
                        <div class="layui-input-block js_image_show">
                            <a href="{{$field[$key]}}" target="_blank"><img src="{{$field[$key]}}" /></a>
                            <span></span>
                        </div>
                    @endif
                </div>
            @elseif($type == 'trees')
                <div class="layui-form-item">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <div class="js_input_trees" id="{{$key}}" data-json='{!! json__encode($val['list']) !!}'></div>
                    </div>
                </div>
            @elseif($type == 'dir')
                <div class="layui-form-item">
                    <label class="layui-form-label">{!!$val['name']!!}</label>
                    <div class="layui-input-block">
                        <input class="layui-input js_dir" type="text" name="{{$key}}">
                        <div class="layui-btn layui-btn-primary css_btn_dir js_btn_dir" data-name="{{$key}}" data-json='{{json__encode($val['list'])}}'><i class="fa fa-ellipsis-h" title="ellipsis-h"></i></div>
                    </div>
                </div>
            @elseif($type == 'field')
                @php
                    $vfd = $val['field'];
                @endphp
                @if(!empty($vfd) && is_array($vfd))
                    @php
                        $vfdkv = json_decode($field[$key], true);
                        empty($vfdkv) && $vfdkv = [];
                    @endphp
                    @foreach($vfd as $k=>$v)
                    @php
                        !is_string($k) && $k = $v;
                    @endphp
                    <div class="layui-form-item">
                        <label class="layui-form-label">{!!$v!!}</label>
                        <div class="layui-input-block">
                            <input class="layui-input" name="{{$key}}[{{$k}}]" type="text" value="{{$vfdkv[$k]}}">
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <label class="layui-form-label" style="width: auto; padding-left: 0px; color:#999;">{!!$val['name']!!}无效</label>
                        </div>
                    </div>
                @endif
            @elseif($type == 'tpl')
                @php
                    $vtpl = trim($val['tpl']);
                @endphp
                @if(!empty($vtpl))
                    @php
                        strpos($vtpl, ".") === false && $vtpl .= ".html";
                        empty($val['config']) ? $tcf = [] : $tcf = $val['config'];
                    @endphp
                    {!! tpl($vtpl, $tcf) !!}
                @endif
            @else
            <div class="layui-form-item">
                <label class="layui-form-label">{!!$val['name']!!}</label>
                @if($type == 'status')
                <div class="layui-input-block">
                    <input class="js_status" type="checkbox" name="{{$key}}" lay-skin="switch" lay-text="{{$val['text']}}" {!!$view!!}>
                </div>
                @elseif($type == 'tree')
                    <input type="hidden" name="{{$key}}" {!!$view!!} />
                    <div class="js_tree layui-select-option-first" data-isview="{{$is_view?'true':'false'}}" data-json='{{json__encode($val['text'])}}' data-notvalues='{{json__encode($val['notvalues'])}}' data-key="{{$key}}"></div>
                @elseif($type == 'textarea')
                    <div class="layui-input-block">
                        <textarea class="layui-textarea" type="{{$type}}" name="{{$key}}" {!!$_verify_str!!} class="layui-input" {!!$view!!}></textarea>
                    </div>
                @elseif($type == 'time')
                    <div class="layui-input-block">
                        <input class="layui-input js_input_time" type="text" name="{{$key}}" id="{{$key}}" {!!$_verify_str!!} {!!$view!!}>
                    </div>
                @else
                <div class="layui-input-block">
                    <input type="{{$type}}" name="{{$key}}" {!!$_verify_str!!} class="layui-input" {!!$view!!}>
                </div>
                @endif
            </div>
            @endif
        @endforeach
        @if($handle_group_count > 1) </div> @endif
    @endforeach
    @if($handle_group_count > 1)
        </div>
    </div>
    @endif
    <div style="display: none"><button class="layui-btn layui-btn-submit" lay-submit="" lay-filter="submit">立即提交</button><button type="reset" class="layui-btn  layui-btn-reset">重置</button></div>
</form>
@if($tpl_type == 'handle')
    <div class="layui-form layui-form-main">
        <div class="layui-input-block">
        <button class="layui-btn layui-btn-normal js_btn_save">保存</button>
        <button class="layui-btn layui-btn-primary js_btn_reset">还原</button>
        <button class="layui-btn layui-btn-primary js_btn_flush">刷新</button>
        </div>
    </div>
@endif
@endif
@if(view()->exists($includepath))@include($includepath)

@endif

@php
    $verify_file = dirname($static_tphp)."/tphp_ext/verify.js";
@endphp
@if(is_file(public_path($verify_file)))<script src="{{url($verify_file)}}" charset="utf-8"></script>
@endif
<script src="{{url($staticadmin.'vim/js/edit.js')}}" charset="utf-8"></script>
@include("sys.vim.handle.js")
@if($handle_group_count > 1)
    <script>
        layui.use(['element'], function(){

        })
    </script>
@endif
</body>
</html>
