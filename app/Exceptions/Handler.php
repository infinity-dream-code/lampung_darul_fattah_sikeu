<?php

namespace App\Exceptions;

use App\Support\PersistentLogin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($this->isSessionReadFailure($e)) {
            return $this->respondAfterSessionRecovery($request);
        }

        if ($e instanceof TokenMismatchException) {
            $this->ensureSessionUsable($request);
            $this->restorePersistentUser($request);

            if ($request->hasSession()) {
                $request->session()->regenerateToken();
            }

            return $this->retryOrRedirectResponse($request, 419);
        }

        if ($e instanceof AuthenticationException) {
            if ($this->restorePersistentUser($request)) {
                return $this->retryOrRedirectResponse($request, 401);
            }

            if ($this->isAjaxRequest($request)) {
                return response()->json([
                    'ok' => true,
                    'retry' => true,
                    'token' => csrf_token(),
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        if (
            $request->isMethod('GET')
            && !$request->boolean('_retry')
            && $this->isTransientServerError($e)
        ) {
            return redirect()->to($request->fullUrlWithQuery(['_retry' => 1]));
        }

        return parent::render($request, $e);
    }

    private function restorePersistentUser(Request $request): bool
    {
        $user = Auth::user() ?: PersistentLogin::userFromRequest($request);
        if (!$user) {
            return false;
        }

        $this->ensureSessionUsable($request);

        PersistentLogin::bind($user, true);
        PersistentLogin::queue($user);

        if ($request->hasSession()) {
            $request->session()->regenerateToken();
        }

        return true;
    }

    private function ensureSessionUsable(Request $request): void
    {
        if (!$request->hasSession()) {
            return;
        }

        try {
            $request->session()->get('_surabaya_alirsyad_session_probe');
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

    private function retryOrRedirectResponse(Request $request, int $status): Response
    {
        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'ok' => true,
                'retry' => true,
                'token' => csrf_token(),
            ], $status);
        }

        if ($request->isMethod('GET')) {
            return redirect()->to($request->fullUrl());
        }

        return redirect()->back();
    }

    private function respondAfterSessionRecovery(Request $request): Response
    {
        $this->ensureSessionUsable($request);
        $this->restorePersistentUser($request);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'ok' => true,
                'retry' => true,
                'token' => csrf_token(),
            ]);
        }

        if ($request->isMethod('GET')) {
            return redirect()->to($request->fullUrl());
        }

        return redirect()->back();
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest'
            || str_contains((string) $request->header('Accept'), 'application/json');
    }

    private function isSessionReadFailure(Throwable $e): bool
    {
        $message = strtolower($e->getMessage() . ' ' . $e->getPrevious()?->getMessage());

        return str_contains($message, 'failed to read session data')
            || str_contains($message, 'session store not set')
            || (str_contains($message, 'unserialize') && str_contains($message, 'session'))
            || str_contains($message, 'sessionpayload')
            || (str_contains($message, 'session') && str_contains($message, 'corrupt'));
    }

    private function isTransientServerError(Throwable $e): bool
    {
        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() !== 500) {
            return false;
        }

        $message = strtolower($e->getMessage() . ' ' . $e->getPrevious()?->getMessage());

        return str_contains($message, 'has gone away')
            || str_contains($message, 'deadlock')
            || str_contains($message, 'lock wait timeout')
            || str_contains($message, 'unable to obtain lock')
            || (str_contains($message, 'session') && str_contains($message, 'lock'))
            || str_contains($message, 'sqlstate[40001]')
            || str_contains($message, 'sqlstate[hy000]');
    }
}