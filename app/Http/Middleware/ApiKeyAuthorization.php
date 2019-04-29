<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class ApiKeyAuthorization
{
    const AUTH_PARAM = 'api_key';

    /**
     * Authorize incoming api request
     * @param  \Closure  $next
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $key = $request->get(self::AUTH_PARAM);

        if((int)env('API_AUTH_ENABLED', 0) == 1) {
            if(empty(Redis::connection()->get('API_KEY:'.$key))) {
                return response('Unauthorized.', 401);
            }
        }

        return $next($request);
    }
}
