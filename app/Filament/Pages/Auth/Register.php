<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class Register extends BaseRegister
{
    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->dehydrateStateUsing(fn ($state) => $state);
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->dehydrated(true);
    }

    protected function handleRegistration(array $data): Model
    {
        try {
            $input = [
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
                'password' => $data['password'] ?? '',
                'password_confirmation' => $data['passwordConfirmation']
                    ?? ($data['password_confirmation']
                    ?? ($this->data['passwordConfirmation'] ?? '')),
            ];

            return app(CreatesNewUsers::class)->create($input);
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->errors() as $key => $errors) {
                $messages["data.{$key}"] = $errors;
                $messages[$key] = $errors;
            }

            throw ValidationException::withMessages($messages);
        }
    }
}
