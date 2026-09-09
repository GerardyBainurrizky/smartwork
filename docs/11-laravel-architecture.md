# 11 — Laravel Architecture

> ISA SmartWork — Production-ready Laravel 12 architecture.
> Monolith-first, service-oriented, ready for modular extraction.

---

## 1. Folder Structure

```
app/
│
├── Actions/                             # Single-action classes (invocable)
│   ├── Attendance/
│   │   ├── ClockIn.php
│   │   ├── ClockOut.php
│   │   └── ApproveAttendance.php
│   ├── Auth/
│   │   ├── LoginUser.php
│   │   ├── LogoutUser.php
│   │   ├── ResetPassword.php
│   │   └── SendVerificationEmail.php
│   ├── Route/
│   │   ├── CreateRoute.php
│   │   ├── AssignRoute.php
│   │   ├── StartRoute.php
│   │   └── CompleteRoute.php
│   ├── Store/
│   │   ├── CreateStore.php
│   │   ├── UpdateStock.php
│   │   └── TransferStock.php
│   ├── Visit/
│   │   ├── PerformCheckIn.php
│   │   ├── PerformCheckOut.php
│   │   └── VerifyVisit.php
│   └── Report/
│       ├── GenerateReport.php
│       └── ExportReport.php
│
├── Console/
│   └── Commands/
│       ├── PruneGpsLogs.php
│       ├── PruneAuditLogs.php
│       ├── PruneExpiredTokens.php
│       ├── SyncOfflineData.php
│       └── GenerateScheduledReports.php
│
├── Contracts/                           # Interfaces
│   ├── AttendanceRepositoryInterface.php
│   ├── StoreRepositoryInterface.php
│   ├── RouteRepositoryInterface.php
│   ├── VisitRepositoryInterface.php
│   ├── GpsLogRepositoryInterface.php
│   ├── AuditLogRepositoryInterface.php
│   ├── ReportRepositoryInterface.php
│   └── UserRepositoryInterface.php
│
├── DTOs/                                # Data Transfer Objects
│   ├── LoginDTO.php
│   ├── RegisterDTO.php
│   ├── AttendanceDTO.php
│   ├── ClockInDTO.php
│   ├── ClockOutDTO.php
│   ├── RouteDTO.php
│   ├── RouteStopDTO.php
│   ├── VisitDTO.php
│   ├── CheckInDTO.php
│   ├── CheckOutDTO.php
│   ├── StoreDTO.php
│   ├── StockMovementDTO.php
│   ├── GpsLogDTO.php
│   ├── ReportDTO.php
│   └── UserDTO.php
│
├── Enums/                               # Backed enums
│   ├── AttendanceStatus.php
│   ├── VisitStatus.php
│   ├── RouteStatus.php
│   ├── UserStatus.php
│   ├── StoreType.php
│   ├── LeaveType.php
│   ├── StockMovementType.php
│   ├── ReportStatus.php
│   └── PermissionModule.php
│
├── Events/
│   ├── Auth/
│   │   ├── UserLoggedIn.php
│   │   └── UserLoggedOut.php
│   ├── Attendance/
│   │   ├── ClockedIn.php
│   │   ├── ClockedOut.php
│   │   └── AttendanceApproved.php
│   ├── Route/
│   │   ├── RouteAssigned.php
│   │   └── RouteCompleted.php
│   ├── Visit/
│   │   ├── VisitCheckedIn.php
│   │   ├── VisitCheckedOut.php
│   │   └── VisitVerified.php
│   └── Store/
│       └── StockLowAlert.php
│
├── Exceptions/
│   ├── Auth/
│   │   ├── InvalidCredentialsException.php
│   │   └── AccountSuspendedException.php
│   ├── Attendance/
│   │   ├── AlreadyClockedInException.php
│   │   ├── NotClockedInException.php
│   │   └── GeofenceViolationException.php
│   ├── Route/
│   │   ├── RouteNotFoundException.php
│   │   └── RouteAlreadyCompletedException.php
│   ├── Visit/
│   │   ├── VisitNotFoundException.php
│   │   └── VisitAlreadyVerifiedException.php
│   └── Store/
│       ├── StoreNotFoundException.php
│       └── InsufficientStockException.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── V1/
│   │   │       ├── AuthController.php
│   │   │       ├── UserController.php
│   │   │       ├── RoleController.php
│   │   │       ├── AttendanceController.php
│   │   │       ├── LeaveController.php
│   │   │       ├── StoreController.php
│   │   │       ├── ProductController.php
│   │   │       ├── StockController.php
│   │   │       ├── RouteController.php
│   │   │       ├── RouteStopController.php
│   │   │       ├── VisitController.php
│   │   │       ├── GpsController.php
│   │   │       ├── ReportController.php
│   │   │       └── AuditLogController.php
│   │   └── Web/
│   │       ├── Auth\LoginController.php
│   │       ├── Auth\ForgotPasswordController.php
│   │       ├── Auth\ResetPasswordController.php
│   │       ├── DashboardController.php
│   │       ├── UserController.php
│   │       ├── AttendanceController.php
│   │       ├── StoreController.php
│   │       ├── RouteController.php
│   │       ├── VisitController.php
│   │       ├── ReportController.php
│   │       └── AuditLogController.php
│   │
│   ├── Middleware/
│   │   ├── Authenticate.php
│   │   ├── CheckRole.php
│   │   ├── CheckPermission.php
│   │   ├── ForceJsonResponse.php
│   │   ├── LogHttpRequests.php
│   │   ├── ThrottleApi.php
│   │   ├── ValidateApiVersion.php
│   │   ├── EnforceGeofence.php
│   │   └── TrackUserActivity.php
│   │
│   ├── Requests/
│   │   ├── Api/
│   │   │   └── V1/
│   │   │       ├── Auth\LoginRequest.php
│   │   │       ├── Auth\ResetPasswordRequest.php
│   │   │       ├── Attendance\ClockInRequest.php
│   │   │       ├── Attendance\ClockOutRequest.php
│   │   │       ├── Route\CreateRouteRequest.php
│   │   │       ├── Route\UpdateRouteRequest.php
│   │   │       ├── Visit\CheckInRequest.php
│   │   │       ├── Visit\CheckOutRequest.php
│   │   │       ├── Store\CreateStoreRequest.php
│   │   │       ├── Store\UpdateStoreRequest.php
│   │   │       ├── Stock\StockMovementRequest.php
│   │   │       ├── Gps\SendLocationRequest.php
│   │   │       ├── User\CreateUserRequest.php
│   │   │       ├── User\UpdateUserRequest.php
│   │   │       └── Report\GenerateReportRequest.php
│   │   └── Web/
│   │       ├── Auth\LoginRequest.php
│   │       ├── User\CreateUserRequest.php
│   │       ├── User\UpdateUserRequest.php
│   │       └── ...
│   │
│   └── Resources/
│       └── Api/
│           └── V1/
│               ├── UserResource.php
│               ├── UserCollection.php
│               ├── AttendanceResource.php
│               ├── AttendanceCollection.php
│               ├── VisitResource.php
│               ├── VisitCollection.php
│               ├── RouteResource.php
│               ├── RouteCollection.php
│               ├── RouteStopResource.php
│               ├── StoreResource.php
│               ├── StoreCollection.php
│               ├── ProductResource.php
│               ├── StockResource.php
│               ├── StockMovementResource.php
│               ├── GpsLogResource.php
│               ├── LeaveResource.php
│               ├── ReportResource.php
│               ├── RoleResource.php
│               ├── PermissionResource.php
│               └── AuditLogResource.php
│
├── Jobs/
│   ├── GenerateReport.php
│   ├── ExportReport.php
│   ├── ProcessGpsBatch.php
│   ├── SendPushNotification.php
│   ├── SyncOfflineVisit.php
│   └── PruneOldData.php
│
├── Listeners/
│   ├── LogUserLogin.php
│   ├── LogUserLogout.php
│   ├── RecordAttendanceAudit.php
│   ├── RecordVisitAudit.php
│   ├── SendAttendanceNotification.php
│   ├── SendRouteAssignedNotification.php
│   ├── CheckStockThreshold.php
│   └── UpdateRouteProgress.php
│
├── Models/
│   ├── User.php
│   ├── Role.php
│   ├── Permission.php
│   ├── Attendance.php
│   ├── Leave.php
│   ├── Store.php
│   ├── Product.php
│   ├── Stock.php
│   ├── StockMovement.php
│   ├── Route.php
│   ├── RouteStop.php
│   ├── Visit.php
│   ├── GpsLog.php
│   ├── ReportTemplate.php
│   ├── Report.php
│   └── AuditLog.php
│
├── Observers/
│   ├── UserObserver.php
│   ├── AttendanceObserver.php
│   ├── VisitObserver.php
│   ├── RouteObserver.php
│   ├── StockObserver.php
│   └── AuditLogObserver.php
│
├── Policies/
│   ├── UserPolicy.php
│   ├── AttendancePolicy.php
│   ├── VisitPolicy.php
│   ├── RoutePolicy.php
│   ├── StorePolicy.php
│   ├── ReportPolicy.php
│   └── AuditLogPolicy.php
│
├── Providers/
│   ├── AppServiceProvider.php
│   ├── AuthServiceProvider.php
│   ├── EventServiceProvider.php
│   ├── RepositoryServiceProvider.php
│   └── RouteServiceProvider.php
│
├── Repositories/
│   ├── BaseRepository.php
│   ├── UserRepository.php
│   ├── RoleRepository.php
│   ├── AttendanceRepository.php
│   ├── LeaveRepository.php
│   ├── StoreRepository.php
│   ├── ProductRepository.php
│   ├── StockRepository.php
│   ├── StockMovementRepository.php
│   ├── RouteRepository.php
│   ├── RouteStopRepository.php
│   ├── VisitRepository.php
│   ├── GpsLogRepository.php
│   ├── ReportRepository.php
│   └── AuditLogRepository.php
│
├── Services/
│   ├── AuthService.php
│   ├── UserService.php
│   ├── RoleService.php
│   ├── AttendanceService.php
│   ├── LeaveService.php
│   ├── StoreService.php
│   ├── StockService.php
│   ├── RouteService.php
│   ├── RoutePlanningService.php
│   ├── VisitService.php
│   ├── GpsService.php
│   ├── GeofenceService.php
│   ├── ReportService.php
│   ├── AuditLogService.php
│   ├── NotificationService.php
│   └── OfflineSyncService.php
│
└── Traits/
    ├── HasAuditLogs.php
    ├── HasGeofence.php
    ├── HasUuid.php
    └── ApiResponse.php
```

