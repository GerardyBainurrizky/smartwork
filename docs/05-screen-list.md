# 05 — Screen List

> Every screen required for v1.0, mapped 1:1 to the IA tree in `04-IA.md`.
> Format: `Screen Name` — purpose · key components · device priority.

---

## 0. Pre-authentication

| # | Screen | Purpose | Device |
|---|--------|---------|--------|
| S00 | **Login** | Email/username + password + 2FA. Hero logo, clean form, "Forgot password" link, PWA prompt. | Desktop + Mobile |
| S01 | **Forgot password** | Email → OTP → new password. | Desktop + Mobile |
| S02 | **Reset password** | OTP-verified new password form. | Desktop + Mobile |
| S03 | **Invite activation** | Deep-linked from email/SMS; worker creates password. | Mobile-first |

---

## 1. Dashboard

| # | Screen | Purpose | Key components | Device |
|---|--------|---------|----------------|--------|
| S10 | **Dashboard** | Role-scoped KPI overview. | Hero stat cards (5–8), recents table, "Needs decision" card, drill-down on click. | Desktop + Mobile |
| S11 | **Empty dashboard** | First-login; no data yet. | Guided onboarding call-to-action, "Create first task / add first employee / view reports". | Desktop + Mobile |

---

## 2. Workforce

| # | Screen | Purpose | Key components | Device |
|---|--------|---------|----------------|--------|
| S20 | **Employee directory** | Searchable, filterable table. | Search bar, filters (dept, status, role), sortable columns, quick actions (clock in, leave). | Desktop |
| S20m | **Employee directory mobile** | Scannable list + search. | Scroll list, search, swipe actions. | Mobile |
| S21 | **New employee wizard** | 4-step sequential form. | Step 1: Personal · Step 2: Employment · Step 3: Bank + Rates · Step 4: Invite. Progress indicator, save draft. | Desktop |
| S21m | **New employee wizard mobile** | Same wizard, single-column. | Full-width steps, bottom "Next" CTA. | Mobile |
| S22 | **Employee profile** | Single employee detail. | Header (photo, name, badge status), tabs (Overview, Employment, Attendance, Leave, Documents). | Desktop + Mobile |
| S23 | **Employee edit** | Edit employee fields. | Form with sections, save + cancel. | Desktop |
| S24 | **Attendance calendar** | Monthly calendar with status dots. | Month grid, day click → day view, filters (employee, department). | Desktop |
| S24m | **Attendance mobile** | Day/week view. | Swipe card per day, clock-in/out actions. | Mobile |
| S25 | **Attendance day view** | Clock log for a single day. | Timeline, status badges, override button (for supervisor). | Desktop + Mobile |
| S26 | **Shifts calendar** | Shift assignment. | Weekly grid, drag assignment, template apply. | Desktop |
| S27 | **Leave overview** | Requests + balances. | Tabbed: Pending, Approved, Rejected. Balances shown as donut/pill. | Desktop + Mobile |
| S28 | **Leave request detail** | Single request review. | Dates, balance impact, approve/reject actions. | Desktop + Mobile |
| S29 | **New leave request** | Worker submits leave. | Date picker, type selector, reason, submit. | Mobile-first |

---

## 3. Field

| # | Screen | Purpose | Key components | Device |
|---|--------|---------|----------------|--------|
| S30 | **Task board** | Kanban-style daily task view. | Columns: Today, Assigned, In Review, Done. Card per task with assignee + status. | Desktop |
| S30m | **My Tasks (worker)** | Worker's task list. | List of my tasks, status badges, "Check in" / "Mark done" button. | Mobile-first |
| S31 | **Task detail** | Full task view. | Header (title, status, assignee, due), timeline, photo gallery, checklist, geo map pin. | Desktop + Mobile |
| S32 | **New task** | Supervisor creates task. | Block selector, activity picker, assignee(s), checklist, photo, notes. | Desktop + Mobile |
| S33 | **Block list** | List/map of plantation blocks. | Card grid or list, search, map toggle. | Desktop + Mobile |
| S34 | **Block profile** | Single block detail. | Crop, area, cycle, activity log (timeline), yield chart. | Desktop + Mobile |
| S35 | **Activity templates** | Activity library. | List of crop activities (fertilization, harvest, etc.), status, rate defaults. | Desktop |
| S36 | **Yield & Input log** | Log per block per season. | Table (date, type, qty, user), add entry. | Desktop + Mobile |
| S37 | **New yield/input entry** | Logging form. | Type, qty, photo, notes, submit. | Mobile-first |

---

## 4. Operations

