<?php

use App\Livewire\Forms\ResetPasswordForm;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Redefinir senha')] class extends Component
{
    public ResetPasswordForm $form;

    public function mount(string $token): void
    {
        $this->form->token = $token;
        $this->form->email = request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->form->validate();

        $status = Password::reset(
            [
                'token'                 => $this->form->token,
                'email'                 => $this->form->email,
                'password'              => $this->form->password,
                'password_confirmation' => $this->form->password_confirmation,
            ],
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', 'Senha redefinida com sucesso. Você já pode entrar com a nova senha.');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $message = $status === Password::INVALID_TOKEN
            ? 'O link de redefinição expirou ou é inválido.'
            : 'Não foi possível redefinir a senha. Verifique os dados e tente novamente.';

        $this->addError('form.email', $message);
    }
};
?>

<div>
    <h2 class="text-xl font-semibold text-gray-800 mb-6 text-center">Redefinir senha</h2>

    <form wire:submit="resetPassword" class="flex flex-col gap-4">
        <x-input
            name="form.email"
            label="E-mail"
            type="email"
            wire:model="form.email"
            required
        />

        <x-input
            name="form.password"
            label="Nova senha"
            type="password"
            wire:model="form.password"
            required
        />

        <x-input
            name="form.password_confirmation"
            label="Confirmar nova senha"
            type="password"
            wire:model="form.password_confirmation"
            required
        />

        <x-button type="submit" class="w-full mt-2">
            Redefinir senha
        </x-button>
    </form>
</div>