---

## 2. Namespace Map

| Directory | Namespace | Purpose |
|-----------|-----------|---------|
| `Actions/` | `App\Actions` | Single-action invocable classes |
| `Contracts/` | `App\Contracts` | Repository/service interfaces |
| `DTOs/` | `App\DTOs` | Data Transfer Objects |
| `Enums/` | `App\Enums` | Backed PHP enums |
| `Events/` | `App\Events` | Event classes |
| `Exceptions/` | `App\Exceptions` | Domain exceptions |
| `Http/Controllers/` | `App\Http\Controllers` | Controllers |
| `Http/Middleware/` | `App\Http\Middleware` | HTTP middleware |
| `Http/Requests/` | `App\Http\Requests` | Form requests |
| `Http/Resources/` | `App\Http\Resources` | API resources |
| `Jobs/` | `App\Jobs` | Queued jobs |
| `Listeners/` | `App\Listeners` | Event listeners |
| `Models/` | `App\Models` | Eloquent models |
| `Observers/` | `App\Observers` | Model observers |
| `Policies/` | `App\Policies` | Authorization policies |
| `Repositories/` | `App\Repositories` | Data access layer |
| `Services/` | `App\Services` | Business logic layer |
| `Traits/` | `App\Traits` | Shared behaviors |

---

