@extends('auth.layout')
@section('title', 'Log in')

@section('content')
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">
                You forgot your password? Here you can easily retrieve a new
                password.
            </p>
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            <form method="POST" action="{{ route('password.email') }}" class="form-loading">
                @csrf
                <div class="input-group mb-3">
                    <input name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="Email" autofocus/>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">{{ __('Send Password Reset Link') }}</button>
                    </div>
                </div>
            </form>
            <p class="mt-3 mb-1">
                <a href="{{ route('login') }}">Login</a>
            </p>
        </div>
    </div>
@endsection
