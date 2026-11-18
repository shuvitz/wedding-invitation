{{--
    Zero-JS baseline: Nuxt shows the cover as a full-screen overlay; here
    it's a plain section whose button jumps to the content below. The
    guest's name comes from `?to=` (e.g. ?to=Budi+Santoso) and is escaped
    by {{ }} like any other text.
--}}
@php($guest = is_string(request()->query('to')) ? \Illuminate\Support\Str::limit(trim(request()->query('to')), 100, '') : '')
@php($image = \App\Models\Media::resolveImage($data['image_media_id'] ?? null))
<section class="space-y-4 py-16 text-center">
    @if ($image)
        <img
            src="{{ $image['url'] }}"
            alt=""
            @if ($image['width']) width="{{ $image['width'] }}" @endif
            @if ($image['height']) height="{{ $image['height'] }}" @endif
            class="mx-auto max-h-96 w-full rounded-lg object-cover"
        >
    @endif
    @if (! empty($data['title']))
        <p class="text-sm uppercase tracking-widest text-muted-foreground">{{ $data['title'] }}</p>
    @endif
    <h1 class="text-4xl font-bold tracking-tight text-foreground">{{ $data['names'] ?? '' }}</h1>
    @if ($guest !== '')
        <div class="space-y-1 pt-4">
            @if (! empty($data['greeting']))
                <p class="text-sm text-muted-foreground">{{ $data['greeting'] }}</p>
            @endif
            <p class="text-xl font-semibold text-foreground">{{ $guest }}</p>
        </div>
    @endif
    <a
        href="#undangan"
        class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition"
    >
        {{ ($data['button_label'] ?? '') ?: 'Buka Undangan' }}
    </a>
</section>
<span id="undangan"></span>