## 3. Service Layer Architecture

### 3.1 Design Pattern

```
Controller → Action → Service → Repository → Model
```

Each layer:
- **Controller** — HTTP concerns only (parse request, return response). Never contains
  business logic.
- **Action** — Single invocable class orchestrating a use case. Calls services.
- **Service** — Business logic, validation, orchestration. Calls repositories.
- **Repository** — Data access abstraction. Returns Eloquent collections/models or
  custom DTOs.
- **Model** — Eloquent ORM, relationships, casts, accessors/mutators.

### 3.2 Service Contract

```php
// app/Services/AttendanceService.php
namespace App\Services;

use App\Contracts\AttendanceRepositoryInterface;
use App\Contracts\AuditLogRepositoryInterface;
use App\DTOs\ClockInDTO;
use App\DTOs\ClockOutDTO;
use App\Exceptions\Attendance\AlreadyClockedInException;
use App\Exceptions\Attendance\GeofenceViolationException;
use App\Models\Attendance;
use App\Services\GeofenceService;
use Illuminate\Support\Facades\DB;

final readonly class AttendanceService
{
    public function __construct(
        private AttendanceRepositoryInterface $attendanceRepo,
        private AuditLogRepositoryInterface   $auditRepo,
        private GeofenceService              $geofence,
    ) {}

    public function clockIn(ClockInDTO $dto): Attendance
    {
        if ($this->attendanceRepo->hasClockedInToday($dto->userId)) {
            throw new AlreadyClockedInException();
        }

        return DB::transaction(function () use ($dto) {
            $attendance = $this->attendanceRepo->create([
                'user_id'     => $dto->userId,
                'date'        => now()->toDateString(),
                'clock_in'    => now(),
                'clock_in_lat' => $dto->latitude,
                'clock_in_lng' => $dto->longitude,
                'status'      => 'present',
            ]);

            $this->auditRepo->log(
                userId: $dto->userId,
                action: 'clock_in',
                module: 'attendance',
                entity: $attendance,
            );

            return $attendance;
        });
    }

    public function clockOut(ClockOutDTO $dto): Attendance
    {
        // ...
    }
}
```

