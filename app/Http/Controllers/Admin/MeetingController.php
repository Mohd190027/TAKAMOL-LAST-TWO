<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingAgendaItem;
use App\Models\Association;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MeetingController extends Controller
{
    /**
     * GET /meetings  — Admin meetings page
     */
    public function index()
    {
        $this->syncPastMeetings();
        return view('meetings');
    }

    /**
     * GET /api/meetings  — Admin: all meetings as JSON
     */
    public function list()
    {
        $this->syncPastMeetings();

        $meetings = Meeting::with('agendaItems')
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get()
            ->map(function (Meeting $meeting) {
                $status = $meeting->status ?? 'upcoming';

                // Attendee count from meeting_association pivot
                $attendeeCount = 0;
                if (Schema::hasTable('meeting_association')) {
                    $attendeeCount = \DB::table('meeting_association')
                        ->where('meeting_id', $meeting->id)
                        ->count();
                }

                $report = null;
                if ($meeting->report_summary || $meeting->report_decisions) {
                    $report = [
                        'summary'   => $meeting->report_summary,
                        'decisions' => $meeting->report_decisions,
                        'attendees' => $meeting->report_attendees,
                        'actions'   => $meeting->report_actions,
                    ];
                }

                return [
                    'id'           => $meeting->id,
                    'title'        => $meeting->title,
                    'cat'          => $meeting->category,
                    'presenter'    => $meeting->presenter ?? '—',
                    'date'         => $meeting->date,
                    'end_date'     => $meeting->end_date,
                    'time'         => $meeting->time,
                    'end_time'     => $meeting->end_time,
                    'duration'     => $meeting->duration_minutes,
                    'type'         => $meeting->type ?? 'onsite',
                    'direction'    => $meeting->direction,
                    'status'       => $status,
                    'link'         => $meeting->link,
                    'location'     => $meeting->location,
                    'location_url' => $meeting->location_url,
                    'notes'        => $meeting->notes,
                    'duration'     => $meeting->duration_minutes,
                    'invitation'   => $meeting->invitation_direction,
                    'agendaItems'  => $meeting->agendaItems->map(fn($a) => [
                        'id'        => $a->id,
                        'title'     => $a->topic_title,
                        'duration'  => $a->duration_minutes,
                        'presenter' => $a->presenter_name,
                    ]),
                    'cancelReason' => $meeting->cancel_reason,
                    'attendee_count' => $attendeeCount,
                    'report'       => $report,
                ];
            });

        return response()->json($meetings);
    }

    /**
     * GET /user/meetings  — User: meetings page
     */
    public function userIndex()
    {
        $this->syncPastMeetings();

        // ── Filter by association category if logged in as association ─────────
        $assocCategory = null;
        if (session()->has('association')) {
            $assocCategory = session('association')['category'] ?? session('association.category') ?? null;
        }

        $query = Meeting::with(['agendaItems'])
            ->where(function ($statusQ) {
                $statusQ->whereIn('status', ['upcoming', 'past', 'cancelled'])
                        ->orWhereNull('status');
            })
            ->orderByDesc('date')
            ->orderByDesc('time');

        // Show meetings where invitation_direction is 'عام', matches category, or is null (defaults to all)
        if ($assocCategory) {
            $query->where(function ($q) use ($assocCategory) {
                $q->where('invitation_direction', 'عام')
                  ->orWhere('invitation_direction', $assocCategory)
                  ->orWhereNull('invitation_direction');
            });
        }

        $meetings = $query->get();

        $formattedMeetings = $meetings->map(function (Meeting $meeting) {
            $status = $meeting->status ?? 'upcoming';

            $report = null;
            if ($meeting->report_summary || $meeting->report_decisions) {
                $report = [
                    'summary'   => $meeting->report_summary,
                    'decisions' => $meeting->report_decisions,
                    'attendees' => $meeting->report_attendees,
                    'actions'   => $meeting->report_actions,
                ];
            }

            return [
                'id'           => $meeting->id,
                'title'        => $meeting->title,
                'cat'          => $meeting->category,
                'presenter'    => $meeting->presenter ?? '—',
                'date'         => $meeting->date,
                'end_date'     => $meeting->end_date,
                'time'         => $meeting->time,
                'end_time'     => $meeting->end_time,
                'duration'     => $meeting->duration_minutes,
                'type'         => $meeting->type ?? 'onsite',
                'direction'    => $meeting->direction,
                'status'       => $status,
                'link'         => $meeting->link,
                'location'     => $meeting->location,
                'location_url' => $meeting->location_url,
                'notes'        => $meeting->notes,
                'description'  => $meeting->description,
                'cancelReason' => $meeting->cancel_reason,
                'agendaItems'  => $meeting->agendaItems->map(fn($a) => [
                    'title'     => $a->topic_title,
                    'presenter' => $a->presenter_name,
                    'duration'  => $a->duration_minutes,
                ])->toArray(),
                'report'       => $report,
            ];
        });

        $attendingIds = [];
        if (Auth::check()) {
            // If User model has attendingMeetings
            if (method_exists(Auth::user(), 'attendingMeetings')) {
                $attendingIds = Auth::user()->attendingMeetings()->pluck('meetings.id')->toArray();
            }
        } elseif (session()->has('association')) {
            $assoc = Association::find(session('association.id'));
            if ($assoc && method_exists($assoc, 'attendingMeetings')) {
                $attendingIds = $assoc->attendingMeetings()->pluck('meetings.id')->toArray();
            }
        }

        $categories = \App\Models\AssociationCategory::where('is_active', true)->get();

        return view('user.meetings', [
            'formattedMeetings' => $formattedMeetings,
            'attendingIds'      => $attendingIds,
            'categories'        => $categories,
            'activeNav'         => 'meetings',
        ]);
    }


    /**
     * GET /api/user/meetings  — User: read-only list
     */
    public function listForUser()
    {
        $this->syncPastMeetings();

        $meetings = Meeting::with(['agendaItems', 'targetAssociations'])
            ->whereIn('status', ['upcoming', 'past', 'cancelled'])
            ->orWhereNull('status')
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get()
            ->map(function (Meeting $meeting) {
                $status = $meeting->status ?? 'upcoming';

                return [
                    'id'           => $meeting->id,
                    'title'        => $meeting->title,
                    'cat'          => $meeting->category,
                    'presenter'    => $meeting->presenter ?? '—',
                    'date'         => $meeting->date,
                    'end_date'     => $meeting->end_date,
                    'time'         => $meeting->time,
                    'end_time'     => $meeting->end_time,
                    'duration'     => $meeting->duration_minutes,
                    'type'         => $meeting->type ?? 'onsite',
                    'status'       => $status,
                    'link'         => $meeting->link,
                    'location'     => $meeting->location,
                    'location_url' => $meeting->location_url,
                    'notes'        => $meeting->notes,
                    'description'  => $meeting->description,
                    'cancelReason' => $meeting->cancel_reason,
                    'targets'      => $meeting->targetAssociations->map(fn($t) => $t->name)->toArray(),
                    'agendaItems'  => $meeting->agendaItems->map(fn($a) => [
                        'title'     => $a->topic_title,
                        'presenter' => $a->presenter_name,
                        'duration'  => $a->duration_minutes,
                    ])->toArray(),
                ];
            });

        return response()->json($meetings);
    }

    /**
     * POST /meetings  — Create a new meeting
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $meeting = new Meeting($this->buildPayload($validated, $request->user()?->id));
        $meeting->save();

        $this->syncAgendaItems($meeting, $request->input('agenda_items', []));

        // Notify creator/admin
        if ($request->user()?->id) {
            Notification::create([
                'user_id'      => $request->user()->id,
                'title'        => 'تم إنشاء اجتماع جديد',
                'body'         => 'تمت إضافة الاجتماع: ' . $meeting->title,
                'type'         => 'meeting_created',
                'related_id'   => $meeting->id,
                'related_type' => Meeting::class,
                'is_read'      => false,
            ]);
        }

        // Notify all regular users
        $userIds = User::whereHas('role', fn($q) => $q->where('name', 'user'))->pluck('id');
        foreach ($userIds as $uid) {
            Notification::create([
                'user_id'      => $uid,
                'title'        => 'اجتماع جديد',
                'body'         => "تمت إضافة اجتماع جديد: {$meeting->title}",
                'type'         => 'meeting_created',
                'related_id'   => $meeting->id,
                'related_type' => Meeting::class,
                'is_read'      => false,
            ]);
        }

        // Notify all approved associations
        $assocIds = Association::where('status', 'approved')->pluck('id');
        foreach ($assocIds as $aid) {
            Notification::create([
                'association_id' => $aid,
                'title'          => 'اجتماع جديد',
                'body'           => "تمت إضافة اجتماع جديد: {$meeting->title}",
                'type'           => 'meeting_created',
                'related_id'     => $meeting->id,
                'related_type'   => Meeting::class,
                'is_read'        => false,
            ]);
        }

        return response()->json(['success' => true, 'id' => $meeting->id]);
    }

    /**
     * PUT /meetings/{meeting}  — Update an existing meeting
     */
    public function update(Request $request, Meeting $meeting)
    {
        $validated = $request->validate($this->rules(true));
        $meeting->update($this->buildPayload($validated, $meeting->created_by));
        $this->syncAgendaItems($meeting, $request->input('agenda_items', []));

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/meetings/{meeting}/join  — User registers attendance (notifies admin)
     */
    public function joinMeeting(Request $request, Meeting $meeting)
    {
        $userName = $request->input('user_name')
            ?? Auth::user()?->full_name
            ?? 'مستخدم';

        $meetingType = $meeting->type === 'online' ? 'عبر الإنترنت' : 'حضوري';
        $action      = $meeting->type === 'online'  ? 'انضم إلى' : 'سيحضر';

        // Notify admin
        $admin = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->first();
        if ($admin) {
            Notification::create([
                'user_id'      => $admin->id,
                'title'        => 'انضمام إلى اجتماع',
                'body'         => $userName . ' ' . $action . ' الاجتماع: ' . $meeting->title . ' (' . $meetingType . ')',
                'type'         => 'meeting_joined',
                'related_id'   => $meeting->id,
                'related_type' => Meeting::class,
                'is_read'      => false,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * POST /user/meetings/{id}/attendance
     * Toggles attendance for the current user OR association.
     */
    public function toggleAttendance($id)
    {
        $meeting = Meeting::findOrFail($id);

        // Case 1: Regular authenticated user
        if (Auth::check()) {
            $user = Auth::user();
            if (method_exists($user, 'attendingMeetings')) {
                $isAttending = $user->attendingMeetings()->where('meetings.id', $id)->exists();

                if ($isAttending) {
                    $user->attendingMeetings()->detach($id);
                    return response()->json(['message' => 'تم إلغاء الحضور', 'status' => 'detached']);
                } else {
                    $user->attendingMeetings()->attach($id);
                    return response()->json(['message' => 'تم تسجيل حضورك بنجاح ✅', 'status' => 'attached']);
                }
            }
        }

        // Case 2: Association logged in via session
        if (session()->has('association')) {
            $assoc = Association::find(session('association.id'));

            if (!$assoc) {
                return response()->json(['message' => 'لم يُعثر على الجمعية'], 404);
            }

            if (method_exists($assoc, 'attendingMeetings')) {
                $isAttending = $assoc->attendingMeetings()->where('meetings.id', $id)->exists();

                if ($isAttending) {
                    $assoc->attendingMeetings()->detach($id);
                    return response()->json(['message' => 'تم إلغاء حضور جمعيتك', 'status' => 'detached']);
                } else {
                    $assoc->attendingMeetings()->attach($id);
                    return response()->json(['message' => 'تم تسجيل حضور جمعيتك بنجاح ✅', 'status' => 'attached']);
                }
            }
        }

        return response()->json(['message' => 'غير مصرح'], 401);
    }

    /**
     * POST /meetings/{meeting}/cancel  — Cancel a meeting
     */
    public function cancel(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $meeting->update([
            'status'        => 'cancelled',
            'cancel_reason' => $validated['cancel_reason'],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /meetings/{meeting}  — Delete a meeting
     */
    public function destroy(Meeting $meeting)
    {
        $meeting->delete();
        return response()->json(['success' => true]);
    }

    /**
     * GET /api/meetings/{meeting}/attendees  — List attending associations
     */
    public function attendees(Meeting $meeting)
    {
        if (!Schema::hasTable('meeting_association')) {
            return response()->json(['total' => 0, 'associations' => []]);
        }

        $rows = \DB::table('meeting_association as ma')
            ->join('associations as a', 'a.id', '=', 'ma.association_id')
            ->where('ma.meeting_id', $meeting->id)
            ->select(
                'a.id',
                'a.association_name',
                'a.manager_name',
                'a.email',
                'ma.created_at as registered_at'
            )
            ->orderBy('ma.created_at', 'asc')
            ->get();

        return response()->json([
            'total'        => $rows->count(),
            'associations' => $rows,
        ]);
    }

    // ─── Private helpers ────────────────────────────────────────────────

    private function rules(bool $isUpdate = false): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'category'            => ['required', 'string', 'max:100'],
            'presenter'           => ['required', 'string', 'max:255'],
            'date'                => ['required', 'date'],
            'end_date'            => ['nullable', 'date', 'after_or_equal:date'],
            'time'                => ['nullable', 'date_format:H:i'],
            'end_time'            => ['nullable', 'date_format:H:i'],
            'type'                => ['required', Rule::in(['online', 'onsite'])],
            'invitation_direction'=> ['nullable', 'string', 'max:100'],
            'link'                => ['nullable', 'url', 'max:1000'],
            'location'            => ['nullable', 'string', 'max:255'],
            'location_url'        => ['nullable', 'url', 'max:1000'],
            'notes'               => ['nullable', 'string', 'max:5000'],
            'status'              => [$isUpdate ? 'sometimes' : 'nullable', Rule::in(['upcoming', 'past', 'cancelled'])],
            'report_summary'      => ['nullable', 'string', 'max:5000'],
            'report_decisions'    => ['nullable', 'string', 'max:5000'],
            'report_attendees'    => ['nullable', 'integer', 'min:0'],
            'report_actions'      => ['nullable', 'string', 'max:5000'],
            'agenda_items'        => ['nullable', 'array'],
            'agenda_items.*.title'    => ['required', 'string', 'max:255'],
            'agenda_items.*.duration' => ['nullable', 'integer', 'min:1'],
            'agenda_items.*.presenter'=> ['nullable', 'string', 'max:255'],
        ];
    }

    private function syncAgendaItems(Meeting $meeting, array $items): void
    {
        $meeting->agendaItems()->delete();
        foreach ($items as $i => $item) {
            if (empty($item['title'])) continue;
            MeetingAgendaItem::create([
                'meeting_id'       => $meeting->id,
                'topic_title'      => $item['title'],
                'duration_minutes' => $item['duration'] ?? 15,
                'presenter_name'   => $item['presenter'] ?? null,
                'order_index'      => $i,
            ]);
        }
    }

    private function buildPayload(array $validated, ?int $createdBy): array
    {
        $meetingDateTime = $validated['date'] . ' ' . ($validated['time'] ?? '00:00') . ':00';

        $payload = [
            'created_by'           => $createdBy,
            'title'                => $validated['title'],
            'main_speaker'         => $validated['presenter'],
            'description'          => $validated['notes'] ?? null,
            'date_time'            => $meetingDateTime,
            'meeting_type'         => ($validated['type'] ?? 'onsite') === 'online' ? 'online' : 'in_person',
            'direction'            => 'local',
            'category'             => $validated['category'],
            'presenter'            => $validated['presenter'],
            'date'                 => $validated['date'],
            'end_date'             => $validated['end_date'] ?? null,
            'time'                 => $validated['time'] ?? null,
            'end_time'             => $validated['end_time'] ?? null,
            'invitation_direction' => $validated['invitation_direction'] ?? null,
            'type'                 => $validated['type'],
            'status'               => $validated['status'] ?? 'upcoming',
            'link'                 => $validated['link'] ?? null,
            'location'             => $validated['location'] ?? null,
            'location_url'         => $validated['location_url'] ?? null,
            'notes'                => $validated['notes'] ?? null,
            'report_summary'       => $validated['report_summary'] ?? null,
            'report_decisions'     => $validated['report_decisions'] ?? null,
            'report_attendees'     => $validated['report_attendees'] ?? null,
            'report_actions'       => $validated['report_actions'] ?? null,
        ];

        // Write only columns that exist (safe against schema variants)
        $existingColumns = Schema::getColumnListing('meetings');
        return array_intersect_key($payload, array_flip($existingColumns));
    }

    private function syncPastMeetings(): void
    {
        $hasEndDate = Schema::hasColumn('meetings', 'end_date');
        $hasEndTime = Schema::hasColumn('meetings', 'end_time');

        Meeting::where(function ($q) {
                $q->where('status', 'upcoming')->orWhereNull('status');
            })
            ->where(function ($query) use ($hasEndDate, $hasEndTime) {
                if ($hasEndDate) {
                    $query->where(function ($q1) use ($hasEndTime) {
                        $q1->whereNotNull('end_date')
                           ->where(function ($sub1) use ($hasEndTime) {
                               $sub1->whereDate('end_date', '<', now()->toDateString())
                                    ->orWhere(function ($sub2) use ($hasEndTime) {
                                        $sub2->whereDate('end_date', now()->toDateString());
                                        if ($hasEndTime) {
                                            $sub2->whereNotNull('end_time')
                                                 ->where('end_time', '<', now()->format('H:i'));
                                        }
                                    });
                           });
                    });

                    if ($hasEndTime) {
                        $query->orWhere(function ($q2) {
                            $q2->whereNull('end_date')
                               ->whereNotNull('end_time')
                               ->where(function ($sub1) {
                                   $sub1->whereDate('date', '<', now()->toDateString())
                                        ->orWhere(function ($sub2) {
                                            $sub2->whereDate('date', now()->toDateString())
                                                 ->where('end_time', '<', now()->format('H:i'));
                                        });
                               });
                        });
                    }

                    $query->orWhere(function ($q3) use ($hasEndTime) {
                        $q3->whereNull('end_date');
                        if ($hasEndTime) {
                            $q3->whereNull('end_time');
                        }
                        $q3->where(function ($sub1) {
                            $sub1->whereDate('date', '<', now()->toDateString())
                                 ->orWhere(function ($sub2) {
                                     $sub2->whereDate('date', now()->toDateString())
                                          ->whereNotNull('time')
                                          ->where('time', '<', now()->format('H:i'));
                                 });
                        });
                    });
                } else {
                    $query->where(function ($sub1) {
                        $sub1->whereDate('date', '<', now()->toDateString())
                             ->orWhere(function ($sub2) {
                                 $sub2->whereDate('date', now()->toDateString())
                                      ->whereNotNull('time')
                                      ->where('time', '<', now()->format('H:i'));
                             });
                    });
                }
            })
            ->update(['status' => 'past']);

        Meeting::whereNull('status')
            ->whereDate('date', '>=', now()->toDateString())
            ->update(['status' => 'upcoming']);
    }
}
