<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        // Registration always produces a plain reader. is_admin is guarded, so
        // it cannot be smuggled in through the request payload.
        $user = User::create($request->safe()->only('name', 'email', 'password'));

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('home')->with('success', 'Welcome aboard!');
    }
}
