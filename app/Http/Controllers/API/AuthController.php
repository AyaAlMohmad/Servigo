<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\PendingRegistration;
use App\Models\Provider;
use App\Models\RefreshToken;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Storage;
class AuthController extends Controller
{
    private function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendOtp($email, $code, $type)
    {
        Mail::to($email)->send(new OtpMail($code, $type));
    }

    private function createRefreshToken($userId): string
    {
        $token = Str::random(80);
        RefreshToken::create([
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'expires_at' => Carbon::now()->addDays(1),
            'revoked' => false,
        ]);
        return $token;
    }

    private function generateTokens($user)
    {
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $refreshToken = $this->createRefreshToken($user->id);
        return [$accessToken, $refreshToken];
    }

    public function mainServices()
    {
        $services = Service::all(['id', 'name_ar', 'name_en', 'photo']);
        return response()->json(['success' => true, 'data' => $services]);
    }

    public function registerUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email|unique:users,email|unique:pending_registrations,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
        ]);

        $otp = $this->generateOtp();
        $expiresAt = Carbon::now()->addMinutes(30);

        $pending = PendingRegistration::updateOrCreate(
            ['email' => $validated['email']],
            [
                'data' => json_encode($validated),
                'role' => 'user',
                'otp_code' => bcrypt($otp),
                'otp_expires_at' => $expiresAt,
                'otp_attempts' => 0,
            ]
        );

        $this->sendOtp($validated['email'], $otp, 'register');

        return response()->json([
            'success' => true,
            'message' => 'otp_sent',
            'data' => ['email' => $validated['email']]
        ], 201);
    }

    public function registerProvider(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email|unique:users,email|unique:pending_registrations,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
            'location_name' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'work_type' => 'required|in:fixed,mobile,both',
            'main_service_id' => 'required|exists:services,id',
            'id_photo_front' => 'required|image|mimes:jpeg,jpg,png|max:5120',
            'id_photo_back' => 'required|image|mimes:jpeg,jpg,png|max:5120',
        ]);

      // استبدل هذين السطرين
// $frontPath = $request->file('id_photo_front')->store('providers/id_photos', 'public');
// $backPath = $request->file('id_photo_back')->store('providers/id_photos', 'public');

// بـ:
$frontFile = $request->file('id_photo_front');
if (!$frontFile || !$frontFile->isValid()) {
    return response()->json(['message' => 'Invalid front photo'], 422);
}
$frontPath = 'providers/id_photos/' . uniqid() . '_' . $frontFile->getClientOriginalName();
Storage::disk('local')->put($frontPath, file_get_contents($frontFile->getPathname()));

$backFile = $request->file('id_photo_back');
if (!$backFile || !$backFile->isValid()) {
    return response()->json(['message' => 'Invalid back photo'], 422);
}
$backPath = 'providers/id_photos/' . uniqid() . '_' . $backFile->getClientOriginalName();
Storage::disk('local')->put($backPath, file_get_contents($backFile->getPathname()));
        $data = $validated;
        $data['id_photo_front'] = $frontPath;
        $data['id_photo_back'] = $backPath;

        $otp = $this->generateOtp();
        $expiresAt = Carbon::now()->addMinutes(30);

        PendingRegistration::updateOrCreate(
            ['email' => $validated['email']],
            [
                'data' => json_encode($data),
                'role' => 'provider',
                'otp_code' => bcrypt($otp),
                'otp_expires_at' => $expiresAt,
                'otp_attempts' => 0,
            ]
        );

        $this->sendOtp($validated['email'], $otp, 'register');

        return response()->json([
            'success' => true,
            'message' => 'otp_sent',
            'data' => ['email' => $validated['email']]
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'type' => 'required|in:register,login,forgot_password,delete_account,change_password',
        ]);

        if ($request->type === 'register') {
            return $this->handleRegisterOtp($request->email, $request->code);
        }

        if ($request->type === 'login') {
            return $this->handleLoginOtp($request->email, $request->code);
        }

        if ($request->type === 'forgot_password') {
            return $this->handleForgotPasswordOtp($request->email, $request->code);
        }
if ($request->type === 'delete_account') {
    return $this->handleDeleteAccountOtp($request->email, $request->code);
}
        // يمكنك إضافة الأنواع الأخرى بنفس المنطق
        return response()->json(['success' => false, 'message' => 'type_not_supported'], 422);
    }