### 3.3 Service Inventory

| Service | Responsibility |
|---------|---------------|
| `AuthService` | Login, logout, 2FA, password reset, token management |
| `UserService` | User CRUD, profile, activation/deactivation |
| `RoleService` | Role CRUD, permission assignment, role hierarchy |
| `AttendanceService` | Clock-in/out, attendance approval, status management |
| `LeaveService` | Leave CRUD, balance calculation, approval workflow |
| `StoreService` | Store CRUD, geofence setup, manager assignment |
| `StockService` | Stock level management, movement recording, threshold alerts |
| `RouteService` | Route CRUD, status management |
| `RoutePlanningService` | Route optimization, stop sequencing, distance calculation |
| `VisitService` | Check-in/out orchestration, GPS validation, status management |
| `GpsService` | GPS log ingestion, batch processing, real-time tracking |
| `GeofenceService` | Proximity validation, distance calculation, zone checking |
| `ReportService` | Report generation orchestration, format selection |
| `AuditLogService` | Audit logging, querying, export |
| `NotificationService` | Push notifications (FCM), email, in-app notifications |
| `OfflineSyncService` | Offline data queue, conflict resolution, sync orchestration |

---

## 4. Repository Pattern

### 4.1 Base Repository

```php
// app/Repositories/BaseRepository.php
namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    public function __construct(
        protected Model $model
    ) {}

    public function find(string|int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail(string|int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    protected function query(): Builder
    {
        return $this->model->newQuery();
    }
}
```

### 4.2 Repository Interfaces

```php
// app/Contracts/AttendanceRepositoryInterface.php
namespace App\Contracts;

use App\Models\Attendance;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AttendanceRepositoryInterface
{
    public function findByUserAndDate(string $userId, string $date): ?Attendance;
    public function hasClockedInToday(string $userId): bool;
    public function getByDateRange(string $startDate, string $endDate, array $filters = []): Collection;
    public function paginateByUser(string $userId, int $perPage = 15): LengthAwarePaginator;
    public function getPendingApprovals(): Collection;
    public function create(array $data): Attendance;
    public function update(Attendance $attendance, array $data): bool;
    public function getMonthlySummary(string $userId, string $month): array;
}
```

### 4.3 Implementation Example

```php
// app/Repositories/AttendanceRepository.php
namespace App\Repositories;

use App\Contracts\AttendanceRepositoryInterface;
use App\Models\Attendance;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    public function findByUserAndDate(string $userId, string $date): ?Attendance
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('date', $date)
            ->first();
    }

    public function hasClockedInToday(string $userId): bool
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->exists();
    }

    public function getByDateRange(string $startDate, string $endDate, array $filters = []): Collection
    {
        return $this->query()
            ->whereBetween('date', [$startDate, $endDate])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['department'] ?? null, fn ($q, $v) =>
                $q->whereHas('user', fn ($uq) => $uq->where('department', $v))
            )
            ->with('user')
            ->orderBy('date', 'desc')
            ->get();
    }

    public function paginateByUser(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->paginate($perPage);
    }

    public function getPendingApprovals(): Collection
    {
        return $this->query()
            ->where('status', 'pending')
            ->with('user')
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getMonthlySummary(string $userId, string $month): array
    {
        return $this->query()
            ->where('user_id', $userId)
            ->whereBetween('date', ["{$month}-01", "{$month}-31"])
            ->selectRaw("
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
            ")
            ->first()
            ->toArray();
    }
}
```

### 4.4 Repository Binding

