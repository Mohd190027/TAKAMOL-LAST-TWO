<?php

namespace App\Http\Requests\AssociationCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreAssociationCategoryRequest extends FormRequest
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
            'name'        => 'required|string|max:100|unique:association_categories,name',
            'icon'        => 'nullable|string|max:10',
            'color'       => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم التصنيف مطلوب',
            'name.string'   => 'اسم التصنيف يجب أن يكون نصاً',
            'name.max'      => 'اسم التصنيف يجب ألا يتجاوز 100 حرف',
            'name.unique'   => 'هذا التصنيف موجود مسبقاً',
            'icon.string'   => 'الأيقونة يجب أن تكون نصاً',
            'icon.max'      => 'الأيقونة يجب ألا تتجاوز 10 أحرف',
            'color.string'  => 'اللون يجب أن يكون نصاً',
            'color.max'     => 'اللون يجب ألا يتجاوز 20 حرفاً',
            'description.string' => 'الوصف يجب أن يكون نصاً',
            'description.max'    => 'الوصف يجب ألا يتجاوز 255 حرفاً',
        ];
    }
}
