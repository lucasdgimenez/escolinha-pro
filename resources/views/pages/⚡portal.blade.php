<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Acesso via aplicativo')] class extends Component {};
?>

<div class="text-center">
    <h2 class="text-xl font-semibold text-gray-800 mb-3">Acesso exclusivo pelo aplicativo</h2>
    <p class="text-sm text-gray-600 mb-6">
        Este painel é exclusivo para academias. O acesso para pais e atletas é feito pelo aplicativo móvel.
    </p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <x-button type="submit" variant="secondary">Sair</x-button>
    </form>
</div>
