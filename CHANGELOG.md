# Changelog: Dadhen Mandiri

All notable changes, architectural milestones, and system transitions for Dadhen Mandiri are documented in this file.

---

## [Phase 8] - 2026-11-08: Owner & Admin Dashboard UI Enhancement

### Improved
- **Visual Design & Dark Navy Identity**:
  - Elevated Dadhen Mandiri dark navy palette (`#0A192F` via `#112240` to `#1D3557`) with subtle cyan/sky accents (`#38bdf8`, `#0ea5e9`).
  - Added smooth hover micro-elevation (`hover:-translate-y-0.5` / `hover:-translate-y-1`), soft ambient glow borders, and enhanced shadows (`hover:shadow-xl hover:shadow-sky-950/40`).
  - Implemented 180–250ms smooth cubic-bezier easing with full `prefers-reduced-motion` compliance.
- **Workspace Year Containers**:
  - Transformed year cards into clickable interactive surfaces (`cursor-pointer`) linking directly to year workspaces.
  - Added directional arrow indicators with hover animation (`group-hover:translate-x-0.5`).
  - Embedded ambient cyan glow orbs on container hover.
  - Added accessible spreadsheet shortcuts for Admin (`Analisa` and `Cost` pills) on verified year cards.
- **Owner Dashboard Experience**:
  - Executive styling with primary navigation to Company Analytics, User Management, and Activity Logs.
  - Gradient buttons for `+ Add Year` and `Analytics`.
  - Intuitive management actions (`Password`, `Arsipkan/Pulihkan`) integrated into year containers.
- **Admin Dashboard Experience**:
  - Minimalist, focused view strictly hiding Owner-only management actions.
  - Direct password authentication trigger on locked cards with amber lock badge.
  - One-click navigation to monthly workspaces and sheets once verified.
- **KPI Statistics Cards**:
  - Added subtle gradient transitions (`hover:bg-gradient-to-br`), icon micro-scaling (`group-hover:scale-110`), and bottom accent line highlights.

---

## [Phase 7] - 2026-11-08: Year-Based Workspace Architecture & Authentication

### Added
- **Migration `2026_11_08_000005_add_password_to_operational_years_table.php`**:
  - Added `password` (string, nullable) and `password_version` (unsigned integer, default 1) to `operational_years`.
- **`app/Models/OperationalYear.php`**:
  - Added password hashing, verification, user session isolation (`year_unlocked_{yearId}_{userId}`), and password invalidation tracking via `password_version`.
- **`app/Models/FisheriesGroup.php`**:
  - Integrated `operationalYearModel()` helper, linking `hasPassword()`, `verifyPassword()`, and `isUnlockedForSession()` directly to the parent `OperationalYear`.
- **`app/Http/Controllers/CompanyYearController.php`**:
  - Implemented `show(int $year)`: Year-based workspace view displaying 12 monthly workspaces.
  - Implemented `unlock(int $year)`: Centralized year password authentication endpoint.
  - Implemented `setPassword(int $year)`: Owner-only password configuration with version incrementing to immediately invalidate active Admin sessions.
- **Routes & Middleware**:
  - Added `company.year`, `company.year.unlock`, `company.year.password` routes.
  - Updated `EnsureWorkspaceAdminReauthentication.php` to allow seamless navigation across all 12 months in an authenticated year without exit prompts or authentication loops.
- **Views**:
  - `resources/views/company/year.blade.php`: Year workspace portal displaying 12 monthly cards and authentication lock screen.
  - `resources/views/dashboard.blade.php`: Streamlined dashboard displaying workspace years only.
- **`tests/Feature/YearBasedWorkspaceArchitectureTest.php`**:
  - 11 comprehensive automated tests (70 assertions) covering year card display, Owner password bypass, Admin password authentication, month-to-month and sheet-to-sheet navigation without repeated passwords, user session isolation, password version invalidation, and direct URL protection.

---

