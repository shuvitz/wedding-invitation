@php($image = \App\Models\Media::resolveImage($data['image_media_id'] ?? null))

<section class="text-center space-y-4">
    @if ($image)
        <img
            src="{{ $image['url'] }}"
            alt="{{ $data['title'] ?? '' }}"
            @if ($image['width']) width="{{ $image['width'] }}" @endif
            @if ($image['height']) height="{{ $image['height'] }}" @endif
            class="mx-auto max-h-96 w-full rounded-lg object-cover"
        >
    @endif
    <h1 class="text-4xl font-bold tracking-tight text-foreground">{{ $data['title'] ?? '' }}</h1>
    @if (! empty($data['subtitle']))
        <p class="text-lg text-muted-foreground">{{ $data['subtitle'] }}</p>
    @endif
</section>
