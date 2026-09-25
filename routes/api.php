<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetTypeController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PropertyContactController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\ServiceQuoteController;
use App\Http\Controllers\TaskProductController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\TimeSessionController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\PropertyDocumentController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/app', [SettingController::class, 'appPublic']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/auth/preferences', [AuthController::class, 'getPreferences']);
        Route::patch('/auth/preferences', [AuthController::class, 'updatePreferences']);

        // Users (admin only)
        Route::middleware('permission:user.view')
            ->apiResource('users', UserController::class);

        // Clients
        Route::middleware('permission:client.view')
            ->apiResource('clients', ClientController::class);

        // Properties
        Route::middleware('permission:property.view')->group(function () {
            Route::apiResource('properties', PropertyController::class);
            Route::patch('properties/{property}/note', [PropertyController::class, 'patchNote']);
            Route::get('properties/{property}/assets', [PropertyController::class, 'assets']);
            Route::get('properties/{property}/routines', [PropertyController::class, 'routines']);
            Route::get('properties/{property}/tasks', [PropertyController::class, 'tasks']);
            // Property Contacts
            Route::get('properties/{property}/contacts', [PropertyContactController::class, 'index']);
            Route::post('properties/{property}/contacts', [PropertyContactController::class, 'store']);
            Route::patch('properties/{property}/contacts/{contact}', [PropertyContactController::class, 'update']);
            Route::delete('properties/{property}/contacts/{contact}', [PropertyContactController::class, 'destroy']);
        });

        // Assets
        Route::middleware('permission:asset.view')->group(function () {
            Route::apiResource('assets', AssetController::class);
            Route::post('assets/{asset}/work-history', [AssetController::class, 'addWorkHistory']);
            Route::get('assets/{asset}/work-history',  [AssetController::class, 'getWorkHistory']);
        });

        Route::apiResource('asset-types', AssetTypeController::class);
        Route::post('asset-types/{assetType}/variants', [AssetTypeController::class, 'storeVariant']);
        Route::post('asset-types/{assetType}/failing-remarks', [AssetTypeController::class, 'storeFailingRemark']);
        Route::put('asset-types/{assetType}/failing-remarks/{remark}', [AssetTypeController::class, 'updateFailingRemark']);
        Route::delete('asset-types/{assetType}/failing-remarks/{remark}', [AssetTypeController::class, 'destroyFailingRemark']);

        // Routines
        Route::middleware('permission:routine.view')->group(function () {
            Route::apiResource('routines', RoutineController::class);
            Route::post('routines/bulk', [RoutineController::class, 'bulkCreate']);
            Route::get('routines/due', [RoutineController::class, 'due']);
        });

        // Tasks
        Route::middleware('permission:task.view')->group(function () {
            Route::apiResource('tasks', TaskController::class);
            Route::post('tasks/on-demand', [TaskController::class, 'createOnDemand']);

            // Appointments (nested under task)
            Route::apiResource('tasks.appointments', AppointmentController::class)->shallow();

            // Asset results (per-task status)
            Route::get('tasks/{task}/asset-results', [TaskController::class, 'assetResults']);
            Route::patch('tasks/{task}/asset-results/{asset}', [TaskController::class, 'updateAssetResult']);

            // Safety acknowledgements
            Route::get('tasks/{task}/safety-acks', [TaskController::class, 'safetyAcks']);
            Route::post('tasks/{task}/safety-acks', [TaskController::class, 'storeSafetyAck']);

            // Attachments
            Route::get('tasks/{task}/attachments', [TaskAttachmentController::class, 'index']);
            Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store']);
            Route::delete('tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy']);

            // Logbook
            Route::get('tasks/{task}/logbook', [LogbookController::class, 'taskIndex']);
            Route::post('tasks/{task}/logbook', [LogbookController::class, 'store']);
            Route::get('properties/{property}/logbook', [LogbookController::class, 'propertyIndex']);

            // Property Documents
            Route::get('properties/{property}/documents', [PropertyDocumentController::class, 'index']);
            Route::post('properties/{property}/documents', [PropertyDocumentController::class, 'store']);

            // Current user's active session across all tasks
            Route::get('time-sessions/active', [TimeSessionController::class, 'myActive']);

            // Time Sessions (per task)
            Route::prefix('tasks/{task}')->group(function () {
                Route::get('time-sessions', [TimeSessionController::class, 'index']);
                Route::post('time-sessions/start', [TimeSessionController::class, 'start']);
                Route::patch('time-sessions/{session}/end', [TimeSessionController::class, 'end']);
                Route::delete('time-sessions/{session}', [TimeSessionController::class, 'destroy']);
            });
        });

        // Property Documents
        Route::get('property-documents/names', [PropertyDocumentController::class, 'names']);
        Route::get('property-documents/{document}/download', [PropertyDocumentController::class, 'download'])->name('property-documents.download');
        Route::delete('property-documents/{document}', [PropertyDocumentController::class, 'destroy']);

        // Attachment download & name suggestions
        Route::get('task-attachments/names', [TaskAttachmentController::class, 'names']);
        Route::get('task-attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])
            ->name('task-attachments.download');

        // Logbook download & name suggestions
        Route::get('logbook/names', [LogbookController::class, 'names']);
        Route::get('logbook/{entry}/download', [LogbookController::class, 'download'])->name('logbook.download');
        Route::delete('logbook/{entry}', [LogbookController::class, 'destroy']);

        // Schedule
        Route::get('schedule', [ScheduleController::class, 'index']);

        // Reports
        Route::middleware('permission:report.time.view')->group(function () {
            Route::get('reports/time', [ReportController::class, 'timeReport']);
            Route::get('reports/time/export', [ReportController::class, 'exportTimeCsv']);
        });

        // Technicians
        Route::apiResource('technicians', TechnicianController::class);

        // Contracts
        Route::apiResource('contracts', ContractController::class);

        // Billings
        Route::apiResource('billings', BillingController::class);

        // Task Products
        Route::get('tasks/{task}/products', [TaskProductController::class, 'index']);
        Route::post('tasks/{task}/products', [TaskProductController::class, 'store']);
        Route::patch('tasks/{task}/products/{product}', [TaskProductController::class, 'update']);
        Route::delete('tasks/{task}/products/{product}', [TaskProductController::class, 'destroy']);

        // Service Quotes
        Route::apiResource('service-quotes', ServiceQuoteController::class);
        Route::post('service-quotes/{serviceQuote}/assets', [ServiceQuoteController::class, 'addAsset']);
        Route::patch(
            'service-quotes/{serviceQuote}/assets/{asset}/status',
            [ServiceQuoteController::class, 'updateAssetStatus']
        );

        // Reports
        Route::get('tasks/{task}/reports', [ReportController::class, 'index']);
        Route::post('tasks/{task}/reports', [ReportController::class, 'store']);
        Route::get('reports/{report}', [ReportController::class, 'show']);
        Route::post('reports/{report}/send-email', [ReportController::class, 'sendEmail']);
        Route::post('reports/test-smtp', [ReportController::class, 'testSmtp']);

        // Roles
        Route::get('roles/permissions', [RoleController::class, 'permissions']);
        Route::apiResource('roles', RoleController::class);

        // Settings
        Route::apiResource('settings', SettingController::class)->except(['show']);
        Route::get('settings/trashed', [SettingController::class, 'trashed']);
        Route::patch('settings/{id}/restore', [SettingController::class, 'restore']);
        Route::patch('settings/{setting}/set-default', [SettingController::class, 'setDefault']);
        Route::get('settings/jsa-template', [SettingController::class, 'getJsaTemplate']);
        Route::post('settings/jsa-template', [SettingController::class, 'saveJsaTemplate']);

        // File Upload
        Route::post('uploads', [UploadController::class, 'store']);
        Route::post('uploads/images', [UploadController::class, 'store']);
    });
});
