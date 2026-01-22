<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use Illuminate\Support\Facades\Auth;

class CreditController extends Controller
{
    public function balance()
    {
        $user = Auth::user();

        return response()->json(['balance' => $user->credits_balance]);
    }

    public function history()
    {
        $user = Auth::user();

        return response()->json(CreditTransaction::where('user_id', $user->id)->latest()->get());
    }
}

