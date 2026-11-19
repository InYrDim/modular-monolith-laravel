<?php

/*
|--------------------------------------------------------------------------
| Trusted Proxies
|--------------------------------------------------------------------------
|
| Laravel's TrustProxies middleware reads this key whenever the application
| does not register its own list, so setting TRUSTED_PROXIES is enough to
| configure it. When HTTPS terminates at a load balancer or reverse proxy,
| list the proxy addresses comma separated (CIDR or single IPs), or use
| "REMOTE_ADDR" to trust the caller's peer address. This lets the application
| see the original scheme, host and client address through the forwarded
| headers. Leave it empty when the application is served directly, and never
| set it to "*" on a host reachable from the internet.
|
*/

return [

    'proxies' => env('TRUSTED_PROXIES'),

];
