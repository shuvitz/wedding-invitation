{{--
    `instagram` is validated (BlockRegistry) to a bare username of
    [A-Za-z0-9._], so it's safe to build the profile link from it here.
--}}
<section class="space-y-6 py-4">
    @if (! empty($data['title']))
        <h2 class="text-2xl font-semibold text-foreground text-center">{{ $data['title'] }}</h2>
    @endif

    @if (! empty($data['items']))
        <div class="grid grid-cols-1 items-center gap-6 sm:grid-cols-[1fr_auto_1fr]">
            @foreach ($data['items'] as $item)
                @if (! $loop->first)
                    <p class="text-center text-4xl text-muted-foreground" aria-hidden="true">&amp;</p>
                @endif
                @php($image = \App\Models\Media::resolveImage($item['image_media_id'] ?? null))
                <div class="space-y-3 text-center">
                    @if ($image)
                        <img
                            src="{{ $image['url'] }}"
                            alt="{{ $item['name'] ?? '' }}"
                            @if ($image['width']) width="{{ $image['width'] }}" @endif
                            @if ($image['height']) height="{{ $image['height'] }}" @endif
                            loading="lazy"
                            class="mx-auto h-40 w-40 rounded-full object-cover"
                        >
                    @endif
                    <h3 class="text-2xl font-semibold text-foreground">{{ $item['name'] ?? '' }}</h3>
                    @if (! empty($item['parents']))
                        <p class="text-sm text-muted-foreground">{{ $item['parents'] }}</p>
                    @endif
                    @if (! empty($item['instagram']))
                        <a
                            href="https://instagram.com/{{ $item['instagram'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-block text-sm font-medium text-primary hover:underline"
                        >{{ '@'.$item['instagram'] }}</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