```php
// app/Providers/RepositoryServiceProvider.php
namespace App\Providers;

use App\Contracts\AttendanceRepositoryInterface;
use App\Contracts\AuditLogRepositoryInterface;
use App\Contracts\GpsLogRepositoryInterface;
use App\Contracts\RouteRepositoryInterface;
use App\Contracts\StoreRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\VisitRepositoryInterface;
use App\Repositories\AttendanceRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\GpsLogRepository;
use App\Repositories\RouteRepository;
use App\Repositories\StoreRepository;
use App\Repositories\UserRepository;
use App\Repositories\VisitRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AttendanceRepositoryInterface::class, AttendanceRepository::class);
        $this->app->bind(StoreRepositoryInterface::class, StoreRepository::class);
        $this->app->bind(RouteRepositoryInterface::class, RouteRepository::class);
        $this->app->bind(VisitRepositoryInterface::class, VisitRepository::class);
        $this->app->bind(GpsLogRepositoryInterface::class, GpsLogRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);
    }
}
```

---

## 5. Middleware Structure

### 5.1 Middleware Stack

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    // Global middleware
    $middleware->append([
        \App\Http\Middleware\LogHttpRequests::class,
        \App\Http\Middleware\TrackUserActivity::class,
    ]);

    // Web group
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);

    // API group
    $middleware->api(append: [
        \App\Http\Middleware\ForceJsonResponse::class,
        \App\Http\Middleware\ThrottleApi::class,
        \App\Http\Middleware\ValidateApiVersion::class,
    ]);

    // Aliased middleware
    $middleware->alias([
        'role'       => \App\Http\Middleware\CheckRole::class,
        'permission' => \App\Http\Middleware\CheckPermission::class,
        'geofence'   => \App\Http\Middleware\EnforceGeofence::class,
    ]);
});
```

### 5.2 Middleware Definitions

```php
// app/Http/Middleware/CheckRole.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (! $request->user() || ! $request->user()->hasAnyRole($roles)) {
            abort(403, 'Forbidden: insufficient role.');
        }

        return $next($request);
    }
}
```

```php
// app/Http/Middleware/CheckPermission.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        if (! $request->user() || ! $request->user()->can($permission)) {
            abort(403, 'Forbidden: insufficient permission.');
        }

        return $next($request);
    }
}
```

```php
// app/Http/Middleware/EnforceGeofence.php
namespace App\Http\Middleware;

use App\Services\GeofenceService;
use Closure;
use Illuminate\Http\Request;

class EnforceGeofence
{
    public function __construct(private GeofenceService $geofence) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $storeId = $request->route('store') ?? $request->input('store_id');

        if ($storeId) {
            $lat = $request->input('latitude');
            $lng = $request->input('longitude');

            if (! $this->geofence->isWithinRadius($storeId, $lat, $lng)) {
                return response()->json([
                    'message' => 'You are outside the allowed geofence area.',
                    'distance' => $this->geofence->getDistanceFromStore($storeId, $lat, $lng),
                    'allowed_radius' => config('gps.geofence_radius', 500),
                ], 422);
            }
        }

        return $next($request);
    }
}
```

```php
// app/Http/Middleware/ValidateApiVersion.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateApiVersion
{
    public function handle(Request $request, Closure $next): mixed
    {
        $version = $request->header('Accept-Version', 'v1');

        if (! in_array($version, ['v1', 'v2'])) {
            return response()->json([
                'message' => 'Unsupported API version.',
                'supported_versions' => ['v1'],
            ], 400);
        }

        $request->attributes->set('api_version', $version);

        return $next($request);
    }
}
```

```php
// app/Http/Middleware/TrackUserActivity.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()) {
            $request->user()->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);
        }

        return $next($request);
    }
}
```

### 5.3 Middleware Map

| Middleware | Type | Scope | Purpose |
|-----------|------|-------|---------|
| `auth` | Laravel | All | Authenticate user |
| `auth:sanctum` | Laravel | API | Token-based auth |
| `role` | Custom | Route | Check user role(s) |
| `permission` | Custom | Route | Check specific permission |
| `geofence` | Custom | Route | Validate GPS proximity |
| `ForceJsonResponse` | Custom | API | Force JSON Content-Type |
| `ThrottleApi` | Custom | API | Rate limiting per route |
| `ValidateApiVersion` | Custom | API | Version header validation |
| `LogHttpRequests` | Custom | Global | Request/response logging |
| `TrackUserActivity` | Custom | Global | Last login tracking |

### 5.4 Route Middleware Application

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Admin only
    Route::middleware('role:super-admin,manager')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('audit-logs', [AuditLogController::class, 'index']);
    });

    // Attendance — requires auth
    Route::prefix('attendance')->group(function () {
        Route::post('clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('history', [AttendanceController::class, 'index']);
        Route::middleware('permission:attendance.approve')->group(function () {
            Route::post('{attendance}/approve', [AttendanceController::class, 'approve']);
            Route::post('{attendance}/reject', [AttendanceController::class, 'reject']);
        });
    });

    // Visits — requires auth + geofence
    Route::prefix('visits')->group(function () {
        Route::middleware('geofence')->group(function () {
            Route::post('check-in', [VisitController::class, 'checkIn']);
            Route::post('check-out', [VisitController::class, 'checkOut']);
        });
        Route::middleware('permission:visits.verify')->group(function () {
            Route::post('{visit}/verify', [VisitController::class, 'verify']);
        });
    });

    // GPS — continuous tracking
    Route::post('gps/ping', [GpsController::class, 'store']);
    Route::get('gps/track/{user}', [GpsController::class, 'track'])
        ->middleware('permission:gps.view');
});
```

