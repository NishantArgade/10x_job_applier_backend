<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'message' => 'User not authenticated',
            ], 401);
        }

        return ['user' => $user];
    }
}
