<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\MeetingController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

// ── Guest routes ──────────────────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── Root redirect ─────────────────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

// ── Protected routes — any authenticated user ─────────────────────────────────
Route::middleware(AuthMiddleware::class)->group(function () {

    // ── Admin routes ──────────────────────────────────────────────────────────
    Route::middleware(RoleMiddleware::class . ':admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'adminDashboard'])->name('dashboard');
        Route::get('/consulting', fn() => view('consulting'))->name('consulting');
        Route::get('/settings', fn() => view('settings'))->name('settings');

        Route::get('/volunteer', fn() => redirect('/consulting#volunteer'))->name('volunteer');

        // ── Meeting routes (Admin) ────────────────────────────────────────────
        Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings');
        Route::get('/api/meetings', [MeetingController::class, 'list'])->name('meetings.list');
        Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
        Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
        Route::post('/meetings/{meeting}/cancel', [MeetingController::class, 'cancel'])->name('meetings.cancel');
        Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
        Route::get('/api/meetings/{meeting}/attendees', [MeetingController::class, 'attendees'])->name('meetings.attendees');
        // ─────────────────────────────────────────────────────────────────────

        Route::get('/orders', fn() => redirect('/consulting#orders'))->name('orders');
        Route::get('/joint-projects', fn() => redirect('/consulting#projects'))->name('joint-projects');

        Route::get('/my-associations', [\App\Http\Controllers\Admin\MyAssociationsController::class, 'index'])->name('my-associations.index');
        Route::delete('/my-associations/{id}', [\App\Http\Controllers\Admin\MyAssociationsController::class, 'destroy'])->name('my-associations.destroy');
    });

    // ── Regular user routes ───────────────────────────────────────────────────
    Route::middleware(RoleMiddleware::class . ':user,association')->group(function () {
        Route::get('/user/dashboard', [App\Http\Controllers\DashboardController::class, 'userDashboard'])->name('user.dashboard');
        Route::get('/user/consulting', fn() => view('user.consulting'))->name('user.consulting');
        Route::get('/user/services', fn() => view('user.services'))->name('user.services');
        Route::get('/user/service-requests', [App\Http\Controllers\User\ServiceRequestController::class, 'index']);
        Route::post('/user/service-requests', [App\Http\Controllers\User\ServiceRequestController::class, 'store']);
        Route::put('/user/service-requests/{id}', [App\Http\Controllers\User\ServiceRequestController::class, 'update']);
        Route::delete('/user/service-requests/{id}', [App\Http\Controllers\User\ServiceRequestController::class, 'destroy']);

        // Join meeting (notifies admin)
        Route::post('/api/meetings/{meeting}/join', [MeetingController::class, 'joinMeeting'])->name('meetings.join');

        // Meetings list for users (read-only)
        Route::get('/api/user/meetings', [MeetingController::class, 'listForUser'])->name('user.meetings.list');

        Route::get('/user/meetings', [MeetingController::class, 'userIndex'])->name('user.meetings');
        Route::post('/user/meetings/{id}/attendance', [MeetingController::class, 'toggleAttendance'])->name('user.meetings.attendance');
        Route::get('/user/my-requests', [App\Http\Controllers\User\MyRequestController::class, 'index'])->name('user.orders');
        Route::get('/user/joint-projects', fn() => redirect('/user/consulting#projects'))->name('user.joint-projects');
        Route::get('/user/settings', fn() => view('user.settings'))->name('user.settings');
    });

});

