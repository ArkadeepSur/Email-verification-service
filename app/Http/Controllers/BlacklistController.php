<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blacklist;

class BlacklistController extends Controller
{
    public function index()
    {
        return response()->json(Blacklist::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate(['pattern' => 'required', 'description' => 'nullable', 'is_active' => 'boolean']);
        $blacklist = Blacklist::create($data);
        return response()->json($blacklist, 201);
    }

    public function show(Blacklist $blacklist)
    {
        return response()->json($blacklist);
    }

    public function update(Request $request, Blacklist $blacklist)
    {
        $blacklist->update($request->only(['pattern', 'description', 'is_active']));
        return response()->json($blacklist);
    }

    public function destroy(Blacklist $blacklist)
    {
        $blacklist->delete();
        return response()->json(null, 204);
    }
}
