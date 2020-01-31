<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $user->name }}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="author" content="Xgerhard">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="{{ asset('/css/bootstrap.min.css') }}" rel="stylesheet"/>
</head>
<body>
    <div class="container" style="/*width:100%;max-width:100%;*/">
        <h1>{{ $user->name }}</h1>
        @forelse($user->TwitchStreams as $twitchStream)

            <h5>[{{ $twitchStream->created_at->format('d-m-Y') }}] {{ $twitchStream->title }} @if($twitchStream->duration == 0)[🔴LIVE]@endif</h5>
            <p>@if($twitchStream->duration == 0)Stream uptime: @else Streamed for: @endif {{ $twitchStream->durationTime }}</p>

            <p>
                Chapters:
                <div class="card-group chapter-list">
                @forelse($twitchStream->TwitchStreamChapters as $streamChapter)
                <div class="card chapter-item">
                    <img src="{{ str_replace(['{width}', '{height}'], [150, 150], $streamChapter->TwitchGame->box_art_url) }}" class="card-img-top" alt="{{ $streamChapter->TwitchGame->name }}">
                    <div class="card-body">
                        <h5 class="card-title">@if($streamChapter->vodUrl)<a href="{{ $streamChapter->vodUrl }}" target="blank">{{ $streamChapter->TwitchGame->name }}</a> @else {{ $streamChapter->TwitchGame->name }} @endif</h5>
                        <!--<p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>-->
                        <p class="card-text"><small class="text-muted">{{ $streamChapter->durationTime }}</small></p>
                    </div>
                </div>
                @if($loop->iteration % 6 == 0) </div><div class="card-group chapter-list">@endif
                @empty
                    No chapters found (yet)
                @endforelse
                </div>
            </p>

            <p>
                Games:
                <div class="card-group chapter-list">
                @forelse($twitchStream->games as $streamGame)
                <div class="card chapter-item">
                    <img src="{{ str_replace(['{width}', '{height}'], [150, 150], $streamGame->img) }}" class="card-img-top" alt="{{ $streamGame->name }}">
                    <div class="card-body">
                        <h5 class="card-title">{{ $streamGame->name }}</h5>
                        <p class="card-text"><small class="text-muted">{{ $streamGame->durationTime }}</small></p>
                    </div>
                </div>
                @if($loop->iteration % 6 == 0) </div><div class="card-group chapter-list">@endif
                @empty
                    No chapters found (yet)
                @endforelse
                </div>
            </p>
        @empty
            No stream data found (yet), start streaming and come back later!
        @endforelse
    </div>

    <style>
        .chapter-item {
        max-width: 185px;
        }
    </style>
</body>
</html>