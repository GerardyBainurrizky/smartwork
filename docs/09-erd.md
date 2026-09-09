# 09 — Entity Relationship Diagram

> ISA SmartWork — Enterprise Field Workforce Management
> Database: MySQL 8.0+ / PostgreSQL 16+ (Laravel 12)

---

## 1. ERD (ASCII)

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                         │
│  ┌──────────────┐     ┌──────────────────┐     ┌──────────────────┐                    │
│  │  permissions  │────<│ permission_role  │>────│      roles       │                    │
│  │              │     │                  │     │                  │                    │
│  │ PK id         │     │ FK permission_id  │     │ PK id             │                    │
│  │    name       │     │ FK role_id        │     │    name          │                    │
│  │    slug       │     └──────────────────┘     │    slug          │                    │
│  │    module     │                               │    description   │                    │
│  └──────────────┘                               └────────┬─────────┘                    │
│                                                           │                              │
│                                                   ┌───────┴───────┐                     │
│                                                   │   role_user   │                     │
│                                                   │               │                     │
│                                                   │ FK role_id    │                     │
│                                                   │ FK user_id    │                     │
│                                                   └───────┬───────┘                     │
│                                                           │                              │
│  ┌───────────────────────────────────────────────────────┴──────────────────────┐       │
│  │                                   users                                       │       │
│  │                                                                              │       │
│  │  PK id          UK email      UK employee_id   department    position        │       │
│  │     name        phone         avatar            password      status          │       │
│  │     fcm_token   email_verified_at               remember_token                │       │
│  │     last_login_at            last_login_ip     deleted_at                     │       │
│  └──┬───────┬───────┬───────┬───────┬───────┬───────┬───────┬──────────────────┘       │
│     │       │       │       │       │       │       │       │                           │
│     │ 1:N   │ 1:N   │ 1:N   │ 1:N   │ 1:N   │ 1:N   │ 1:N   │ 1:N                      │
│     ▼       ▼       ▼       ▼       ▼       ▼       ▼       ▼                          │
│ ┌────────┐┌──────┐┌──────┐┌──────┐┌──────┐┌──────┐┌──────┐┌──────────┐                │
│ │attend- ││leaves││visits││gps_  ││audit_││store ││report││personal_ │                │
│ │ances   ││      ││      ││logs  ││logs  ││_mgrs ││_gen  ││tokens    │                │
│ │        ││      ││      ││      ││      ││(FK)  ││(FK)  ││          │                │
│ │PK id   ││PK id ││PK id ││PK id ││PK id ││      ││      ││PK id     │                │
│ │FK user ││FK usr││FK usr││FK usr││FK usr││      ││      ││FK user   │                │
│ │date    ││type  ││FK rs ││lat   ││action││      ││      ││token     │                │
│ │clock_in││start ││c_in  ││lng   ││module││      ││      ││abilities │                │
│ │clock_ou││end   ││c_out ││accur ││entity││      ││      ││expires   │                │
│ │c_in_lat││reason││c_lat ││speed ││old   ││      ││      ││last_used │                │
│ │c_in_lng││status││c_lng ││batt  ││new   ││      ││      │└──────────┘                │
│ │c_out_lat││apprvd││photo ││rec_at││ip    ││      ││      │                            │
│ │c_out_lng││notes ││status││      ││ua    ││      ││      │                            │
│ │status  ││      ││notes ││      ││      ││      ││      │                            │
│ │notes   ││      ││      ││      ││      ││      ││      │                            │
│ └────────┘└──────┘└──┬───┘└──────┘└──────┘└──────┘└──────┘                            │
│                      │ N:1                                                              │
│                      ▼                                                                  │
│              ┌──────────────┐                                                           │
│              │ route_stops  │                                                           │
│              │              │                                                           │
│              │ PK id        │                                                           │
│              │ FK route_id  │                                                           │
│              │ FK store_id  │                                                           │
│              │ sequence     │                                                           │
│              │ planned_arr  │                                                           │
│              │ planned_dep  │                                                           │
│              │ status       │                                                           │
│              └──────┬───────┘                                                           │
│                     │ N:1                                                               │
│                     ▼                                                                   │
│              ┌──────────────┐                                                           │
│              │    routes    │                                                           │
│              │              │                                                           │
│              │ PK id        │                                                           │
│              │ FK user_id   │◄── supervisor / field worker                              │
│              │ name         │                                                           │
│              │ date         │                                                           │
│              │ status       │                                                           │
│              └──────────────┘                                                           │
│                                                                                         │
│  ┌──────────────────────────────────────────────────────────────────────┐              │
│  │                             stores                                   │              │
│  │                                                                      │              │
│  │  PK id      name      code      address      lat      lng            │              │
│  │     FK manager_id (users)    status      created_at      updated_at   │              │
│  └──┬───────┬───────────────────────────────────────────────────────────┘              │
│     │       │                                                                           │
│     │ 1:N   │ 1:N                                                                       │
│     ▼       ▼                                                                           │
│  ┌──────┐ ┌──────────────┐                                                              │
│  │stock │ │stock_movements│                                                             │
│  │      │ │              │                                                              │
│  │PK id │ │PK id         │                                                              │
│  │FK st │ │FK store_id   │                                                              │
│  │FK pr │ │FK product_id │                                                              │
│  │qty   │ │type (in/out) │                                                              │
│  │min_q │ │quantity      │                                                              │
│  │      │ │reference     │                                                              │
│  │      │ │FK user_id    │                                                              │
│  │      │ │notes         │                                                              │
│  └──┬───┘ └──────┬───────┘                                                              │
│     │ N:1        │ N:1                                                                  │
│     ▼            ▼                                                                      │
│  ┌──────────────────────┐                                                               │
│  │       products       │                                                               │
│  │                      │                                                               │
│  │  PK id   name   sku  │                                                               │
│  │     category   unit  │                                                               │
│  └──────────────────────┘                                                               │
│                                                                                         │
│  ┌──────────────────────────────────────────────────────────────────────┐              │
│  │                          report_templates                             │              │
│  │                                                                      │              │
│  │  PK id      name      slug      module      query_config (JSON)      │              │
│  └──┬───────────────────────────────────────────────────────────────────┘              │
│     │ 1:N                                                                               │
│     ▼                                                                                   │
│  ┌──────────────────────────────────────────────────────────────────────┐              │
│  │                             reports                                   │              │
│  │                                                                      │              │
│  │  PK id      FK template_id      FK user_id      parameters (JSON)    │              │
│  │     file_path      status      generated_at      created_at          │              │
│  └──────────────────────────────────────────────────────────────────────┘              │
│                                                                                         │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Entity Summary

