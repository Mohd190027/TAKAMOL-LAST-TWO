<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UploadAvatarRequest extends FormRequest
{
    /**
     * تحقق من صلاحية المستخدم لتنفيذ هذا الطلب
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * قواعد التحقق
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'image', 'max:2048'],
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'avatar.required' => 'يرجى اختيار صورة',
            'avatar.file'     => 'المرفق يجب أن يكون ملفاً',
            'avatar.image'    => 'الملف يجب أن يكون صورة',
            'avatar.max'      => 'الحد الأقصى للصورة 2 ميجابايت',
        ];
    }
}
