@extends('layouts.shop')

@section('content')
    <section class="checkout-shell account-auth-shell">
        <div class="checkout-main">
            <span class="eyebrow">YOUR ACCOUNT</span>
            <h2>Create account<span class="heading-period">.</span></h2>
            <p class="checkout-intro">Register to order and keep all your delivery updates in one place.</p>

            @if ($errors->any())
                <div class="form-errors" role="alert">
                    <strong>Please check your details.</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form class="checkout-form" method="POST" action="{{ route('customer.register.store') }}">
                @csrf
                <label for="name">Full name
                    <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="120" required autofocus>
                </label>
                <label for="email">Email address
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                </label>
                <label for="password">Password
                    <input id="password" type="password" name="password" autocomplete="new-password" required>
                </label>
                <label for="password_confirmation">Confirm password
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                </label>
                <button class="button button-green checkout-submit" type="submit">Create account <span aria-hidden="true">→</span></button>
            </form>

            <p class="account-auth-switch">Already registered? <a href="{{ route('customer.login') }}">Sign in</a></p>
        </div>
    </section>
@endsection
