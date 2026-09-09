# 06 — Design Principles

> Operational principles that govern every pixel, interaction, and code decision.
> These are the contract: if a screen violates one, it is a bug.

---

## P1 — Clarity over cleverness

**Do not make the user think about the UI.**

- Every screen has a single, obvious primary action — the "one thing" (Linear-style).
- Labels are plain Indonesian/English: "Simpan", "Kirim", "Setujui", not "Execute payload".
- Empty states explain *what goes here* and *how to create it* — never just "No data".
- Danger actions are explicit: "Delete employee" is a two-step confirmation, not a single click.

**Test:** show the screen to a new supervisor for 5 seconds. Can they state what to do?

---

## P2 — Mobile-first, not mobile-only

- Design at 375 px first. Every micro-interaction must work with a thumb.
- Bottom tab bar (4–5 items) on mobile; desktop sidebar on ≥ 1024 px.
- Touch targets: minimum 44×44 px.
- Desktop gets the *same* data, reflowed into multi-column layouts — never less.
- PWA: offline-first, storage-aware, installable with a 192 px icon and manifest.

---

## P3 — Data density with grace

Enterprise users need to see *a lot* at once — but not be overwhelmed.

- **Cards** for summary; **tables** for depth; **charts** for trends.
- Tables use subtle zebra striping (1 px, 2% opacity), fixed headers, and horizontal
  scroll on mobile.
- Progressive disclosure: summary card → drill-down modal/sheet → detail page.
- Never show raw IDs in the UI; always use human-readable labels.
- Numbers align right; text aligns left; statuses are colour-coded pills.

---

## P4 — One design system, one language

- Every component is a named token in the design system (see `07-branding.md`).
- Spacing uses a 4 px grid (4, 8, 12, 16, 24, 32, 48, 64, 96).
- Radius uses: 0, 4, 8, 12, 16, full. No `6 px` or `3 px` corners.
- Icon set: single family (Lucide or Phosphor), 20 px / 24 px stroke 1.5–2.
- Consistent verbs across all modules: "New", "Approve", "Reject", "Adjust", "Export".

---

## P5 — Trust through transparency

- Every status has a defined lifecycle (e.g. `Pending → In Review → Done`).
- Audit trails are visible by default; reversible actions show "undo" toast for 5 seconds.
- Error messages say *what happened* and *what to do next* — never raw SQL or stack traces.
- Loading states: skeleton screens matching the final layout, never a blank page.
- Offline state: orange subtle banner, queued count, "Last synced" timestamp.

---

## P6 — Speed is a feature

- Page transitions: no full-page reloads. SPA-style navigation via Livewire/Turbo.
- Optimistic UI: actions appear to succeed immediately, roll back on failure.
- List items: virtualised for 1000+ rows (TanStack Table / Alpine Virtual).
- Images: lazy-loaded, WebP with fallback.
- PWA cache: code split, pre-cache critical routes.

---

## P7 — Accessibility (WCAG 2.1 AA)

- All text meets contrast ratio ≥ 4.5:1 (≥ 3:1 for large text).
- Focus indicators: visible 2 px ring, never removed.
- Keyboard: Tab through every interactive element; Enter/Space to activate; Escape to close.
- Screen reader labels on every icon-button and form field.
- Colour is never the sole indicator of state (status pills include text + icon).

---

## P8 — Role-aware, not role-cloned

- One codebase, one screen per noun. Visibility is gated by permission, not by
  duplicating screens.
- The sidebar and bottom nav are role-scoped: a Worker never sees Settings.
- A Manager sees the same "Employee profile" as HR but with fewer action buttons.
- Danger zone actions (delete, impersonate) are Super Admin only.

---

## P9 — Error prevention & recovery

- Forms: inline validation on blur, not just on submit.
- Required fields are marked with `*` and a helper text if ambiguous.
- Auto-save drafts on long forms (Employee wizard, Task creation).
- Confirm before destructive actions: "Are you sure you want to delete Ahmad Fauzi?
  This cannot be undone." — with the name bolded.
- 404/403 pages: friendly, branded, with a "Back to Dashboard" link.

---

## P10 — Shipped is better than perfect

- Ship the smallest coherent slice of value (e.g. "Workforce: Employee directory + 
  Attendance") before expanding to Field.
- Every screen in this spec is v1-essential; deferred features are documented as
  follow-on releases.
- `Settings > Feature flags` gates experimental features behind toggles so we can
  ship production-stable rapidly.

---

## Principle checklist

When designing or reviewing any screen, verify:

- [ ] P1 — Primary action is obvious within 5 seconds.
- [ ] P2 — Works at 375 px width with touch targets ≥ 44 px.
- [ ] P3 — Data is dense but scannable; progressive disclosure used.
- [ ] P4 — Uses design tokens; no ad-hoc values; icon family consistent.
- [ ] P5 — Status lifecycle is clear; error messages are helpful.
- [ ] P6 — Skeleton states exist; no full-page reloads.
- [ ] P7 — Tab order is logical; colour is not sole indicator.
- [ ] P8 — Screen is gated by role, not duplicated.
- [ ] P9 — Inline validation, auto-save, destructive confirm.
- [ ] P10 — Is this the smallest shippable slice?