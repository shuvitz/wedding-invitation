{{--
    Zero-JS baseline: no copy button here (Nuxt has one), so the account
    number is `select-all` — one tap selects the whole number.
--}}
<section class="space-y-6 py-4">
    @if (! empty($data['title']))
        <h2 class="text-2xl font-semibold text-foreground text-center">{{ $data['title'] }}</h2>
    @endif
    @if (! empty($data['description']))
        <p class="mx-auto max-w-prose text-center text-muted-foreground whitespace-pre-line">{{ $data['description'] }}</p>
    @endif

    @if (! empty($data['items']))
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($data['items'] as $item)
                <div class="space-y-2 rounded-lg border border-border bg-card p-6 text-center">
                    <h3 class="text-lg font-semibold text-foreground">{{ $item['bank'] ?? '' }}</h3>
                    <p class="select-all font-mono text-xl text-foreground">{{ $item['account_number'] ?? '' }}</p>
                    <p class="text-sm text-muted-foreground">a.n. {{ $item['account_name'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    @endif
</section>
