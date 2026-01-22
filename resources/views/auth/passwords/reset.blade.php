@extends('layouts.app')

@section('content')
    <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Reset Password</h1>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-4">
                <label class="block mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="border p-2 w-full {{ $errors->has('email') ? 'border-red-500' : '' }}" required>
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block mb-1">New Password</label>
                <input type="password" name="password" class="border p-2 w-full {{ $errors->has('password') ? 'border-red-500' : '' }}" required>
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" class="border p-2 w-full {{ $errors->has('password_confirmation') ? 'border-red-500' : '' }}" required>
                @error('password_confirmation')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <button class="bg-green-600 text-white px-4 py-2">Reset Password</button>
            </div>
        </form>
    </div>
@endsection

