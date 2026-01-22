@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold mb-4">API Tokens</h1>

    @if(session('token_plain'))
        <div class="p-4 bg-green-100 mb-4">New token: <code>{{ session('token_plain') }}</code> — copy it now, it will not be shown again.</div>
    @endif

    <form method="POST" action="{{ route('tokens.store') }}" class="mb-6">
        @csrf
        <input type="text" name="name" placeholder="Token name" class="border p-2" required>
        <button class="bg-blue-600 text-white px-3 py-2">Create Token</button>
    </form>

    <table class="w-full bg-white shadow rounded">
        <thead><tr class="text-left"><th class="p-3">Name</th><th class="p-3">Last Used</th><th class="p-3">Actions</th></tr></thead>
        <tbody>
            @foreach($tokens as $token)
                <tr class="border-t"><td class="p-3">{{ $token->name }}</td><td class="p-3">{{ $token->last_used_at }}</td><td class="p-3">
                    <form method="POST" action="{{ route('tokens.destroy', $token->id) }}" onsubmit="return confirm('Revoke token?');">
                        @csrf
                        @method('DELETE')
                        <button class="bg-red-500 text-white px-3 py-1">Revoke</button>
                    </form>
                </td></tr>
            @endforeach
        </tbody>
    </table>
@endsection

