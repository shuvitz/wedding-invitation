<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $seoTitle }}</title>
        @if ($seoDescription)
            <meta name="description" content="{{ $seoDescription }}">
        @endif
        @if ($faviconUrl)
            <link rel="icon" href="{{ $faviconUrl }}">
        @endif
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-background text-foreground antialiased">
        <header class="border-b border-border">
            <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-6 py-4">
                <a href="/" class="flex items-center gap-2 font-bold text-lg">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto">
                    @else
                        <span>{{ $siteName }}</span>
                    @endif
                </a>
                @if ($navigationItems->isNotEmpty())
                    <nav class="flex flex-wrap gap-5 text-sm font-medium">
                        @foreach ($navigationItems as $item)
                            <a href="{{ $item->url }}" class="text-muted-foreground hover:text-foreground transition">{{ $item->label }}</a>
                        @endforeach
                    </nav>
                @endif
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-6 py-10">
            @yield('content')
        </main>

        <footer class="border-t border-border">
            <div class="mx-auto max-w-4xl px-6 py-8 text-sm text-muted-foreground">
                <p>&copy; {{ now()->year }} {{ $siteName }}</p>
                @if (! empty($contact['email']) || ! empty($contact['phone']))
                    <p class="mt-1">
                        @if (! empty($contact['email'])) {{ $contact['email'] }} @endif
                        @if (! empty($contact['phone'])) &middot; {{ $contact['phone'] }} @endif
                    </p>
                @endif
                @if (array_filter($social ?? []))
                    <p class="mt-2 flex gap-4">
                        @foreach (['instagram', 'facebook', 'tiktok', 'linkedin'] as $platform)
                            @if (! empty($social[$platform]))
                                <span>{{ ucfirst($platform) }}: {{ $social[$platform] }}</span>
                            @endif
                        @endforeach
                    </p>
                @endif
            </div>
        </footer>
    </body>
</html>
