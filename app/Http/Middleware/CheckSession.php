<?php

namespace App\Http\Middleware;

use App\Support\PersistentLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->ensureSessionUsable($request);

        if (!Auth::check()) {
            $user = PersistentLogin::userFromRequest($request);
            if ($user) {
                // Soft bind only: Auth::login()/migrate() on every request
                // can lock the session file and break DataTables get-data (500).
                PersistentLogin::bind($user);
                PersistentLogin::queue($user);
            }
        }

        if (!Auth::check()) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'ok' => true,
                    'retry' => true,
                    'token' => csrf_token(),
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        PersistentLogin::queue(Auth::user());

        return $next($request);
    }

    private function ensureSessionUsable(Request $request): void
    {
        if (!$request->hasSession()) {
            return;
        }

        try {
            $request->session()->get('_darul_fattah_session_probe');
        } catch (Throwable $e) {
            try {
                $request->session()->flush();
            } catch (Throwable $flushError) {
                //
            }

            try {
                $request->session()->regenerate(true);
            } catch (Throwable $regenerateError) {
                //
            }
        }
    }
}
