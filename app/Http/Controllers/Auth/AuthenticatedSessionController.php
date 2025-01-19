<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\PageConfigurationService;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected PageConfigurationService $pageConfigurationService)
    {
    }

    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile ?? '-',
            'permissions' => $user->permissionNames(),
            'redirect' => $this->pageConfigurationService->getRedirectURL(),
        ];

        Log::channel("auth")->info("{$user->id} : {$user->name} - logged in via password");

        return response()->json($data);
    }

    public function destroy(Request $request): Response
    {
        $user = Auth::user();
        Log::channel("auth")->info("{$user?->id} : {$user?->name} - logged out");

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
