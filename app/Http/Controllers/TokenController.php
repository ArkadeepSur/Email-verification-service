<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TokenController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tokens = $user->tokens()->get();

        return view('dashboard.tokens', compact('tokens'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $user = $request->user();
        $token = $user->createToken($request->input('name'));

        return redirect()->back()->with('token_plain', $token->plainTextToken);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $token = $user->tokens()->where('id', $id)->first();
        if ($token) {
            $token->delete();
        }

        return redirect()->back();
    }
}

