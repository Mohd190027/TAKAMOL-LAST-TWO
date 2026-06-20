<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $userId = Auth::id();

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email'     => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio'   => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'الاسم الكامل مطلوب',
            'full_name.string'   => 'الاسم الكامل يجب أن يكون نصاً',
            'full_name.max'      => 'الاسم الكامل يجب ألا يتجاوز 255 حرفاً',
            'email.required'     => 'البريد الإلكتروني مطلوب',
            'email.email'        => 'صيغة البريد الإلكتروني غير صحيحة',
            'email.max'          => 'البريد الإلكتروني يجب ألا يتجاوز 255 حرفاً',
            'email.unique'       => 'هذا البريد الإلكتروني مستخدم مسبقاً',
            'phone.string'       => 'رقم الجوال يجب أن يكون نصاً',
            'phone.max'          => 'رقم الجوال يجب ألا يتجاوز 30 حرفاً',
            'bio.string'         => 'نبذة تعريفية يجب أن تكون نصاً',
            'bio.max'            => 'نبذة تعريفية يجب ألا تتجاوز 5000 حرف',
        ];
    }
}
