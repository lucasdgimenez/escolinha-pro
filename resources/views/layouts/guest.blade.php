<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-primary-50 flex flex-col items-center justify-center p-4">

    <div class="mb-8 text-center">
        <span class="text-2xl font-bold text-primary-800">{{ config('app.name') }}</span>
    </div>

    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
