# 02 — User Flows

> Job-to-be-done oriented flows. Each flow is the happy path with the primary
> edge cases called out. Flows are the contract for screen design and navigation.

**Legend:** `[A]=Authentication  [AUTH]` · `[W]=Workforce  [F]=Field  [I]=Intelligence`

---

## F1 — Sign in (cross-platform)

```
Start
 ├─ PWA Offline → "No network. Sign in later?" → queue credentials → sync on reconnect
 ├─ Email/username → password → [Invalid? → inline error, reset link] → 2FA code (if enabled)
 └─ PKCE / session create → role decision → land on:
      · Field Worker → My Tasks (mobile)
      · Supervisor/HR/Manager → Dashboard (workbench)
```

Edge cases: expired session (modal re-auth), wrong password lockout (3 tries), SSO
return, password reset via email OTP.

---

## F2 — Field worker daily check-in (mobile, offline-first)

```
Morning → Open app (offline OK)
  → Pull My Tasks (cached list)
  → Tap "Check in" on active task
       → Geo-fence check (GPS) — if out of range: warn, allow "manual" + supervisor flag
       → Capture optional photo of work area
  → Queue records locally
  → On next online window → sync → Task Status → Submitted
Supervisor sees the log the same day.
```

**Edge cases:** no GPS permission, out-of-coverage sync, duplicate check-in guard,
task reassigned mid-shift.

---

## F3 — Supervisor assigns & verifies work

```
Admin → Field → Tasks → "New task"
  → Pick block + crop activity + assign worker(s) → set bounds (qty/duration/due)
  → (Optional) attach checklist / photos references
  → Publish → workers receive in-app + push/offline notice
Worker submissions arrive in "Pending verification"
  → Approve / Reject (reason) → worker notified
  → Rejected → worker sees reason, can re-submit
```

---

## F4 — Attendance & leave (workforce)

```
Worker Mobile → Attendance → "Request leave" → type (annual/sick) + dates + reason
  → submits → HR/FM receives approved task (in-context action)
  → Approve → clock applier → worker notified + calendar entry
  → Reject(with reason) → worker notified
Auto-capture: shift start/end from daily check-in for report payroll reconciliation.
```

---

## F5 — Payroll run (Finance)

```
Finance → Payroll → Select period (e.g. Weeks 23–24)
  → System aggregates: attendance verified + approved leave + rates
  → Review variance (flagged anomalies) → adjust (with audit reason)
  → Create payroll records → Finance can re-agree
  → Approve (Manager) → Lock period → export report / submit to bank legacy
Audit: every action logged; payroll period is immutable after lock.
```

---

## F6 — Approvals funnel

```
Any pending approval surfaces in 3 places:
  1) In-context primary action button (e.g. "Approve task")
  2) Global Inbox (bell) in navbar — grouped by module
  3) Dashboard "Needs decision" card
Action → Approve / Reject + optional comment → auto-notifies requester → audit log.
Approval must be possible entirely from the Inbox (no context switch).
```

---

## F7 — Manager reads & exports report

```
Dashboard → Select KPI card → drill into drill-down list
  → Filter (period, area, supervisor, employee)
  → Toggle chart/table
  → Export (PDF/EXCEL) with white-label header → Share (email)
  → Saved view → appears on Dashboard
```

---

## F8 — Offline sync (system-level)

```
Action → local store (SQLite) → dirty queue
On emotion: connectivity → upload dirty → merge → conflict policy (last-write wins + audit)
Field worker reads from cache when offline; UI shows "Last synced 12:41".
```

---

## F9 — New employee onboarding (HR)

```
HR → Employees → "New employee" → 4-step wizard:
   1) Personal data   2) Employment (contract, department, role)  3) Assign bank/rates
   4) Invite (SMS/email) → worker creates password → activates
Progress indicator; saved mid-way; draft state until "Activate".
```

---

## Flow inventory (quick lookup)

| ID | Flow | Primary role | Offline? |
|----|------|--------------|----------|
| F1 | Authentication | All | Partial |
| F2 | Daily check-in | Worker | Yes |
| F3 | Task assignment | Supervisor | Yes |
| F4 | Leave request | Worker + HR | Yes |
| F5 | Payroll run | Finance | No |
| F6 | Approvals inbox | Supervisor/Manager | No |
| F7 | Reporting & export | Manager/Exec | No |
| F8 | Offline sync | System | Yes |
| F9 | New hire setup | HR | No |