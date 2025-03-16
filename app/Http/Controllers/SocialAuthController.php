<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use App\Services\PageConfigurationService;

class SocialAuthController extends Controller
{
    public function __construct(protected PageConfigurationService $pageConfigurationService)
    {
    }

    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, $provider)
    {
        try {
            $httpClient = app()->isLocal()
                ? new \GuzzleHttp\Client(['verify' => false])
                : new \GuzzleHttp\Client();

            $socialiteUser = Socialite::driver($provider)
                ->stateless()
                ->setHttpClient($httpClient)
                ->user();

            $user = User::updateOrCreate([
                'email' => $socialiteUser->getEmail()
            ], [
                'name' => $socialiteUser->getName(),
                'password' => Hash::make('123')
            ]);

            if ($user->wasRecentlyCreated) {
                $user->assignRole(Role::CLIENT);
            }

            Auth::login($user, true);
            session()->regenerate();

            return redirect()->to(config('app.frontend_url').$this->pageConfigurationService->getRedirectURL());

        } catch (\Exception $e) {
            return redirect()->to(config('app.frontend_url').'/auth/login');
        }
    }
}
