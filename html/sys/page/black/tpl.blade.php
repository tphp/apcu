@php
    $thispage = $paginator->currentPage();
    $lastpage = $paginator->lastPage();
    $oneachside <= 0 && $oneachside = 3;
    $start = $thispage - $oneachside;
    if($start < 1){
        $end = $thispage + $oneachside - $start + 1;
        $end > $lastpage && $end = $lastpage;
        $start = 1;
    }else{
        $end = $thispage + $oneachside;
        if($end > $lastpage){
            $start = $start - ($end - $lastpage);
            $start < 1 && $start = 1;
            $end = $lastpage;
        }
    }
    $pagelist = $paginator->getUrlRange($start, $end);
@endphp
@if ($paginator->hasPages())
    <ul class="pagination">
        {{-- First Page Link --}}
        <li><a href="{{ $paginator->url(1) }}">首页</a></li>

        {{-- PageList --}}
        @foreach ($pagelist as $page => $url)
            @if ($page == $paginator->currentPage())
                <li class="active"><span>{{ $page }}</span></li>
            @else
                <li><a href="{{ $url }}">{{ $page }}</a></li>
            @endif
        @endforeach

        {{-- Last Page Link --}}
        <li><a href="{{ $paginator->url($paginator->lastPage()) }}">末页</a></li>
    </ul>
@endif
