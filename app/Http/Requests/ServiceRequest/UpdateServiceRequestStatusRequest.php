<?php

namespace App\Http\Requests\ServiceRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateServiceRequestStatusRequest extends FormRequest
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
            'status' => 'required|string|in:pending,processing,approved,rejected',
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'status.required' => 'حالة الطلب مطلوبة',
            'status.string'   => 'حالة الطلب يجب أن تكون نصاً',
            'status.in'       => 'حالة الطلب يجب أن تكون إحدى القيم: pending، processing، approved، rejected',
        ];
    }
}
