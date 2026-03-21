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

        <nav class="flex-1 py-4 px-3">
            {{-- Navigation items will be added in Phase 2+ --}}
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
