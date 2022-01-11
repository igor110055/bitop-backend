<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;

use App\Http\Controllers\Traits\SecurityCodeTrait;
use Throwable;
use UnexpectedValueException;
use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
    BadRequestHttpException,
};
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\{
    Arr,
    Str,
    Facades\Log,
};

use App\Exceptions\{
    Auth\ConflictDataError,
    Auth\ReAuthenticationError,
    Auth\WrongPasswordError,
    Auth\SamePasswordError,
    Auth\UserLoginLockError,
    Auth\WrongSecurityCodeError,
    Auth\WrongMobileLengthError,
    Auth\WrongCaptchaError,
    Core\BadRequestError,
    Core\WrongRequestHeaderError,
    Verification\WrongCodeError,
    Verification\WrongEmailCodeError,
    Verification\WrongMobileCodeError,
    DuplicateRecordError,
    InvalidInvitationError,
};
use App\Http\Resources\{
    VerificationResource,
};
use App\Http\Requests\{
    LoginRequest,
    EmailVerificationRequest,
    MobileVerificationRequest,
    PasswordVerificationRequest,
    RegisterRequest,
    RecoverPasswordRequest,
    ResetPasswordRequest,
    CreateAuthRequest,
    RecoverSecurityCodeRequest,
    ResetEmailVerificationRequest,
    ResetMobileVerificationRequest,
    ResetEmailRequest,
    ResetMobileRequest,
    ActivateTFARequest,
    DeactivateTFARequest,
    RegisterDeviceTokenRequest,
    ChangeDeviceTokenRequest,
};
use App\Models\{
    Config,
    Group,
    Verification,
    UserLog,
    UserLock,
    DeviceToken,
};
use App\Notifications\{
    EmailVerification,
    MobileVerification,
    PasswordVerification,
    SecurityCodeVerification,
    ResetEmailVerification,
    ResetMobileVerification,
    DeactivateTFAVerification,
    LoginFailUserLockNotification,
};
use App\Repos\Interfaces\{
    ConfigRepo,
    UserRepo,
    VerificationRepo,
    AuthenticationRepo,
    GroupRepo,
    DeviceTokenRepo,
};
use App\Services\{
    TwoFactorAuthServiceInterface,
    CaptchaServiceInterface,
};

class AuthController extends ApiController
{
    use SecurityCodeTrait;

    public function __construct(
        ConfigRepo $cr,
        UserRepo $ur,
        VerificationRepo $vr,
        AuthenticationRepo $ar,
        GroupRepo $gr,
        DeviceTokenRepo $dtr,
        TwoFactorAuthServiceInterface $tfa,
        CaptchaServiceInterface $cs
    ) {
        parent::__construct();
        $this->ConfigRepo = $cr;
        $this->UserRepo = $ur;
        $this->VerificationRepo = $vr;
        $this->AuthenticationRepo = $ar;
        $this->GroupRepo = $gr;
        $this->DeviceTokenRepo = $dtr;
        $this->TwoFactorAuthService = $tfa;
        $this->CaptchaService = $cs;

        $this->middleware(
            'auth:api',
            ['only' => [
                'refresh',
                'logout',
                'sendSecurityCodeVerification',
                'resetPassword',
                'recoverSecurityCode',
                'uploadFiles',
                'requestVerifyIdentity',
                'sendResetEmailVerification',
                'resetEmail',
                'sendResetMobileVerification',
                'resetMobile',
                'preActivateTFA',
                'activateTFA',
                'sendDeactivateTFAVerification',
                'deactivateTFA',
                'changeDeviceTokenStatus',
            ]]
        );
        $this->middleware(
            'userlock',
            ['only' => [
                'refresh',
                'sendSecurityCodeVerification',
                'resetPassword',
                'recoverSecurityCode',
                'uploadFiles',
                'requestVerifyIdentity',
                'sendResetEmailVerification',
                'resetEmail',
                'sendResetMobileVerification',
                'resetMobile',
                'preActivateTFA',
                'activateTFA',
                'sendDeactivateTFAVerification',
                'deactivateTFA',
                'changeDeviceTokenStatus',
            ]]
        );
    }

    protected function genTokenInfo($access_token)
    {
        try {
            $payload = auth()->payload();
            $accessible_until = $payload['exp'] * 1000;
            $refreshable_until = $accessible_until + config('jwt.refresh_ttl') * 60 * 1000;
            return compact('access_token', 'accessible_until', 'refreshable_until');
        } catch (Throwable $e) {
            throw new BadRequestHttpException;
        }
    }