| # | Entity | Table | Row estimate | Primary key |
|---|--------|-------|-------------|-------------|
| 1 | User | `users` | 10,000+ | `id` (UUID) |
| 2 | Role | `roles` | 10 | `id` |
| 3 | Permission | `permissions` | 50 | `id` |
| 4 | Attendance | `attendances` | 300,000+/yr | `id` (UUID) |
| 5 | Leave | `leaves` | 2,000+/yr | `id` (UUID) |
| 6 | Store | `stores` | 500 | `id` (UUID) |
| 7 | Product | `products` | 5,000 | `id` (UUID) |
| 8 | Stock | `stocks` | 500 stores × 5,000 products | `id` (UUID) |
| 9 | Stock Movement | `stock_movements` | 50,000+/yr | `id` (UUID) |
| 10 | Route | `routes` | 10,000+/yr | `id` (UUID) |
| 11 | Route Stop | `route_stops` | 50,000+/yr | `id` (UUID) |
| 12 | Visit | `visits` | 100,000+/yr | `id` (UUID) |
| 13 | GPS Log | `gps_logs` | 1,000,000+/yr | `id` (bigint) |
| 14 | Report Template | `report_templates` | 20 | `id` |
| 15 | Report | `reports` | 5,000+/yr | `id` (UUID) |
| 16 | Audit Log | `audit_logs` | 500,000+/yr | `id` (bigint) |
| 17 | Personal Access Token | `personal_access_tokens` | varies | `id` (UUID) |

---

## 3. Relationship Matrix

```
users ──< role_user >── roles
roles ──< permission_role >── permissions

users ──< attendances
users ──< leaves
users ──< gps_logs
users ──< audit_logs
users ──< personal_access_tokens
users ──< reports (generated_by)
users ──< routes (supervisor)
users ──< visits (field_worker)
users ──< stores (manager)

routes ──< route_stops
route_stops ──< visits
route_stops ──> stores
visits ──> route_stops

stores ──< stocks
stores ──< stock_movements
products ──< stocks
products ──< stock_movements

report_templates ──< reports
```

---

## 4. Cardinality Details

| Parent | Child | Cardinality | Notes |
|--------|-------|-------------|-------|
| User | Role | N:M | via `role_user` pivot |
| Role | Permission | N:M | via `permission_role` pivot |
| User | Attendance | 1:N | One user, many attendance days |
| User | Leave | 1:N | |
| User | GPS Log | 1:N | High volume, time-series |
| User | Audit Log | 1:N | Immutable, append-only |
| User | Route | 1:N | Supervisor creates routes |
| User | Visit | 1:N | Field worker performs visits |
| User | Store | 1:N | Manager assigned to store |
| Route | RouteStop | 1:N | One route has many stops |
| RouteStop | Visit | 1:1 | One stop = one visit record |
| RouteStop | Store | N:1 | Each stop is at a store |
| Store | Stock | 1:N | Per product |
| Product | Stock | 1:N | Per store |
| Store | StockMovement | 1:N | |
| Product | StockMovement | 1:N | |
| ReportTemplate | Report | 1:N | |

---

## 5. Key Design Decisions

1. **UUID primary keys** for all user-facing entities (users, stores, products, visits, routes).
   `bigint` auto-increment for high-volume write-heavy tables (`gps_logs`, `audit_logs`).

2. **Soft deletes** on `users`, `stores`, `products`, `routes`. Hard deletes on logs
   (aged out via scheduled pruning).

3. **JSON columns** for flexible data: `report_templates.query_config`, `reports.parameters`,
   `audit_logs.old_values`, `audit_logs.new_values`.

4. **Spatial index** on `gps_logs(lat, lng)` and `stores(lat, lng)` for proximity queries.

5. **Partitioning** on `gps_logs` and `audit_logs` by `recorded_at` / `created_at` (monthly).

6. **Composite unique index** on `stocks(store_id, product_id)`.