<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeManagementController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('employees', [EmployeeManagementController::class, 'index'])
        ->name('employees.index');
    Route::get('employees/{employee}', [EmployeeManagementController::class, 'show'])
        ->name('employees.show');
    Route::controller(AttendanceController::class)
        ->prefix('attendance')
        ->name('attendance.')
        ->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::post('clock-in', 'clockIn')->name('clock-in');
            Route::post('clock-out', 'clockOut')->name('clock-out');
            Route::get('timesheets', 'timesheets')->name('timesheets');
            Route::patch('timesheets/{attendance}', 'updateTimesheet')->name('timesheets.update');
        });
});

require __DIR__.'/settings.php';
