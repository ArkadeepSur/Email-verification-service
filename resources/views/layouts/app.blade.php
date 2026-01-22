<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ config('app.name', 'CatchAll Verifier') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800">
    <div class="bg-white shadow-sm">
        <div class="container mx-auto p-4 flex justify-between items-center">
            <div><a href="/" class="font-bold">{{ config('app.name', 'CatchAll Verifier') }}</a></div>
            <div>
                @auth
                    <span class="mr-4">Hello, {{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button class="bg-red-500 text-white px-3 py-1">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-blue-600">Login</a>
                @endauth
            </div>
        </div>
    </div>
    <div class="container mx-auto p-6">
        {{-- Flash messages --}}
        <div class="mb-6 space-y-2">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded">
                    <strong>There were some problems with your submission:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @yield('content')
    </div>
</body>
</html>