| # | Screen | Purpose | Key components | Device |
|---|--------|---------|----------------|--------|
| S40 | **Work orders list** | Order management. | Table, filters (status, priority, block), sort. | Desktop |
| S40m | **Work orders mobile** | List view. | Scroll, status pills. | Mobile |
| S41 | **Work order detail** | Order with parts/labour/equipment. | Header, sections (description, checklist, progress). | Desktop + Mobile |
| S42 | **New work order** | Create order. | Form with sections, save. | Desktop |
| S43 | **Inventory** | Stock list. | Table, search, filter by warehouse/store, low-stock alerts. | Desktop |
| S43m | **Inventory mobile** | Stock card list. | Cards, filter, swipe. | Mobile |
| S44 | **Inventory log** | Stock movement. | Table (in/out, qty, user, date), add entry. | Desktop + Mobile |
| S45 | **Assets** | Machinery/tools. | Table, status, service due. | Desktop |
| S45m | **Assets mobile** | Asset cards. | Card list, QR scan option. | Mobile |
| S46 | **Asset detail** | Single asset. | Photo, specs, service history, maintenance log. | Desktop + Mobile |
| S47 | **Maintenance schedule** | Calendar of maintenance. | Calendar grid, schedule service. | Desktop |

---

## 5. Payroll

| # | Screen | Purpose | Key components | Device |
|---|--------|---------|----------------|--------|
| S50 | **Pay periods** | List of pay periods. | Table, lock/unlock, status (open / locked). | Desktop |
| S51 | **Payroll run wizard** | Step-by-step run. | Aggregate → Review variance → Preview → Confirm. | Desktop |
| S52 | **Payroll run preview** | Detail view of a run. | Employee rows, summary footer, export/approve. | Desktop |
| S53 | **Payroll runs archive** | Past locked runs. | Table, view-only. | Desktop |
| S54 | **Export centre** | Export to bank feed. | Format picker, date range, generate. | Desktop |

---

## 6. Reports

| # | Screen | Purpose | Key components | Device |
|---|--------|---------|----------------|--------|
| S60 | **Report library** | List of available reports. | Cards (productivity, costing, yield, payroll), description, last viewed. | Desktop |
| S61 | **Report viewer** | Filtered report view. | Filter bar, chart/table toggle, export, save view. | Desktop |
| S61m | **Report viewer mobile** | Simplified report. | Top filter, chart, summary table. | Mobile |
| S62 | **Scheduled reports** | Status of recurring reports. | List, toggle on/off, change schedule. | Desktop |

---

## 7. Settings

| # | Screen | Purpose | Key components | Device |
|---|--------|---------|----------------|--------|
| S70 | **Organisation** | Company profile, location tree. | Form, tree editor. | Desktop |
| S71 | **Teams & Roles** | Role definitions. | Table, permission matrix. | Desktop |
| S72 | **Users** | User list. | Table, invite, deactivate, reset 2FA. | Desktop |
| S73 | **Rates & Policies** | Hourly rates, leave policy, overtime. | Accordion forms per policy. | Desktop |
| S74 | **Feature flags** | Feature toggles. | Toggle table, per environment. | Desktop |
| S75 | **Audit log** | Immutable audit trail. | Table, filter by user/module/date, export. | Desktop |

---

## 8. Global / Cross-cutting

| # | Screen | Purpose | Key components | Device |
|---|--------|---------|----------------|--------|
| S80 | **Global Inbox** | Approvals + notifications. | Feed, grouped by module, inline approve/reject. | Desktop + Mobile |
| S81 | **Global Search** | cmd+k search. | Modal, recent, results grouped by module. | Desktop + Mobile |
| S82 | **Profile / Settings** | User self-service. | Name, avatar, password, 2FA, language, sign out. | Desktop + Mobile |
| S83 | **Offline banner** | System status. | "Last synced 12:41" pill, "Sync now" action. | Mobile |
| S84 | **403 / 404** | Access denied / not found. | Friendly message, "Back to dashboard" link. | Desktop + Mobile |

---

## Screen count summary

| Module | Screens (desktop) | Screens (mobile) | Total |
|--------|-------------------|-------------------|-------|
| Auth | 3 | 3 | 6 |
| Dashboard | 2 | 2 | 4 |
| Workforce | 10 | 10 | 20 |
| Field | 8 | 8 | 16 |
| Operations | 8 | 8 | 16 |
| Payroll | 5 | 0 | 5 |
| Reports | 3 | 3 | 6 |
| Settings | 6 | 0 | 6 |
| Global | 5 | 5 | 10 |
| **Total** | | | **~89** |

> Note: "Desktop" screens listed above have mobile equivalents (responsive breakpoints).
> "Mobile-first" screens are designed mobile-first with desktop layered on. Screens 
> counted as 0 for mobile mean they are primarily desktop-only admin tools where
> mobile is read-only notification.