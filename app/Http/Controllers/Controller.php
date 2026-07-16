<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    /**
     * AJAX dialog forms submit via fetch, which would otherwise auto-follow the redirect
     * itself and consume the one-shot session flash before the browser ever navigates there.
     * For JSON requests we hand back the destination URL instead so the client can navigate
     * to it directly, letting the flashed message survive for that real page load.
     */
    protected function respond(Request $request, RedirectResponse $redirect): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirect->getTargetUrl()]);
        }

        return $redirect;
    }
}
