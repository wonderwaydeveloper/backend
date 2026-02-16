<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustedProxies
{
    protected $proxies = '*';
    
    protected $headers = Request::HEADER_X_FORWARDED_FOR |
                         Request::HEADER_X_FORWARDED_HOST |
                         Request::HEADER_X_FORWARDED_PORT |
                         Request::HEADER_X_FORWARDED_PROTO |
                         Request::HEADER_X_FORWARDED_AWS_ELB;

    public function handle(Request $request, Closure $next): Response
    {
        $request->setTrustedProxies(
            $this->proxies === '*' ? ['127.0.0.1', $request->server->get('REMOTE_ADDR')] : $this->proxies,
            $this->headers
        );

        return $next($request);
    }
}
