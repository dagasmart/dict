<?php
declare(strict_types=1);
namespace DagaSmart\Dict\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Middleware
{

    public function handle(Request $request, Closure $next)
    {
        if (!admin_extension_expiry('dagasmart.dict')) {
            return admin_response()->fail('软件已过期,请续费');
        }
        if (!admin_extension_enabled('dagasmart.dict')) {
            return admin_response()->fail('请在已订购软件里启用');
        }
        return $next($request);
    }


}
