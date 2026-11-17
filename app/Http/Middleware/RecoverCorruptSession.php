<?php

namespace App\Http\Middleware;

use App\Support\PersistentLogin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecoverCorruptSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            try {
                $request->session()->get('_darul_fattah_session_probe');
            } catch (Throwable $e) {
                $this->resetSession($request);
            }
        }

        return $next($request);
    }

    private function resetSession(Request $request): void
    {
        try {
            $request->session()->flush();
        } catch (Throwable $e) {
            //
        }

        try {
            $request->session()->regenerate(true);
        } catch (Throwable $e) {
            //
        }

        $user = PersistentLogin::userFromRequest($request);
        if ($user) {
            PersistentLogin::bind($user, true);
            PersistentLogin::queue($user);
        }

        if ($request->hasSession()) {
            try {
                $request->session()->regenerateToken();
            } catch (Throwable $e) {
                //
            }
        }
    }
}