public function deleteAccountRequest(Request $request)
{
    $user = $request->user();
    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    // إرسال OTP للتأكيد
    $otp = $this->generateOtp();
    $expiresAt = Carbon::now()->addSeconds(120);
    OtpVerification::updateOrCreate(
        ['email' => $user->email, 'type' => 'delete_account'],
        [
            'code' => bcrypt($otp),
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'is_verified' => false,
        ]
    );
    $this->sendOtp($user->email, $otp, 'delete_account');

    return response()->json([
        'success' => true,
        'message' => 'otp_sent',
        'data' => ['email' => $user->email]
    ]);
}

private function handleDeleteAccountOtp($email, $code)
{
    $otpRecord = OtpVerification::where('email', $email)
        ->where('type', 'delete_account')
        ->first();

    if (!$otpRecord) {
        return response()->json(['message' => 'otp_invalid'], 422);
    }

    if ($otpRecord->attempts >= 5) {
        return response()->json(['message' => 'otp_max_attempts'], 422);
    }

    if (Carbon::now()->gt($otpRecord->expires_at)) {
        return response()->json(['message' => 'otp_expired'], 422);
    }

    if (!Hash::check($code, $otpRecord->code)) {
        $otpRecord->increment('attempts');
        return response()->json(['message' => 'otp_invalid'], 422);
    }

    // التحقق ناجح – نقوم بحذف الحساب
    $user = User::where('email', $email)->first();
    if (!$user) {
        return response()->json(['message' => 'user_not_found'], 404);
    }

    // حذف توكنات الوصول الحالية
    $user->tokens()->delete();
    RefreshToken::where('user_id', $user->id)->update(['revoked' => true]);

    // تطبيق soft delete أو force delete حسب الدور
    if ($user->role === 'user') {
        // زبون: حذف نهائي فوري
        $user->forceDelete();
        $message = 'account_permanently_deleted';
    } else {
        // مزود: soft delete أولاً – سيتم forceDelete بعد 30 يوم عبر Job
        $user->delete(); // يضيف deleted_at فقط
        $message = 'account_soft_deleted_will_be_permanently_removed_after_30_days';
    }

    // حذف سجل OTP المستخدم
    $otpRecord->delete();

    return response()->json([
        'success' => true,
        'message' => $message,
        'data' => null
    ]);
}
    private function handleRegisterOtp($email, $code)
    {
        $pending = PendingRegistration::where('email', $email)->first();
        if (!$pending) {
            return response()->json(['success' => false, 'message' => 'otp_invalid'], 422);
        }

        if (Carbon::now()->gt($pending->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'otp_expired'], 422);
        }

        if ($pending->otp_attempts >= 5) {
            return response()->json(['success' => false, 'message' => 'otp_max_attempts'], 422);
        }

        if (!Hash::check($code, $pending->otp_code)) {
            $pending->increment('otp_attempts');
            return response()->json(['success' => false, 'message' => 'otp_invalid'], 422);
        }

        // إنشاء الحساب بشكل دائم
        $data = json_decode($pending->data, true);
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $email,
            'password' => Hash::make($data['password']),
            'role' => $pending->role,
        ]);

        if ($pending->role === 'provider') {
            Provider::create([
                'user_id' => $user->id,
                'location_name' => $data['location_name'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'work_type' => $data['work_type'],
                'main_service_id' => $data['main_service_id'],
                'id_photo_front' => $data['id_photo_front'],
                'id_photo_back' => $data['id_photo_back'],
                'status' => 'pending',
                'profile_completed' => false,
            ]);
        }

        $pending->delete();

        // توليد التوكنات
        list($accessToken, $refreshToken) = $this->generateTokens($user);

        // تحديد response بناءً على role و status و profile_completed
        if ($user->role === 'user') {
            return response()->json([
                'success' => true,
                'message' => 'otp_verified',
                'data' => [
                    'token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'role' => 'user',
                    'is_banned' => $user->is_banned,
                    'message' => 'welcome',
                ]
            ]);
        }

        // provider
        $provider = $user->provider;
        if ($provider->status === 'rejected') {
            return response()->json([
                'success' => true,
                'message' => 'otp_verified',
                'data' => [
                    'token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'role' => 'provider',
                    'status' => 'rejected',
                    'profile_completed' => false,
                    'is_banned' => $user->is_banned,
                    'message' => 'account_rejected',
                    'rejection_reason' => $provider->rejection_reason,
                ]
            ]);
        }

        if ($provider->status === 'pending') {
            return response()->json([
                'success' => true,
                'message' => 'otp_verified',
                'data' => [
                    'token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'role' => 'provider',
                    'status' => 'pending',
                    'profile_completed' => false,
                    'is_banned' => $user->is_banned,
                    'message' => 'account_under_review',
                ]
            ]);
        }

        // approved
        if (!$provider->profile_completed) {
            return response()->json([
                'success' => true,
                'message' => 'otp_verified',
                'data' => [
                    'token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'role' => 'provider',
                    'status' => 'approved',
                    'profile_completed' => false,
                    'is_banned' => $user->is_banned,
                    'message' => 'complete_your_profile',
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'otp_verified',
            'data' => [
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
                'role' => 'provider',
                'status' => 'approved',
                'profile_completed' => true,
                'is_banned' => $user->is_banned,
                'message' => 'welcome',
            ]
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:register,login,forgot_password,change_password,delete_account',
        ]);

        // حالة التسجيل: استخدام جدول pending_registrations
        if ($request->type === 'register') {
            $pending = PendingRegistration::where('email', $request->email)->first();
            if (!$pending) {
                return response()->json(['success' => false, 'message' => 'email_not_found'], 404);
            }
            $newOtp = $this->generateOtp();
            $pending->update([
                'otp_code' => bcrypt($newOtp),
                'otp_expires_at' => Carbon::now()->addMinutes(30),
                'otp_attempts' => 0,
            ]);
            $this->sendOtp($request->email, $newOtp, 'register');
            return response()->json([
                'success' => true,
                'message' => 'otp_sent',
                'data' => ['email' => $request->email]
            ]);
        }

        // باقي الأنواع: login, forgot_password, change_password, delete_account
        // استخدام جدول otp_verifications
        $otpRecord = OtpVerification::where('email', $request->email)
            ->where('type', $request->type)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'no_active_otp_request'
            ], 404);
        }

        $newOtp = $this->generateOtp();
        $otpRecord->update([
            'code' => bcrypt($newOtp),
            'expires_at' => Carbon::now()->addMinutes(30),
            'attempts' => 0,
            'is_verified' => false,
            'verified_at' => null,
        ]);

        $this->sendOtp($request->email, $newOtp, $request->type);

        return response()->json([
            'success' => true,
            'message' => 'otp_sent',
            'data' => ['email' => $request->email]
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'invalid_credentials'], 401);
        }

        if ($user->is_banned) {
            return response()->json(['message' => 'account_banned'], 403);
        }

        // إرسال OTP
        $otp = $this->generateOtp();
        $expiresAt = Carbon::now()->addMinutes(30);
        OtpVerification::updateOrCreate(
            ['email' => $user->email, 'type' => 'login'],
            [
                'code' => bcrypt($otp),
                'expires_at' => $expiresAt,
                'attempts' => 0,
                'is_verified' => false,
            ]
        );
        $this->sendOtp($user->email, $otp, 'login');

        $responseData = [
            'email' => $user->email,
            'is_banned' => $user->is_banned,
        ];

        // إضافة حقول إضافية للمزود
        if ($user->role === 'provider') {
            $provider = $user->provider;
            $responseData['status'] = $provider->status;
            $responseData['profile_completed'] = $provider->profile_completed;
        }

        return response()->json([
            'success' => true,
            'message' => 'otp_sent',
            'data' => $responseData
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'email_not_found'], 404);
        }

        $otp = $this->generateOtp();
        $expiresAt = Carbon::now()->addMinutes(30);
        OtpVerification::updateOrCreate(
            ['email' => $user->email, 'type' => 'forgot_password'],
            [
                'code' => bcrypt($otp),
                'expires_at' => $expiresAt,
                'attempts' => 0,
                'is_verified' => false,
            ]
        );
        $this->sendOtp($user->email, $otp, 'forgot_password');

        return response()->json(['success' => true, 'message' => 'otp_sent', 'data' => ['email' => $user->email]]);
    }

    private function handleForgotPasswordOtp($email, $code)
    {
        $otpRecord = OtpVerification::where('email', $email)
            ->where('type', 'forgot_password')
            ->first();
        if (!$otpRecord) {
            return response()->json(['message' => 'otp_invalid'], 422);
        }

        if ($otpRecord->attempts >= 5) {
            return response()->json(['message' => 'otp_max_attempts'], 422);
        }

        if (Carbon::now()->gt($otpRecord->expires_at)) {
            return response()->json(['message' => 'otp_expired'], 422);
        }

        if (!Hash::check($code, $otpRecord->code)) {
            $otpRecord->increment('attempts');
            return response()->json(['message' => 'otp_invalid'], 422);
        }

        $otpRecord->update(['is_verified' => true, 'verified_at' => Carbon::now()]);

        return response()->json(['success' => true, 'message' => 'otp_verified', 'data' => null]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
        ]);

        $otpRecord = OtpVerification::where('email', $request->email)
            ->where('type', 'forgot_password')
            ->where('is_verified', true)
            ->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'otp_not_verified'], 403);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'email_not_found'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // حذف جميع التوكنات القديمة (اختياري)
        $user->tokens()->delete();
        RefreshToken::where('user_id', $user->id)->update(['revoked' => true]);

        // حذف سجل OTP
        $otpRecord->delete();

        return response()->json(['success' => true, 'message' => 'password_reset_success', 'data' => null]);
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->bearerToken();
        if (!$refreshToken) {
            return response()->json(['message' => 'refresh_token_invalid'], 401);
        }

        $hashed = hash('sha256', $refreshToken);
        $tokenRecord = RefreshToken::where('token', $hashed)
            ->where('revoked', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$tokenRecord) {
            return response()->json(['message' => 'refresh_token_expired'], 401);
        }

        $user = User::find($tokenRecord->user_id);
        if (!$user) {
            return response()->json(['message' => 'refresh_token_invalid'], 401);
        }

        // إلغاء التوكن المستخدم
        $tokenRecord->revoked = true;
        $tokenRecord->save();

        // إنشاء توكنات جديدة
        list($newAccessToken, $newRefreshToken) = $this->generateTokens($user);

        return response()->json([
            'success' => true,
            'message' => 'token_refreshed',
            'data' => [
                'token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // حذف التوكن الحالي
            $request->user()->currentAccessToken()->delete();

            // حذف refresh tokens المرتبطة
            RefreshToken::where('user_id', $user->id)->update(['revoked' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'logout_success',
            'data' => null
        ]);
    }
    private function handleLoginOtp($email, $code)
{
    $otpRecord = OtpVerification::where('email', $email)
        ->where('type', 'login')
        ->first();

    if (!$otpRecord) {
        return response()->json(['message' => 'otp_invalid'], 422);
    }

    if ($otpRecord->attempts >= 5) {
        return response()->json(['message' => 'otp_max_attempts'], 422);
    }

    if (Carbon::now()->gt($otpRecord->expires_at)) {
        return response()->json(['message' => 'otp_expired'], 422);
    }

    if (!Hash::check($code, $otpRecord->code)) {
        $otpRecord->increment('attempts');
        return response()->json(['message' => 'otp_invalid'], 422);
    }

    // التحقق ناجح، نحذف سجل OTP
    $otpRecord->delete();

    // جلب المستخدم
    $user = User::where('email', $email)->first();
    if (!$user) {
        return response()->json(['message' => 'user_not_found'], 404);
    }

    if ($user->is_banned) {
        return response()->json(['message' => 'account_banned'], 403);
    }

    // توليد التوكنات
    list($accessToken, $refreshToken) = $this->generateTokens($user);

    // استجابة حسب الدور
    if ($user->role === 'user') {
        return response()->json([
            'success' => true,
            'message' => 'otp_verified',
            'data' => [
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
                'role' => 'user',
                'is_banned' => $user->is_banned,
                'message' => 'welcome'
            ]
        ]);
    }

    // role = provider
    $provider = $user->provider;
    if ($provider->status === 'rejected') {
        return response()->json([
            'success' => true,
            'message' => 'otp_verified',
            'data' => [
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
                'role' => 'provider',
                'status' => 'rejected',
                'profile_completed' => false,
                'is_banned' => $user->is_banned,
                'message' => 'account_rejected',
                'rejection_reason' => $provider->rejection_reason,
            ]
        ]);
    }

    if ($provider->status === 'pending') {
        return response()->json([
            'success' => true,
            'message' => 'otp_verified',
            'data' => [
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
                'role' => 'provider',
                'status' => 'pending',
                'profile_completed' => false,
                'is_banned' => $user->is_banned,
                'message' => 'account_under_review',
            ]
        ]);
    }

    // approved
    if (!$provider->profile_completed) {
        return response()->json([
            'success' => true,
            'message' => 'otp_verified',
            'data' => [
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
                'role' => 'provider',
                'status' => 'approved',
                'profile_completed' => false,
                'is_banned' => $user->is_banned,
                'message' => 'complete_your_profile',
            ]
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'otp_verified',
        'data' => [
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
            'role' => 'provider',
            'status' => 'approved',
            'profile_completed' => true,
            'is_banned' => $user->is_banned,
            'message' => 'welcome',
        ]
    ]);
}
}
