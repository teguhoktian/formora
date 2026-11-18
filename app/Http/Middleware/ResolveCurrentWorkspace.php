<?php

namespace App\Http\Middleware;

use App\Services\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            $workspace = app(CurrentWorkspace::class)->get($request->user(), $request);
            $request->attributes->set('currentWorkspace', $workspace);
        }

        return $next($request);
    }
}
