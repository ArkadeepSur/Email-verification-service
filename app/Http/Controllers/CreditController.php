<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CreditTransaction;

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
