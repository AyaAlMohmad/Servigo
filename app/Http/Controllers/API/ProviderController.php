<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\OffDay;
use App\Models\Certificate;
use App\Models\Portfolio;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;

class ProviderController extends Controller
{

    public function subServices(Request $request)
    {
        $provider = $request->user()->provider;

        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        $Service = Service::with('subServices')->find($provider->main_service_id);

        if (!$Service) {
            return response()->json(['message' => 'Main service not found'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => [
                'main_service' => [
                    'id' => $Service->id,
                    'name_ar' => $Service->name_ar,
                    'name_en' => $Service->name_en,
                    'sub_services' => $Service->subServices->map(fn($sub) => [
                        'id' => $sub->id,
                        'name_ar' => $sub->name_ar,
                        'name_en' => $sub->name_en,
                    ]),
                ],
            ],
        ]);
    }

    // 2. إكمال البروفايل
    public function completeProfile(Request $request)
    {
        $user = $request->user();
        $provider = $user->provider;

        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        if ($provider->profile_completed) {
            return response()->json(['message' => 'profile_already_completed'], 403);
        }

        // التحقق من صحة البيانات مع تسجيل الأخطاء
        try {
            $validated = $request->validate([
                'sub_service_id' => 'required|exists:sub_services,id',
            'location_description' => 'required|string',
            'currency' => 'required|string|size:3',
            'min_price' => 'required|numeric|min:0.01',
            'max_price' => 'required|numeric|gte:min_price',
            'work_start_time' => 'required|date_format:H:i',
            'work_end_time' => 'required|date_format:H:i',
            'off_days' => 'required|array',
            'off_days.*' => 'string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'about_me' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'certificates' => 'nullable|array',
            'certificates.*' => 'image|mimes:jpeg,jpg,png|max:5120',
            'portfolio' => 'nullable|array',
            'portfolio.*.file' => 'required|file|mimes:jpeg,jpg,png,mp4,mov|max:51200', // 50MB
            'portfolio.*.description' => 'nullable|string',
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // التأكد من أن sub_service_id يتبع main_service_id الخاص بالمزود
        $subService = \App\Models\SubService::find($validated['sub_service_id']);
        if (!$subService || $subService->service_id != $provider->main_service_id) {
            return response()->json(['message' => 'sub_service_id_invalid'], 422);
        }

        DB::beginTransaction();

        try {
            // تحديث جدول providers
            $provider->update([
                'sub_service_id' => $validated['sub_service_id'],
                'location_description' => $validated['location_description'],
                'currency' => $validated['currency'],
                'min_price' => $validated['min_price'],
                'max_price' => $validated['max_price'],
                'work_start_time' => $validated['work_start_time'],
                'work_end_time' => $validated['work_end_time'],
                'about_me' => $validated['about_me'] ?? null,
                'profile_completed' => true,
            ]);

            // تحديث overnight تلقائياً (سيتم في model boot)
            $provider->refresh();

            // إدارة أيام العطلة: حذف القديمة وإضافة الجديدة
            OffDay::where('provider_id', $provider->id)->delete();
            foreach ($validated['off_days'] as $day) {
                OffDay::create([
                    'provider_id' => $provider->id,
                    'day' => $day,
                ]);
            }

            // تحديث صورة المستخدم إن وُجدت
            $photoFile = $request->file('photo');
            if ($photoFile && $photoFile->isValid()) {
                $photoPath = 'users/photos/' . uniqid() . '_' . $photoFile->getClientOriginalName();
                Storage::disk('public')->put($photoPath, file_get_contents($photoFile->getPathname()));
                // حذف الصورة القديمة إن وجدت
                if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                    Storage::disk('public')->delete($user->photo);
                }
                $user->photo = $photoPath;
                $user->save();
            }

            // رفع الشهادات
            if ($request->hasFile('certificates')) {
                foreach ($request->file('certificates') as $certFile) {
                    if ($certFile && $certFile->isValid()) {
                        $path = 'providers/certificates/' . uniqid() . '_' . $certFile->getClientOriginalName();
                        Storage::disk('public')->put($path, file_get_contents($certFile->getPathname()));
                        Certificate::create([
                            'provider_id' => $provider->id,
                            'file_path' => $path,
                        ]);
                    }
                }
            }

            // رفع معرض الأعمال (portfolio)
            if ($request->has('portfolio')) {
                foreach ($request->input('portfolio') as $index => $item) {
                    $file = $request->file("portfolio.{$index}.file");
                    if ($file && $file->isValid()) {
                        $path = 'providers/portfolio/' . uniqid() . '_' . $file->getClientOriginalName();
                        Storage::disk('public')->put($path, file_get_contents($file->getPathname()));
                        $type = in_array($file->getClientOriginalExtension(), ['mp4', 'mov']) ? 'video' : 'image';
                        Portfolio::create([
                            'provider_id' => $provider->id,
                            'file_path' => $path,
                            'file_type' => $type,
                            'description' => $item['description'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'profile_completed',
                'data' => null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }
}
