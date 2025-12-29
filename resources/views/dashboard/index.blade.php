@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Dashboard</h1>
    <p>Welcome, {{ auth()->user()->name }}.</p>

    <ul class="mt-4">
        <li><a href="{{ route('tokens.index') }}" class="text-blue-600">Manage API Tokens</a></li>
    </ul>
@endsection
