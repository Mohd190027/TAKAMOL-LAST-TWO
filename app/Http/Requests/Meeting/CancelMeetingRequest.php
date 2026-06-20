<?php

namespace App\Http\Requests\Meeting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CancelMeetingRequest extends FormRequest
{
    /**
     * تحقق من صلاحية المستخدم لتنفيذ هذا الطلب
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->role?->name === 'admin';
    }

    /**
     * قواعد التحقق
     */
    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'سبب الإلغاء مطلوب',
            'cancel_reason.string'   => 'سبب الإلغاء يجب أن يكون نصاً',
            'cancel_reason.min'      => 'سبب الإلغاء يجب أن يكون 5 أحرف على الأقل',
            'cancel_reason.max'      => 'سبب الإلغاء يجب ألا يتجاوز 1000 حرف',
        ];
    }
}
