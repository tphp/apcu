<fieldset class="layui-elem-field layui-field-title"><legend>系统登录入口</legend></fieldset>

@foreach($list as $key=>$val)
    <a class="btn btn-medium btn-radius btn-normal" href="{{$key}}" target="_blank">{!! $val[0] !!}</a>
@endforeach

<fieldset class="layui-elem-field layui-field-title"><legend>系统状态</legend></fieldset>

<div class="sys_info">
{!! $sys_info !!}
</div>