// ── JSON API endpoints — defined in web.php to share session middleware ───────
Route::prefix('api')
    ->middleware([AuthMiddleware::class])
    ->group(function () {

        // ── Shared GET Endpoints (Admin & Users) ──────────────────────────────
        Route::get(
            '/association-categories',
            [App\Http\Controllers\Admin\AssociationCategoryController::class, 'index']
        );

        Route::get(
            '/opportunities',
            [App\Http\Controllers\Admin\OpportunityController::class, 'index']
        );

        Route::get(
            '/joint-projects',
            [App\Http\Controllers\Admin\JointProjectController::class, 'index']
        );

        // Volunteering: User can apply + view own requests
        Route::post(
            '/opportunities/{id}/apply',
            [App\Http\Controllers\Admin\OpportunityController::class, 'apply']
        );
        Route::get(
            '/my-opportunity-requests',
            [App\Http\Controllers\Admin\OpportunityController::class, 'myRequests']
        );

        // Joint Projects: User can apply + view own requests
        Route::post(
            '/joint-projects/{id}/apply',
            [App\Http\Controllers\Admin\JointProjectController::class, 'apply']
        );
        Route::get(
            '/my-project-requests',
            [App\Http\Controllers\Admin\JointProjectController::class, 'myRequests']
        );

        // ── User / Association Notification Endpoints ─────────────────────────
        Route::middleware(RoleMiddleware::class . ':user,association')->group(function () {
            Route::get(
                '/user/notifications',
                [\App\Http\Controllers\User\NotificationController::class, 'index']
            );
            Route::post(
                '/user/notifications/{id}/read',
                [\App\Http\Controllers\User\NotificationController::class, 'markRead']
            );
            Route::post(
                '/user/notifications/read-all',
                [\App\Http\Controllers\User\NotificationController::class, 'markAllRead']
            );
            Route::post(
                '/user/notifications/clear-all',
                [\App\Http\Controllers\User\NotificationController::class, 'clearAll']
            );

            // User Profile Settings
            Route::post('/user/settings/profile', [\App\Http\Controllers\User\SettingsController::class, 'updateProfile']);
            Route::post('/user/settings/password', [\App\Http\Controllers\User\SettingsController::class, 'updatePassword']);
            Route::post('/user/settings/avatar', [\App\Http\Controllers\User\SettingsController::class, 'uploadAvatar']);
        });

        // ── Admin-Only Endpoints ──────────────────────────────────────────────
        Route::middleware(RoleMiddleware::class . ':admin')->group(function () {

            // Association registration requests
            Route::get(
                '/association-requests',
                [App\Http\Controllers\Admin\AssociationRequestController::class, 'index']
            );
            Route::post(
                '/association-requests/{id}/approve',
                [App\Http\Controllers\Admin\AssociationRequestController::class, 'approve']
            );
            Route::post(
                '/association-requests/{id}/reject',
                [App\Http\Controllers\Admin\AssociationRequestController::class, 'reject']
            );
            Route::post(
                '/association-requests/{id}/review',
                [App\Http\Controllers\Admin\AssociationRequestController::class, 'requestReview']
            );

            // Mobaderoon Service Requests (Admin Management)
            Route::get(
                '/orders/services',
                [\App\Http\Controllers\Admin\ServiceRequestController::class, 'index']
            );
            Route::post(
                '/orders/services/{id}/status',
                [\App\Http\Controllers\Admin\ServiceRequestController::class, 'updateStatus']
            );

            // Notifications
            Route::get(
                '/notifications',
                [App\Http\Controllers\Admin\NotificationController::class, 'index']
            );
            Route::post(
                '/notifications/{id}/read',
                [App\Http\Controllers\Admin\NotificationController::class, 'markRead']
            );
            Route::post(
                '/notifications/read-all',
                [App\Http\Controllers\Admin\NotificationController::class, 'markAllRead']
            );
            Route::post(
                '/notifications/clear-all',
                [App\Http\Controllers\Admin\NotificationController::class, 'clearAll']
            );

            // Settings / Profile
            Route::post('/settings/profile', [App\Http\Controllers\Admin\SettingsController::class, 'updateProfile']);
            Route::post('/settings/password', [App\Http\Controllers\Admin\SettingsController::class, 'updatePassword']);
            Route::post('/settings/avatar', [App\Http\Controllers\Admin\SettingsController::class, 'uploadAvatar']);

            // Association Categories (Admin CRUD)
            Route::post(
                '/association-categories',
                [App\Http\Controllers\Admin\AssociationCategoryController::class, 'store']
            );
            Route::put(
                '/association-categories/{id}',
                [App\Http\Controllers\Admin\AssociationCategoryController::class, 'update']
            );
            Route::delete(
                '/association-categories/{id}',
                [App\Http\Controllers\Admin\AssociationCategoryController::class, 'destroy']
            );

            // All registered associations (with filters)
            Route::get(
                '/associations',
                [App\Http\Controllers\Admin\AssociationCategoryController::class, 'associations']
            );

            // Volunteering Opportunities (Admin)
            Route::get(
                '/opportunities/admin',
                [App\Http\Controllers\Admin\OpportunityController::class, 'index']
            );
            Route::post(
                '/opportunities',
                [App\Http\Controllers\Admin\OpportunityController::class, 'store']
            );
            Route::put(
                '/opportunities/{id}',
                [App\Http\Controllers\Admin\OpportunityController::class, 'update']
            );
            Route::delete(
                '/opportunities/{id}',
                [App\Http\Controllers\Admin\OpportunityController::class, 'destroy']
            );
            Route::get(
                '/opportunity-requests',
                [App\Http\Controllers\Admin\OpportunityController::class, 'requests']
            );
            Route::post(
                '/opportunity-requests/{id}/approve',
                [App\Http\Controllers\Admin\OpportunityController::class, 'approveRequest']
            );
            Route::post(
                '/opportunity-requests/{id}/reject',
                [App\Http\Controllers\Admin\OpportunityController::class, 'rejectRequest']
            );

            // Joint Projects (Admin CRUD)
            Route::post(
                '/joint-projects',
                [App\Http\Controllers\Admin\JointProjectController::class, 'store']
            );
            Route::put(
                '/joint-projects/{id}',
                [App\Http\Controllers\Admin\JointProjectController::class, 'update']
            );
            Route::post(
                '/joint-projects/{id}/cancel',
                [App\Http\Controllers\Admin\JointProjectController::class, 'cancel']
            );
            Route::delete(
                '/joint-projects/{id}',
                [App\Http\Controllers\Admin\JointProjectController::class, 'destroy']
            );

            // Project Join Requests (Admin)
            Route::get(
                '/project-join-requests',
                [App\Http\Controllers\Admin\JointProjectController::class, 'joinRequests']
            );
            Route::post(
                '/project-join-requests/{id}/approve',
                [App\Http\Controllers\Admin\JointProjectController::class, 'approveJoinRequest']
            );
            Route::post(
                '/project-join-requests/{id}/reject',
                [App\Http\Controllers\Admin\JointProjectController::class, 'rejectJoinRequest']
            );
        });
    });


