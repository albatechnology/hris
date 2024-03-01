<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    private array $unwantedHeaders = [
        'X-Powered-By', 'Server', 'server'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (config('app.env', 'production') == 'production') {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $response->headers->set('Content-Security-Policy', "");
            // $response->headers->set('Content-Security-Policy', "default-src *;");
            $response->headers->set('Expect-CT', 'enforce, max-age=30');
            $response->headers->set('Permissions-Policy', 'autoplay=(self), camera=(), encrypted-media=(self), fullscreen=(), geolocation=(self), gyroscope=(self), magnetometer=(), microphone=(), midi=(), payment=(), sync-xhr=(self), usb=()');
            // $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
            // $response->headers->set('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Requested-With,X-CSRF-Token');

            foreach ($this->unwantedHeaders ?? [] as $header) {
                header_remove($header);
            }
        }

        return $response;
    }
}