---

## 6. API Structure

### 6.1 API Versioning

```php
// routes/api.php
Route::prefix('v1')->group(base_path('routes/api_v1.php'));

// routes/api_v1.php
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/refresh', [AuthController::class, 'refresh']);
Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('stores', StoreController::class);
    Route::apiResource('routes', RouteController::class);
    Route::apiResource('visits', VisitController::class)->only(['index', 'show']);
    // ...
});
```

### 6.2 Controller Pattern

```php
// app/Http/Controllers/Api/V1/VisitController.php
namespace App\Http\Controllers\Api\V1;

use App\Actions\Visit\PerformCheckIn;
use App\Actions\Visit\PerformCheckOut;
use App\Actions\Visit\VerifyVisit;
use App\DTOs\CheckInDTO;
use App\DTOs\CheckOutDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Visit\CheckInRequest;
use App\Http\Requests\Api\V1\Visit\CheckOutRequest;
use App\Http\Resources\Api\V1\VisitResource;
use App\Http\Resources\Api\V1\VisitCollection;
use App\Contracts\VisitRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function __construct(
        private VisitRepositoryInterface $visitRepo,
    ) {}

    public function index(Request $request): VisitCollection
    {
        $visits = $this->visitRepo->paginateByUser(
            $request->user()->id,
            $request->integer('per_page', 15)
        );

        return new VisitCollection($visits);
    }

    public function show(string $id): VisitResource
    {
        $visit = $this->visitRepo->findOrFail($id);
        return new VisitResource($visit);
    }

    public function checkIn(CheckInRequest $request, PerformCheckIn $action): JsonResponse
    {
        $dto = CheckInDTO::fromRequest($request);
        $visit = $action->execute($dto);

        return (new VisitResource($visit))
            ->response()
            ->setStatusCode(201);
    }

    public function checkOut(CheckOutRequest $request, PerformCheckOut $action): JsonResponse
    {
        $dto = CheckOutDTO::fromRequest($request);
        $visit = $action->execute($dto);

        return new VisitResource($visit);
    }

    public function verify(string $id, VerifyVisit $action): JsonResponse
    {
        $visit = $action->execute($id, request()->user()->id);

        return new VisitResource($visit);
    }
}
```

### 6.3 Form Request

```php
// app/Http/Requests/Api/V1/Visit/CheckInRequest.php
namespace App\Http\Requests\Api\V1\Visit;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_stop_id' => ['required', 'uuid', 'exists:route_stops,id'],
            'latitude'      => ['required', 'numeric', 'between:-90,90'],
            'longitude'     => ['required', 'numeric', 'between:-180,180'],
            'accuracy'      => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'photo'         => ['nullable', 'image', 'max:5120'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'route_stop_id.required' => 'Route stop ID is required.',
            'latitude.between'       => 'Latitude must be between -90 and 90.',
            'longitude.between'      => 'Longitude must be between -180 and 180.',
        ];
    }
}
```

### 6.4 API Resource

