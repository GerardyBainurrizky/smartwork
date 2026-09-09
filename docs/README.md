# ISA SmartWork — Design Documentation

> **Enterprise Workforce & Field Operations Platform**
> **PT ISA Tri Selaras Gemilang Agricultural**

---

## Index

| # | Document | Description |
|---|----------|-------------|
| 01 | [Product Vision & Positioning](01-product-vision.md) | Why, what, who, success metrics, non-goals |
| 02 | [User Flows](02-user-flows.md) | F1–F9: sign-in, check-in, tasks, leave, payroll, approvals, reports, sync, onboarding |
| 03 | [Navigation Flow](03-navigation-flow.md) | Global chrome, module routing, cross-cutting patterns, guards |
| 04 | [Information Architecture](04-information-architecture.md) | Full sitemap, IA principles, breadcrumb grammar |
| 05 | [Screen List](05-screen-list.md) | ~89 screens across 8 modules, purpose + key components per screen |
| 06 | [Design Principles](06-design-principles.md) | P1–P10: clarity, mobile-first, accessibility, speed, trust, shipping |
| 07 | [Branding Guidelines](07-branding-guidelines.md) | Logo, colour, typography, spacing, radius, elevation, motion, icons, favicon, PWA, reports |
| 08 | [Enterprise Design System](08-design-system.md) | Complete token system: colours (extracted from logo), typography, spacing, radius, shadows, buttons, inputs, tables, cards, modals, badges, sidebar, navbar, empty/error/success/loading states, dark mode, CSS custom properties, Tailwind config |
| 09 | [Entity Relationship Diagram](09-erd.md) | Full ERD: 17 entities, 14 relationships, cardinality matrix, key design decisions |
| 10 | [Database Design](10-database-design.md) | 23 migration schemas, index strategy, partitioning, UUID v7, pruning policy |
| 11 | [Laravel Architecture](11-laravel-architecture.md) | Folder structure, service layer, repository pattern, DTOs, actions, middleware, API v1 endpoints, form requests, API resources, events, listeners, enums, config |

---

## How to use these docs

1. **Start with 01** — align on product vision.
2. **Read 02 + 03** — understand what users do and how they navigate.
3. **Read 04** — map the sitemap to database and route design.
4. **Use 05** — as the implementation checklist per screen.
5. **Apply 06** — as the review gate for every PR and design review.
6. **Follow 07** — for all CSS, components, assets, and exports.

---

## Design decisions log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-08-04 | Initial design system created | Greenfield; no prior design debt |
| 2026-08-04 | Colour palette extracted from logo | Pixel-cluster analysis: primary `#0DA4CE` (cyan-blue), accent `#90F022` (lime green), neutral `#111112` (dark navy) |
| 2026-08-04 | Logo file has double `.png.png` extension | File: `public/assets/images/logo-isa-smartwork.png.png` — rename to `.png` |

---

## Status

- [x] Product Vision
- [x] User Flows
- [x] Navigation Flow
- [x] Information Architecture
- [x] Screen List
- [x] Design Principles
- [x] Branding Guidelines
- [x] Enterprise Design System
- [x] Entity Relationship Diagram
- [x] Database Design
- [x] Laravel Architecture
- [ ] Design system component library (Figma tokens export)
- [ ] Prototype (Figma / code)
- [ ] Implementation

---

**Maintained by:** Product Design & Architecture team
**Last updated:** 04 Aug 2026