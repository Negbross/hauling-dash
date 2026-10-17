<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as FilamentLoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest;
use Symfony\Component\HttpFoundation\Response;

class Login extends BaseLogin
{
    public function authenticate(): ?FilamentLoginResponse
    {
        $this->callHook('beforeValidate');

        $data = $this->form->getState();

        $this->callHook('afterValidate');

        $loginRequest = LoginRequest::create(
            route('login.store'),
            'POST',
            [
                Fortify::username() => $data['email'] ?? ($data[Fortify::username()] ?? ''),
                'password' => $data['password'],
                'remember' => $data['remember'] ?? false,
            ]
        );
        $loginRequest->setLaravelSession(request()->hasSession() ? request()->session() : app('session.store'));

        if (config('fortify.lowercase_usernames') && $loginRequest->has(Fortify::username())) {
            $loginRequest->merge([
                Fortify::username() => Str::lower($loginRequest->{Fortify::username()}),
            ]);
        }

        try {
            $pipeline = (new Pipeline(app()))
                ->send($loginRequest)
                ->through(array_filter([
                    config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                    config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
                    Features::enabled(Features::twoFactorAuthentication()) ? RedirectsIfTwoFactorAuthenticatable::class : null,
                    AttemptToAuthenticate::class,
                    PrepareAuthenticatedSession::class,
                ]));

            $response = $pipeline->then(function () {
                return true;
            });

            if ($response instanceof Response) {
                return new class($response) implements FilamentLoginResponse {
                    public function __construct(private Response $response) {}

                    public function toResponse($request): Response
                    {
                        return $this->response;
                    }
                };
            }
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->errors() as $key => $errors) {
                $messages["data.{$key}"] = $errors;
                $messages[$key] = $errors;
            }

            throw ValidationException::withMessages($messages);
        }

        return app(FilamentLoginResponse::class);
    }
}
