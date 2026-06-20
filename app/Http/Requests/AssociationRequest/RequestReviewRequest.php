<?php

namespace App\Http\Requests\AssociationRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RequestReviewRequest extends FormRequest
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
            'notes.required' => 'يرجى إدخال ملاحظات التعديل المطلوبة',
            'notes.string'   => 'الملاحظات يجب أن تكون نصاً',
            'notes.min'      => 'يجب أن تكون الملاحظات 5 أحرف على الأقل',
        ];
    }
}
