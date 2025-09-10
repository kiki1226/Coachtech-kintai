<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\{
    AttendanceController,
    RequestController,
    Auth\LoginController,
    Auth\RegisterController
};
use App\Http\Controllers\Admin\{
    AdminAttendanceController,
    AdminUserController,
    AdminRequestController,
    AdminLoginController
};

// -------------------- トップ --------------------
Route::get('/', function () {
    if (!auth()->check()) return to_route('login');
    return auth()->user()->can('manage')
        ? to_route('admin.attendances.index')
        : to_route('attendance.register');
});

// -------------------- ゲスト（ログイン/登録） --------------------
Route::middleware('guest')->group(function () {
    // login
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    // register
    Route::get('/register',  [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
});

// -------------------- ログアウト（ログイン必須） --------------------
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// -------------------- 一般ユーザー（要ログイン） --------------------
Route::middleware(['auth'])->group(function () {

    // 勤怠：固定パスを先に、可変は最後に
    Route::prefix('attendance')->name('attendance.')->group(function () {
        // 固定
        Route::get('register', [AttendanceController::class, 'register'])->name('register');
        Route::get('/',        [AttendanceController::class, 'index'])->name('index');

        Route::post('clock-in',    [AttendanceController::class, 'clockIn'])->name('clock_in');
        Route::post('clock-out',   [AttendanceController::class, 'clockOut'])->name('clock_out');
        Route::post('break-start', [AttendanceController::class, 'breakStart'])->name('break_start');
        Route::post('break-end',   [AttendanceController::class, 'breakEnd'])->name('break_end');

        // 可変（最後に寄せる & 制約を付ける）
        Route::get('{attendance}/edit', [AttendanceController::class, 'edit'])
            ->whereNumber('attendance')->name('edit');

        Route::patch('{attendance}', [AttendanceController::class, 'update'])
            ->whereNumber('attendance')->name('update');

        Route::get('{attendance}', [AttendanceController::class, 'show'])
            ->whereNumber('attendance')->name('show');
    });

    // 申請
    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [RequestController::class, 'index'])->name('index');
        Route::get('/create/{type}', [RequestController::class, 'create'])->name('create');
        Route::post('/{type}', [RequestController::class, 'store'])->name('store');
        Route::get('/{requestItem}', [RequestController::class, 'show'])
            ->whereNumber('requestItem')->name('show');
    });

    // メール認証（verified の外）
    Route::view('/email/verify', 'auth.verify')->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('attendance.register');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', '認証メールを再送しました。');
    })->middleware('throttle:6,1')->name('verification.send');
});

// -------------------- 管理者（公開：未ログインOK） --------------------
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',  [AdminLoginController::class, 'create'])->name('login.form');
    Route::post('login', [AdminLoginController::class, 'store'])->name('login.post');
});

// -------------------- 管理者（要ログイン＋権限） --------------------
Route::prefix('admin')->name('admin.')->middleware(['auth','admin'])->group(function () {
    Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
    Route::get('/', fn () => redirect()->route('admin.attendances.index'))->name('dashboard');

    // 勤怠
    Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendances/user/{user}', [AdminAttendanceController::class, 'user'])->name('attendances.user');
    Route::get('/attendances/{user}/{date}', [AdminAttendanceController::class, 'showDay'])
        ->where('date', '\d{4}-\d{2}-\d{2}')->name('attendances.show');
    Route::put('/attendances/{user}/{date}', [AdminAttendanceController::class, 'updateDay'])
        ->where('date', '\d{4}-\d{2}-\d{2}')->name('attendances.update');

    // 申請
    Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/{request}', [AdminRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{request}/approve', [AdminRequestController::class, 'approve'])->name('requests.approve');
    Route::post('/requests/{request}/reject',  [AdminRequestController::class, 'reject'])->name('requests.reject');

    // ユーザー管理
    Route::get('/users', [AdminUserController::class, 'staffIndex'])->name('users.index');
    Route::get('admins',           [AdminUserController::class, 'adminIndex'])->name('admins.index');
    Route::post('admins',          [AdminUserController::class, 'store'])->name('admins.store');
    Route::post('admins/promote',  [AdminUserController::class, 'promote'])->name('admins.promote');
    Route::patch('admins/{user}/demote', [AdminUserController::class, 'demote'])->name('admins.demote');
});
