@extends('master')

@section('content')
<transition name="fade" mode="out-in" appear>
    <router-view></router-view>
</transition>
@endsection