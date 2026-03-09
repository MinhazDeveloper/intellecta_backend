<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // যদি রিকোয়েস্টটি GET না হয় (অর্থাৎ POST, PUT, DELETE বা PATCH হয়)
        if (!$request->isMethod('get')) {
            return response()->json([
                'message' => 'Demo Mode: You cannot update or delete data in this preview.'
            ], 403);
        }

        return $next($request);
    }
}
