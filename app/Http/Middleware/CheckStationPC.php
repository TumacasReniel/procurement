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
  dd($ip);
        // $hashids = new Hashids('krad', 10);     
        //  $a = $hashids->encode(8);
        //  $decrypted = Crypt::encryptString($a);
        //  dd($decrypted);

        try {
            $decrypted = Crypt::decryptString($station);
        } catch (DecryptException $e) {
            abort(403, 'Unauthorized access attempt detected. This activity is prohibited and may be prosecuted under RA 10175 (Cybercrime Prevention Act of 2012). Please contact your administrator.');
        }
        $code = explode('/', $decrypted)[0];
        $hashids = new Hashids('krad', 10);
        $id = $hashids->decode($code)[0] ?? null;
        
        switch($id){
            case 5: //n6QXrVXyVN //n8LX7Aow79
                $allowedPcs = ['127.0.0.1','136.239.177.3','113.19.124.130']; //eyJpdiI6Im5ROGtmK0RkNWYvcm5OWXZMNW44Mnc9PSIsInZhbHVlIjoib1RVMVhzOUltSjhZNjlyeXZ0d1gzQT09IiwibWFjIjoiODUzNzE3YmMwMGFmNGY2NjhkODczYzViNjY0OThmNTE5NWRiZTM3YmVjNzBmZDdlMDczNjdhZWRiZjc2NDJkNCIsInRhZyI6IiJ9
            break;
            case 6: //d21XyMXgDE //kO5xGJXyn2
                $allowedPcs = ['127.0.0.1','119.92.71.96']; //eyJpdiI6IjBrT2xMNVREVHpqNGtCbkFXc2F4bkE9PSIsInZhbHVlIjoibiszOXIrVUxGT2d4Mzh2QkNXT2ZuUT09IiwibWFjIjoiNWM1NGYwODQwZTA1YzkwNDg2ZmY0ODQ0YTMwMzZiMDBhZjdkZjJkOTZlZmVlNGEwZmM2Nzc2NzcyNjY3NDRkNSIsInRhZyI6IiJ9
            break;
            case 7: //lO8oBMxrvK //3EnxJ3ojD1
               $allowedPcs = ['127.0.0.1','119.93.167.200']; //eyJpdiI6ImxRRmR3bldoaEpvNGZBV2xtY2UybkE9PSIsInZhbHVlIjoibHFESVl5SmplbHlTUFhsRzd1TUx4QT09IiwibWFjIjoiMzA3Y2FkZTQxZThmM2IzZGRkMTY2YzdjN2I5OGExMmZmOGFjYzJjMzdkNDEwNWFjMGVmZTcxMWYwMDVjMWI0ZCIsInRhZyI6IiJ9
            break;
            case 8: //lO8oBMxrvK //pl4aO0XZ5D
               $allowedPcs = ['127.0.0.1','119.92.230.203']; //eyJpdiI6Im90S1Yza2VyWHNjTXhiTGtrWnFEWnc9PSIsInZhbHVlIjoieWxkVW5ESFoyK0I4TWlJRkVLRlZtQT09IiwibWFjIjoiZTA5M2MxOTgxZTdkMzRjMjFlZTcyYTA0NTkzZmE3ZmM0MWUzYjUzNzE2MzdlZGYxZjU2MDE5ZjAwYTgxNmMzMyIsInRhZyI6IiJ9
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
