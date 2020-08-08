<input class="js_input" type="hidden" name="{{$_GET['key']}}" value="{{$_GET['value']}}" />
@foreach($_ as $key=>$val)
    <div class="group-main">
        <div class="group-title">{{$key}}</div>
        <div class="group-body">@foreach($val as $k=>$v)<i class="fa fa-{{$v}}" title="{{$v}}"></i>@endforeach</div>
    </div>
@endforeach
