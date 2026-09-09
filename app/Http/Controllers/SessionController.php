<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function keepAlive(Request $request)
    {
        return response()->json([
            'message' => 'Sesi diperpanjang.',
            'expires_at' => time() + ((int) config('session.lifetime')) * 60,
        ]);
    }
}
