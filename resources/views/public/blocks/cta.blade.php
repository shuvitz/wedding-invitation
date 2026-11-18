{{--
    `url` is validated at write time (BlockRegistry) to only ever be a
    relative path or an http(s) URL — never javascript:/data:/etc — so
    it's safe to place directly in href here.
--}}
<section class="rounded-lg border border-border bg-card p-8 text-center space-y-3">
    <h2 class="text-2xl font-semibold text-foreground">{{ $data['title'] ?? '' }}</h2>
    @if (! empty($data['description']))
        <p class="text-muted-foreground">{{ $data['description'] }}</p>
    @endif
    @if (! empty($data['url']) && ! empty($data['button_label']))
        <a
            href="{{ $data['url'] }}"
            class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition"
        >
            {{ $data['button_label'] }}
        </a>
    @endif
</section>
