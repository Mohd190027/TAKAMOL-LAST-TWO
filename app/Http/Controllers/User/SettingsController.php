<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UploadAvatarRequest;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function uploadAvatar(UploadAvatarRequest $request)
    {
        $user = Auth::user();
        $path = $request->file('avatar')->store('avatars', 'public');

        // Delete old avatar if exists
        if (!empty($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = $path;
        $user->save();

        return response()->json([
            'success'    => true,
            'message'    => 'تم تحديث الصورة',
            'avatar_url' => asset('storage/' . $path),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        $user->full_name = $validated['full_name'];
        $user->email     = $validated['email'];
        $user->phone     = $validated['phone'] ?? null;
        $user->bio       = $validated['bio']   ?? null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ بيانات الملف الشخصي',
            'user'    => [
                'full_name'  => $user->full_name,
                'email'      => $user->email,
                'phone'      => $user->phone ?? null,
                'bio'        => $user->bio   ?? null,
                'avatar_url' => $user->avatar_path
                    ? asset('storage/' . $user->avatar_path)
                    : null,
            ],
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $validated = $request->validated();

        $user = Auth::user();
        $user->password_hash = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث كلمة المرور بنجاح',
        ]);
    }
}
