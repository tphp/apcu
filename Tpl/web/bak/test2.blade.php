@extends('apcu')
@section('content')
{!! tpl('item.list') !!}
{!! tpl('item.list', ['data' => 'item.my', 'class' => 'item.my']) !!}
{!! tpl('item.my', ['data' => 'item.find']) !!}
{!! tpl('item.my', ['data' => 'item.info']) !!}
{!! tpl('item.list', ['class' => 'item.find']) !!}
{!! tpl('item.list', ['class' => 'r.c']) !!}
{!! tpl('item.list', ['class' => 'item.my']) !!}
{!! tpl('item.list', ['class' => 'item.list.demo']) !!}
@endsection