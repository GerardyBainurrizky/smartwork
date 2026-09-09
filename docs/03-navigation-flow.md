# 03 — Navigation Flow

## 1. Global chrome by device class

### Desktop / Tablet (workbench) — Left sidebar + top navbar
- **Left sidebar (icon+label, collapsible to icon-only)** — primary modules, role-scoped.
- **Top navbar** — global search, Inbox/bell (approvals), contextual actions, user menu.
- **Breadcrumbs** — appear on any page 2+ levels deep ("Attendance › Employees › 2026 · Week 30").
- **Contextual action bar** — primary action anchored top-right of a screen ("New task").

### Mobile — **Bottom tab bar + header**
- **Bottom nav (4–5 tabs)** — Home, Tasks, Inbox, Profile (role-scoped). Reserved for
  the 4 highest-value destinations per role.
- **Header** — app mark + context title + search + overflow menu.
- **Hamburger / sheet** — reveals the full module drawer (top-down secondary navigation).

---

## 2. Global navigation map

```
┌──────────────────────────── ROOT ────────────────────────────┐
│  Authenticated workbench (desktop) / Home (mobile)           │
│  │  │  │  │  │  │  │  │  │                                   │
│  ▼  ▼  ▼  ▼  ▼  ▼  ▼  ▼  ▼                                  │
│ [DASHBOARD] [WORKFORCE] [FIELD] [OPERATIONS] [PAYROLL] [REPORTS] [SETTINGS]│
```

Module groups are collapsed by default; only visible to authorised roles.
Critical states (Inbox badge, payroll pending, offline sync) appear in the navbar
globally.

---

## 3. Primary navigation table

| Module | Icons (concept) | Reachable actions | Roles |
|--------|-----------------|-------------------|-------|
| **Dashboard** | `Overview` | KPI cards → drill-down lists | Super Admin, Manager, Exec, Finance |
| **Workforce** | `People` | Employees, Directory, Attendance, Shifts, Leave, Reports | HR, Finance |
| **Field** | `Tractor / Map pin` | Tasks, Blocks, Activities, Yield/Input, Tie | Supervisor, Worker |
| **Operations** | `Box / Clipboard` | Work Orders, Inventory, Assets, Maintenance | Supervisor, Manager |
| **Payroll** | `Calculator` | Pay periods, Aggregation, Validation, Runs, Exports | Finance, Manager |
| **Reports** | `Chart` | Productivity, Costing, Yield, Payroll export | Manager, Exec |
| **Settings** | `Cog` | Org tree, Departments, Roles, Users, Rates, Feature flags | Admin |

---

## 4. Module → screen routing

A micro-map of how module roots lead to screens. Full path tree in
`04-information-architecture.md`; each leaf in `05-screen-list.md`.

```
Workforce ──┬─> Employee directory ──> Employee detail ──> Edit / Invite
            ├─> Attendance calendar ─> Day view
            └─> Leave requests ──────> Request detail ─> Approve / Reject

Field ──────┬─ Tasks (list) ──> Task detail ──> Timeline / Photos
            ├─ Blocks ──────> Block detail ──> Activity log (yield/input)
            └─ Activities (template) ─> New task (pre-fill)

Payroll ────> Periods ──> Run wizard ──> Preview (variance) ──> Approve ──> Export
Reports ────> Library ──> Open report ──> Filters ──> Drill-down / chart / export ─> Save view
```

---

## 5. Cross-cutting navigation patterns

- **Global Inbox** (`bell`): one place to approve & be notified across modules (F6).
- **Global Search** (`cmd/ctrl + K`): jump anywhere — employee, task, block, report.
- **Profile → Settings & Sign out** in the user menu (top-right / avatar).
- **Deep links** shareable & route-guarded (e.g. `/field/tasks/381`, `/workforce/employees/12`).
- **Unsaved-state guard**: navigating away with dirty form opens a confirm sheet
  (discard / keep editing), unless action is bulk/async.

---

## 6. Guard & fallback flows

| State | Behaviour |
|-------|-----------|
| First login / no data | Guided empty states with a primary "Create first…" CTA |
| No permission on module | Route hides item; direct URL → "Request access" screen |
| Offline (worker) | Bottom "Last synced" pill; actions queued; banner in orange |
| Deep link to deleted record | Friendly "not found" with back to module root |