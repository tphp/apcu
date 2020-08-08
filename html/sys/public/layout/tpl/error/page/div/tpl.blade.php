<div class="error-page">
    <div class="error-page-container">
        <div class="error-page-main">
            <h3>
                <strong>{!! $code !!}</strong>{!! $msg !!}<a class="float_right" href='javascript:window.location.reload();' style='text-decoration: none'>刷新</a>
            </h3>
            @if(is_array($data))
                @if(!empty($data))
                    <div class="error-page-actions">
                        <div>
                            <ol>
                                @foreach($data as $key=>$val)
                                    <li>{!! $val !!}</li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                @endif
            @else
                <div class="error-page-actions">
                    <div class="gray">
                        {!! $data !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>