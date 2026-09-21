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
        Vite::useCspNonce();

        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($request));
        }
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->user() !== null || str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request): string
    {
        $nonce = "'nonce-".Vite::cspNonce()."'";

        $directives = [
            'default-src' => ["'none'"],
            'script-src' => [$nonce, "'strict-dynamic'", "'self'"],
            'script-src-attr' => ["'none'"],
            'style-src' => ["'self'", $nonce],
            'style-src-attr' => [$request->routeIs('documents.udf.show', 'documents.udf.edit') ? "'unsafe-inline'" : "'none'"],
            'img-src' => ["'self'", 'data:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'"],
            'media-src' => ["'self'"],
            'frame-src' => ["'none'"],
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'none'"],
            'form-action' => ["'self'"],
        ];

        $reverbOrigin = $this->reverbWebsocketOrigin();

        if ($reverbOrigin !== null) {
            $directives['connect-src'][] = $reverbOrigin;
        }

        $viteOrigins = $this->viteDevelopmentOrigins();

        if ($viteOrigins !== null) {
            foreach (['script-src', 'style-src', 'img-src', 'font-src', 'connect-src'] as $directive) {
                $directives[$directive][] = $viteOrigins['http'];
            }

            $directives['connect-src'][] = $viteOrigins['websocket'];
        }

        return implode(' ', array_map(
            fn (string $directive, array $sources): string => $directive.' '.implode(' ', array_unique($sources)).';',
            array_keys($directives),
            $directives,
        ));
    }

    private function reverbWebsocketOrigin(): ?string
    {
        $host = config('broadcasting.connections.reverb.options.host');
        $port = config('broadcasting.connections.reverb.options.port');
        $scheme = config('broadcasting.connections.reverb.options.scheme', 'https');

        if (! is_string($host) || $host === '') {
            return null;
        }

        $websocketScheme = $scheme === 'https' ? 'wss' : 'ws';

        $defaultPort = $websocketScheme === 'wss' ? 443 : 80;

        $portSuffix = is_numeric($port) && (int) $port !== $defaultPort
            ? ':'.$port
            : '';

        return $websocketScheme.'://'.$host.$portSuffix;
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

        if (
            ! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || empty($parts['host'])
        ) {
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
