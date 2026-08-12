<?php

namespace App\Controllers;

use App\Middleware\AuthGuard;
use App\Services\AuthException;
use App\Services\AuthService;
use App\Support\Session;
use App\Support\View;

/**
 * Handles the POST actions behind the forms already built into app/Views/auth/*.php
 * (Phase 0). Every method either redirects on success or re-renders the same view with an
 * inline error, matching the `$loginError` pattern login.php already expects.
 */
final class AuthController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    /**
     * Surfaces the flash error set by public/index.php's central CSRF check (Phase 12) —
     * a rejected POST redirects here with no other way to reach $loginError.
     */
    public function loginPageData(): array
    {
        return ['loginError' => Session::pullFlashError()];
    }

    public function register(): void
    {
        try {
            $this->auth->register($_POST);
            header('Location: /pending-approval');
            exit;
        } catch (AuthException $e) {
            View::render('auth/register', 'guest', [
                'pageTitle' => 'สมัครสมาชิกนักศึกษา - IMS THAI',
                'registerError' => $e->getMessage(),
            ]);
        }
    }

    public function login(): void
    {
        $identifier = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            $sessionUser = $this->auth->login($identifier, $password, $ip);
            Session::login($sessionUser);
            header('Location: ' . AuthGuard::dashboardPathFor($sessionUser['role']));
            exit;
        } catch (AuthException $e) {
            View::render('auth/login', 'guest', [
                'pageTitle' => 'เข้าสู่ระบบ - IMS THAI',
                'loginError' => $e->getMessage(),
            ]);
        }
    }

    public function logout(): void
    {
        $this->auth->logout();
        header('Location: /login');
        exit;
    }

    public function forgotPassword(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email !== '') {
            $this->auth->forgotPassword($email);
        }
        View::render('auth/forgot_password', 'guest', [
            'pageTitle' => 'ลืมรหัสผ่าน - IMS THAI',
            'message' => 'ถ้าอีเมลนี้มีอยู่ในระบบ เราได้ส่งลิงก์สำหรับตั้งรหัสผ่านใหม่ไปให้ทางอีเมลแล้ว',
        ]);
    }

    public function resetPassword(): void
    {
        $accessToken = (string) ($_POST['access_token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($accessToken === '') {
            View::render('auth/reset_password', 'guest', [
                'pageTitle' => 'ตั้งรหัสผ่านใหม่ - IMS THAI',
                'resetError' => 'ลิงก์ไม่ถูกต้องหรือหมดอายุ กรุณาขอลิงก์ใหม่จากหน้าลืมรหัสผ่าน',
            ]);
        }
        if ($password !== $confirmation) {
            View::render('auth/reset_password', 'guest', [
                'pageTitle' => 'ตั้งรหัสผ่านใหม่ - IMS THAI',
                'resetError' => 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน',
            ]);
        }

        try {
            $this->auth->resetPassword($accessToken, $password);
            View::render('auth/reset_password', 'guest', [
                'pageTitle' => 'ตั้งรหัสผ่านใหม่ - IMS THAI',
                'resetSuccess' => true,
            ]);
        } catch (AuthException $e) {
            View::render('auth/reset_password', 'guest', [
                'pageTitle' => 'ตั้งรหัสผ่านใหม่ - IMS THAI',
                'resetError' => $e->getMessage(),
            ]);
        }
    }
}
