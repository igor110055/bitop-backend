<?php

namespace App\Http\Controllers\Admin;

use Symfony\Component\HttpKernel\Exception\{
    MethodNotAllowedHttpException,
    BadRequestHttpException
};
use App\Exceptions\{
    Core\BadRequestError,
    Auth\WrongCaptchaError,
    Auth\WrongTFACodeError,
    Auth\UserLoginLockError,
};
use Illuminate\Http\Request;
use App\Services\{
    CaptchaServiceInterface,
    TwoFactorAuthServiceInterface,
};
use App\Models\UserLog;
use App\Repos\Interfaces\UserRepo;


class AuthController extends AdminController
{
    public function __construct(
        UserRepo $UserRepo,
        CaptchaServiceInterface $CaptchaService,
        TwoFactorAuthServiceInterface $TwoFactorAuthService
    ) {
        parent::__construct();
        $this->UserRepo = $UserRepo;
        $this->CaptchaService = $CaptchaService;
        $this->TwoFactorAuthService = $TwoFactorAuthService;
        $this->middleware('guest:web')->except('logout');
    }

    public function login(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('admin.login');
        }
        if ($request->isMethod('post')) {
            try {
                if (config('services.captcha.key')) {
                    $this->CaptchaService->verify($request->input('h-captcha-response'));
                }

                $values = $request->validate([
                    'email' => 'required|email',
                    'password' => 'required',
                    'code' => 'required',
                ]);
                $credentials = [
                    'email' => $values['email'],
                    'password' => $values['password'],
                ];

                # check user lock
                if ($user = $this->UserRepo->findByEmailOrFail($credentials['email'])) {
                    if ($this->UserRepo->checkAdminUserLock($user)) {
                        throw new UserLoginLockError;
                    }
                }

                # check user is admin
                if (!$user->is_admin) {
                    throw new BadRequestError;
                }
                # check user activate 2fa
                if (!$user->two_factor_auth) {
                    throw new BadRequestError;
                }

                # try login
                if ($this->auth()->attempt($credentials)) {
                    # check 2fa
                    if (!$this->TwoFactorAuthService->verify($user, $values['code'])) {
                        $this->auth()->logout();
                        $lock = $this->UserRepo->authEventRecordLock($user, UserLog::ADMIN_LOG_IN_2FA_FAIL);
                        throw new WrongTFACodeError;
                    }
                    user_log(UserLog::ADMIN_LOG_IN, [], $request);
                    return redirect()->intended('admin');
                }
                $lock = $this->UserRepo->authEventRecordLock($user, UserLog::ADMIN_LOG_IN_PASSWORD_FAIL);
            } catch (BadRequestHttpException $e) {
                return redirect()
                    ->route('login')
                    ->with('email', $credentials['email'])
                    ->with('error', 'please finish the security validation');
            } catch (WrongCaptchaError $e) {
                return redirect()
                    ->route('login')
                    ->with('error', $e->getMessage());
            } catch (UserLoginLockError $e) {
                return redirect()
                    ->route('login')
                    ->with('error', $e->getMessage());
            } catch (BadRequestError $e) {
                return redirect()
                    ->route('login')
                    ->with('error', $e->getMessage());
            } catch (WrongTFACodeError $e) {
                return redirect()
                    ->route('login')
                    ->with('error', $e->getMessage());
            }
            return redirect()
                ->route('login')
                ->with('email', $credentials['email'])
                ->with('error', 'failed to login');
        }
        return new MethodNotAllowedHttpException();
    }

    public function logout()
    {
        $this->auth()->logout();
        return redirect()->route('login');
    }

    protected function auth()
    {
        return \Auth::guard('web');
    }
}