## [Phase 6 & Final QA] - 2026-11-08: User Management, Activity Logs, Bug Fixes & Feature Removals

### Added
- **Migration `2026_11_08_000003_add_status_and_activity_to_users_and_create_logs_table.php`**:
  - Added `users.is_active` (boolean default true) and `users.last_active_at` (nullable timestamp).
  - Created table `company_activity_logs` with indexed columns (`user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties` JSON, `created_at`).
- **`app/Models/CompanyActivityLog.php`**: Model for company-wide activity audit logging.
- **`app/Services/ActivityLogger.php`**: Security-first activity logging service with recursive secret sanitization (`ActivityLogger::sanitize()`). Passwords, hashes, and tokens are never logged.
- **`app/Http/Controllers/CompanyUserController.php`**: Centralized user management controller for Owner:
  - User catalog with search, role filters, status filters, and pagination.
  - User creation, update, role change, activation/deactivation, and password reset.
  - Last Active Owner Protection preventing demotion, deactivation, or self-demotion of the last active Owner.
- **`app/Http/Controllers/CompanyActivityLogController.php`**: Centralized activity log viewer for Owner with filters (action, user, date range) and metadata inspection accordion.
- **Views**:
  - `resources/views/company/users/index.blade.php`: User management table, KPI stats, and modals (Add User, Edit User, Change Role, Reset Password).
  - `resources/views/company/activity_logs/index.blade.php`: Activity logs audit timeline, action badges, and sanitized properties inspector.
- **`tests/Feature/WorkspacePasswordAuthenticationTest.php`**: Regression test suite verifying workspace password authentication, session persistence, switching between spreadsheets without loop, and direct URL protection.
- **`tests/Feature/RemovedFeaturesTest.php`**: Regression test suite verifying that Create Workspace and Join Team buttons are removed and routes return 404/405 while internal provisioning remains functional.
- **`tests/Feature/CompanyUserManagementAndActivityLogsTest.php`**: Feature test suite verifying User Management CRUD, Last Active Owner Protection, deactivation login block, Activity Log recording, and Admin 403 Forbidden enforcement.

### Fixed
- **Workspace Password Authentication Loop**:
  - Replaced `window.Livewire.navigate(result.redirect)` with `window.location.reload()` / `window.location.href = result.redirect` in `layouts/app.blade.php`, cleanly resetting Alpine state and preventing infinite modal display.
  - Enhanced `FisheriesGroup.php` to store cumulative verified sheets in session (`_sheets`), preventing repeated password prompts when switching between verified sheets in a workspace.
  - Updated `spreadsheet-worksheet.blade.php` tab click handler to dispatch `sheet-switch` only when a sheet is actually unverified in the session.
  - Updated `EnsureWorkspaceAdminReauthentication.php` to recognize `company.workspace` without false-positive exit intercepts.

### Removed
- **Create Workspace & Join Team Features**:
  - Removed "Buat Workspace" and "Gabung Tim" action buttons from `resources/views/dashboard.blade.php`.
  - Removed "+ Buat Workspace Baru" from the dashboard empty state.
  - Removed "Buat Baru" and "Gabung" sidebar buttons from `resources/views/layouts/app.blade.php`.
  - Removed Modal 1 (`modal-create-group`) and Modal 2 (`modal-join-group`) from `resources/views/layouts/app.blade.php`.
  - Removed `groups.store` and `groups.join` routes from `routes/web.php`.
  - Removed `createGroup` and `joinGroup` methods from `app/Http/Controllers/DashboardController.php`.

---

## [Phase 5] - 2026-11-08: Company Analytics & Centralized Reporting Module

