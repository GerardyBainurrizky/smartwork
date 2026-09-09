# 10 — Database Design

> ISA SmartWork — Full migration schemas for Laravel 12 / MySQL 8.0+
> All foreign keys use `cascade` or `restrict` as appropriate. UUIDs for
> user-facing entities. `bigint` for high-volume log tables.

---

## 1. Migration Order

```
01  create_roles_table
02  create_permissions_table
03  create_permission_role_table
04  create_users_table
05  create_role_user_table
06  create_personal_access_tokens_table
07  create_stores_table
08  create_products_table
09  create_stocks_table
10  create_stock_movements_table
11  create_routes_table
12  create_route_stops_table
13  create_visits_table
14  create_attendances_table
15  create_leaves_table
16  create_gps_logs_table
17  create_report_templates_table
18  create_reports_table
19  create_audit_logs_table
20  create_jobs_table
21  create_cache_table
22  create_sessions_table
```

---

## 2. Schema Definitions

### 2.1 `roles`

```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('slug', 100)->unique();
    $table->string('description', 255)->nullable();
    $table->boolean('is_system')->default(false); // system roles cannot be deleted
    $table->timestamps();
});
```

**Seed data:**
| id | name | slug | is_system |
|----|------|------|-----------|
| 1 | Super Admin | super-admin | true |
| 2 | Manager | manager | true |
| 3 | Field Supervisor | field-supervisor | true |
| 4 | Field Worker | field-worker | true |
| 5 | HR Officer | hr-officer | true |
| 6 | Finance Officer | finance-officer | true |

---

### 2.2 `permissions`

```php
Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('slug', 100)->unique();
    $table->string('module', 50);    // auth, users, attendance, stores, routes, visits, reports, settings
    $table->string('description', 255)->nullable();
    $table->timestamps();
});
```

**Seed data (partial):**
| module | slug | name |
|--------|------|------|
| users | users.view | View Users |
| users | users.create | Create Users |
| users | users.edit | Edit Users |
| users | users.delete | Delete Users |
| attendance | attendance.view | View Attendance |
| attendance | attendance.approve | Approve Attendance |
| stores | stores.view | View Stores |
| stores | stores.manage | Manage Stores |
| routes | routes.create | Create Routes |
| routes | routes.assign | Assign Routes |
| visits | visits.view | View Visits |
| visits | visits.verify | Verify Visits |
| reports | reports.view | View Reports |
| reports | reports.export | Export Reports |
| gps | gps.track | Track GPS |
| gps | gps.view | View GPS Logs |
| audit | audit.view | View Audit Logs |
| settings | settings.manage | Manage Settings |

---

### 2.3 `permission_role`

```php
Schema::create('permission_role', function (Blueprint $table) {
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->primary(['permission_id', 'role_id']);
});
```

---

### 2.4 `users`

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('employee_id', 50)->unique()->nullable();
    $table->string('name', 200);
    $table->string('email', 200)->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->string('phone', 20)->nullable();
    $table->string('avatar', 500)->nullable();
    $table->string('department', 100)->nullable();
    $table->string('position', 100)->nullable();
    $table->string('status', 20)->default('active');           // active, inactive, suspended
    $table->string('fcm_token', 500)->nullable();              // push notification
    $table->timestamp('last_login_at')->nullable();
    $table->string('last_login_ip', 45)->nullable();
    $table->json('metadata')->nullable();                      // emergency contact, bank, etc.
    $table->timestamps();
    $table->softDeletes();

    $table->index('status');
    $table->index('department');
    $table->index('employee_id');
});
```

---

### 2.5 `role_user`

```php
Schema::create('role_user', function (Blueprint $table) {
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->primary(['user_id', 'role_id']);
});
```

---

### 2.6 `personal_access_tokens` (Laravel Sanctum)

```php
Schema::create('personal_access_tokens', function (Blueprint $table) {
    $table->id();
    $table->uuidMorphs('tokenable');
    $table->string('name');
    $table->string('token', 64)->unique();
    $table->text('abilities')->nullable();
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});
```

---

### 2.7 `stores`

```php
Schema::create('stores', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('code', 20)->unique();
    $table->string('name', 200);
    $table->string('type', 50);                                // warehouse, outlet, field-depot, plantation_block
    $table->string('address', 500)->nullable();
    $table->decimal('latitude', 10, 7)->nullable();
    $table->decimal('longitude', 10, 7)->nullable();
    $table->foreignUuid('manager_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('status', 20)->default('active');           // active, inactive
    $table->string('phone', 20)->nullable();
    $table->json('metadata')->nullable();                      // opening hours, area size, etc.
    $table->timestamps();
    $table->softDeletes();

    $table->index('code');
    $table->index('type');
    $table->index('status');
    $table->spatialIndex('location'); // requires POINT column, see below
});
```

**Note:** For spatial queries, add a generated `POINT` column:
```sql
ALTER TABLE stores ADD COLUMN location POINT
    GENERATED ALWAYS AS (ST_SRID(POINT(longitude, latitude), 4326)) STORED;
