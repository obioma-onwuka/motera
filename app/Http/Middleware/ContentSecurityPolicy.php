<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $viteHosts = app()->environment('local') ? ' http://localhost:5173 https://localhost:5173 http://motera.test:5173 https://motera.test:5173 http://127.0.0.1:5173 https://127.0.0.1:5173 wss://motera.test:5173 ws://motera.test:5173' : '';

        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval'{$viteHosts}; "; // Livewire/Alpine; Chart.js is bundled via Vite
        $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net{$viteHosts}; ";
        $csp .= "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net{$viteHosts}; ";
        $csp .= "img-src 'self' data: https:; ";
        $csp .= "connect-src 'self' https: ws: wss:{$viteHosts}; ";
        $csp .= "frame-ancestors 'none'; ";

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