### Added
- **`app/Services/CompanyAnalyticsService.php`**: High-performance aggregation service executing targeted cell queries from `Rank`, `Margin`, `Cost`, and `Analisa` sheets.
- **Overview Sub-Module**: Executive dashboard with Total Revenue (Omset), Gross Margin (with %), Total Operational Cost, Net Profit (with %), Production volume & rendemen indicators, and monthly trend overview.
- **Financial Analytics Sub-Module**: Detailed financial ledger, cost composition breakdown (Raw Materials vs Operational Cost vs Payroll), and itemized supplies & utility expenses.
- **Operational Analytics Sub-Module**: Production volumes (Raw Material Input kg, Fillet kg, Rendemen Fillet %, Finished Goods kg, Side Products kg, Total Rendemen %), and itemized utility consumption (MC, PB, Chemicals, Listrik PLN, Air & Es, Solar, Transport, Cold Storage, Sewa).
- **Yearly Comparison Sub-Module**: Side-by-side comparative table across all active and archived operational years.
- **Owner-Only Security**: Strictly guarded `CompanyYearController::analytics` and `CompanyYearController::yearlyComparison`. Admin accounts are rejected with HTTP 403 Forbidden on all web routes and JSON endpoints.
- **`tests/Feature/CompanyAnalyticsTest.php`**: 6 automated feature tests verifying Owner access, Admin 403 enforcement, verified calculations against manual fixtures, dynamic year inclusion, and zero data fabrication safety.

---

## [Phase 4] - 2026-11-08: Dynamic Year Management & Provisioning

### Added
- **Dynamic Year Creation**: Added `+ Add Year` button and modal to `resources/views/groups/workspace.blade.php` and `resources/views/dashboard.blade.php`.
- **Automatic Provisioning**: `CompanyWorkspaceInitializer::provisionYear()` creates 1 OperationalYear, 12 MonthlyWorkspaces, and 60 canonical SpreadsheetSheets with formulas and styling intact.
- **Safe Recovery & Idempotency**: Provisioning is wrapped in database transactions and safely recovers incomplete years without creating duplicates.
- **Year Archiving & Restoring**: Implemented `POST /company/years/{year}/archive` and `POST /company/years/{year}/restore`.
- **Strict Read-Only Enforcement on Archived Years**: `SpreadsheetController` and `SpreadsheetWorksheet` abort with HTTP 403 on all mutation operations (`saveCell`, `addColumn`, `deleteColumn`, `updateColumn`, `addRow`, `deleteRow`, `pasteTsvData`, `formatCell`, `sortColumn`, `executeFindReplace`).
- **`tests/Feature/DynamicYearManagementTest.php`**: 10 automated feature tests (125 assertions) verifying Owner creation, Admin rejection, format validation, idempotency, safe recovery, archiving, and read-only enforcement.

---

## [Phase 3] - 2026-11-08: Owner & Admin Two-Role Transition

### Changed
- Executed migration `2026_11_08_000002_migrate_to_owner_and_admin_roles.php` adding `users.role` and mapping users to strictly `owner` or `admin`.
- Updated `User::isOwner()` and `User::isAdmin()`.
- Updated `SpreadsheetSheet::isRestrictedForRole(?string $role)` to grant Owner full unrestricted access and restrict Admin on Margin, Pendukung, and Rank.

---

## [Phase 2] - 2026-11-08: Centralized Company Workspace Architecture

### Added
- Executed migration `2026_11_08_000001_create_company_years_and_monthly_workspaces_tables.php`.
- Created `OperationalYear` and `MonthlyWorkspace` models.
- Created `CompanyWorkspaceInitializer` and initialized base operational years (2024, 2025, 2026) with 36 monthly workspaces and 180 spreadsheets.
- Implemented dynamic database-driven year and month navigation in `GroupController.php` and Blade layouts.
- Created `tests/Feature/CentralizedCompanyWorkspaceTest.php` (7 passed, 113 assertions).

---

## [Phase 1] - 2026-11-08: Technical & Functional System Audit

### Added
- `PROJECT_CONTEXT.md`, `IMPLEMENTATION_PROGRESS.md`, `SYSTEM_AUDIT_REPORT.md`.
- Comprehensive audit of models, migrations, spreadsheet engine, controllers, views, and test suites.
