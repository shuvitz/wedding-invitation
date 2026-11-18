{{--
    `maps_url` is validated at write time (BlockRegistry) to only ever be an
    http(s) URL, so it's safe to place directly in href here. Dates render
    in APP_LOCALE (e.g. APP_LOCALE=id → "Sabtu, 14 Juni 2026").
--}}
<section class="space-y-6 py-4">
    @if (! empty($data['title']))
        <h2 class="text-2xl font-semibold text-foreground text-center">{{ $data['title'] }}</h2>
    @endif

    @if (! empty($data['items']))
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($data['items'] as $item)
                <div class="space-y-2 rounded-lg border border-border bg-card p-6 text-center">
                    <h3 class="text-xl font-semibold text-foreground">{{ $item['title'] ?? '' }}</h3>
                    @if (! empty($item['date']))
                        <p class="font-medium text-foreground">{{ \Illuminate\Support\Carbon::parse($item['date'])->translatedFormat('l, j F Y') }}</p>
                    @endif
                    @if (! empty($item['start_time']))
                        <p class="text-muted-foreground">{{ $item['start_time'] }}@if (! empty($item['end_time'])) – {{ $item['end_time'] }}@endif</p>
                    @endif
                    @if (! empty($item['venue']))
                        <p class="font-medium text-foreground">{{ $item['venue'] }}</p>
                    @endif
                    @if (! empty($item['address']))
                        <p class="text-sm text-muted-foreground">{{ $item['address'] }}</p>
                    @endif
                    @if (! empty($item['maps_url']))
                        <a
                            href="{{ $item['maps_url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition"
                        >
                            Open in Maps
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
