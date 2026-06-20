<?php

namespace App\Http\Requests\Opportunity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RejectOpportunityRequestRequest extends FormRequest
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
            'notes' => 'required|string|min:5',
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'notes.required' => 'يرجى إدخال سبب الرفض',
            'notes.string'   => 'سبب الرفض يجب أن يكون نصاً',
            'notes.min'      => 'يجب أن يكون سبب الرفض 5 أحرف على الأقل',
        ];
    }
}
