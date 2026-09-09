# 04 — Information Architecture

> Logical sitemap. Six columns: breadth at root, depth ≤ 3–4 levels. Grouped by
> pillar: **[W] Workforce · [F] Field · [O] Operations · [P] Payroll · [I] Intelligence · [S] System**.

```
Root
│
├── Dashboard ...................................... [All roles, role-scoped cards]
├── Workforce  .................................. [W]
│   ├── Employees
│   │   ├── Directory (searchable table, filters)
│   │   ├── New employee wizard (4 steps)
│   │   └── Employee profile
│   │       ├── Overview (experiment fits)
│   │       ├── Employment (contract, role, dept, rates)
│   │       ├── Attendance
│   │       ├── Leave
│   │       └── Documents (IDs, certs, banking)
│   ├── Attendance
│   │   ├── Calendar (month) / Day view
│   │   ├── Logs / register
│   │   └── Exceptions (missed clock, geo-fence flags)
│   ├── Shifts
│   │   ├── Templates
│   │   └── Assignments
│   ├── Leave
│   │   ├── Requests (pending/approved/rejected)
│   │   └── Balances
│   └── Workforce report (turnover, headcount)
│
├── Field  ................................... [F]
│   ├── Tasks
│   │   ├── Board (Today / Assigned / In review / Done)
│   │   ├── Task detail (timeline, photos, checklist, geo)
│   │   └── New task
│   ├── Blocks
│   │   ├── Block map / list
│   │   └── Block profile (area, crop, cycle, activity log)
│   ├── Activities (task templates & crop activities)
│   └── Yield & Input (logs per block/season)
│
├── Operations  .............................. [O]
│   ├── Work Orders
│   │   ├── Order list
│   │   └── Order detail (parts/equipment/labour)
│   ├── Inventory (stores, stock, movements)
│   ├── Assets (machinery, tractors, tools, service log)
│   └── Maintenance (schedule & requests)
│
├── Payroll ...................................  [Finance]
│   ├── Pay periods (lockable)
│   ├── Run wizard (aggregate → variance → preview)
│   ├── Runs (archive, locked view)
│   └── Exports / bank feed
│
├── Reports ................................... [I → Manager/Exec]
│   ├── Library (productivity, costing, yield, payroll)
│   ├── Report builder (filters, chart/table, save view)
│   └── Scheduled / shared reports
│
└── Settings .................................. [S → Admin]
    ├── Organisation (company, locations, tree)
    ├── Teams & Roles (permissions per role)
    ├── Users (invite, status, 2FA)
    ├── Rates & Policies (hourly rates, leave policy, distances)
    ├── Feature flags
    └── Audit log
```

---

## IA principles enforced

1. **Predictable depth** — never more than 3 clicks to a primary object.
2. **One canonical object per noun** — "Block" lives once under Field; Reports only
   reference it (no duplicated CRUD).
3. **Consistent verbs** — `New`, `Approve`, `Reject`, `Adjust`, `Export`, `Save view`
   mean the same thing everywhere.
4. **Role-scoped, not role-cloned** — one tree, filtered by permission instead of
   forking screens per role (keeps consistency + low maintenance).
5. **Nouns, not tech** — IA uses business language ("Yield & Input", not "Laravel CRUD").

---

## Breadcrumb grammar

`{Module} > {Collection} > {Object}` e.g.:
- `Workforce > Employees > Rudi Hartono`
- `Field > Tasks > Task #T-2148`
- `Payroll > Runs > 2026 · Week 30`