@php($image = \App\Models\Media::resolveImage($data['media_id'] ?? null))

@if ($image)
    <section>
        <img
            src="{{ $image['url'] }}"
            alt="{{ $data['alt_text'] ?? '' }}"
            @if ($image['width']) width="{{ $image['width'] }}" @endif
            @if ($image['height']) height="{{ $image['height'] }}" @endif
            loading="lazy"
            class="w-full rounded-lg object-cover"
        >
    </section>
@endif
