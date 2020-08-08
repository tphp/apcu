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
    $type = $_GET['type'];
    empty($type) && $type = 'todo';
@endphp
<script language="javascript" src="{{$static}}sys/js/flow.js"></script>
<script>
    vue_list = config_vue_list('app_flow', {
        type: '{!! $type !!}',
        url: '{!! $url !!}',
    });
</script>