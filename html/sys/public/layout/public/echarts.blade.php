@define $color = $_DC_['color']
@if(!empty($color) && is_array($color) && count($color) >= 2)
    @define $c1 = $color[0]
    @define $c2 = $color[1]
@else
    @define $c1 = '4176e7'
    @define $c2 = '2e99d4'
@endif
<style>
    .layui-table-cell .js_tree_fa,
    .layui-tab-brief>.layui-tab-title .layui-this,
    .layui-form-radio>i:hover,
    .layui-form-radioed>i,
    .layui-elem-field legend{
        color:#{{$c1}} !important;
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
    .layui-tab-brief>.layui-tab-more li.layui-this:after,
    .layui-tab-brief>.layui-tab-title .layui-this:after{
        border-color:#{{$c2}} !important;
    }

</style>