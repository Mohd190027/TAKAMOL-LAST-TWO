<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdatePasswordRequest extends FormRequest
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
            'new_password'     => ['required', 'string', 'min:8', 'max:255'],
            'confirm_password' => ['required', 'same:new_password'],
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'new_password.required'     => 'كلمة المرور الجديدة مطلوبة',
            'new_password.string'       => 'كلمة المرور يجب أن تكون نصاً',
            'new_password.min'          => 'كلمة المرور يجب ألا تقل عن 8 أحرف',
            'new_password.max'          => 'كلمة المرور يجب ألا تتجاوز 255 حرفاً',
            'confirm_password.required' => 'تأكيد كلمة المرور مطلوب',
            'confirm_password.same'     => 'تأكيد كلمة المرور غير مطابق',
        ];
    }
}
