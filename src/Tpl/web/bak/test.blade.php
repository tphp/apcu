@extends('apcu')
@section('content')
{!! tpl('item/page_v', ['data' => 'item/page', 'ispagE' => true]) !!}
{!! tpl('item/my', ['where' => ['id', '=', ['2010', '11509']]]) !!}
{{--!! tpl('item.my', ['data' => 'item.list']) !!}
{!! tpl('item.my', ['data' => 'item.info']) !!}
{!! tpl('item.my', ['data' => 'item.tier', 'where' => ['level', '=', ['3', '2']]]) !!--}}
@endsection