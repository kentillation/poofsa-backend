<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = [
            'http://localhost:8080',
            'http://localhost:8081',
            'https://poofsa-vent.vercel.app',
            'https://poofsa-tend.vercel.app',
            'https://poofsa-kitch.vercel.app',
            'https://poofsa-bris.vercel.app',
            'https://poofsa-des.vercel.app',
            'https://poofsa-stom.vercel.app',
            'https://poofsa-yals.vercel.app',
        ];

        $origin = $request->headers->get('Origin');
        
        $headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Session-ID, X-Requested-With, X-CSRF-Token',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
            'Access-Control-Allow-Credentials' => 'true',
        ];

        if (in_array($origin, $allowedOrigins)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        }

        if ($request->getMethod() === 'OPTIONS') {
            return response()->json('OK', 204, $headers);
        }

        $response = $next($request);

        if ($response instanceof BinaryFileResponse) {
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
            return $response;
        }

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }
}
