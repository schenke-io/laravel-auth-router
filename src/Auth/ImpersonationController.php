<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    public function start(Request $request, string $userId, RouterData $routerData): RedirectResponse
    {
        /*
         * Store current user ID in SessionKey::IMPERSONATOR_ID
         */
        $request->session()->put(SessionKey::IMPERSONATOR_ID, Auth::id());

        /*
         * Log in as the target $user
         */
        Auth::loginUsingId($userId);

        /*
         * Redirect to success route
         */
        return redirect()->route($routerData->routeSuccess);
    }

    public function stop(Request $request, RouterData $routerData): RedirectResponse
    {
        /*
         * Retrieve original user ID from SessionKey::IMPERSONATOR_ID
         */
        $impersonatorId = $request->session()->get(SessionKey::IMPERSONATOR_ID);

        /*
         * Log in as the original user
         */
        if ($impersonatorId) {
            Auth::loginUsingId($impersonatorId);
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
}
