<section class="space-y-6 py-4">
    @if (! empty($data['title']))
        <h2 class="text-2xl font-semibold text-foreground text-center">{{ $data['title'] }}</h2>
    @endif

    @if (! empty($data['items']))
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($data['items'] as $item)
                @php($image = \App\Models\Media::resolveImage($item['media_id'] ?? null))
                @if ($image)
                    <img
                        src="{{ $image['url'] }}"
                        alt="{{ $item['alt_text'] ?? '' }}"
                        @if ($image['width']) width="{{ $image['width'] }}" @endif
                        @if ($image['height']) height="{{ $image['height'] }}" @endif
                        loading="lazy"
                        class="aspect-square w-full rounded-lg object-cover"
                    >
                @endif
            @endforeach
        </div>
    @endif
</section>
