@extends('layouts.app')

@section('content')
    <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Email Verification</h1>

        <p class="text-gray-600 mb-6">
            Thanks for signing up! Before getting started, please verify your email
            address by clicking the link we just emailed to you.
        </p>

        @if (session('message'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded">
                <p class="text-green-700">{{ session('message') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded">
                <p class="text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->has('email'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded">
                <p class="text-red-700">{{ $errors->first('email') }}</p>
            </div>
        @endif

        <div class="space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Resend Verification Email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">
                    Logout
                </button>
            </form>
        </div>
    </div>
@endsection
