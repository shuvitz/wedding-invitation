<section class="space-y-6 py-4">
    @if (! empty($data['title']))
        <h2 class="text-2xl font-semibold text-foreground text-center">{{ $data['title'] }}</h2>
    @endif

    @if (! empty($data['items']))
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($data['items'] as $item)
                @php($image = \App\Models\Media::resolveImage($item['image_media_id'] ?? null))
                <div class="flex gap-4 rounded-lg border border-border bg-card p-4">
                    @if ($image)
                        <img
                            src="{{ $image['url'] }}"
                            alt="{{ $item['name'] ?? '' }}"
                            @if ($image['width']) width="{{ $image['width'] }}" @endif
                            @if ($image['height']) height="{{ $image['height'] }}" @endif
                            loading="lazy"
                            class="h-20 w-20 shrink-0 rounded-md object-cover"
                        >
                    @endif
                    <div class="flex-1 space-y-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <h3 class="font-semibold text-foreground">{{ $item['name'] ?? '' }}</h3>
                            <span class="whitespace-nowrap font-semibold text-foreground">{{ $item['price'] ?? '' }}</span>
                        </div>
                        @if (! empty($item['description']))
                            <p class="text-sm text-muted-foreground">{{ $item['description'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