CREATE SPATIAL INDEX idx_stores_location ON stores(location);
```

---

### 2.8 `products`

```php
Schema::create('products', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('sku', 50)->unique();
    $table->string('name', 200);
    $table->string('category', 50);                            // fertilizer, pesticide, seed, tool, equipment, fuel
    $table->string('unit', 20);                                // kg, liter, pcs, sack, ton
    $table->decimal('default_price', 15, 2)->nullable();
    $table->string('status', 20)->default('active');
    $table->text('description')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('category');
    $table->index('sku');
});
```

---

### 2.9 `stocks`

```php
Schema::create('stocks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
    $table->decimal('quantity', 15, 3)->default(0);
    $table->decimal('min_quantity', 15, 3)->default(0);       // low-stock alert threshold
    $table->timestamps();

    $table->unique(['store_id', 'product_id']);
    $table->index('quantity');
});
```

---

### 2.10 `stock_movements`

```php
Schema::create('stock_movements', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['in', 'out', 'transfer', 'adjustment']);
    $table->decimal('quantity', 15, 3);
    $table->decimal('balance_after', 15, 3);                  // snapshot after movement
    $table->string('reference_type', 50)->nullable();          // visit, route, manual
    $table->uuid('reference_id')->nullable();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->text('notes')->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['store_id', 'product_id']);
    $table->index('type');
    $table->index('created_at');
    $table->index(['reference_type', 'reference_id']);
});
```

---

### 2.11 `routes`

```php
Schema::create('routes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name', 200);
    $table->date('date');
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();  // field worker / supervisor
    $table->string('status', 20)->default('draft');             // draft, assigned, in_progress, completed, cancelled
    $table->decimal('distance_planned', 10, 2)->nullable();     // km
    $table->decimal('distance_actual', 10, 2)->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('date');
    $table->index('status');
    $table->index(['user_id', 'date']);
});
```

---

### 2.12 `route_stops`

```php
Schema::create('route_stops', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('route_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
    $table->integer('sequence');                               // 1, 2, 3, ...
    $table->time('planned_arrival')->nullable();
    $table->time('planned_departure')->nullable();
    $table->string('status', 20)->default('pending');          // pending, in_progress, completed, skipped
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->unique(['route_id', 'sequence']);
    $table->index(['route_id', 'store_id']);
});
```

---

### 2.13 `visits`

```php
Schema::create('visits', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('route_stop_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

    // Check-in
    $table->timestamp('check_in_at')->nullable();
    $table->decimal('check_in_latitude', 10, 7)->nullable();
    $table->decimal('check_in_longitude', 10, 7)->nullable();
    $table->decimal('check_in_accuracy', 8, 2)->nullable();    // GPS accuracy in meters
    $table->string('check_in_photo', 500)->nullable();

    // Check-out
    $table->timestamp('check_out_at')->nullable();
    $table->decimal('check_out_latitude', 10, 7)->nullable();
    $table->decimal('check_out_longitude', 10, 7)->nullable();
    $table->decimal('check_out_accuracy', 8, 2)->nullable();
    $table->string('check_out_photo', 500)->nullable();

    // Status & metadata
    $table->string('status', 20)->default('pending');          // pending, checked_in, checked_out, verified, rejected
    $table->integer('duration_minutes')->nullable();            // computed: check_out - check_in
    $table->decimal('distance_from_store', 8, 2)->nullable();   // meters from planned store location
    $table->text('notes')->nullable();
    $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();

    $table->index('status');
    $table->index('check_in_at');
    $table->index(['user_id', 'check_in_at']);
});
```

---

### 2.14 `attendances`

```php
Schema::create('attendances', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->date('date');
    $table->timestamp('clock_in')->nullable();
    $table->timestamp('clock_out')->nullable();
    $table->decimal('clock_in_latitude', 10, 7)->nullable();
    $table->decimal('clock_in_longitude', 10, 7)->nullable();
    $table->decimal('clock_out_latitude', 10, 7)->nullable();
    $table->decimal('clock_out_longitude', 10, 7)->nullable();
    $table->string('status', 20)->default('present');          // present, absent, late, half_day, holiday
    $table->integer('overtime_minutes')->default(0);
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'date']);
    $table->index('date');
    $table->index('status');
});
```

---

### 2.15 `leaves`

```php
Schema::create('leaves', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['annual', 'sick', 'maternity', 'unpaid', 'emergency']);
    $table->date('start_date');
    $table->date('end_date');
    $table->integer('total_days');
    $table->text('reason')->nullable();
    $table->string('status', 20)->default('pending');          // pending, approved, rejected, cancelled
    $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'status']);
    $table->index('start_date');
});
```

---

### 2.16 `gps_logs`

*High-volume time-series table. Partitioned monthly on `recorded_at`.*

```php
Schema::create('gps_logs', function (Blueprint $table) {
    $table->id();                                               // bigint auto-increment
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->decimal('latitude', 10, 7);
    $table->decimal('longitude', 10, 7);
    $table->decimal('accuracy', 8, 2)->nullable();              // meters
    $table->decimal('altitude', 8, 2)->nullable();              // meters
    $table->decimal('speed', 6, 2)->nullable();                 // km/h
    $table->decimal('bearing', 6, 2)->nullable();               // degrees
    $table->string('provider', 20)->nullable();                 // gps, network, fused
    $table->integer('battery_level')->nullable();               // percentage
    $table->timestamp('recorded_at');

    $table->index(['user_id', 'recorded_at']);
    $table->index('recorded_at');
});
```

**Partitioning (MySQL):**
```sql
ALTER TABLE gps_logs PARTITION BY RANGE (TO_DAYS(recorded_at)) (
    PARTITION p202601 VALUES LESS THAN (TO_DAYS('2026-02-01')),
    PARTITION p202602 VALUES LESS THAN (TO_DAYS('2026-03-01')),
    -- ... add partitions monthly
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

**Pruning schedule:** Delete partitions older than 90 days via scheduled command.

---

### 2.17 `report_templates`

```php
Schema::create('report_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name', 200);
    $table->string('slug', 100)->unique();
    $table->string('module', 50);                              // attendance, visits, stores, gps, payroll
    $table->json('query_config');                              // JSON: filters, aggregations, joins
    $table->json('columns');                                   // JSON: column definitions
    $table->string('chart_type', 50)->nullable();              // table, bar, line, pie, map
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

---

### 2.18 `reports`

```php
Schema::create('reports', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('report_template_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->json('parameters');                                // user-selected filters: date range, store, user
    $table->string('file_path', 500)->nullable();              // path to exported PDF/Excel
    $table->string('format', 20)->default('pdf');              // pdf, excel, csv
    $table->string('status', 20)->default('pending');          // pending, processing, completed, failed
    $table->timestamp('generated_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'created_at']);
    $table->index('status');
});
```

---

### 2.19 `audit_logs`

*High-volume immutable log. Partitioned monthly on `created_at`.*

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();                                               // bigint auto-increment
    $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('action', 50);                              // created, updated, deleted, login, logout, export
    $table->string('module', 50);                              // users, attendance, stores, routes, visits, etc.
    $table->string('entity_type', 100);                        // Model class
    $table->uuid('entity_id')->nullable();
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->string('url', 500)->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['user_id', 'created_at']);
    $table->index('module');
    $table->index('action');
    $table->index('created_at');
    $table->index(['entity_type', 'entity_id']);
});
```

---

### 2.20 `jobs` (Laravel queue)

```php
Schema::create('jobs', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('queue')->index();
    $table->longText('payload');
    $table->unsignedTinyInteger('attempts');
    $table->unsignedInteger('reserved_at')->nullable();
    $table->unsignedInteger('available_at');
    $table->unsignedInteger('created_at');
});
```

---

### 2.21 `failed_jobs`

```php
Schema::create('failed_jobs', function (Blueprint $table) {
    $table->id();
    $table->string('uuid')->unique();
    $table->text('connection');
    $table->text('queue');
    $table->longText('payload');
    $table->longText('exception');
    $table->timestamp('failed_at')->useCurrent();
});
```

---

### 2.22 `cache` & `cache_locks`

```php
Schema::create('cache', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->mediumText('value');
    $table->integer('expiration');
});

Schema::create('cache_locks', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->string('owner');
    $table->integer('expiration');
});
```

---

### 2.23 `sessions`

```php
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->foreignUuid('user_id')->nullable()->index();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->longText('payload');
    $table->integer('last_activity')->index();
});
```

---

## 3. Index Strategy

| Table | Index Type | Columns | Purpose |
|-------|-----------|---------|---------|
| `users` | unique | `email` | Login lookup |
| `users` | unique | `employee_id` | Employee search |
| `users` | index | `status` | Filter active/inactive |
| `users` | index | `department` | Filter by department |
| `attendances` | unique | `user_id, date` | One record per user per day |
| `attendances` | index | `date` | Period queries |
| `attendances` | index | `status` | Status filter |
| `gps_logs` | composite | `user_id, recorded_at` | User timeline |
| `gps_logs` | index | `recorded_at` | Time-range queries |
| `visits` | composite | `user_id, check_in_at` | User visit history |
| `visits` | index | `status` | Pending verification |
| `routes` | composite | `user_id, date` | Daily route lookup |
| `route_stops` | unique | `route_id, sequence` | Ordered stops |
| `stocks` | unique | `store_id, product_id` | Single stock record |
| `stock_movements` | composite | `store_id, product_id` | Stock history |
| `stock_movements` | index | `created_at` | Time-range |
| `audit_logs` | composite | `user_id, created_at` | User audit trail |
| `audit_logs` | composite | `entity_type, entity_id` | Entity history |
| `audit_logs` | index | `module` | Module filter |
| `stores` | spatial | `location` | Proximity queries |

---

## 4. Data Types Decision

| Domain | MySQL Type | Reason |
|--------|-----------|--------|
| Primary keys (user-facing) | `CHAR(36)` / UUID | Distributed-safe, no collision |
| Primary keys (logs) | `BIGINT UNSIGNED AUTO_INCREMENT` | Write performance, smaller index |
| Coordinates | `DECIMAL(10,7)` | 1.1 cm precision, exact math |
| Currency / quantities | `DECIMAL(15,3)` | Exact decimal, no float rounding |
| JSON metadata | `JSON` | Schema-flexible, queryable with `JSON_EXTRACT` |
| Status enums | `VARCHAR(20)` | Flexible, avoids ALTER TABLE for new statuses |
| Dates | `DATE` | Attendance, leaves, routes |
| Timestamps | `TIMESTAMP` | Auto timezone-aware |
| IP addresses | `VARCHAR(45)` | IPv6 compatible |
| File paths | `VARCHAR(500)` | S3/cloud storage paths |

---

## 5. Data Retention & Pruning

| Table | Retention | Action |
|-------|-----------|--------|
| `gps_logs` | 90 days | Drop partition older than 90 days |
| `audit_logs` | 2 years | Archive to cold storage, drop partition |
| `personal_access_tokens` | Auto | Expired tokens pruned daily |
| `sessions` | Auto | Laravel garbage collection |
| `failed_jobs` | 30 days | Prune older than 30 days |
| `reports` (files) | 1 year | Delete generated files, keep record |