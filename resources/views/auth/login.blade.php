@extends('layouts.shop')

@section('content')
    <section class="checkout-shell account-auth-shell">
        <div class="checkout-main">
            <span class="eyebrow">WELCOME BACK</span>
            <h2>Sign in<span class="heading-period">.</span></h2>
            <p class="checkout-intro">Sign in to place orders and follow every delivery.</p>

            @if ($errors->any())
                <div class="form-errors" role="alert">
                    <strong>Please check your details.</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form class="checkout-form" method="POST" action="{{ route('customer.login.store') }}">
                @csrf
                <label for="email">Email address
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </label>
                <label for="password">Password
                    <input id="password" type="password" name="password" autocomplete="current-password" required>
                </label>
                <label class="account-remember" for="remember">
                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    Keep me signed in
                </label>
                <button class="button button-green checkout-submit" type="submit">Sign in <span aria-hidden="true">→</span></button>
            </form>

            <p class="account-auth-switch">New here? <a href="{{ route('customer.register') }}">Create an account</a></p>
            <p class="account-auth-switch">Store administrator? <a href="{{ route('filament.bikrify.auth.login') }}">Admin sign in</a></p>
        </div>
    </section>
@endsection
