<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SpreadsheetController;
use App\Http\Middleware\EnsureWorkspaceAdminReauthentication;

/*
|--------------------------------------------------------------------------
| Web Routes - Dadhen Mandiri Collaborative Spreadsheet Platform
|--------------------------------------------------------------------------
*/

// Root redirect
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Authentication Routes (Guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Logout (Authenticated only)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware(['auth', EnsureWorkspaceAdminReauthentication::class]);

// Protected Application Routes (Strictly Authenticated)
Route::middleware(['auth', EnsureWorkspaceAdminReauthentication::class])->group(function () {
    
    // Dashboard & Overview
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/groups', [DashboardController::class, 'createGroup'])->name('groups.store');
    Route::post('/groups/join', [DashboardController::class, 'joinGroup'])->name('groups.join')->middleware('throttle:15,1');
    
    // Friends & Colleague Networking
    Route::get('/friends/search', [DashboardController::class, 'searchFriends'])->name('friends.search');
    Route::get('/friends/list', [DashboardController::class, 'getFriendsData'])->name('friends.list');
    Route::post('/friends', [DashboardController::class, 'addFriend'])->name('friends.store');
    Route::post('/friends/{friendship}/accept', [DashboardController::class, 'acceptFriend'])->name('friends.accept');
    Route::delete('/friends/{friendship}', [DashboardController::class, 'removeFriend'])->name('friends.remove');
    
    // User Profile (Public Viewable Profile)
    Route::get('/users/{user}/profile', [DashboardController::class, 'showProfile'])->name('users.profile');
    
    Route::post('/notifications/read-all', [DashboardController::class, 'markNotificationsRead'])->name('notifications.read-all');

    // Group Workspace & Sheets (Verified in GroupController)
    Route::get('/groups/{group}/workspace/{sheet?}', [GroupController::class, 'workspace'])->name('group.workspace');
    Route::get('/groups/{group}/search-users', [GroupController::class, 'searchUsers'])->name('groups.users.search');
    Route::post('/groups/{group}/unlock', [GroupController::class, 'unlock'])->name('groups.unlock');
    Route::post('/groups/{group}/leave', [GroupController::class, 'leaveWorkspace'])->name('groups.leave')->middleware('throttle:15,1');
    Route::post('/groups/bulk-destroy', [GroupController::class, 'bulkDestroy'])->name('groups.bulk-destroy');
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember'])->name('groups.members.add');
    Route::put('/groups/{group}/members/{member}', [GroupController::class, 'updateMemberRole'])->name('groups.members.update');
    Route::delete('/groups/{group}/members/{member}', [GroupController::class, 'removeMember'])->name('groups.members.remove');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::get('/groups/{group}/backup-xlsx', [GroupController::class, 'backupXlsx'])->name('groups.backup');

    // Sheet operations
    Route::post('/groups/{group}/sheets', [GroupController::class, 'addSheet'])->name('groups.sheets.add');
    Route::put('/sheets/{sheet}/rename', [GroupController::class, 'renameSheet'])->name('sheets.rename');
    Route::post('/sheets/{sheet}/duplicate', [GroupController::class, 'duplicateSheet'])->name('sheets.duplicate');
    Route::delete('/sheets/{sheet}', [GroupController::class, 'deleteSheet'])->name('sheets.delete');

    // Spreadsheet Endpoints
    Route::get('/spreadsheet/export/{sheet}', [SpreadsheetController::class, 'exportCsv'])->name('spreadsheet.export');
    Route::post('/spreadsheet/import/{sheet}', [SpreadsheetController::class, 'importCsv'])->name('spreadsheet.import');
    Route::post('/spreadsheet/cell', [SpreadsheetController::class, 'saveCell'])->name('spreadsheet.cell.save');
    Route::post('/spreadsheet/column/{sheet}', [SpreadsheetController::class, 'addColumn'])->name('spreadsheet.column.add');
    Route::delete('/spreadsheet/column/{column}', [SpreadsheetController::class, 'deleteColumn'])->name('spreadsheet.column.delete');
    Route::put('/spreadsheet/column/{column}', [SpreadsheetController::class, 'updateColumn'])->name('spreadsheet.column.update');
    Route::post('/spreadsheet/row/{sheet}', [SpreadsheetController::class, 'addRow'])->name('spreadsheet.row.add');
    Route::delete('/spreadsheet/row/{row}', [SpreadsheetController::class, 'deleteRow'])->name('spreadsheet.row.delete');
});
