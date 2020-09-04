<!DOCTYPE html>
@php
    $static_tphp = config('path.static_tphp');
    $static = config('path.static');
    $staticadmin = $static_tphp."admin/";
    $is_backstage = false;
    if(isset($GLOBALS['DOMAIN_CONFIG'])){
        $dc = $GLOBALS['DOMAIN_CONFIG'];
        isset($dc['backstage']) && $is_backstage = $dc['backstage'];
        empty($title) && isset($dc['title']) && $title = $dc['title'];
    }
@endphp
<html lang="zh-CN">
<head>
    <meta name="keywords" content="{{$keywords}}" />
    <meta name="description" content="{{$description}}" />
    <title>{{$title}}</title>
    @if($is_backstage)
    <link href="{{url($staticadmin.'css/font-awesome.min.css')}}" rel="stylesheet"/>
    @include("sys.public.layout.public.style")
    @endif
    <script src="{{url($staticadmin.'js/jquery.min.js')}}"></script>
</head>
<body>
{!! $__tpl__ !!}
</body>
</html>