```php
// app/Http/Resources/Api/V1/VisitResource.php
namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'check_in' => [
                'at'        => $this->check_in_at?->toISOString(),
                'latitude'  => $this->check_in_latitude,
                'longitude' => $this->check_in_longitude,
                'accuracy'  => $this->check_in_accuracy,
                'photo_url' => $this->check_in_photo_url,
            ],
            'check_out' => $this->when($this->check_out_at, [
                'at'        => $this->check_out_at?->toISOString(),
                'latitude'  => $this->check_out_latitude,
                'longitude' => $this->check_out_longitude,
                'accuracy'  => $this->check_out_accuracy,
                'photo_url' => $this->check_out_photo_url,
            ]),
            'duration_minutes' => $this->duration_minutes,
            'distance_from_store' => $this->distance_from_store,
            'within_geofence' => $this->distance_from_store <= config('gps.geofence_radius'),
            'notes' => $this->notes,
            'user' => new UserResource($this->whenLoaded('user')),
            'route_stop' => new RouteStopResource($this->whenLoaded('routeStop')),
            'verified_by' => new UserResource($this->whenLoaded('verifiedBy')),
            'verified_at' => $this->verified_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

### 6.5 API Response Standard

```json
// Success
{
    "data": { ... },
    "meta": {
        "api_version": "v1",
        "timestamp": "2026-08-04T14:30:00+07:00"
    }
}

// Collection
{
    "data": [ ... ],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 72,
        "api_version": "v1",
        "timestamp": "2026-08-04T14:30:00+07:00"
    }
}

// Error
{
    "error": {
        "code": "GEOFENCE_VIOLATION",
        "message": "You are outside the allowed geofence area.",
        "details": {
            "distance_meters": 750,
            "allowed_radius": 500
        }
    },
    "meta": {
        "api_version": "v1",
        "timestamp": "2026-08-04T14:30:00+07:00"
    }
}
```

### 6.6 API Endpoints

| Method | URI | Controller | Middleware |
|--------|-----|-----------|------------|
| POST | `v1/auth/login` | AuthController@login | — |
| POST | `v1/auth/refresh` | AuthController@refresh | — |
| POST | `v1/auth/logout` | AuthController@logout | auth:sanctum |
| GET | `v1/me` | AuthController@me | auth:sanctum |
| GET | `v1/users` | UserController@index | auth:sanctum, role:super-admin,manager,hr |
| POST | `v1/users` | UserController@store | auth:sanctum, role:super-admin,manager,hr |
| GET | `v1/users/{user}` | UserController@show | auth:sanctum |
| PUT | `v1/users/{user}` | UserController@update | auth:sanctum |
| DELETE | `v1/users/{user}` | UserController@destroy | auth:sanctum, role:super-admin |
| GET | `v1/roles` | RoleController@index | auth:sanctum, role:super-admin |
| POST | `v1/attendance/clock-in` | AttendanceController@clockIn | auth:sanctum |
| POST | `v1/attendance/clock-out` | AttendanceController@clockOut | auth:sanctum |
| GET | `v1/attendance` | AttendanceController@index | auth:sanctum |
| POST | `v1/attendance/{attendance}/approve` | AttendanceController@approve | auth:sanctum, permission:attendance.approve |
| GET | `v1/stores` | StoreController@index | auth:sanctum |
| POST | `v1/stores` | StoreController@store | auth:sanctum, permission:stores.manage |
| GET | `v1/stores/{store}` | StoreController@show | auth:sanctum |
| GET | `v1/stores/{store}/stock` | StockController@index | auth:sanctum |
| POST | `v1/stock-movements` | StockController@movement | auth:sanctum |
| GET | `v1/routes` | RouteController@index | auth:sanctum |
| POST | `v1/routes` | RouteController@store | auth:sanctum, permission:routes.create |
| GET | `v1/routes/{route}` | RouteController@show | auth:sanctum |
| GET | `v1/routes/{route}/stops` | RouteStopController@index | auth:sanctum |
| POST | `v1/visits/check-in` | VisitController@checkIn | auth:sanctum, geofence |
| POST | `v1/visits/check-out` | VisitController@checkOut | auth:sanctum, geofence |
| GET | `v1/visits` | VisitController@index | auth:sanctum |
| GET | `v1/visits/{visit}` | VisitController@show | auth:sanctum |
| POST | `v1/visits/{visit}/verify` | VisitController@verify | auth:sanctum, permission:visits.verify |
| POST | `v1/gps/ping` | GpsController@store | auth:sanctum |
| GET | `v1/gps/track/{user}` | GpsController@track | auth:sanctum, permission:gps.view |
| GET | `v1/reports/templates` | ReportController@templates | auth:sanctum |
| POST | `v1/reports/generate` | ReportController@generate | auth:sanctum, permission:reports.export |
| GET | `v1/reports` | ReportController@index | auth:sanctum |
| GET | `v1/audit-logs` | AuditLogController@index | auth:sanctum, permission:audit.view |

---

## 7. DTO Pattern

```php
// app/DTOs/CheckInDTO.php
namespace App\DTOs;

