@extends('layouts.site-editor')

@section('title', 'Moymoy Chats')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'assistantChats'])
@endsection

@section('content')
    @php
        $fmt = fn ($date) => $date
            ? \Illuminate\Support\Carbon::parse($date)->timezone('Asia/Manila')->format('M j, Y · g:i A')
            : '—';
    @endphp

    {{-- Keep this workspace within the viewport. The individual panes, not
         the browser page, own their scroll areas. --}}
    <div class="flex min-h-0 flex-col md:h-[calc(100vh-12rem)]">
    <div class="mb-5 shrink-0 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">Read-only transcripts from visitors using the public Moymoy AI assistant.</p>
        </div>
        <form method="GET" action="{{ route('admin.assistant-chats') }}" class="flex w-full gap-2 sm:w-auto">
            <label for="chat-search" class="sr-only">Search chats</label>
            <input id="chat-search" type="search" name="q" value="{{ $query }}" placeholder="Search chat or email"
                class="min-w-0 flex-1 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm text-navy-800 outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 sm:w-64">
            <button type="submit" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm">Search</button>
        </form>
    </div>

    @if($conversations->isEmpty())
        <div class="flex min-h-[360px] flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 text-center shadow-sm">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-500">
                <x-admin-icon name="sparkle" class="h-7 w-7" />
            </div>
            <h2 class="mt-4 text-base font-bold text-navy-800">No Moymoy chats yet</h2>
            <p class="mt-1 text-sm text-slate-500">Visitor conversations will appear here after they message Moymoy.</p>
        </div>
    @else
        <div data-chat-inbox class="flex min-h-[580px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:min-h-0 md:flex-1">
            <aside data-chat-list class="flex w-full shrink-0 flex-col overflow-hidden border-slate-200 md:w-[35%] md:min-w-[300px] md:max-w-[420px] md:border-r">
                <div class="min-h-0 flex-1 overflow-y-auto">
                @foreach($conversations as $conversation)
                    @php
                        $turns = $messages->get($conversation->conversation_id, collect());
                        $firstUser = $turns->firstWhere('role', 'user');
                        $visitor = $turns->first(fn ($turn) => filled($turn->user_email));
                        $label = $visitor?->user_email ?: 'Guest visitor';
                        $preview = \Illuminate\Support\Str::limit((string) ($firstUser?->content ?? 'Started a conversation'), 82);
                    @endphp
                    <button type="button" data-chat-item data-id="{{ $conversation->conversation_id }}" data-active="false"
                        class="flex w-full gap-3 border-b border-slate-100 border-l-4 border-l-transparent px-4 py-3 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500/40 data-[active=true]:border-l-brand-500 data-[active=true]:bg-brand-50">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600"><x-admin-icon name="sparkle" class="h-4 w-4" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-baseline justify-between gap-2"><span class="truncate text-sm font-semibold text-navy-800">{{ $label }}</span><span class="shrink-0 text-[11px] text-slate-400">{{ $fmt($conversation->latest_at) }}</span></span>
                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $preview }}</span>
                            <span class="mt-1 block text-[11px] font-medium text-slate-400">{{ $turns->count() }} messages</span>
                        </span>
                    </button>
                @endforeach
                </div>
                @if($conversations->hasPages())
                    <div class="shrink-0 border-t border-slate-200 bg-white px-3 py-2">{{ $conversations->links() }}</div>
                @endif
            </aside>

            <section data-chat-detail class="hidden min-w-0 flex-1 flex-col overflow-y-auto md:flex">
                <div data-chat-empty class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                    <x-admin-icon name="sparkle" class="h-9 w-9 text-slate-300" />
                    <p class="mt-3 font-semibold text-navy-800">Select a conversation</p>
                    <p class="mt-1 text-sm text-slate-500">Choose a chat to read its transcript.</p>
                </div>
                @foreach($conversations as $conversation)
                    @php($turns = $messages->get($conversation->conversation_id, collect()))
                    @php($visitor = $turns->first(fn ($turn) => filled($turn->user_email)))
                    <article data-chat-transcript data-id="{{ $conversation->conversation_id }}" class="hidden min-h-full flex-col">
                        <button type="button" data-chat-back class="border-b border-slate-100 px-4 py-2.5 text-left text-sm font-semibold text-navy-700 md:hidden">← Back to chats</button>
                        <header class="border-b border-slate-100 px-5 py-4 sm:px-7">
                            <h2 class="text-lg font-bold text-navy-800">{{ $visitor?->user_email ?: 'Guest visitor' }}</h2>
                            <p class="mt-0.5 text-xs text-slate-400">Conversation ID: {{ $conversation->conversation_id }}</p>
                        </header>
                        <div class="flex flex-1 flex-col gap-4 px-5 py-5 sm:px-7">
                            @foreach($turns as $turn)
                                @php($isUser = $turn->role === 'user')
                                <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-6 {{ $isUser ? 'rounded-br-md bg-brand-500 text-white' : 'rounded-bl-md bg-slate-100 text-navy-800' }}">
                                        <p class="mb-1 text-[11px] font-semibold {{ $isUser ? 'text-white/75' : 'text-slate-400' }}">{{ $isUser ? 'Visitor' : 'Moymoy' }} · {{ $fmt($turn->created_at) }}</p>
                                        <p class="whitespace-pre-wrap">{{ $turn->content }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    @endif
    </div>
@endsection

@section('scripts')
<script>
;(() => {
    const inbox = document.querySelector('[data-chat-inbox]')
    if (!inbox) return
    const list = inbox.querySelector('[data-chat-list]')
    const detail = inbox.querySelector('[data-chat-detail]')
    const items = Array.from(inbox.querySelectorAll('[data-chat-item]'))
    const transcripts = Array.from(inbox.querySelectorAll('[data-chat-transcript]'))
    const empty = inbox.querySelector('[data-chat-empty]')
    const desktop = () => window.matchMedia('(min-width: 768px)').matches
    function select(id) {
        transcripts.forEach((transcript) => {
            const active = transcript.dataset.id === id
            transcript.classList.toggle('hidden', !active)
            transcript.classList.toggle('flex', active)
        })
        items.forEach((item) => { item.dataset.active = String(item.dataset.id === id) })
        empty.classList.add('hidden')
        if (!desktop()) { list.classList.add('hidden'); detail.classList.remove('hidden'); detail.classList.add('flex') }
    }
    items.forEach((item) => item.addEventListener('click', () => select(item.dataset.id)))
    inbox.querySelectorAll('[data-chat-back]').forEach((button) => button.addEventListener('click', () => {
        detail.classList.add('hidden'); detail.classList.remove('flex'); list.classList.remove('hidden')
    }))
})()
</script>
@endsection
