<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    use AuthorizesRequests;

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

    /** True for a relative path or an absolute URL pointing at this same host — never an external redirect target. */
    protected function isSameHostUrl(Request $request, string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return ! $host || $host === $request->getHost();
    }

    /**
     * Called after a password change so a stolen or lingering session doesn't survive it.
     * Only the 'database' session driver stores a queryable user_id, so this is a no-op on
     * 'file'/'array' — those have no central registry of a user's sessions to sweep.
     */
    protected function invalidateSessionsFor(int $userId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->delete();
    }
}
