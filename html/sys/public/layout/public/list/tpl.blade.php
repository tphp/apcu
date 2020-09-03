<!DOCTYPE html>
@php
    $static_tphp = config('path.static_tphp');
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
    <link rel="stylesheet" href="{{url($staticadmin.'vim/css/list.css')}}"/>
    @include("sys.public.layout.public.style")
    <script src="{{url($static_tphp.'layui/layui.js')}}" charset="utf-8"></script>
    <script src="{{url($static_tphp.'js/jquery/jquery.js')}}"></script>
    <script src="{{url($static_tphp.'js/jquery.index.js')}}"></script>
    @if(!empty($md5_css))<link rel="stylesheet" href="{{ url($static.'tpl/css/'.$md5_css.'.css')}}" />@endif
</head>
<body data-tpl-type="{{$tpl_type}}" data-tpl-base="{{trim($tpl_base, '/')}}" data-base-url="/{{$tpl_path}}" data-static-path="{{$static_tphp}}" data-field='{{ empty($field) ? '' : json__encode($field) }}'>
{!! $__tpl__ !!}
@if(!empty($md5_js))<script src="{{ url($static.'tpl/js/'.$md5_js.'.js')}}"></script>@endif
</body>
</html>
