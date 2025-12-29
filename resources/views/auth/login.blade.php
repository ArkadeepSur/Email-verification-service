@extends('layouts.app')

@section('content')
    <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Login</h1>

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="mb-4">
                <label class="block mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="border p-2 w-full {{ $errors->has('email') ? 'border-red-500' : '' }}" required>
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block mb-1">Password</label>
                <input type="password" name="password" class="border p-2 w-full {{ $errors->has('password') ? 'border-red-500' : '' }}" required>
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="inline-flex items-center"><input type="checkbox" name="remember" class="mr-2"> Remember me</label>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <button class="bg-blue-600 text-white px-4 py-2">Login</button>
                </div>
                <div>
                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600">Forgot password?</a>
                    <span class="mx-2">|</span>
                    <a href="{{ route('register') }}" class="text-sm text-blue-600">Register</a>
                </div>
            </div>
        </form>
    </div>
@endsection
