# 01 — Product Vision & Positioning

> **Platform:** ISA SmartWork
> **Owner:** PT ISA Tri Selaras Gemilang Agricultural
> **Classification:** Enterprise Internal Platform

---

## 1. Why ISA SmartWork exists

Agricultural operations are distributed, seasonal, and heavily field-reliant.
Disconnected spreadsheets, paper field logs, and offline cash outlets create three
chronic problems:

1. **No single source of truth** — attendance, tasks, crop inputs, and costs live in
   separate silos, making payroll and yield analysis slow and error-prone.
2. **Weak field-to-office signal** — supervisors and workers operate offline; HQ only
   finds out about missed work, weather logs, or yield issues days later.
3. **Poor operational control** — managers lack the real-time visibility needed to
   allocate labour, approve overtime, and forecast costs.

**ISA SmartWork** is the enterprise-grade command center that unifies **workforce
management** and **field operations** on one platform, built mobile-first so it works
where the crops are — online *and* offline.

---

## 2. Positioning Statement

> For **PT ISA Tri Selaras Gemilang Agricultural**, ISA SmartWork is the **enterprise
> workforce & field-operations platform** that turns scattered HR and farm data into
> a single, real-time, auditable system of record — enabling confident payroll,
> precise task allocation, and executive-grade visibility, all from a mobile-first
> experience comparable to Workday, Odoo, and Stripe-class product quality.

---

## 3. Core pillars (the "three screens of truth")

| # | Pillar | Delivers | Primary audience |
|---|--------|----------|------------------|
| 1 | **Workforce** | Hire→pay lifecycle: profiles, attendance, shifts, leave, payroll | HR, Finance, Field Staff |
| 2 | **Field Operations** | Work orders, tasks, crop blocks, yield & input logs | Field Supervisors, Workers |
| 3 | **Command** | Dashboards, approvals, reports, exports | Managers, Executives, Compliance |

Each pillar shares one design system, one user model, and one data model so they feel
like a single product, not three features.

---

## 4. Users & roles

| Role | Typical actor | Primary job-to-be-done |
|------|---------------|------------------------|
| **Super Admin** | IT / System owner | Configure organisational tree, roles, security |
| **HR Officer** | HR staff | Maintain employee records, approve attendance & payroll |
| **Field Supervisor** | Site lead | Assign and verify tasks, log field data |
| **Field Worker** | Labour on site | Self-check-in, do tasks, submit logs (offline-capable) |
| **Finance Officer** | Finance | Verify shifts, run payroll, track cost |
| **Manager / Executive** | Directorate | Monitor KPIs, approve, review reports |

---

## 5. Success metrics (proxy health of the product)

- **Time-to-find-record** < 5 seconds for any employee/task (search-first UX).
- **Daily check-in completion rate** ≥ 95% via PWA, with graceful offline.
- **Payroll accuracy** — zero manual recalculation on first payroll run after pilot.
- **Approval cycle time** reduced ≥ 40% via in-context approvals.
- **Field log latency** — supervisor sees worker activity within 1 day of sync.

---

## 6. Non-goals (v1)

- No public portal (this is an internal/enterprise tool).
- No financial ERP GL; payroll produces reports that reconcile externally.
- No IoT integrations in v1 (planned: block/sensor telemetry later).

---

## 7. Platform quality bar

The product must meet the bar of Stripe / Linear / Notion / HubSpot / Odoo /
Workday / SAP SuccessFactors:

- **Consistency:** one design system shared by every module.
- **Clarity:** data-dense without clutter; hierarchy via space and weight, not color.
- **Trust:** deterministic, reversible actions; clear status language.
- **Accessibility:** WCAG 2.1 AA minimum; keyboard-complete.
- **Performance:** sub-1500ms first paint on mid-range Android; 60fps interactions.
- **Mobile-first:** every desktop screen has a coherent mobile equivalent and bottom-nav.

*See `06-design-principles.md` for the operationalisation of these.*