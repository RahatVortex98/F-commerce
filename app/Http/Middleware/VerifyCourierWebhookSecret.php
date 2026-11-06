<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCourierWebhookSecret
{
    public function handle(Request $request, Closure $next, ?string $provider = null): Response
    {
        $provider ??= (string) $request->route('provider');
        $secret = (string) config("courierhub.webhook.secrets.{$provider}");

        if ($secret === '') {
            return response('Courier webhook secret is not configured', 503);
        }

        $header = $provider === 'pathao' ? 'X-PATHAO-SIGNATURE' : 'X-COURIER-SIGNATURE';
        $signature = (string) $request->header($header);
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            return response('Invalid courier webhook signature', 403);
        }

        return $next($request);
    }
}
