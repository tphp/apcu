<!DOCTYPE html>
@define $static = config('path.static')
@define $md5_css = tpl_css()
@define $md5_js = tpl_js()
<html>
<head>
    <title>TPL : {{$tpl_path}}</title>
@if(!empty($md5_css))    <link rel="stylesheet" href="{{ url($static.'tpl/css/'.tpl_css().'.css')}}">
@endif
    <script src="{{ url($static.'js/jquery/jquery.js') }}"></script>
</head>
<body>
{!! $content !!}
@if(!empty($md5_js))<script src="{{ url($static.'tpl/js/'.tpl_js().'.js')}}"></script>
@endif
</body>
</html>