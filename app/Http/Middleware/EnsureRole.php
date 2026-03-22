<?php

namespace App\Http\Middleware;

use App\Enums\RoleSlug;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowedSlugs = array_map(fn ($r) => RoleSlug::from($r), $roles);

        if (! in_array($user->role->slug, $allowedSlugs, strict: true)) {
            abort(403);
        }

        return $next($request);
    }
}
