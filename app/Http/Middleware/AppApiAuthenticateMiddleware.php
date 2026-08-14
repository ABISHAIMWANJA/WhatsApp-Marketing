<?php

namespace App\Http\Middleware;

use App\Yantrana\Components\Auth\Models\AuthModel;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use Auth;
use Closure;
use Illuminate\Support\Facades\Route;
use YesTokenAuth;

class AppApiAuthenticateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        config([
            '__tech.auth_info' => [
                'authorized' => false,
                'reaction_code' => 9,
            ],
        ]);

        // Check if addon installed and activated or not
        $addonLicInfo = getAppSettings('lwAddon' . 'WhatsJetChatMobileApp');
        if ((__isEmpty($addonLicInfo['registration_id'])) or (sha1(array_get($_SERVER, 'HTTP_HOST', '') . $addonLicInfo['registration_id'] . '1.0+') !== $addonLicInfo['signature'])) {
            abort(404, __tr('Invalid Request'));
        }

        $isVerified = YesTokenAuth::verifyToken();

        if(($isVerified['error'] or ($isVerified['error'] !== false)) and (Route::currentRouteName() != 'base_data')) {
            return __apiResponse([
                'message' => $isVerified['error'],
                'auth_info' => getUserAuthInfo(),
            ], 2);
        }

        $tokenRefreshed = array_get($isVerified, 'refreshed_token', null);

        if ($tokenRefreshed) {
            /*set refreshed token */
            setAccessToken($tokenRefreshed);
        }
        if (!$isVerified['error'] or ($isVerified['error'] === false)) {
            $userInfo = AuthModel::where('_id', $isVerified['aud'])->first();
            if (! __isEmpty($userInfo) && in_array($userInfo->status, [2, 3, 4, 5, 6])) {
                return __apiResponse([
                    'message' => __tr('Your Account seems to Inactive or Deleted or Not Activated, Please contact Administrator.'),
                    'auth_info' => getUserAuthInfo(),
                ], 9);
            } elseif (! __isEmpty($userInfo) && $userInfo->status == 1) {
                Auth::loginUsingId($isVerified['aud']);
            } else {
                if (Route::currentRouteName() != 'base_data') {
                    return __apiResponse([
                        'message' => __tr('Please login to complete request.'),
                        'auth_info' => getUserAuthInfo(),
                    ], 9);
                }
            }
        }

        return $next($request);
    }
}
