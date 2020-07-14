<!DOCTYPE html>{{~$static = config('path.static')}}
<html>
<head>
    <title>芯球Apcu</title>
    <link rel="stylesheet" href="{{ url($static.'tpl/css/'.tpl_css().'.css')}}">
    <script src="{{ url($static.'pro/home/js/jquery.js') }}"></script>
@yield('css')
</head>
<body>
@yield('content')
<script src="{{ url($static.'tpl/js/'.tpl_js().'.js')}}"></script>
@yield('js')
</body>
</html>