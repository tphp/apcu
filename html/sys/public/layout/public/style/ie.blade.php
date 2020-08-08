<style>
    .layui-table-view .layui-table-body{
        margin-right: -3px;
    }
    .layui-table-view .layui-table-cell{
        display: flex;
    }
    .layui-table-view .laytable-cell-numbers, .layui-table-view .laytable-cell-checkbox{
        display: table;
    }
</style>
@if($borwser_name === 'IE') <script src="{{url($static_tphp.'js/babel.min.js')}}"></script> @endif