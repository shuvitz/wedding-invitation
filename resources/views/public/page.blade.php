@extends('public.layout')

@section('content')
    <div class="space-y-12">
        @foreach ($page->content['blocks'] ?? [] as $block)
            @php($view = \App\Services\BlockRegistry::view($block['type'] ?? null))
            {{-- Unknown/unwhitelisted block types are silently skipped, not
                 rendered or executed — see BlockRegistry. --}}
            @if ($view)
                @include($view, ['data' => $block['data'] ?? []])
            @endif
        @endforeach
    </div>
@endsection
