<?php

namespace App\Http\Controllers\Api;

use App\Classes\Response as ClassesResponse;
use App\Classes\SSO;
use App\Gadget;
use App\User;
use Carbon\Carbon;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response as FacadesResponse;
use Illuminate\Support\Facades\Validator;

class UserSsoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    // public function __construct() {
    //     $this->middleware('auth:api');
    // }
    const VERIFY_CODE_VALIDATE_TIME = 300;

    public function signUp(Request $request): array
    {


        $validator = Validator::make($request->all(), [
            'phone' => [
                'required'
            ]
        ]);

        if ($validator->fails()) {

            $status = false;
            $wait = false;
            $exist = false;
            $data = null;
            $need_wait = false;
            $message = trans('messages.not_valid_input');
        } else {

            $smsResult = false;
            $expire = Carbon::now()->addSeconds(self::VERIFY_CODE_VALIDATE_TIME);
            $canSendVerifyCode = true;

            $msisdn = setClearPhone($request->input('phone'), 98);
            $phone = setClearPhone($request->input('phone'), 0);

            $exist = false;
            $wait = false;


            $user = User::withoutGlobalScope(ActiveScope::class)->firstOrNew(['phone' => $phone]);


            if ($user->exists) {
                $exist = true;
            }

            $user->phone = $msisdn;
            //  $user->ip = $request->ip();



            if ($user->token_expire) {
                try {
                    $now = Carbon::now();
                    $verifyCodeSentAt = Carbon::createFromFormat('Y-m-d H:i:s', $user->token_expire, 'UTC');

                    $verifyCodeSentAtDifference = $now->diffInSeconds($verifyCodeSentAt, false);

                    if ($verifyCodeSentAtDifference > 0) {
                        $canSendVerifyCode = false;
                    }
                } catch (Exception $e) {
                    // do nothing
                }
            }

            if ($canSendVerifyCode) {

                $data = [
                    'receptor'    => $msisdn,
                    'message'      => 'msisdn'
                ];
                // $client = new \GuzzleHttp\Client();
                // $response = $client->post(
                //     'https://api.kavenegar.com/v1/6678375779354847474F64797150525A62383952564F723657504D4D49465771/sms/send.json',
                //     array(
                //         'form_params' => array(
                //             'receptor' => '09129287725',
                //             'message' => '123'

                //         ),
                //         'verify'   => false,
                //         'curl' => [
                //             CURLOPT_SSL_VERIFYPEER => false
                //         ],
                //     )
                // );

               // dd($response);
                // $smsResult = $response;
                $smsResult = true;
                // if ($smsResult->status) {
                //     $user->token_expire = $expire;
                // } elseif ($smsResult->wait) {
                //     $wait = true;
                // }
            }

            if ($smsResult) {

                if ($user->save()) {
                    $status = true;
                    $data = $user;
                    $message = trans('messages.sms_sent');
                } else {
                    $status = false;
                    $data = null;
                    $message = trans('messages.unknown_error');
                }
            } elseif (!$canSendVerifyCode) {
                $data = null;
                $status = false;
                $wait = true;
                $message = trans('messages.code_already_sent');
            } else {
                $status = false;
                // $data = ($smsResult and $smsResult->data) ? $smsResult->data : null;
                $data = null;
                $message = trans('messages.error_on_send_code');
            }
        }

        return  [
            'status'  => $status,
            'exist'   => $exist,
            'user'    => $data,
            'message' => $message,
            'wait'    => $wait,
        ];
    }

    /**
     * Verify Store User Code
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => [
                'required',
                'regex:/^(9|09|989|[+]989)[0,1,2,3,9]{1}[0-9]{8}$/',
            ],

            'code'        => 'required',
            'device_id'   => 'required',
            'type'        => 'required', //APP 1 web 2
            'hash'        => 'required'
        ]);

        if ($validator->fails()) {
            return Response::validation($validator->errors());
        } else {

            $phone = setClearPhone($request->input('phone'), 0);
            $now = Carbon::now();

            AdminActivity::$checkEvent = false;

            $user = People::withoutGlobalScope(ActiveScope::class)->where('phone', $phone)
                ->whereDate('verification_expire', '>=', $now)
                ->first();

            if ($user) {
                if ($user->active) {
                    $result = SSO::confirmOtp($user->msisdn, $request->code);
                } else {
                    $result = SSO::login($user->msisdn, $request->input('code'));
                }


                if ($result->status) {
                    if (!$user->active) {
                        $user->sso_token = $result->body;
                    }
                } else {
                    return Response::json('messages.code_is_wrong', null, static::HTTP_UNPROCESSABLE_ENTITY);
                }


                //                $user->sso_token = 'ddddddddddddddddddddddd';

                //                $user->verification_code = null;

                $user->verification_expire = null;

                $user->active = ActiveScope::ACTIVE;

                if ($user->save()) {
                    $token = Token::generate($user->id, $request->device_id, $request->type);

                    //Amenin Users
                    $amenin_user = AmeninUser::on(self::AMENIN_DATABASE)->withoutGlobalScope(ActiveScope::class)->firstOrNew(['phone' => $phone]);

                    $amenin_user->msisdn = $user->msisdn;

                    $amenin_user->line_type = $user->mobile_service_provider;

                    $amenin_user->active = ActiveScope::ACTIVE;

                    $amenin_user->other_service = self::CURRENT_APP;

                    $amenin_user->ip = $request->ip();

                    if ($amenin_user->save()) {

                        $amenin_token = Token::generate($amenin_user->id, $request->device_id, $request->type, self::AMENIN_DATABASE);
                    }

                    $user->token = $token;

                    $data = [
                        'user_id'       => $user->id,
                        'token'         => $token,
                        'user'          => $user,
                        'amenin_token'  => $amenin_token,
                        'amenin_user'   => $amenin_user
                    ];

                    return Response::json('messages.activated', $data, static::HTTP_OK);
                } else {
                    return Response::json(static::MESSAGE_UNKNOWN_ERROR, null, static::HTTP_UNPROCESSABLE_ENTITY);
                }
            } else {
                return Response::json(static::MESSAGE_NOT_FOUND, null, static::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
    }



    /**
     * Change phone
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => [
                'required',
                'regex:/^(9|09|989|[+]989)[0,1,2,3,9]{1}[0-9]{8}$/'
            ],
            'hash'  => 'required',
        ]);

        if ($validator->fails()) {
            return FacadesResponse::validation($validator->errors());
        } else {

            $phone = setClearPhone($request->phone, 0);
            $msisdn = setClearPhone($request->phone, 98);

            $user = People::find($request->header('userid'));

            $wait = false;

            $change_phone_request = ChangePhoneRequest::where('user_id', $request->header('userid'))
                ->where('status', ChangePhoneRequest::WAIT_STATUS)
                ->first();


            if (empty($change_phone_request)) {

                $expire = Carbon::now()->addMinute(5)->format('Y-m-d H:i:s');

                $change = new ChangePhoneRequest();
                $change->phone = $phone;
                $change->msisdn = $msisdn;
                $change->previous_phone = $user->phone;

                if ($change->save()) {
                    $smsResult = SSO::sendOtp($msisdn);

                    if ($smsResult->status) {
                        $change->verification_expire = $expire;
                        $change->save();

                        return Response::json(trans('messages.sms_sent'), null, static::HTTP_OK);
                    } elseif ($smsResult->wait) {
                        $change->status = ChangePhoneRequest::SMS_NOT_SENT_STATUS;
                        $change->save();
                        return Response::json(trans('messages.wait_until_expire'), null, static::HTTP_OK);
                    }
                } else {
                    return Response::json(trans('messages.unknown_error'), null, static::HTTP_ERROR);
                }
            } else {
                return Response::json(trans('messages.checking'), null, static::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
    }
}