    protected function linkDeviceToken($request)
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }
        if ($request->headers->has('X-PLATFORM') and
            $request->headers->has('X-SERVICE') and
            $request->headers->has('X-DEVICE-TOKEN')
        ) {
            if (empty($request->header('X-DEVICE-TOKEN'))) {
                return;
            }
            if (!in_array($request->header('X-PLATFORM'), DeviceToken::PLATFORMS) or
                !in_array($request->header('X-SERVICE'), DeviceToken::SERVICES)
            ) {
                throw new WrongRequestHeaderError;
            }
            $data = [
                'platform' => $request->header('X-PLATFORM'),
                'service' => $request->header('X-SERVICE'),
                'token' => $request->header('X-DEVICE-TOKEN'),
            ];
            if ($token = $this->DeviceTokenRepo->getUnique($data)) {
                return $this->DeviceTokenRepo->update($token, [
                    'user_id' => $user->id,
                    'last_active_at' => Carbon::now(),
                ]);
            } else {
                return $this->DeviceTokenRepo
                    ->create(array_merge([
                        'user_id' => $user->id,
                        'last_active_at' => Carbon::now(),
                    ], $data));
            }
        }
    }

    protected function unlinkDeviceToken($request)
    {
        if ($request->headers->has('X-PLATFORM') and
            $request->headers->has('X-SERVICE') and
            $request->headers->has('X-DEVICE-TOKEN')
        ) {
            if (!in_array($request->header('X-PLATFORM'), DeviceToken::PLATFORMS) or
                !in_array($request->header('X-SERVICE'), DeviceToken::SERVICES)
            ) {
                throw new WrongRequestHeaderError;
            }
            $data = [
                'platform' => $request->header('X-PLATFORM'),
                'service' => $request->header('X-SERVICE'),
                'token' => $request->header('X-DEVICE-TOKEN'),
            ];
            if ($token = $this->DeviceTokenRepo->getUnique($data)) {
                return $this->DeviceTokenRepo->update($token, [
                    'user_id' => null,
                ]);
            }
            throw new BadRequestError;
        }
    }
    /**
     *  API Login, on success return JWT Auth token
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            # captcha
            if (config('services.captcha.key')) {
                $this->CaptchaService->verify($request->input('hcaptcha_response'));
            }

            $credentials = $request->only(['email', 'password']);
            #check user lock
            if ($user = $this->UserRepo->findByEmailOrFail($credentials['email'])) {
                if ($this->UserRepo->checkUserLock($user)) {
                    throw new UserLoginLockError;
                }
            }

            # try login
            if ($access_token = auth()->attempt($credentials)) {
                user_log(UserLog::LOG_IN, [], $request);
                user_log(UserLog::PASSWORD_SUCCESS, []);
                $this->linkDeviceToken($request);
                return $this->genTokenInfo($access_token);
            }
            # login fail
            $lock = $this->UserRepo->authEventRecordLock($user, UserLog::PASSWORD_FAIL);
            if ($lock) {
                $user->notify(new LoginFailUserLockNotification($lock));
            }

        } catch (UserLoginLockError $e) {
            throw $e;
        } catch (WrongCaptchaError $e) {
            throw $e;
        } catch (Throwable $e) {
            # supress all other exceptions
        }
        throw new BadRequestHttpException;
    }

    /**
     * Log the user out (Invalidate the token).
     *
     */
    public function logout(Request $request)
    {
        $this->unlinkDeviceToken($request);
        auth()->logout();
        return response(null, 204);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            $access_token = auth()->refresh();
            auth()->setToken($access_token)->user();
            return $this->genTokenInfo($access_token);
        } catch (Throwable $e) {
            throw new AccessDeniedHttpException('unable to refresh token');
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $user = auth()->user();

        $input = $request->validated();
        $credentials = [
            'email' => $user->email,
            'password' => $input['old_password'],
        ];

        if (!auth()->attempt($credentials)) {
            throw new WrongPasswordError;
        }

        if ($input['old_password'] === $input['password']) {
            throw new SamePasswordError;
        }

        $this->UserRepo->setPassword($user, $input['password']);
        return response(null, 204);
    }

    public function sendSecurityCodeVerification()
    {
        $user = auth()->user();
        $verification = $this->VerificationRepo->getOrCreate([
            'type' => Verification::TYPE_SECURITY_CODE,
            'data' => $user->email,
        ]);
        $this->VerificationRepo->notify($verification, $user, new SecurityCodeVerification($verification));
        return (new VerificationResource($verification))
            ->response()
            ->setStatusCode(201);
    }

    public function recoverSecurityCode(RecoverSecurityCodeRequest $request)
    {
        $validated = $request->validated();
        if (!$verification = $this->VerificationRepo->find(data_get($validated, 'verification_id'))) {
            throw new BadRequestError('verification not found');
        }

        $user = auth()->user();
        $this->VerificationRepo->verify(
            $verification,
            data_get($validated, 'verification_code'),
            $user->email,
            Verification::TYPE_SECURITY_CODE
        );

        if ($user->email !== $verification->data) {
            throw new BadRequestError('verification data error');
        }

        $this->UserRepo->setSecurityCode($user, data_get($validated, 'security_code'));

        return response(null, 204);
    }

    public function sendEmailVerification(EmailVerificationRequest $request) {
        if (auth()->user()) {
            throw new AccessDeniedHttpException('User already logged in');
        }
        if ($this->UserRepo->findByEmail($request->get('email'))) {
            throw new ConflictDataError;
        }
        $verification = $this->VerificationRepo->getOrCreate([
            'type' => Verification::TYPE_EMAIL,
            'data' => $request->get('email'),
        ]);
        $this->VerificationRepo->notify($verification, $verification, new EmailVerification($verification));
        return (new VerificationResource($verification))
            ->response()
            ->setStatusCode(201);
    }

    public function sendMobileVerification(MobileVerificationRequest $request) {
        if (auth()->user()) {
            throw new AccessDeniedHttpException('User already logged in');
        }
        $mobile = $this->checkMobile($request->get('mobile'));
        if ($this->UserRepo->findByMobile($mobile)) {
            throw new ConflictDataError;
        }
        $verification = $this->VerificationRepo->getOrCreate([
            'type' => Verification::TYPE_MOBILE,
            'data' => $mobile,
        ]);
        $this->VerificationRepo->notify($verification, $verification, new MobileVerification($verification));
        return (new VerificationResource($verification))
            ->response()
            ->setStatusCode(201);
    }

    public function register(RegisterRequest $request) {
        if (auth()->user()) {
            throw new AccessDeniedHttpException('User already logged in');
        }
        $validated = $request->validated();
        if (!$email_verification = $this->VerificationRepo->find(data_get($validated, 'email_verification_id'))) {
            throw new BadRequestError('email verification not found');
        }

        try {
            $this->VerificationRepo->verify(
                $email_verification,
                data_get($validated, 'email_verification_code'),
                data_get($validated, 'email'),
                Verification::TYPE_EMAIL
            );
        } catch (WrongCodeError $e) {
            throw new WrongEmailCodeError;
        }

        $validated['password'] = \Hash::make(data_get($validated, 'password'));

        # Use header locale if payload locale not existed.
        $validated['locale'] = data_get($validated, 'locale', \App::getLocale());

        #group
        if ($invitation_required = $this->ConfigRepo->get(Config::ATTRIBUTE_INVITATION_REQUIRED)) {
            if (!data_get($validated, 'invitation_code')) {
                throw new InvalidInvitationError;
            }
        }
        $validated['group_id'] = Group::DEFAULT_GROUP_ID;
        if ($invitation_code = data_get($validated, 'invitation_code')) {
            # check the code and add group_id to $validated
            $inv = $this->GroupRepo->getInvitationByCode($invitation_code);
            if ($inv &&
                ($inv->expired_at > Carbon::now()->format('Uv')) &&
                ($inv->used_at === null)
            ) {
                $validated['group_id'] = $inv->group_id;
                $validated['invitation_id'] = $inv->id;
            } else {
                throw new InvalidInvitationError;
            }
        }

        try {
            $user = $this->UserRepo->create($validated, $email_verification, null);
        } catch (Throwable $e) {
            throw new ConflictDataError;
        }

        # set invitation code used time
        if ($user->invitation) {
            $this->GroupRepo->setInvitationUsedTime($user->invitation);
        }

        $email_verification->verificable()->associate($user)->save();

        $access_token = auth()->login($user);
        $this->linkDeviceToken($request);
        return response()->json($this->genTokenInfo($access_token), 201);
    }

    public function sendPasswordVerification(PasswordVerificationRequest $request) {
        if (auth()->user()) {
            throw new AccessDeniedHttpException('User already logged in');
        }
        if ($user = $this->UserRepo->findByEmail($request->get('email'))) {
            $verification = $this->VerificationRepo->getOrCreate([
                'type' => Verification::TYPE_PASSWORD,
                'data' => $user->email,
            ], $user);
            $this->VerificationRepo->notify($verification, $user, new PasswordVerification($verification));
        } else {
            # Generate fake verification and return
            $verification = new Verification;
            $verification->id = (string)Str::orderedUuid();
            $verification->type = Verification::TYPE_PASSWORD;
            $verification->expired_at = Carbon::now()->addMinutes(
                Verification::timeout(Verification::TYPE_PASSWORD)
            );
            # NOTE: We don't save the verification here because it's a fake.
        }
        return (new VerificationResource($verification))
            ->response()
            ->setStatusCode(201);
    }

    public function recoverPassword(RecoverPasswordRequest $request) {
        if (auth()->user()) {
            throw new AccessDeniedHttpException('User already logged in');
        }
        $validated = $request->validated();
        if (!$verification = $this->VerificationRepo->find(data_get($validated, 'verification_id'))) {
            throw new BadRequestError('verification not found');
        }

        $this->VerificationRepo->verify(
            $verification,
            data_get($validated, 'verification_code'),
            data_get($validated, 'email'),
            Verification::TYPE_PASSWORD
        );

        $user = $verification->verificable;
        if ($user->email !== $verification->data) {
            throw new BadRequestError('verification data error');
        }

        $this->UserRepo->setPassword($user, data_get($validated, 'password'));

        return response(null, 204);
    }

    public function uploadFiles(Request $request)
    {
        # examine file type
        $input = $request->validate([
            'avatar' => 'required|file',
        ]);
        $file = $request->avatar;
        $accept_format = ['jpeg', 'jpg', 'png', 'pdf'];
        if (!in_array($file->guessExtension(), $accept_format)) {
            Log::debug('Unaccetable file extension: ' .$file->guessExtension());
            throw new BadRequestError('Unavailable upload file format');
        }

        $user = auth()->user();
        if ($user->is_verified) {
            throw new ReAuthenticationError;
        }
        $pre_path = config('core')['aws_cloud_storage']['user_authentication']['pre_path_name'];

        # store in s3
        $path = $file->store($pre_path, 's3');
        $auth_file = $this->AuthenticationRepo
            ->createAuthFile($user, ['url' => Str::after($path, $pre_path.'/')]);
        $auth_file['link'] = $path;
        return $auth_file;
    }

    public function requestVerifyIdentity(CreateAuthRequest $request)
    {
        $user = auth()->user();
        if ($user->is_verified) {
            throw new ReAuthenticationError;
        }
        $input = $request->validated();

        if (!$this->UserRepo->checkUsernameAvailability(data_get($input, 'username', ''), $user)) {
            throw new ConflictDataError;
        }
        $input['security_code'] = \Hash::make($input['security_code']);
        $file_ids = Arr::pull($input, 'file_ids');

        #create Auth model
        $auth = $this->AuthenticationRepo
            ->createAuth($user, $input);

        #associate auth_id to auth_file model
        $this->AuthenticationRepo->associateAuthId($auth, $file_ids);

        return response(null, 200);
    }

    public function sendResetEmailVerification(ResetEmailVerificationRequest $request)
    {
        $values = $request->validated();
        if ($this->UserRepo->findByEmail($values['email'])) {
            throw new DuplicateRecordError;
        }
        $user = auth()->user();
        $verification = $this->VerificationRepo->getOrCreate([
            'type' => Verification::TYPE_RESET_EMAIL,
            'data' => $values['email'],
        ], $user);
        $this->VerificationRepo->notify($verification, $user, new ResetEmailVerification($verification));
        return (new VerificationResource($verification))
            ->response()
            ->setStatusCode(201);
    }

    public function resetEmail(ResetEmailRequest $request)
    {
        $values = $request->validated();

        # check email duplication
        if ($this->UserRepo->findByEmail($values['email'])) {
            throw new DuplicateRecordError;
        }

        $user = auth()->user();
        # check security_code
        $this->checkSecurityCode($user, $values['security_code']);

        # verification confirm
        if (!$verification = $this->VerificationRepo->find($values['verification_id'])) {
            throw new BadRequestError('verification not found');
        }
        $this->VerificationRepo->verify(
            $verification,
            $values['verification_code'],
            $values['email'],
            Verification::TYPE_RESET_EMAIL
        );

        # reset email
        $this->UserRepo->update($user, ['email' => $values['email']]);
        return response(null, 204);
    }

    public function sendResetMobileVerification(ResetMobileVerificationRequest $request)
    {
        $value = $request->validated();
        $user = auth()->user();
        $verification = $this->VerificationRepo->getOrCreate([
            'type' => Verification::TYPE_RESET_MOBILE,
            'data' => $this->checkMobile($value['mobile']),
        ], $user);
        $this->VerificationRepo->notify($verification, $verification, new ResetMobileVerification($verification, $user->preferred_locale));
        return (new VerificationResource($verification))
            ->response()
            ->setStatusCode(201);
    }

    public function resetMobile(ResetMobileRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        # check security_code
        $this->checkSecurityCode($user, $validated['security_code']);

        # verification confirm
        if (!$verification = $this->VerificationRepo->find(data_get($validated, 'verification_id'))) {
            throw new BadRequestError('verification not found');
        }
        $validated['mobile'] = $this->checkMobile($validated['mobile']);
        $this->VerificationRepo->verify(
            $verification,
            data_get($validated, 'verification_code'),
            data_get($validated, 'mobile'),
            Verification::TYPE_RESET_MOBILE
        );

        # reset mobile
        $this->UserRepo->update($user, ['mobile' => $validated['mobile']]);
        return response(null, 204);

    }

    public function preActivateTFA()
    {
        $user = auth()->user();
        $response = $this->TwoFactorAuthService->preActivate($user);
        return response($response, 200);
    }

    public function activateTFA(ActivateTFARequest $request)
    {
        $input = $request->validated();

        $user = auth()->user();
        $this->checkSecurityCode($user, $input['security_code']);
        $this->TwoFactorAuthService->activate($user, $input['code']);
    }

    public function sendDeactivateTFAVerification()
    {
        $user = auth()->user();
        $verification = $this->VerificationRepo->getOrCreate([
            'type' => Verification::TYPE_DEACTIVATE_TFA,
            'data' => $user->email,
        ]);
        $this->VerificationRepo->notify($verification, $user, new DeactivateTFAVerification($verification));
        return (new VerificationResource($verification))
            ->response()
            ->setStatusCode(201);
    }

    public function deactivateTFA(DeactivateTFARequest $request)
    {
        $input = $request->validated();

        $user = auth()->user();
        # check security_code
        $this->checkSecurityCode($user, $input['security_code']);

        # verification confirm
        if (!$verification = $this->VerificationRepo->find($input['verification_id'])) {
            throw new BadRequestError('verification not found');
        }
        $this->VerificationRepo->verify(
            $verification,
            $input['verification_code'],
            $user->email,
            Verification::TYPE_DEACTIVATE_TFA
        );

        # deactivate
        $this->TwoFactorAuthService->deactivate($user, $input['code']);
        return response(null, 204);
    }

    public function changeDeviceTokenStatus(ChangeDeviceTokenRequest $request)
    {
        if (!$request->headers->has('X-PLATFORM') or
            !$request->headers->has('X-SERVICE') or
            !$request->headers->has('X-DEVICE-TOKEN') or
            !in_array($request->header('X-PLATFORM'), DeviceToken::PLATFORMS) or
            !in_array($request->header('X-SERVICE'), DeviceToken::SERVICES)
        ) {
            throw new WrongRequestHeaderError;
        }

        if ($token = $this->DeviceTokenRepo->getUnique([
            'platform' => $request->header('X-PLATFORM'),
            'service' => $request->header('X-SERVICE'),
            'token' => $request->header('X-DEVICE-TOKEN'),
        ], auth()->user())
        ) {
            $this->DeviceTokenRepo->changeActivation($token, $request->input('active'));
            return response(null, 200);
        }
        throw new BadRequestError;
    }

    protected function checkMobile(string $mobile)
    {
        if (!preg_match('/^((?=886)[0-9]+)$/', $mobile)) {
            return $mobile;
        }
        if (preg_match('/^((?=8869)[0-9]{12})$/', $mobile)) {
            return $mobile;
        } elseif (preg_match('/^((?=88609)[0-9]{13})$/', $mobile)) {
            return preg_replace('/^88609/', '8869', $mobile);
        } else {
            throw new WrongMobileLengthError;
        }
    }
}
