<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

/**
 * Class ImpersonationController
 *
 * Handles starting and stopping user impersonation sessions.
 *
 * Main Responsibilities:
 * - Start: Switches the authenticated user to a target user while storing the original ID.
 * - Stop: Reverts the authenticated user to the original impersonator ID.
 */
class ImpersonationController
{
    public function start(Request $request, ?string $userId = null, ?RouterData $routerData = null): RedirectResponse
    {
        $userId = $userId ?? $request->route('user');
        /** @var RouterData $routerData */
        $routerData = $routerData ?? $request->route('routerData');

        /*
         * Store current user ID in SessionKey::IMPERSONATOR_ID
         */
        $request->session()->put(SessionKey::IMPERSONATOR_ID, Auth::id());

        /*
         * Neutralise the impersonator's "remember me" (recaller) cookie.
         *
         * Impersonation only swaps the *session* user. If the impersonator logged in
         * with rememberMe, their recaller cookie outlives the swap, and Laravel's
         * SessionGuard silently logs them back in from that cookie on any request
         * where the session login id cannot be resolved (e.g. a session-regeneration
         * race across the concurrent requests of a media-heavy page) — abruptly and
         * invisibly ending the impersonation. Forgetting the cookie makes a session
         * miss fail safe (logged out) instead of secretly reverting to the impersonator.
         */
        $this->forgetRecallerCookie($request);

        /*
         * Log in as the target $user
         */
        Auth::loginUsingId($userId);

        /*
         * Redirect to success route
         */
        return redirect()->route($routerData->routeSuccess);
    }

    public function stop(Request $request, ?RouterData $routerData = null): RedirectResponse
    {
        /** @var RouterData $routerData */
        $routerData = $routerData ?? $request->route('routerData');

        /*
         * Retrieve original user ID from SessionKey::IMPERSONATOR_ID
         */
        $impersonatorId = $request->session()->get(SessionKey::IMPERSONATOR_ID);

        /*
         * Log in as the original user, re-honouring the configured rememberMe setting
         * (their recaller cookie was cleared when impersonation started).
         */
        if ($impersonatorId) {
            Auth::loginUsingId($impersonatorId, $routerData->rememberMe);
            /*
             * Clear SessionKey::IMPERSONATOR_ID from session
             */
            $request->session()->forget(SessionKey::IMPERSONATOR_ID);
        }

        /*
         * Redirect to home/success route
         */
        return redirect()->route($routerData->routeHome);
    }

    /**
     * Queue the deletion of the active guard's "remember me" recaller cookie and
     * drop it from the current request so it cannot be consulted again this cycle.
     */
    private function forgetRecallerCookie(Request $request): void
    {
        $guard = Auth::guard();

        if (! $guard instanceof SessionGuard) {
            return;
        }

        $recallerName = $guard->getRecallerName();

        Cookie::queue(Cookie::forget($recallerName));
        $request->cookies->remove($recallerName);
    }
}
