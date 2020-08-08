<!DOCTYPE html>
@define $static_tphp = "/".config('path.static_tphp')
<html>
<head>
    <meta charset="utf-8">
    <title>{{$title}}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        table{
            display: none;
        }
        .layui-table tbody tr:hover{
            background-color: #FFFFFF !important;
        }
    </style>
</head>
<body>
<link rel="stylesheet" href="{{url($static_tphp.'layui/css/layui.css')}}"  media="all">
<script src="{{url($static_tphp.'js/jquery/jquery.js')}}"></script>
<table class="layui-table" lay-size="sm" style="display: none;">
    <thead>
    <tr>
        @foreach($field as $key=>$val)<th style="width: {{is_numeric($field_kv[$key]) ? $field_kv[$key]."px" : "calc(".$field_kv[$key]." - {$cot_width}px)"}};">{{$val}}</th>@endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($list as $key=>$val)
    <tr>
        @foreach($field as $fkey=>$fval)<td>{!! $val[$fkey] !!}</td>@endforeach
    </tr>
    @endforeach
    </tbody>
</table>
<script>
    $(function () {
        $('table').show();
    });
</script>
</body>
</html>
