<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate(['url' => 'required|url', 'event' => 'required', 'secret' => 'nullable']);
        $webhook = Webhook::create(array_merge($data, ['is_active' => true]));

        return response()->json($webhook, 201);
    }
}