use App\Http\Requests\Api\V1\Visit\CheckInRequest;

final readonly class CheckInDTO
{
    public function __construct(
        public string $userId,
        public string $routeStopId,
        public float  $latitude,
        public float  $longitude,
        public ?float $accuracy,
        public ?string $photo,
        public ?string $notes,
    ) {}

    public static function fromRequest(CheckInRequest $request): self
    {
        return new self(
            userId:      $request->user()->id,
            routeStopId: $request->input('route_stop_id'),
            latitude:    (float) $request->input('latitude'),
            longitude:   (float) $request->input('longitude'),
            accuracy:    $request->has('accuracy') ? (float) $request->input('accuracy') : null,
            photo:       $request->file('photo')?->store('visits/check-in', 's3'),
            notes:       $request->input('notes'),
        );
    }
}
```

---

## 8. Action Pattern

```php
// app/Actions/Visit/PerformCheckIn.php
namespace App\Actions\Visit;

use App\DTOs\CheckInDTO;
use App\Exceptions\Visit\VisitNotFoundException;
use App\Models\Visit;
use App\Services\GeofenceService;
use App\Services\VisitService;

final readonly class PerformCheckIn
{
    public function __construct(
        private VisitService $visitService,
        private GeofenceService $geofence,
    ) {}

    public function execute(CheckInDTO $dto): Visit
    {
        $store = $this->visitService->getStoreForRouteStop($dto->routeStopId);

        $distance = $this->geofence->calculateDistance(
            $dto->latitude, $dto->longitude,
            $store->latitude, $store->longitude
        );

        return $this->visitService->checkIn($dto, $distance);
    }
}
```

---

## 9. Enum Pattern

```php
// app/Enums/VisitStatus.php
namespace App\Enums;

enum VisitStatus: string
{
    case Pending    = 'pending';
    case CheckedIn  = 'checked_in';
    case CheckedOut = 'checked_out';
    case Verified   = 'verified';
    case Rejected   = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::CheckedIn  => 'Checked In',
            self::CheckedOut => 'Checked Out',
            self::Verified   => 'Verified',
            self::Rejected   => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending    => 'warning',
            self::CheckedIn  => 'info',
            self::CheckedOut => 'info',
            self::Verified   => 'success',
            self::Rejected   => 'danger',
        };
    }
}
```

---

## 10. Event & Listener

```php
// app/Events/Visit/VisitCheckedIn.php
namespace App\Events\Visit;

use App\Models\Visit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitCheckedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(public Visit $visit) {}
}

// app/Listeners/RecordVisitAudit.php
namespace App\Listeners;

use App\Events\Visit\VisitCheckedIn;
use App\Services\AuditLogService;

class RecordVisitAudit
{
    public function __construct(private AuditLogService $audit) {}

    public function handle(VisitCheckedIn $event): void
    {
        $this->audit->log(
            userId: $event->visit->user_id,
            action: 'checked_in',
            module: 'visits',
            entity: $event->visit,
        );
    }
}
```

---

## 11. Configuration Files

```
config/
├── app.php
├── auth.php
├── database.php
├── sanctum.php
├── gps.php                    # GPS & geofence settings
│   ├── geofence_radius        # 500 (meters)
│   ├── ping_interval          # 30 (seconds)
│   ├── batch_size             # 100
│   ├── accuracy_threshold     # 50 (meters, reject if >)
│   └── offline_sync_retry     # 3
├── attendance.php             # Attendance settings
│   ├── clock_in_start         # '05:00'
│   ├── clock_in_end           # '09:00'
│   ├── late_threshold         # 30 (minutes)
│   └── overtime_start         # '17:00'
├── leave.php                  # Leave balance rules
├── notification.php           # FCM, email config
└── report.php                 # Report generation settings
```

---

## 12. Composer Dependencies

```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.9",
        "laravel/horizon": "^5.0",
        "spatie/laravel-permission": "^6.0",
        "spatie/laravel-data": "^4.0",
        "matanyada/laravel-eloquent-spatial": "^2.0",
        "kreait/laravel-firebase": "^5.0",
        "barryvdh/laravel-dompdf": "^3.0",
        "maatwebsite/excel": "^3.1",
        "ramsey/uuid": "^4.7"
    },
    "require-dev": {
        "laravel/pint": "^1.0",
        "larastan/larastan": "^2.0",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0"
    }
}
```

---

**Document version:** 1.0
**Last updated:** 04 Aug 2026
**Laravel version:** 12.x
**PHP version:** 8.3+