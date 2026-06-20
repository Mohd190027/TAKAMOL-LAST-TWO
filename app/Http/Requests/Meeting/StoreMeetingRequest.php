<?php

namespace App\Http\Requests\Meeting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreMeetingRequest extends FormRequest
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
            'title'                => ['required', 'string', 'max:255'],
            'category'             => ['required', 'string', 'max:100'],
            'presenter'            => ['required', 'string', 'max:255'],
            'date'                 => ['required', 'date'],
            'end_date'             => ['nullable', 'date', 'after_or_equal:date'],
            'time'                 => ['nullable', 'date_format:H:i'],
            'end_time'             => ['nullable', 'date_format:H:i'],
            'type'                 => ['required', Rule::in(['online', 'onsite'])],
            'invitation_direction' => ['nullable', 'string', 'max:100'],
            'link'                 => ['nullable', 'url', 'max:1000'],
            'location'             => ['nullable', 'string', 'max:255'],
            'location_url'         => ['nullable', 'url', 'max:1000'],
            'notes'                => ['nullable', 'string', 'max:5000'],
            'status'               => ['nullable', Rule::in(['upcoming', 'past', 'cancelled'])],
            'report_summary'       => ['nullable', 'string', 'max:5000'],
            'report_decisions'     => ['nullable', 'string', 'max:5000'],
            'report_attendees'     => ['nullable', 'integer', 'min:0'],
            'report_actions'       => ['nullable', 'string', 'max:5000'],
            'agenda_items'         => ['nullable', 'array'],
            'agenda_items.*.title'     => ['required', 'string', 'max:255'],
            'agenda_items.*.duration'  => ['nullable', 'integer', 'min:1'],
            'agenda_items.*.presenter' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'title.required'                    => 'عنوان الاجتماع مطلوب',
            'title.string'                      => 'عنوان الاجتماع يجب أن يكون نصاً',
            'title.max'                         => 'عنوان الاجتماع يجب ألا يتجاوز 255 حرفاً',
            'category.required'                 => 'تصنيف الاجتماع مطلوب',
            'category.string'                   => 'التصنيف يجب أن يكون نصاً',
            'category.max'                      => 'التصنيف يجب ألا يتجاوز 100 حرف',
            'presenter.required'                => 'اسم المقدم مطلوب',
            'presenter.string'                  => 'اسم المقدم يجب أن يكون نصاً',
            'presenter.max'                     => 'اسم المقدم يجب ألا يتجاوز 255 حرفاً',
            'date.required'                     => 'تاريخ الاجتماع مطلوب',
            'date.date'                         => 'تاريخ الاجتماع يجب أن يكون تاريخاً صحيحاً',
            'end_date.date'                     => 'تاريخ الانتهاء يجب أن يكون تاريخاً صحيحاً',
            'end_date.after_or_equal'           => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء أو مساوياً له',
            'time.date_format'                  => 'وقت البدء يجب أن يكون بصيغة HH:MM',
            'end_time.date_format'              => 'وقت الانتهاء يجب أن يكون بصيغة HH:MM',
            'type.required'                     => 'نوع الاجتماع مطلوب',
            'type.in'                           => 'نوع الاجتماع يجب أن يكون إما (online) أو (onsite)',
            'invitation_direction.string'       => 'اتجاه الدعوة يجب أن يكون نصاً',
            'invitation_direction.max'          => 'اتجاه الدعوة يجب ألا يتجاوز 100 حرف',
            'link.url'                          => 'رابط الاجتماع يجب أن يكون رابطاً صحيحاً',
            'link.max'                          => 'رابط الاجتماع يجب ألا يتجاوز 1000 حرف',
            'location.string'                   => 'موقع الاجتماع يجب أن يكون نصاً',
            'location.max'                      => 'موقع الاجتماع يجب ألا يتجاوز 255 حرفاً',
            'location_url.url'                  => 'رابط الموقع يجب أن يكون رابطاً صحيحاً',
            'location_url.max'                  => 'رابط الموقع يجب ألا يتجاوز 1000 حرف',
            'notes.string'                      => 'الملاحظات يجب أن تكون نصاً',
            'notes.max'                         => 'الملاحظات يجب ألا تتجاوز 5000 حرف',
            'status.in'                         => 'حالة الاجتماع غير صحيحة',
            'report_summary.string'             => 'ملخص التقرير يجب أن يكون نصاً',
            'report_summary.max'                => 'ملخص التقرير يجب ألا يتجاوز 5000 حرف',
            'report_decisions.string'           => 'قرارات التقرير يجب أن تكون نصاً',
            'report_decisions.max'              => 'قرارات التقرير يجب ألا تتجاوز 5000 حرف',
            'report_attendees.integer'          => 'عدد الحضور يجب أن يكون رقماً صحيحاً',
            'report_attendees.min'              => 'عدد الحضور يجب ألا يكون سالباً',
            'report_actions.string'             => 'إجراءات التقرير يجب أن تكون نصاً',
            'report_actions.max'                => 'إجراءات التقرير يجب ألا تتجاوز 5000 حرف',
            'agenda_items.array'                => 'بنود جدول الأعمال يجب أن تكون مصفوفة',
            'agenda_items.*.title.required'     => 'عنوان بند جدول الأعمال مطلوب',
            'agenda_items.*.title.string'       => 'عنوان البند يجب أن يكون نصاً',
            'agenda_items.*.title.max'          => 'عنوان البند يجب ألا يتجاوز 255 حرفاً',
            'agenda_items.*.duration.integer'   => 'مدة البند يجب أن تكون رقماً صحيحاً',
            'agenda_items.*.duration.min'       => 'مدة البند يجب ألا تقل عن دقيقة واحدة',
            'agenda_items.*.presenter.string'   => 'اسم مقدم البند يجب أن يكون نصاً',
            'agenda_items.*.presenter.max'      => 'اسم مقدم البند يجب ألا يتجاوز 255 حرفاً',
        ];
    }
}
