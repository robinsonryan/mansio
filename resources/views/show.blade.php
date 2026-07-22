<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
</head>
<body>
    {{-- Minimal default landing. Consuming apps publish/override this view
         (e.g. afwd renders an Inertia recipient viewer). --}}
    <main>
        <h1>{{ $title }}</h1>

        @if ($currentVersion)
            <p>Version {{ $currentVersion->sequence }} &middot;
                published {{ $currentVersion->published_at }}</p>
            <p><a href="{{ $downloadUrl }}" rel="nofollow">Download</a></p>
        @else
            <p>No published version yet.</p>
        @endif

        @if (count($changelog))
            <section>
                <h2>Change history</h2>
                <ul>
                    @foreach ($changelog as $entry)
                        <li>
                            <strong>v{{ $entry['sequence'] }}</strong>
                            @if (! empty($entry['summary'])) — {{ $entry['summary'] }} @endif
                            <em>({{ $entry['published_at'] }})</em>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </main>
</body>
</html>
