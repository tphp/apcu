@php
    $static_tphp = config('path.static_tphp');
    $staticadmin = $static_tphp."admin/";
    $color = $_DC_['color'];
    list($borwser_name) = _get_browser();
    if(!empty($color) && is_array($color) && count($color) >= 2){
        $c1 = $color[0];
        $c2 = $color[1];
    }else{
        $c1 = '4176e7';
        $c2 = '2e99d4';
    }
@endphp
<style>
    .layui-table-cell .js_tree_fa,
    .layui-tab-brief>.layui-tab-title .layui-this,
    .layui-form-radio>i:hover,
    .layui-form-radioed>i,
    .layui-elem-field legend{
        color:#{{$c1}} !important;
    }
    .layui-form-radio{
        margin: 4px 10px 0 0;
    }
    .layui-form-select dl{
        width: 100%;
    }
    .layui-form-select dl dd.layui-this,
    .layui-btn-normal,
    .layui-btn{
        background-color:#{{$c2}} !important;
    }
    .layui-select-option-first dl.layui-anim dd.layui-this:first-child,
    .layui-btn-primary,
    .layui-laydate .layui-this{
        background-color:#FFF !important;
    }
    .layui-form-onswitch,
    .layui-layer-btn .layui-layer-btn0,
    .layui-form-checked[lay-skin=primary] i{
        border-color: #{{$c2}} !important;
        background-color: #{{$c2}} !important;
    }
    .layui-form-checkbox[lay-skin=primary]:hover i{border-color:#{{$c2}} !important;}
    .layui-tab-brief>.layui-tab-more li.layui-this:after,
    .layui-tab-brief>.layui-tab-title .layui-this:after{
        border-color:#{{$c2}} !important;
    }
    div[xm-select-skin=default] dl dd:not(.xm-dis-disabled) i{
        border-color:#{{$c2}} !important;
    }
    div[xm-select-skin=default] dl dd.xm-select-this:not(.xm-dis-disabled) i{
        color:#{{$c2}} !important;
    }

    .layui-transfer .layui-transfer-active button{
        background-color: #{{$c2}} !important;
    }

    .layui-transfer .layui-transfer-active button.layui-btn-disabled{
        background-color: #FFF !important;
    }
</style>
<link rel="stylesheet" href="{{url($staticadmin.'vim/css/index.css')}}" />
@if($borwser_name !== 'Chrome') @include("sys.public.layout.public.style.ie") @endif
