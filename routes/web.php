<?php

use App\Http\Controllers\Admin\AdminStoreSubmissionController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\StaffReportController;
use App\Http\Controllers\StoreSubmissionController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\VisitPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('attendance')->name('attendance.')->middleware('role:super-admin,sales,staff,driver')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/history', [AttendanceController::class, 'history'])->name('history');
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
        Route::get('/status', [AttendanceController::class, 'status'])->name('status');
        Route::post('/submit-absence', [AttendanceController::class, 'submitAbsence'])->name('submit-absence');
    });

    Route::prefix('laporan-presensi')->name('staff.report.')->middleware('role:staff')->group(function () {
        Route::get('/', [StaffReportController::class, 'index'])->name('index');
        Route::get('/pdf', [StaffReportController::class, 'exportPdf'])->name('pdf');
        Route::get('/excel', [StaffReportController::class, 'exportExcel'])->name('excel');
    });

    Route::prefix('route')->name('route.')->middleware('role:super-admin,admin,sales,driver')->group(function () {
        Route::get('/', [RouteController::class, 'index'])->name('index');
        Route::get('/create', [RouteController::class, 'create'])->name('create');
        Route::post('/store', [RouteController::class, 'store'])->name('store');
        Route::get('/history', [RouteController::class, 'history'])->name('history');
        Route::get('/{id}', [RouteController::class, 'show'])->name('show');
        Route::get('/{id}/map', [RouteController::class, 'map'])->name('map');
        Route::post('/{id}/start', [RouteController::class, 'start'])->name('start');
        Route::post('/{id}/complete', [RouteController::class, 'complete'])->name('complete');
        Route::post('/{id}/add-stop', [RouteController::class, 'addStop'])->name('stop.add');
        Route::patch('/{routeId}/stop/{stopId}', [RouteController::class, 'updateStop'])->name('stop.update');
        Route::put('/{id}/sequence', [RouteController::class, 'updateSequence'])->name('sequence');
        Route::delete('/{id}', [RouteController::class, 'destroy'])->name('destroy');
        Route::post('/gps', [RouteController::class, 'storeGpsLocation'])->name('gps.store');
    });

    Route::prefix('visit')->name('visit.')->middleware('role:super-admin,admin,sales,driver')->group(function () {
        Route::get('/', [VisitController::class, 'index'])->name('index');
        Route::get('/history', [VisitController::class, 'history'])->name('history');
        Route::get('/rekap/pdf', [VisitPdfController::class, 'rekap'])->name('pdf.rekap');
        Route::get('/rekap/excel', [VisitPdfController::class, 'rekapExcel'])->name('excel.rekap');
        Route::get('/{id}', [VisitController::class, 'show'])->name('show');
        Route::get('/{id}/pdf', [VisitPdfController::class, 'detail'])->name('pdf.detail');
        Route::get('/{routeStopId}/check-in', [VisitController::class, 'checkInForm'])->name('check-in-form');
        Route::post('/check-in', [VisitController::class, 'checkIn'])->name('store');
        Route::get('/{visitId}/check-out', [VisitController::class, 'checkOutForm'])->name('check-out-form');
        Route::post('/{visitId}/check-out', [VisitController::class, 'checkOut'])->name('check-out');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/dropdown', [NotificationController::class, 'dropdown'])->name('dropdown');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/destroy-all', [NotificationController::class, 'destroyAll'])->name('destroy-all');
        Route::get('/{id}/read', [NotificationController::class, 'read'])->name('read');
        Route::post('/{id}/mark-read', [NotificationController::class, 'markRead'])->name('mark-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('sales')->name('sales.')->middleware('role:sales')->group(function () {
        Route::get('/stores', [\App\Http\Controllers\SalesStoreReceivableController::class, 'index'])->name('stores.index');
        Route::get('/stores/{storeId}', [\App\Http\Controllers\SalesStoreReceivableController::class, 'show'])->name('stores.show');
    });

    Route::prefix('driver')->name('driver.')->middleware('role:driver')->group(function () {
        Route::get('/stores', [\App\Http\Controllers\DriverStoreController::class, 'index'])->name('stores.index');
    });

    Route::prefix('stores')->name('stores.')->group(function () {
        Route::prefix('submissions')->name('submissions.')->middleware('role:sales')->group(function () {
            Route::get('/', [StoreSubmissionController::class, 'index'])->name('index');
            Route::get('/create', [StoreSubmissionController::class, 'create'])->name('create');
            Route::post('/', [StoreSubmissionController::class, 'store'])->name('store');
        });
    });

    Route::prefix('admin')->name('admin.')->middleware(['role:super-admin,admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/restore', [UserController::class, 'restore'])
            ->name('users.restore')
            ->withTrashed();
        Route::delete('/users/{user}/force-destroy', [UserController::class, 'forceDestroy'])
            ->name('users.force-destroy')
            ->withTrashed();
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('/stores/by-sales', [StoreController::class, 'bySales'])->name('stores.by-sales');
        Route::get('/stores/create', [StoreController::class, 'create'])->name('stores.create');
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::get('/stores/submissions', [AdminStoreSubmissionController::class, 'index'])->name('stores.submissions.index');
        Route::get('/stores/submissions/{id}', [AdminStoreSubmissionController::class, 'show'])->name('stores.submissions.show');
        Route::post('/stores/submissions/{id}/approve', [AdminStoreSubmissionController::class, 'approve'])->name('stores.submissions.approve');
        Route::post('/stores/submissions/{id}/reject', [AdminStoreSubmissionController::class, 'reject'])->name('stores.submissions.reject');
        Route::get('/stores/{store}', [StoreController::class, 'show'])->name('stores.show');
        Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])
            ->name('stores.edit')
            ->withTrashed();
        Route::put('/stores/{store}', [StoreController::class, 'update'])
            ->name('stores.update')
            ->withTrashed();
        Route::delete('/stores/{store}', [StoreController::class, 'destroy'])
            ->name('stores.destroy')
            ->withTrashed();
        Route::post('/stores/{store}/restore', [StoreController::class, 'restore'])
            ->name('stores.restore')
            ->withTrashed();
        Route::get('/routes', [RouteController::class, 'adminIndex'])->name('routes.index');
        Route::get('/routes/create', [RouteController::class, 'adminCreate'])->name('routes.create');
        Route::post('/routes', [RouteController::class, 'adminStore'])->name('routes.store');
        Route::get('/routes/{route}/edit', [RouteController::class, 'adminEdit'])->name('routes.edit');
        Route::put('/routes/{route}', [RouteController::class, 'adminUpdate'])->name('routes.update');
        Route::delete('/routes/{route}', [RouteController::class, 'adminDestroy'])->name('routes.destroy');
        Route::get('/routes/report', [RouteController::class, 'report'])->name('routes.report');
        Route::get('/driver/routes', [RouteController::class, 'adminDriverIndex'])->name('driver.routes.index');
        Route::get('/driver/routes/create', [RouteController::class, 'adminDriverCreate'])->name('driver.routes.create');
        Route::post('/driver/routes', [RouteController::class, 'adminDriverStore'])->name('driver.routes.store');
        Route::get('/driver/routes/{route}/edit', [RouteController::class, 'adminDriverEdit'])->name('driver.routes.edit');
        Route::put('/driver/routes/{route}', [RouteController::class, 'adminDriverUpdate'])->name('driver.routes.update');
        Route::delete('/driver/routes/{route}', [RouteController::class, 'adminDriverDestroy'])->name('driver.routes.destroy');
        Route::get('/driver/routes/report', [RouteController::class, 'adminDriverReport'])->name('driver.routes.report');
        Route::get('/visits', [VisitController::class, 'admin'])->name('visits.index');
        Route::get('/driver/visits', [VisitController::class, 'adminDriver'])->name('driver.visits.index');
        Route::get('/attendance', [AttendanceController::class, 'adminIndex'])->name('attendance.index');
        Route::post('/attendance/{attendance}/cancel', [AttendanceController::class, 'cancel'])->name('attendance.cancel');
        Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

        Route::prefix('receivables')->name('receivables.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ReceivableController::class, 'index'])->name('index');
            Route::prefix('summary')->name('summary.')->group(function () {
                Route::get('/total', [\App\Http\Controllers\Admin\ReceivableController::class, 'summaryTotal'])->name('total');
                Route::get('/indebted-stores', [\App\Http\Controllers\Admin\ReceivableController::class, 'summaryIndebtedStores'])->name('indebted-stores');
                Route::get('/open-transactions', [\App\Http\Controllers\Admin\ReceivableController::class, 'summaryOpenTransactions'])->name('open-transactions');
                Route::get('/payments', [\App\Http\Controllers\Admin\ReceivableController::class, 'summaryPayments'])->name('payments');
                Route::get('/today-payments', [\App\Http\Controllers\Admin\ReceivableController::class, 'summaryTodayPayments'])->name('today-payments');
                Route::get('/paid-transactions', [\App\Http\Controllers\Admin\ReceivableController::class, 'summaryPaidTransactions'])->name('paid-transactions');
                Route::get('/new-transactions', [\App\Http\Controllers\Admin\ReceivableController::class, 'summaryNewTransactions'])->name('new-transactions');
                Route::get('/new-transaction-payments', [\App\Http\Controllers\Admin\ReceivableController::class, 'summaryNewTransactionPayments'])->name('new-transaction-payments');
                Route::get('/trends', [\App\Http\Controllers\Admin\ReceivableController::class, 'summaryTrends'])->name('trends');
                Route::get('/by-sales', [\App\Http\Controllers\Admin\ReceivableController::class, 'summaryBySales'])->name('by-sales');
            });
            Route::post('/transactions', [\App\Http\Controllers\Admin\ReceivableController::class, 'storeTransaction'])->name('transactions.store');
            Route::post('/transactions/{transactionId}/payments', [\App\Http\Controllers\Admin\ReceivableController::class, 'storeTransactionPayment'])->name('transactions.payments.store');
            Route::post('/opening-balance', [\App\Http\Controllers\Admin\ReceivableController::class, 'storeOpeningBalance'])->name('opening-balance.store');
            Route::get('/export', [\App\Http\Controllers\Admin\ReceivableController::class, 'export'])->name('export');
            Route::get('/transactions/{transactionId}', [\App\Http\Controllers\Admin\ReceivableController::class, 'showTransaction'])->name('transactions.show');
            Route::get('/{storeId}', [\App\Http\Controllers\Admin\ReceivableController::class, 'show'])->name('show');
            Route::post('/{storeId}/adjust', [\App\Http\Controllers\Admin\ReceivableController::class, 'adjust'])->name('adjust');
        });
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/users', [ReportController::class, 'users'])->name('users');
            Route::get('/stores', [ReportController::class, 'stores'])->name('stores');
            Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
            Route::get('/routes', [ReportController::class, 'routes'])->name('routes');
            Route::get('/driver-routes', [ReportController::class, 'driverRoutes'])->name('driver-routes');
            Route::get('/visits', [ReportController::class, 'visits'])->name('visits');
            Route::get('/driver-visits', [ReportController::class, 'driverVisits'])->name('driver-visits');
            Route::get('/transactions', [ReportController::class, 'transactions'])->name('transactions');
            Route::get('/sales-activities', [ReportController::class, 'salesActivities'])->name('sales-activities');
            Route::get('/export-pdf', [ReportController::class, 'exportPdf'])->name('export-pdf');
            Route::get('/export-excel', [ReportController::class, 'exportExcel'])->name('export-excel');
        });

        Route::prefix('archives')->name('archives.')->middleware('role:super-admin')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\DataArchiveController::class, 'index'])->name('index');
            Route::post('/preview', [\App\Http\Controllers\Admin\DataArchiveController::class, 'preview'])->name('preview');
            Route::post('/', [\App\Http\Controllers\Admin\DataArchiveController::class, 'archive'])->name('store');
            Route::get('/{id}/export-pdf', [\App\Http\Controllers\Admin\DataArchiveController::class, 'exportPdf'])->name('export-pdf');
            Route::get('/{id}/export-excel', [\App\Http\Controllers\Admin\DataArchiveController::class, 'exportExcel'])->name('export-excel');
            Route::post('/{id}/restore', [\App\Http\Controllers\Admin\DataArchiveController::class, 'restore'])->name('restore');
            Route::delete('/{id}/destroy-history', [\App\Http\Controllers\Admin\DataArchiveController::class, 'destroyHistory'])->name('destroy-history');
            Route::delete('/{id}/purge', [\App\Http\Controllers\Admin\DataArchiveController::class, 'purge'])->name('purge');
        });

        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/dropdown', [NotificationController::class, 'dropdown'])->name('dropdown');
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
            Route::delete('/destroy-all', [NotificationController::class, 'destroyAll'])->name('destroy-all');
            Route::get('/{id}/read', [NotificationController::class, 'read'])->name('read');
            Route::post('/{id}/mark-read', [NotificationController::class, 'markRead'])->name('mark-read');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        });
    });

    Route::post('/session/keep-alive', [App\Http\Controllers\SessionController::class, 'keepAlive'])
        ->name('session.keep-alive');

    });

require __DIR__.'/auth.php';