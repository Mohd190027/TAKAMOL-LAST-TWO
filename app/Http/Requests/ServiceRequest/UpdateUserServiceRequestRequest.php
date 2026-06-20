<?php

namespace App\Http\Requests\ServiceRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserServiceRequestRequest extends FormRequest
{
    /**
     * تحقق من صلاحية المستخدم لتنفيذ هذا الطلب
     * (مستخدم عادي أو جمعية مسجلة)
     */
    public function authorize(): bool
    {
        $isAuthUser = Auth::check() && Auth::user()->role?->name === 'user';
        $isAssoc    = (bool) session('association');

        return $isAuthUser || $isAssoc;
    }

    /**
     * قواعد التحقق
     */
    public function rules(): array
    {
        return [
            'service_type'   => 'required|string|in:units,training,initiatives,consulting,other',
            'title'          => 'required|string|max:255',
            'details'        => 'required|string',
            'budget'         => 'nullable|numeric|min:0',
            'preferred_date' => 'nullable|date',
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'service_type.required' => 'نوع الخدمة مطلوب',
            'service_type.string'   => 'نوع الخدمة يجب أن يكون نصاً',
            'service_type.in'       => 'نوع الخدمة المحدد غير صحيح',
            'title.required'        => 'عنوان الطلب مطلوب',
            'title.string'          => 'العنوان يجب أن يكون نصاً',
            'title.max'             => 'العنوان يجب ألا يتجاوز 255 حرفاً',
            'details.required'      => 'تفاصيل الطلب مطلوبة',
            'details.string'        => 'التفاصيل يجب أن تكون نصاً',
            'budget.numeric'        => 'الميزانية يجب أن تكون رقماً',
            'budget.min'            => 'الميزانية يجب ألا تكون سالبة',
            'preferred_date.date'   => 'التاريخ المفضل يجب أن يكون تاريخاً صحيحاً',
        ];
    }
}
