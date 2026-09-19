<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class ApplySecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        }
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->user() !== null) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'"],
            'media-src' => ["'self'"],
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
        ];

        $viteOrigins = $this->viteDevelopmentOrigins();

        if ($viteOrigins !== null) {
            foreach (['script-src', 'style-src', 'img-src', 'font-src', 'connect-src'] as $directive) {
                $directives[$directive][] = $viteOrigins['http'];
            }

            $directives['connect-src'][] = $viteOrigins['websocket'];
        }

        return implode(' ', array_map(
            fn (string $directive, array $sources): string => $directive.' '.implode(' ', $sources).';',
            array_keys($directives),
            $directives,
        ));
    }

    /**
     * @return array{http: string, websocket: string}|null
     */
    private function viteDevelopmentOrigins(): ?array
    {
        if (! app()->isLocal() || ! is_readable(Vite::hotFile())) {
            return null;
        }

        $viteUrl = trim((string) file_get_contents(Vite::hotFile()));
        $parts = parse_url($viteUrl);

        if (! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || empty($parts['host'])) {
            return null;
        }

        $host = trim($parts['host'], '[]');

        // Browsers reject IPv6 literals in CSP host sources. Vite is configured
        // to publish an IPv4 hot URL, so a stale IPv6 hot file must be ignored.
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $httpOrigin = $parts['scheme'].'://'.$host.$port;
        $websocketScheme = $parts['scheme'] === 'https' ? 'wss' : 'ws';

        return [
            'http' => $httpOrigin,
            'websocket' => $websocketScheme.'://'.$host.$port,
        ];
    }
}
