<!doctype html>
<html lang="hu">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('datatable::header')
</head>

@if(\Illuminate\Support\Facades\Session::has('message'))
    <p class="alert {{ \Illuminate\Support\Facades\Session::get('alert-class', 'alert-info') }}">
        {{ \Illuminate\Support\Facades\Session::get('message') }}
    </p>
@endif

    <h2 style="padding-left: 30px">ssss</h2>
    <div style="padding: 0 30px">
        {!! $datatable !!}
    </div>
    <hr>
@if(!empty($csvfiles))
    <div>
        <h4>Export csv fájlok</h4>
        <ul>
            @foreach($csvfiles as $csv)
                <li><a href="/some_file_downloader?file={{ $csv }}">{{ $csv }}</a> </li>
            @endforeach
        </ul>
    </div>
@endif
@include('datatable::footer')
