{{--
    `body` is treated as plain text, never HTML. It's escaped with e()
    first and nl2br() applied after — the only unescaped markup in the
    output is the literal <br> tags this template adds itself, so nothing
    from the stored content can ever be interpreted as a tag, script, or
    event handler. See the Phase 4 report for why this was chosen over an
    HTML-sanitization library.
--}}
<section class="prose max-w-none space-y-3">
    @if (! empty($data['title']))
        <h2 class="text-2xl font-semibold text-foreground">{{ $data['title'] }}</h2>
    @endif
    <div class="text-foreground leading-relaxed">
        {!! nl2br(e($data['body'] ?? '')) !!}
    </div>
</section>
