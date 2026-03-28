<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-100 flex">

    <aside class="w-64 min-h-screen bg-sidebar flex flex-col flex-shrink-0">
        <div class="h-16 flex items-center px-6 border-b border-primary-900">
            <span class="text-white font-bold text-lg">{{ config('app.name') }}</span>
        </div>

        <nav class="flex-1 py-4 px-3 flex flex-col gap-1">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                Painel
            </a>

            @if (auth()->user()->isSuperAdmin() || auth()->user()->hasRole(\App\Enums\RoleSlug::AcademyDirector) || auth()->user()->hasRole(\App\Enums\RoleSlug::Coach))
                <div class="mt-4 mb-1 px-3 text-xs font-semibold text-primary-400 uppercase tracking-wider">Gestão</div>

                <a href="{{ route('players.index') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('players.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Atletas
                </a>
            @endif

            @if (auth()->user()->hasRole(\App\Enums\RoleSlug::AcademyDirector))
                <div class="mt-4 mb-1 px-3 text-xs font-semibold text-primary-400 uppercase tracking-wider">Academia</div>

                <a href="{{ route('academy.profile') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('academy.profile') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    Perfil da Academia
                </a>

                <a href="{{ route('academy.categories') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('academy.categories') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z" /></svg>
                    Categorias
                </a>

                <a href="{{ route('coaches.assignments') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('coaches.assignments') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    Atribuições
                </a>

                <a href="{{ route('invitations.index') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('invitations.index') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    Convites
                </a>
                
                <a href="{{ route('players.index') }}" wire:navigate
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('players.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Atletas
                </a>
            @endif
        </nav>

        @auth
        <div class="p-4 border-t border-primary-900">
            <p class="text-primary-200 text-sm truncate mb-2">{{ auth()->user()->name }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-primary-300 text-xs hover:text-white transition-colors">
                    Sair
                </button>
            </form>
        </div>
        @endauth
    </aside>

    <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6 flex-shrink-0">
            <h1 class="text-gray-800 font-semibold text-lg">{{ $header ?? '' }}</h1>
        </header>

        <main class="flex-1 overflow-auto p-6">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
