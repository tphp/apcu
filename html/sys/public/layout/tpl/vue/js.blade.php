@php
    unset($gets['__dir__']);
    unset($gets['__show__']);
    $ob = $_GET['__ob__'];
    $ob_prop = null;
    $ob_order = null;
    if(!empty($ob)){
        $ob_arr = explode(",", $ob);
        if(count($ob_arr) === 2){
            $ob0 = strtolower(trim($ob_arr[0]));
            $ob1 = strtolower(trim($ob_arr[1]));
            if(!empty($ob0)){
                $ob_prop = $ob0;
                if($ob1 !== 'desc'){
                    $ob1 = 'asc';
                }
                $ob1 .= 'ending';
                $ob_order = $ob1;
            }
        }
    }
    $ob_json = [
        'prop' => $ob_prop,
        'order' => $ob_order,
    ];
    $form_info['edit'] > 0 ? $edit_name = '编辑' : $edit_name = '查看';
@endphp
@if(!empty($search[0]))
<script>
    var vue_list;
    var search = {
        data: {!! json_encode($search[1], true) !!},
        json_str: '{!! json__encode($search[2]) !!}',
        lists: {!! json_encode($search[3], true) !!},
        show: {!! $search_show !!}
    };
</script>
<script language="javascript" src="{{$static}}sys/js/search.js"></script>
@endif
@if(!empty($list))
<script language="javascript" src="{{$static}}sys/js/list.js"></script>
<script>
    vue_list = config_vue_list('{{$app_list_name}}', {
        field: {!! json_encode($list, true) !!},
        url: '{!! $url !!}',
        url_more: '{!! $url_more !!}',
        rules: {!! json_encode($list_rules, true) !!},
        order_by_default: {!! json_encode($ob_json, true) !!},
        edit_name: '{{$edit_name}}'
    });
</script>
@endif