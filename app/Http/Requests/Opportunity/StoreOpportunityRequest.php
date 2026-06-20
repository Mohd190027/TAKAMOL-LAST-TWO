<?php

namespace App\Http\Requests\Opportunity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreOpportunityRequest extends FormRequest
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
            'title'        => 'required|string|max:255',
            'type'         => 'required|string',
            'description'  => 'required|string',
            'requirements' => 'nullable|string',
            'deadline'     => 'nullable|date',
            'direction'    => 'nullable|in:local,international,both',
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'title.required'       => 'عنوان الفرصة التطوعية مطلوب',
            'title.string'         => 'العنوان يجب أن يكون نصاً',
            'title.max'            => 'العنوان يجب ألا يتجاوز 255 حرفاً',
            'type.required'        => 'نوع/تصنيف الفرصة مطلوب',
            'type.string'          => 'النوع يجب أن يكون نصاً',
            'description.required' => 'وصف الفرصة التطوعية مطلوب',
            'description.string'   => 'الوصف يجب أن يكون نصاً',
            'requirements.string'  => 'المتطلبات يجب أن تكون نصاً',
            'deadline.date'        => 'تاريخ الانتهاء يجب أن يكون تاريخاً صحيحاً',
            'direction.in'         => 'اتجاه الفرصة يجب أن يكون: محلي أو دولي أو كلاهما',
        ];
    }
}
