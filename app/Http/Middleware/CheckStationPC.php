<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ListDropdown;
use Illuminate\Http\Request;
use Hashids\Hashids;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Encryption\DecryptException;

class CheckStationPC
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $station = $request->route('station');
        $ip = $request->ip();
        try {
            $decrypted = Crypt::decryptString($station);
        } catch (DecryptException $e) {
            abort(403, 'Unauthorized access attempt detected. This activity is prohibited and may be prosecuted under RA 10175 (Cybercrime Prevention Act of 2012). Please contact your administrator.');
        }
        $code = explode('/', $decrypted)[0];
        $hashids = new Hashids('krad', 10);
        $id = $hashids->decode($code)[0] ?? null;
        
        switch($id){
            case 2: //n6QXrVXyVN
                $allowedPcs = ['127.0.0.1','136.239.177.3','113.19.124.130']; //eyJpdiI6ImhTTElCTW9FSUpvK0swUDN0dlVmR0E9PSIsInZhbHVlIjoiQ0NMUGs2d001WXErM1gycEV1SU1lQ1hVa3lja2NLRmJ1aDk3R1FQeG9mZz0iLCJtYWMiOiJjMzRjMmU5YTQxMTk0ODE0NWJlNWE0NDg0YjY0OWU3N2ViYzA3ODliMGJhZDU5MGJiNWU3NTE4ZmI0ZjA0YWMwIiwidGFnIjoiIn0=
            break;
            case 3: //d21XyMXgDE
                $allowedPcs = []; //eyJpdiI6Inp6OG9TZmN1N282WHRIeFZFaEVlQ3c9PSIsInZhbHVlIjoiNEFsN1FXZERuZlNzd1Z4eWIvY3phMmRVTE1nY05vQmlIS3I4Mmc2aUxmMD0iLCJtYWMiOiI3ZGZhY2ZkMjU2NDM2NGYxMmJmMzc3NTcwNDU2ZGU3NmFiOTNiZWE1ZjJjMDYxMGQ1YWFlZDZlMWUwOTZjMzA0IiwidGFnIjoiIn0=
            break;
            case 4: //lO8oBMxrvK
               $allowedPcs = []; //eyJpdiI6IlZZb3lVVCtzNDNFdXorTjBHTk1nSVE9PSIsInZhbHVlIjoiTU5kS3RsTDlGa1JZSHdEbGp3VGVHbTdibnFqejdwRTUzb2Z4ejk3SGhmVT0iLCJtYWMiOiIwNGQ5ZThhM2YxNTA5NDJhYjU1YTBkNzMxNjFhODRiNzJhODc2NWE2OGZkMzM3MTljODhjOTIyMGFjMTc3OGJhIiwidGFnIjoiIn0=
            break;
            default: 
            $allowedPcs = [];
        }

        if (!in_array($ip, $allowedPcs)) {
            abort(403, 'Unauthorized access attempt detected. This activity is prohibited and may be prosecuted under RA 10175 (Cybercrime Prevention Act of 2012). Please contact your administrator.');
        }
        return $next($request);
    }
}
