<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    private const SOCIALITE_CLASS = \Laravel\Socialite\Facades\Socialite::class;

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:40'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'player_token' => ['nullable', 'string', 'max:64'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('game-web')->plainTextToken;
        $this->linkGuestProfileToUser($validated['player_token'] ?? null, $user);

        return response()->json([
            'auth_token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'player_token' => ['nullable', 'string', 'max:64'],
        ]);

        $user = User::query()->where('email', Str::lower($validated['email']))->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 422);
        }

        $token = $user->createToken('game-web')->plainTextToken;
        $this->linkGuestProfileToUser($validated['player_token'] ?? null, $user);

        return response()->json([
            'auth_token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['user' => null], 401);
        }

        return response()->json([
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada',
        ]);
    }

    public function googleRedirect(Request $request): RedirectResponse
    {
        if (! class_exists(self::SOCIALITE_CLASS)) {
            return $this->redirectWithAuthError('socialite_missing');
        }

        $state = [
            'player_token' => (string) $request->query('player_token', ''),
        ];

        $encodedState = base64_encode(json_encode($state));

        try {
            return Socialite::driver('google')
                ->stateless()
                ->with(['state' => $encodedState])
                ->redirect();
        } catch (\Throwable) {
            return $this->redirectWithAuthError('google_redirect_failed');
        }
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        if (! class_exists(self::SOCIALITE_CLASS)) {
            return $this->redirectWithAuthError('socialite_missing');
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable) {
            return $this->redirectWithAuthError('google_callback_failed');
        }

        $user = User::query()->where('google_id', $googleUser->id)->first();

        if (! $user && $googleUser->email) {
            $user = User::query()->where('email', Str::lower($googleUser->email))->first();
        }

        if (! $user) {
            $resolvedEmail = $googleUser->email
                ? Str::lower((string) $googleUser->email)
                : ('google_'.$googleUser->id.'@pokemon-who-is.local');

            $user = User::query()->create([
                'name' => $googleUser->name ?: $googleUser->nickname ?: 'Trainer',
                'email' => $resolvedEmail,
                'password' => Str::password(20),
                'google_id' => $googleUser->id,
                'avatar_url' => $googleUser->avatar,
            ]);
        } else {
            $user->google_id = $googleUser->id;
            $user->avatar_url = $googleUser->avatar;
            $user->name = $user->name ?: ($googleUser->name ?: 'Trainer');
            $user->save();
        }

        $stateRaw = (string) $request->query('state', '');
        $state = json_decode(base64_decode($stateRaw, true) ?: '{}', true);
        $playerToken = is_array($state) ? (($state['player_token'] ?? null) ?: null) : null;

        $this->linkGuestProfileToUser($playerToken, $user);

        $token = $user->createToken('game-web')->plainTextToken;
        $params = http_build_query([
            'auth_token' => $token,
            'auth_name' => $user->name,
            'auth_email' => $user->email,
        ]);

        return redirect('/?'.$params);
    }

    private function redirectWithAuthError(string $error): RedirectResponse
    {
        return redirect('/?'.http_build_query(['auth_error' => $error]));
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'has_google' => (bool) $user->google_id,
        ];
    }

    private function linkGuestProfileToUser(?string $playerToken, User $user): void
    {
        $token = trim((string) $playerToken);
        if ($token === '') {
            return;
        }

        $profile = PlayerProfile::query()->where('session_id', $token)->first();
        if (! $profile) {
            return;
        }

        $meta = $profile->meta ?? [];
        $meta['user_id'] = $user->id;
        $profile->meta = $meta;

        if (! $profile->nickname) {
            $profile->nickname = $user->name;
        }

        $profile->save();
    }
}
