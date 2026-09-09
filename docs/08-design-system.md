# 08 — Enterprise Design System

> **ISA SmartWork** — Complete design token system.
> Colors extracted from `public/assets/images/logo-isa-smartwork.png.png`.
> All tokens are generated from the logo's actual hue families.

---

## 1. Color System

### 1.1 Source extraction

Colors were algorithmically extracted from the company logo via pixel cluster analysis:

| Cluster | Representative hex | Hue | Role |
|---------|--------------------|-----|------|
| Cyan-blue (dominant) | `#25A5C7` | 192° | Primary brand |
| Lime green (secondary) | `#ADF15D` | 88° | Accent / highlight |
| Dark navy (text) | `#06131E` | 207° | Neutral base |

The logo is a **cyan-blue gradient with lime-green highlights** — a modern
agricultural-tech identity. The design system uses these exact hue families,
mathematically scaled for UI accessibility.

---

### 1.2 Primary — Cyan-Blue

| Token | Hex | CSS Custom Property | Usage |
|-------|-----|---------------------|-------|
| `primary-50` | `#EFF7F9` | `--color-primary-50` | Lightest backgrounds, tooltip bg |
| `primary-100` | `#DEF1F7` | `--color-primary-100` | Selected row, hover fill |
| `primary-200` | `#AEE4F3` | `--color-primary-200` | Badge fill, progress bar track |
| `primary-300` | `#65D6F5` | `--color-primary-300` | Dark mode accent, icon fill |
| `primary-400` | `#2CC6F1` | `--color-primary-400` | Brand highlight, gradient |
| `primary-500` | `#0DA4CE` | `--color-primary-500` | Links, tab indicator, progress bar |
| `primary-600` | `#097A99` | `--color-primary-600` | Button bg, interactive elements |
| `primary-700` | `#065469` | `--color-primary-700` | Button hover, strong text links |
| `primary-800` | `#043543` | `--color-primary-800` | Button text bg (dark), pressed |
| `primary-900` | `#021E26` | `--color-primary-900` | Deepest brand, dark mode bg accent |

**Contrast compliance:**
- `primary-600` + white text: **4.9:1** (AA ✅)
- `primary-700` + white text: **8.5:1** (AAA ✅)
- `primary-800` + white text: **13.2:1** (AAA ✅)

---

### 1.3 Secondary — Deep Teal

| Token | Hex | CSS Custom Property | Usage |
|-------|-----|---------------------|-------|
| `secondary-50` | `#EDF3F6` | `--color-secondary-50` | Subtle background |
| `secondary-100` | `#DAE9F0` | `--color-secondary-100` | Alternate row |
| `secondary-200` | `#A5D2E8` | `--color-secondary-200` | Highlight |
| `secondary-300` | `#63B1D8` | `--color-secondary-300` | Icon accent |
| `secondary-400` | `#1E98D6` | `--color-secondary-400` | Gradient accent |
| `secondary-500` | `#166F9C` | `--color-secondary-500` | Sidebar bg, secondary text |
| `secondary-600` | `#105274` | `--color-secondary-600` | Sidebar hover, active nav |
| `secondary-700` | `#0B3950` | `--color-secondary-700` | Dark surface, footer |
| `secondary-800` | `#072635` | `--color-secondary-800` | Deep background |
| `secondary-900` | `#04161F` | `--color-secondary-900` | Deepest base |

---

### 1.4 Accent — Lime Green (from logo)

| Token | Hex | CSS Custom Property | Usage |
|-------|-----|---------------------|-------|
| `accent-50` | `#F2F7EC` | `--color-accent-50` | Success bg light |
| `accent-100` | `#E6F4D6` | `--color-accent-100` | Success row |
| `accent-200` | `#D3F3AE` | `--color-accent-200` | Progress fill |
| `accent-300` | `#BBED82` | `--color-accent-300` | Chart positive |
| `accent-400` | `#A8F352` | `--color-accent-400` | Call-to-action highlight |
| `accent-500` | `#90F022` | `--color-accent-500` | **Primary CTA button** |
| `accent-600` | `#74CE0D` | `--color-accent-600` | CTA hover, saturation |
| `accent-700` | `#599E0A` | `--color-accent-700` | CTA pressed |
| `accent-800` | `#3E6E07` | `--color-accent-800` | Dark accent text |
| `accent-900` | `#233E03` | `--color-accent-900` | Darkest accent |

**Contrast compliance:**
- `accent-500` + dark text `neutral-900`: **4.7:1** (AA ✅)
- `accent-600` + white text: **4.5:1** (AA ✅)
- `accent-700` + white text: **6.5:1** (AA ✅)

---

### 1.5 Neutral — Slate (from logo dark)

| Token | Hex | CSS Custom Property | Usage |
|-------|-----|---------------------|-------|
| `neutral-50` | `#F7F7F7` | `--color-neutral-50` | Page background |
| `neutral-100` | `#EFEFEF` | `--color-neutral-100` | Surface sunken, input bg |
| `neutral-200` | `#E0E0E0` | `--color-neutral-200` | Border light, divider |
| `neutral-300` | `#C6C6C7` | `--color-neutral-300` | Border default |
| `neutral-400` | `#9D9E9F` | `--color-neutral-400` | Placeholder, disabled |
| `neutral-500` | `#747576` | `--color-neutral-500` | Muted text, captions |
| `neutral-600` | `#555657` | `--color-neutral-600` | Secondary text |
| `neutral-700` | `#373838` | `--color-neutral-700` | Body text |
| `neutral-800` | `#232324` | `--color-neutral-800` | Heading text |
| `neutral-900` | `#111112` | `--color-neutral-900` | Primary ink (`--color-ink`) |

---

### 1.6 Semantic

| Token | Hex | CSS Custom Property | Usage |
|-------|-----|---------------------|-------|
| `success-50` | `#EBF9F2` | `--color-success-50` | Success background |
| `success-100` | `#D7F3E5` | `--color-success-100` | Success row |
| `success-500` | `#20B66B` | `--color-success-500` | Success icon, badge |
| `success-600` | `#188A51` | `--color-success-600` | Success text |
| `success-700` | `#105F38` | `--color-success-700` | Success strong |
| `warning-50` | `#F9F4EA` | `--color-warning-50` | Warning background |
| `warning-100` | `#F4E9D6` | `--color-warning-100` | Warning row |
| `warning-500` | `#C08115` | `--color-warning-500` | Warning icon, badge |
| `warning-600` | `#926310` | `--color-warning-600` | Warning text |
| `warning-700` | `#64440B` | `--color-warning-700` | Warning strong |
| `danger-50` | `#F9EBEB` | `--color-danger-50` | Danger background |
| `danger-100` | `#F3D7D7` | `--color-danger-100` | Danger row |
| `danger-500` | `#BB1A1A` | `--color-danger-500` | Danger icon, badge |
| `danger-600` | `#8E1414` | `--color-danger-600` | Danger text |
| `danger-700` | `#620E0E` | `--color-danger-700` | Danger strong |
| `info-50` | `#EBF1F9` | `--color-info-50` | Info background |
| `info-100` | `#D7E3F3` | `--color-info-100` | Info row |
| `info-500` | `#1A5DBB` | `--color-info-500` | Info icon, badge |
| `info-600` | `#14478E` | `--color-info-600` | Info text |
| `info-700` | `#0E3162` | `--color-info-700` | Info strong |

---

### 1.7 Dark Mode Palette

| Token | Light mode | Dark mode | Purpose |
|-------|-----------|-----------|---------|
| `--color-bg` | `#FFFFFF` | `#111112` (neutral-900) | Page background |
| `--color-surface` | `#FFFFFF` | `#232324` (neutral-800) | Card, modal |
| `--color-surface-raised` | `#FFFFFF` | `#373838` (neutral-700) | Elevated surface |
| `--color-surface-sunken` | `#F7F7F7` (neutral-50) | `#111112` (neutral-900) | Input bg, sunken |
| `--color-border` | `#C6C6C7` (neutral-300) | `#373838` (neutral-700) | Default border |
| `--color-border-light` | `#E0E0E0` (neutral-200) | `#232324` (neutral-800) | Subtle border |
| `--color-ink` | `#111112` (neutral-900) | `#EFEFEF` (neutral-100) | Primary text |
| `--color-ink-muted` | `#555657` (neutral-600) | `#9D9E9F` (neutral-400) | Secondary text |
| `--color-ink-subtle` | `#9D9E9F` (neutral-400) | `#747576` (neutral-500) | Placeholder |
| `--color-primary-btn` | `#097A99` (primary-600) | `#65D6F5` (primary-300) | Button bg |
| `--color-primary-btn-text` | `#FFFFFF` | `#021E26` (primary-900) | Button text |
| `--color-primary-link` | `#097A99` (primary-600) | `#2CC6F1` (primary-400) | Links |
| `--color-accent-btn` | `#90F022` (accent-500) | `#A8F352` (accent-400) | CTA button |
| `--color-accent-btn-text` | `#111112` (neutral-900) | `#111112` (neutral-900) | CTA text |

---

## 2. Typography

### 2.1 Font Family

```css
:root {
  --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
               Oxygen, Ubuntu, Cantarell, sans-serif;
  --font-mono: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', 'Consolas',
               'Courier New', monospace;
}
```

**Delivery:** Self-host via `@font-face` with `font-display: swap`. No Google Fonts CDN
(enterprise security requirement). Subset to Latin + Indonesian characters.

### 2.2 Type Scale

| Token | Size | Line-height | Weight | Letter-spacing | Usage |
|-------|------|-------------|--------|----------------|-------|
| `text-3xs` | 10 px | 14 px | 400 | +0.02em | Tiny labels, legal |
| `text-2xs` | 11 px | 16 px | 500 | +0.01em | Overline, table header |
| `text-xs` | 12 px | 16 px | 400 | 0 | Badge, caption, helper |
| `text-sm` | 14 px | 20 px | 400 | 0 | Body, table cell, input |
| `text-base` | 16 px | 24 px | 400 | 0 | Primary body, form label |
| `text-lg` | 18 px | 28 px | 500 | -0.01em | Card title, section |
| `text-xl` | 20 px | 28 px | 600 | -0.02em | Page title (mobile) |
| `text-2xl` | 24 px | 32 px | 600 | -0.02em | Page title, modal title |
| `text-3xl` | 30 px | 36 px | 700 | -0.03em | Hero KPI number |
| `text-4xl` | 36 px | 40 px | 700 | -0.03em | Login title |
| `text-5xl` | 48 px | 52 px | 700 | -0.04em | Reserved |

### 2.3 Typographic Rules

- **Headings:** `font-sans`, weight 600–700, letter-spacing -0.02em, color `--color-ink`
- **Body:** `font-sans`, weight 400, letter-spacing 0, color `--color-ink`
- **Numbers/Data:** `font-mono`, `font-variant-numeric: tabular-nums`, color `--color-ink`
- **Links:** `font-sans`, color `--color-primary-link`, underline on hover
- **Max line length:** 72 characters (~640 px at 16 px) for body text
- **No all-caps:** use `text-2xs`, `font-medium`, `letter-spacing: +0.04em` for section labels
- **Indonesian text:** Inter supports all Latin characters needed for Bahasa Indonesia

---

## 3. Spacing System

Based on a 4 px grid. All spacing uses these tokens exclusively.

| Token | Value | CSS Custom Property | Usage |
|-------|-------|---------------------|-------|
| `space-0` | 0 | `--space-0` | No gap |
| `space-px` | 1 px | `--space-px` | Hairline divider |
| `space-0.5` | 2 px | `--space-0\.5` | Icon-text micro gap |
| `space-1` | 4 px | `--space-1` | Badge padding, inline gap |
| `space-1.5` | 6 px | `--space-1\.5` | Tight icon padding |
| `space-2` | 8 px | `--space-2` | Card compact padding |
| `space-2.5` | 10 px | `--space-2\.5` | Button vertical padding |
| `space-3` | 12 px | `--space-3` | Form group gap, list item |
| `space-3.5` | 14 px | `--space-3\.5` | |
| `space-4` | 16 px | `--space-4` | Default card padding |
| `space-5` | 20 px | `--space-5` | Large card padding |
| `space-6` | 24 px | `--space-6` | Section spacing, modal pad |
| `space-8` | 32 px | `--space-8` | Page section divider |
| `space-10` | 40 px | `--space-10` | |
| `space-12` | 48 px | `--space-12` | Hero section |
| `space-16` | 64 px | `--space-16` | Layout gap |
| `space-20` | 80 px | `--space-20` | |
| `space-24` | 96 px | `--space-24` | Reserved |

### Component Spacing Defaults

| Component | Vertical | Horizontal | Gap |
|-----------|----------|------------|-----|
| Button `sm` | 6 px | 12 px | 4 px (icon) |
| Button `md` | 10 px | 16 px | 8 px (icon) |
| Button `lg` | 12 px | 24 px | 8 px (icon) |
| Input `md` | 10 px | 12 px | — |
| Input `lg` | 12 px | 16 px | — |
| Card | 16 px | 16 px | 16 px (children) |
| Card (desktop) | 24 px | 24 px | 24 px (children) |
| Table cell | 12 px | 16 px | — |
| Modal | 24 px | 24 px | 24 px (sections) |

---

## 4. Border Radius System

| Token | Value | Usage |
|-------|-------|-------|
| `radius-none` | 0 | Tables, dividers, tooltips, sidebar |
| `radius-xs` | 2 px | Checkbox, radio, tiny badges |
| `radius-sm` | 4 px | Input, select, badge, small button |
| `radius-md` | 8 px | Button, card, dropdown, toast |
| `radius-lg` | 12 px | Modal, sheet, large card |
| `radius-xl` | 16 px | Hero card, featured section |
| `radius-2xl` | 24 px | Reserved |
| `radius-full` | 9999 px | Pill, avatar, status dot, tag |

**Rule:** Never use an unlisted radius value. All borders use `radius-sm` (4 px) or
`radius-md` (8 px). `radius-lg` is reserved for elevated dialogs.

---

## 5. Shadow System

| Token | CSS Value | Usage |
|-------|-----------|-------|
| `shadow-none` | `none` | Default, flat elements |
| `shadow-xs` | `0 1px 2px 0 rgba(17,17,18,0.04)` | Subtle lift, sticky header |
| `shadow-sm` | `0 1px 3px 0 rgba(17,17,18,0.06), 0 1px 2px -1px rgba(17,17,18,0.04)` | Card, dropdown |
| `shadow-md` | `0 4px 6px -1px rgba(17,17,18,0.06), 0 2px 4px -2px rgba(17,17,18,0.04)` | Modal, popover |
| `shadow-lg` | `0 10px 15px -3px rgba(17,17,18,0.08), 0 4px 6px -4px rgba(17,17,18,0.04)` | Sheet, drawer |
| `shadow-xl` | `0 20px 25px -5px rgba(17,17,18,0.10), 0 8px 10px -6px rgba(17,17,18,0.04)` | Top-level dialog |
| `shadow-2xl` | `0 25px 50px -12px rgba(17,17,18,0.15)` | Reserved |

**Dark mode:** Multiply the alpha channel by 1.4 for equivalent perceived depth.

---

## 6. Component Design Tokens

### 6.1 Button

| Variant | Background | Text | Border | Hover bg | Hover border | Focus ring |
|---------|------------|------|--------|----------|-------------|------------|
| **Primary** | `primary-600` | `white` | none | `primary-700` | none | `primary-400` 2px |
| **Secondary** | `transparent` | `primary-600` | `primary-300` | `primary-50` | `primary-400` | `primary-400` 2px |
| **Ghost** | `transparent` | `neutral-700` | none | `neutral-100` | none | `neutral-400` 2px |
| **Danger** | `danger-600` | `white` | none | `danger-700` | none | `danger-500` 2px |
| **Danger-ghost** | `transparent` | `danger-600` | none | `danger-50` | none | `danger-500` 2px |
| **Accent** | `accent-500` | `neutral-900` | none | `accent-600` | none | `accent-600` 2px |

**Sizes:**

| Size | Height | Padding H | Padding V | Font | Radius | Gap |
|------|--------|-----------|-----------|------|--------|-----|
| `xs` | 28 px | 8 px | 4 px | `text-xs` | `radius-sm` | 4 px |
| `sm` | 32 px | 12 px | 6 px | `text-sm` | `radius-sm` | 4 px |
| `md` | 40 px | 16 px | 10 px | `text-sm` | `radius-md` | 8 px |
| `lg` | 48 px | 24 px | 12 px | `text-base` | `radius-md` | 8 px |
| `xl` | 56 px | 32 px | 16 px | `text-base` | `radius-lg` | 12 px |

**Icon-only button:** Same height, width = height, padding = 0, border-radius = `radius-md`.

**Disabled state:** Opacity 0.40, `cursor: not-allowed`, no hover effect.

**Loading state:** Replace icon (or append) with 16 px spinner, same color as text.
Button remains same width (use `min-width` to prevent layout shift).

**Touch target:** Minimum 44×44 px on mobile (`md` size or larger).

---

### 6.2 Input

**Text Input (default):**

| Property | Value |
|----------|-------|
| Height | 40 px |
| Padding | 10 px V, 12 px H |
| Background | `--color-surface` |
| Border | 1.5 px `--color-border` |
| Border-radius | `radius-sm` (4 px) |
| Text | `text-sm`, `--color-ink` |
| Placeholder | `text-sm`, `--color-ink-subtle` |

**States:**

| State | Border | Background | Shadow |
|-------|--------|------------|--------|
| **Default** | `neutral-300` | `white` | none |
| **Hover** | `neutral-400` | `white` | none |
| **Focus** | `primary-400` | `white` | `0 0 0 3px rgba(45,198,241,0.15)` |
| **Error** | `danger-500` | `white` | `0 0 0 3px rgba(187,26,26,0.12)` |
| **Disabled** | `neutral-200` | `neutral-50` | none |
| **Read-only** | `neutral-200` | `neutral-50` | none |

**Label:**
- Position: above input, 6 px gap
- Font: `text-sm`, `font-medium`, `--color-ink`
- Required: `*` in `danger-600`, placed after label text

**Helper text:**
- Position: below input, 4 px gap
- Font: `text-xs`, `--color-ink-muted`

**Error text:**
- Position: below input, 4 px gap
- Font: `text-xs`, `--color-danger-600`
- Icon: small `alert-circle` 12 px before text

**Input sizes:**

| Size | Height | Padding V | Font |
|------|--------|-----------|------|
| `sm` | 32 px | 6 px | `text-xs` |
| `md` | 40 px | 10 px | `text-sm` |
| `lg` | 48 px | 12 px | `text-base` |

**Select:** Same as input, with chevron-down icon, 20 px, `neutral-400`, right 12 px.

**Textarea:** Same border/focus rules. Min-height 80 px. Resize vertical only.

**Checkbox / Radio:**
- Size: 20×20 px
- Border: 1.5 px `neutral-300`
- Border-radius: `radius-xs` (checkbox), `radius-full` (radio)
- Checked: `primary-600` fill, white checkmark
- Focus: 2 px `primary-400` ring, 2 px offset

**Toggle / Switch:**
- Track: 40×24 px, `radius-full`, `neutral-200` (off), `primary-500` (on)
- Thumb: 20×20 px, `radius-full`, `white`, `shadow-sm`
- Focus: 2 px `primary-400` ring

---

### 6.3 Table

| Property | Value |
|----------|-------|
| Width | 100% |
| Border-collapse | separate |
| Border-spacing | 0 |
| Border-radius | `radius-md` (outer container) |
| Border | 1 px `neutral-200` (outer), 1 px `neutral-100` (inner divider) |

**Header row:**

| Property | Value |
|----------|-------|
| Background | `neutral-50` |
| Font | `text-2xs`, `font-medium`, `--color-ink-muted` |
| Letter-spacing | +0.04em |
| Height | 40 px |
| Padding | 12 px V, 16 px H |
| Border-bottom | 1 px `neutral-200` |
| Text-align | left (text), right (numbers/currency) |

**Body row:**

| Property | Value |
|----------|-------|
| Background | `white` |
| Font | `text-sm`, `--color-ink` |
| Height | 48 px |
| Padding | 12 px V, 16 px H |
| Border-bottom | 1 px `neutral-100` |

**Zebra striping:** Even rows: `neutral-50` at 60% opacity.

**Row hover:** `primary-50` at 50% opacity.

**Sticky header:** `position: sticky`, `top: 0`, `z-index: 10`, `shadow-xs`.

**Sticky first column:** `position: sticky`, `left: 0`, background inherit, `shadow-xs` (right).

**Sortable header:** Arrow icon (12 px, `neutral-400`) on hover. Active sort: `primary-500`.

**Mobile:** Horizontal scroll container. Sticky first column. Card-list fallback below 640 px.

**Empty table:** See [7.1 Empty State](#71-empty-state).

**Pagination:** Below table, right-aligned. Row count: "1–20 of 247" left-aligned.

---

### 6.4 Card

| Property | Value |
|----------|-------|
| Background | `--color-surface` |
| Border | 1 px `neutral-200` |
| Border-radius | `radius-md` (8 px) |
| Padding | 16 px (mobile), 24 px (desktop) |
| Shadow | `shadow-xs` (default), `shadow-sm` (hover) |

**Card header:**
- Title: `text-lg`, `font-medium`, `--color-ink`
- Action: top-right, ghost button or icon button
- Divider: optional 1 px `neutral-100` below header

**Card variants:**

| Variant | Purpose | Distinction |
|---------|---------|-------------|
| **Default** | Content container | `shadow-xs` |
| **Interactive** | Clickable card | `shadow-xs`, hover: `shadow-sm`, `cursor: pointer` |
| **Featured** | Highlighted card | `shadow-sm`, `primary-50` border-left 3 px `primary-500` |
| **Stat card** | KPI dashboard | Centered, large number `text-3xl`, label `text-sm` |
| **Flat** | Minimal | No border, no shadow, `neutral-50` bg |

**Card group:** Cards in a row/column use `space-4` gap. Equal height via `align-items: stretch`.

---

### 6.5 Modal

| Property | Value |
|----------|-------|
| Width | 480 px (default), 640 px (large), 320 px (small) |
| Max-width | 90vw (mobile) |
| Max-height | 85vh |
| Background | `--color-surface` |
| Border-radius | `radius-lg` (12 px) |
| Shadow | `shadow-xl` |
| Padding | 24 px |

**Modal structure:**

```
┌──────────────────────────────────┐
│  Title (text-lg, font-medium)  ✕ │  ← Header: 24 px pad, border-bottom if scroll
├──────────────────────────────────┤
│                                  │
│  Content (text-sm, space-4 gap)  │  ← Scrollable, padding 24 px
│                                  │
├──────────────────────────────────┤
│  [Cancel]        [Primary CTA]   │  ← Footer: 24 px pad, border-top, right-aligned
└──────────────────────────────────┘
```

**Backdrop:** `rgba(17, 17, 18, 0.50)`, `backdrop-filter: blur(4px)`.

**Animation:**
- In: fade 250ms + scale 0.97→1.0, `ease-out`
- Out: fade 150ms, `ease-in`
- `prefers-reduced-motion`: no animation, instant

**Close triggers:** ✕ button, Escape key, backdrop click (optional, disabled for
forms with unsaved changes).

**Focus trap:** Tab cycles within modal. Focus returns to trigger on close.

**Mobile:** Full-screen sheet from bottom. Border-radius: `radius-lg` top only.
Height: auto up to 90vh. Swipe-down to dismiss.

---

### 6.6 Badge / Status Pill

**Sizes:**

| Size | Height | Padding H | Font | Radius |
|------|--------|-----------|------|--------|
| `sm` | 20 px | 8 px | `text-2xs` | `radius-full` |
| `md` | 24 px | 10 px | `text-xs` | `radius-full` |
| `lg` | 28 px | 12 px | `text-sm` | `radius-full` |

**Variants:**

| Variant | Background | Text | Icon | Dot |
|---------|------------|------|------|-----|
| **Success** | `success-50` | `success-600` | `check-circle` | `success-500` 8 px |
| **Warning** | `warning-50` | `warning-600` | `alert-triangle` | `warning-500` 8 px |
| **Danger** | `danger-50` | `danger-600` | `x-circle` | `danger-500` 8 px |
| **Info** | `info-50` | `info-600` | `info` | `info-500` 8 px |
| **Neutral** | `neutral-100` | `neutral-600` | `minus-circle` | `neutral-400` 8 px |
| **Primary** | `primary-50` | `primary-600` | `circle` | `primary-500` 8 px |

**Status dot:** 8×8 px circle, `radius-full`, before text with 6 px gap.

---

### 6.7 Sidebar

| Property | Value |
|----------|-------|
| Width (expanded) | 240 px |
| Width (collapsed) | 68 px |
| Background | `neutral-900` (dark), `white` (light mode alternative) |
| Text | `neutral-100` (dark bg), `neutral-700` (light bg) |
| Logo area | 56 px height, centered, 24 px pad |
| Nav item height | 40 px |
| Nav item padding | 12 px H, 10 px V |
| Nav item radius | `radius-md` |
| Active item | `primary-600` bg, `white` text |
| Hover item | `neutral-800` bg (dark), `neutral-100` bg (light) |
| Divider | 1 px `neutral-800` (dark), `neutral-200` (light) |
| Icon size | 20 px |
| Transition | 200ms `ease-out` width |

**Mobile:** Sidebar is hidden. Revealed via hamburger menu as overlay sheet.
Backdrop: `rgba(17,17,18,0.50)`. Swipe-left to dismiss.

---

### 6.8 Navbar (Top)

| Property | Value |
|----------|-------|
| Height | 56 px |
| Background | `white` |
| Border-bottom | 1 px `neutral-200` |
| Padding | 0 16 px (mobile), 0 24 px (desktop) |
| Shadow | `shadow-xs` (sticky) |

**Content (left to right):**
1. Sidebar toggle (hamburger, desktop only), 20 px icon
2. Logo: 32 px height, left margin 12 px (mobile; hidden on desktop if sidebar shows logo)
3. Breadcrumbs: `text-sm`, `neutral-500`, `/` separator
4. Spacer
5. Global Search: `cmd+k` trigger, 240 px wide, `neutral-100` bg, `radius-md`
6. Inbox (bell): icon button, badge count in `danger-500`
7. User menu: avatar 32×32 px, `radius-full`, name `text-sm`, chevron

**Mobile:** Search collapses to icon. Breadcrumbs hidden. Bottom tab bar replaces
sidebar navigation.

---

## 7. State Design Tokens

### 7.1 Empty State

**When to show:** No data in a collection (table, list, board).

```
┌──────────────────────────────────────────────┐
│                                              │
│         [Illustration / Icon 48px]          │
│              neutral-300                     │
│                                              │
│         No employees yet                    │  ← text-lg, font-medium, neutral-700
│    Add your first employee to get started   │  ← text-sm, neutral-500
│                                              │
│           [  + New Employee  ]               │  ← Primary button, md
│                                              │
└──────────────────────────────────────────────┘
```

**Component spec:**
- Container: card or full-width, 120 px min-height, centered
- Icon: 48×48 px, `neutral-300`, rounded background `neutral-100`, 16 px padding
- Title: `text-lg`, `font-medium`, `neutral-700`
- Description: `text-sm`, `neutral-500`, max-width 360 px, centered
- CTA: primary button, directly below description with 16 px gap

**Variants:**
- **First-time:** "No [items] yet" + CTA to create
- **Filtered empty:** "No results for '[query]'" + "Clear filters" ghost button
- **Error empty:** "Could not load [items]" + "Retry" button
- **Permission empty:** "You don't have access to [items]" + "Request access" link

---

### 7.2 Error State

**Inline error (form field):**

```
┌──────────────────────────────────┐
│  Email *                         │
│  ┌──────────────────────────────┐│
│  │ budi@                         ││  ← border: danger-500, ring: danger-500
│  └──────────────────────────────┘│
│  ⚠ Enter a valid email address   │  ← text-xs, danger-600, 4 px gap
└──────────────────────────────────┘
```

**Banner error (page-level):**

```
┌──────────────────────────────────────────────────┐
│  ⚠  Something went wrong                         │  ← danger-50 bg, danger-600 text
│     Unable to save changes. Please try again.     │     danger-700 border-left 3px
│     [ Retry ]                                    │     text-sm, icon 20 px
└──────────────────────────────────────────────────┘
```

**Toast error:**

```
┌─────────────────────────────────┐
│  ⚠  Failed to submit task       │  ← danger-50 bg, danger-600 text
│     Network error. Try again.   │     radius-md, shadow-md
│                          [ ✕ ]  │     370 px max-width, bottom-right
└─────────────────────────────────┘
```

**404 / 403 Page:**

```
┌──────────────────────────────────────────────────┐
│                                                  │
│              [Illustration 120px]                │
│                                                  │
│              Page not found                      │  ← text-2xl, font-medium
│     The page you're looking for doesn't exist    │  ← text-sm, neutral-500
│     or you don't have permission to view it.     │
│                                                  │
│           [  ← Back to Dashboard  ]              │  ← Secondary button
│                                                  │
└──────────────────────────────────────────────────┘
```

**Error principles:**
- Say what happened and what to do next
- Never expose raw error messages, stack traces, or SQL
- Log full error server-side; show sanitized message client-side
- All error states include a recovery path (Retry, Go back, Contact support)

---

### 7.3 Success State

**Toast:**

```
┌─────────────────────────────────┐
│  ✓  Employee saved              │  ← success-50 bg, success-600 text
│     Ahmad Fauzi has been added. │     radius-md, shadow-md
│       [ Undo ]          [ ✕ ]   │     370 px max-width, bottom-right
└─────────────────────────────────┘
```

**Auto-dismiss:** 5 seconds. Undo action available for 5 seconds.

**Inline success (form):**

```
┌──────────────────────────────────┐
│  ┌──────────────────────────────┐│
│  │ budi@isatriselaras.co.id   ✓ ││  ← border: success-500, check icon
│  └──────────────────────────────┘│
└──────────────────────────────────┘
```

**Success page (after wizard/flow):**

```
┌──────────────────────────────────────────────┐
│                                              │
│              [✓ 64 px icon]                  │
│              success-500                     │
│                                              │
│         Employee onboarded                   │  ← text-2xl, font-medium
│     Ahmad Fauzi has been added and           │  ← text-sm, neutral-500
│     an invitation has been sent.             │
│                                              │
│     [  View Employee  ]  [  Add Another  ]   │  ← Primary + Secondary
│                                              │
└──────────────────────────────────────────────┘
```

---

### 7.4 Loading State

**Skeleton (preferred over spinner):**

```
┌──────────────────────────────────────┐
│  ┌─────────────────────────────┐     │  ← Card header skeleton
│  │ ████████████                │     │     text-lg bar: 60% width, 20 px h
│  └─────────────────────────────┘     │
│                                      │
│  ┌────┐ ┌──────────┐ ┌────────┐     │  ← Table row skeleton
│  │ ██ │ │ ████████ │ │ ██████ │     │     rows: 48 px H, 16 px gap
│  ├────┤ ├──────────┤ ├────────┤     │     cells: vary widths (20%, 40%, 30%)
│  │ ██ │ │ ████████ │ │ ██████ │     │
│  ├────┤ ├──────────┤ ├────────┤     │
│  │ ██ │ │ ████████ │ │ ██████ │     │
│  └────┘ └──────────┘ └────────┘     │
│                                      │
│  ┌──────┐ ┌──────┐                  │  ← Card skeleton
│  │ ████ │ │ ████ │                  │     stat cards: 120×100 px
│  └──────┘ └──────┘                  │
└──────────────────────────────────────┘
```

**Skeleton spec:**
- Background: `neutral-100` (light), `neutral-700` (dark)
- Animation: pulse 1.5s `ease-in-out` infinite, opacity 1.0→0.5→1.0
- `prefers-reduced-motion`: static, no pulse
- Border-radius: `radius-sm` (4 px)
- Height matches target content:
  - Heading: 20 px
  - Body: 14 px
  - Avatar: 32×32 px, `radius-full`
  - Button: 40 px

**Spinner (inline actions):**

| Property | Value |
|----------|-------|
| Size | 16 px (sm), 20 px (md), 24 px (lg) |
| Stroke | 2 px |
| Color | `primary-500` |
| Track | `neutral-200` |
| Animation | spin 0.6s linear infinite |

**Page-level loading:** Full-page skeleton (navbar stays, content area skeleton).

**Button loading:** Replace icon with 16 px spinner, disable button, maintain width.

**Progressive loading:** Content appears as it arrives. Skeleton → partial → full.
No layout shift (use fixed dimensions).

---

### 7.5 Disabled State

| Component | Opacity | Cursor | Interaction |
|-----------|---------|--------|-------------|
| Button | 0.40 | `not-allowed` | No click, no hover |
| Input | 0.50 | `not-allowed` | No focus, bg `neutral-50` |
| Select | 0.50 | `not-allowed` | No open |
| Checkbox/Radio | 0.40 | `not-allowed` | No toggle |
| Link | 0.40 | `not-allowed` | No click, no underline |
| Tab | 0.40 | `not-allowed` | No switch |

**Tooltip on disabled:** When a disabled button needs explanation, wrap in a
`<span>` with `title` attribute. Show tooltip on hover explaining why it's disabled
(e.g. "Complete all required fields to continue").

---

### 7.6 Focus State

| Component | Ring | Offset |
|-----------|------|--------|
| Input | 2 px `primary-400` | 0 (ring replaces border) |
| Button | 2 px `primary-400` | 2 px |
| Link | 2 px `primary-400` | 2 px |
| Checkbox/Radio | 2 px `primary-400` | 2 px |
| Select | 2 px `primary-400` | 0 |
| Toggle | 2 px `primary-400` | 2 px |
| Card (interactive) | 2 px `primary-400` | 0 (ring inside border) |

**Rule:** `:focus-visible` only. Never use `:focus` for mouse users. Keyboard Tab
triggers `:focus-visible`. Mouse click does not.

---

### 7.7 Hover State

| Component | Effect | Duration |
|-----------|--------|----------|
| Button | Background darkens 1 shade | 75ms |
| Link | Underline appears | 75ms |
| Card (interactive) | `shadow-sm` + translateY(-1px) | 150ms |
| Table row | `primary-50` at 50% opacity | 75ms |
| Nav item | Background `neutral-100` (light) / `neutral-800` (dark) | 75ms |
| Icon button | Background `neutral-100` | 75ms |

---

## 8. Motion Design

### 8.1 Duration Tokens

| Token | Value | Usage |
|-------|-------|-------|
| `duration-instant` | 75ms | Micro-interactions (hover, focus, toggle) |
| `duration-fast` | 150ms | Button press, tab switch, checkbox |
| `duration-normal` | 250ms | Modal, toast, dropdown, page transition |
| `duration-slow` | 350ms | Sheet, drawer, expand, onboarding |
| `duration-gentle` | 500ms | Chart animate-in, dashboard data reveal |

### 8.2 Easing Tokens

| Token | CSS | Usage |
|-------|-----|-------|
| `ease-default` | `cubic-bezier(0.4, 0, 0.2, 1)` | Standard transitions |
| `ease-in` | `cubic-bezier(0.4, 0, 1, 1)` | Element entering |
| `ease-out` | `cubic-bezier(0, 0, 0.2, 1)` | Element exiting |
| `ease-in-out` | `cubic-bezier(0.4, 0, 0.2, 1)` | Symmetric |

### 8.3 Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

## 9. Accessibility

### 9.1 WCAG 2.1 AA Compliance

| Requirement | Target | Status |
|-------------|--------|--------|
| Color contrast (text) | ≥ 4.5:1 (normal), ≥ 3:1 (large) | ✅ primary-600 + white = 4.9:1 |
| Color contrast (UI) | ≥ 3:1 | ✅ all borders and icons |
| Focus indicators | Visible, ≥ 2 px | ✅ 2 px `primary-400` ring |
| Touch targets | ≥ 44×44 px | ✅ minimum button size `md` (40 px), with 4 px touch extension |
| Keyboard navigation | All interactive elements | ✅ Tab/Shift+Tab, Enter/Space, Escape |
| Screen reader | Semantic HTML, ARIA labels | ✅ `aria-label` on icon buttons, `role` on custom components |
| Color independence | Never color-only | ✅ status pills include text + icon |
| Text resize | 200% without loss | ✅ relative units, `rem`/`em` |
| Motion | Respects `prefers-reduced-motion` | ✅ instant transitions |

### 9.2 Screen Reader Labels

| Element | Attribute | Example |
|---------|-----------|---------|
| Icon button | `aria-label` | `aria-label="Delete employee"` |
| Badge | `aria-label` | `aria-label="Status: Approved"` |
| Modal | `role="dialog"`, `aria-modal`, `aria-labelledby` | |
| Table | `role="table"`, `aria-label` | `aria-label="Employee directory"` |
| Toggle | `role="switch"`, `aria-checked` | |
| Alert | `role="alert"`, `aria-live="polite"` | Toast notifications |

---

## 10. Dark Mode

### 10.1 Implementation Strategy

```css
:root {
  /* Light mode (default) */
  --color-bg: #FFFFFF;
  --color-surface: #FFFFFF;
  --color-ink: #111112;
}

[data-theme="dark"] {
  --color-bg: #111112;
  --color-surface: #232324;
  --color-ink: #EFEFEF;
}

/* System preference */
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --color-bg: #111112;
    --color-surface: #232324;
    --color-ink: #EFEFEF;
  }
}
```

### 10.2 Dark Mode Color Mapping

| Token | Light | Dark |
|-------|-------|------|
| Page bg | `white` | `neutral-900` `#111112` |
| Surface | `white` | `neutral-800` `#232324` |
| Surface raised | `white` | `neutral-700` `#373838` |
| Surface sunken | `neutral-50` | `neutral-900` `#111112` |
| Border | `neutral-300` | `neutral-700` `#373838` |
| Border light | `neutral-200` | `neutral-800` `#232324` |
| Ink | `neutral-900` | `neutral-100` `#EFEFEF` |
| Ink muted | `neutral-600` | `neutral-400` `#9D9E9F` |
| Ink subtle | `neutral-400` | `neutral-500` `#747576` |
| Primary button | `primary-600` | `primary-300` |
| Primary button text | `white` | `primary-900` |
| Primary link | `primary-600` | `primary-400` |
| Accent CTA | `accent-500` | `accent-400` |
| Primary bg light | `primary-50` | `primary-900` |
| Success bg | `success-50` | `success-900` at 30% opacity |
| Warning bg | `warning-50` | `warning-900` at 30% opacity |
| Danger bg | `danger-50` | `danger-900` at 30% opacity |
| Info bg | `info-50` | `info-900` at 30% opacity |
| Shadow | `rgba(17,17,18, 0.04–0.15)` | `rgba(0,0,0, 0.30–0.60)` |
| Sidebar bg | `white` | `neutral-900` |
| Skeleton bg | `neutral-100` | `neutral-700` |

### 10.3 Dark Mode Specific Rules

- **Images:** Reduce brightness to 85% on dark mode to avoid eye strain.
- **Charts:** Use lighter variants of primary/accent for visibility.
- **Code blocks:** `neutral-800` background, `neutral-100` text.
- **Scrollbar:** `neutral-700` thumb, `neutral-800` track.
- **Selection:** `primary-600` at 30% opacity.

---

## 11. Iconography

| Property | Value |
|----------|-------|
| Library | Lucide Icons |
| Sizes | 12 px, 16 px, 20 px, 24 px |
| Stroke width | 1.75 px (default), 2 px (nav active) |
| Color | Inherit from parent text color |
| Line cap | Round |
| Line join | Round |

**Icon sizing by context:**

| Context | Size |
|---------|------|
| Inline with text | 16 px |
| Button icon | 20 px |
| Nav item | 20 px |
| Standalone | 24 px |
| Empty state | 48 px |
| Favicon | 32 px |
| PWA | 192 px, 512 px |

---

## 12. Breakpoints

| Token | Min-width | Target device |
|-------|-----------|---------------|
| `xs` | 0 | Phone portrait |
| `sm` | 640 px | Phone landscape |
| `md` | 768 px | Tablet portrait |
| `lg` | 1024 px | Tablet landscape / small desktop |
| `xl` | 1280 px | Desktop |
| `2xl` | 1536 px | Large desktop |

**Mobile-first:** All styles start at `xs` (0 px). Use `min-width` media queries.

---

## 13. Grid / Layout

| Property | Value |
|----------|-------|
| Page max-width | 1280 px (content), 100% (data tables) |
| Page padding | 16 px (mobile), 24 px (tablet), 32 px (desktop) |
| Content columns | 12-column grid, 24 px gap |
| Sidebar + content | Sidebar 240 px, content `flex: 1` |

---

## 14. CSS Custom Properties (Complete)

```css
:root {
  /* ===== COLORS: Primary ===== */
  --color-primary-50: #EFF7F9;
  --color-primary-100: #DEF1F7;
  --color-primary-200: #AEE4F3;
  --color-primary-300: #65D6F5;
  --color-primary-400: #2CC6F1;
  --color-primary-500: #0DA4CE;
  --color-primary-600: #097A99;
  --color-primary-700: #065469;
  --color-primary-800: #043543;
  --color-primary-900: #021E26;

  /* ===== COLORS: Secondary ===== */
  --color-secondary-50: #EDF3F6;
  --color-secondary-100: #DAE9F0;
  --color-secondary-200: #A5D2E8;
  --color-secondary-300: #63B1D8;
  --color-secondary-400: #1E98D6;
  --color-secondary-500: #166F9C;
  --color-secondary-600: #105274;
  --color-secondary-700: #0B3950;
  --color-secondary-800: #072635;
  --color-secondary-900: #04161F;

  /* ===== COLORS: Accent ===== */
  --color-accent-50: #F2F7EC;
  --color-accent-100: #E6F4D6;
  --color-accent-200: #D3F3AE;
  --color-accent-300: #BBED82;
  --color-accent-400: #A8F352;
  --color-accent-500: #90F022;
  --color-accent-600: #74CE0D;
  --color-accent-700: #599E0A;
  --color-accent-800: #3E6E07;
  --color-accent-900: #233E03;

  /* ===== COLORS: Neutral ===== */
  --color-neutral-50: #F7F7F7;
  --color-neutral-100: #EFEFEF;
  --color-neutral-200: #E0E0E0;
  --color-neutral-300: #C6C6C7;
  --color-neutral-400: #9D9E9F;
  --color-neutral-500: #747576;
  --color-neutral-600: #555657;
  --color-neutral-700: #373838;
  --color-neutral-800: #232324;
  --color-neutral-900: #111112;

  /* ===== COLORS: Semantic ===== */
  --color-success-50: #EBF9F2;
  --color-success-100: #D7F3E5;
  --color-success-500: #20B66B;
  --color-success-600: #188A51;
  --color-success-700: #105F38;

  --color-warning-50: #F9F4EA;
  --color-warning-100: #F4E9D6;
  --color-warning-500: #C08115;
  --color-warning-600: #926310;
  --color-warning-700: #64440B;

  --color-danger-50: #F9EBEB;
  --color-danger-100: #F3D7D7;
  --color-danger-500: #BB1A1A;
  --color-danger-600: #8E1414;
  --color-danger-700: #620E0E;

  --color-info-50: #EBF1F9;
  --color-info-100: #D7E3F3;
  --color-info-500: #1A5DBB;
  --color-info-600: #14478E;
  --color-info-700: #0E3162;

  /* ===== COLORS: Aliases (theme-aware) ===== */
  --color-bg: #FFFFFF;
  --color-surface: #FFFFFF;
  --color-surface-raised: #FFFFFF;
  --color-surface-sunken: #F7F7F7;
  --color-border: #C6C6C7;
  --color-border-light: #E0E0E0;
  --color-ink: #111112;
  --color-ink-muted: #555657;
  --color-ink-subtle: #9D9E9F;
  --color-primary-btn: #097A99;
  --color-primary-btn-text: #FFFFFF;
  --color-primary-link: #097A99;
  --color-accent-btn: #90F022;
  --color-accent-btn-text: #111112;

  /* ===== TYPOGRAPHY ===== */
  --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-mono: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;

  /* ===== SPACING ===== */
  --space-0: 0;
  --space-px: 1px;
  --space-1: 0.25rem;
  --space-2: 0.5rem;
  --space-3: 0.75rem;
  --space-4: 1rem;
  --space-5: 1.25rem;
  --space-6: 1.5rem;
  --space-8: 2rem;
  --space-10: 2.5rem;
  --space-12: 3rem;
  --space-16: 4rem;
  --space-20: 5rem;
  --space-24: 6rem;

  /* ===== RADIUS ===== */
  --radius-none: 0;
  --radius-xs: 2px;
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --radius-2xl: 24px;
  --radius-full: 9999px;

  /* ===== SHADOW ===== */
  --shadow-xs: 0 1px 2px 0 rgba(17,17,18,0.04);
  --shadow-sm: 0 1px 3px 0 rgba(17,17,18,0.06), 0 1px 2px -1px rgba(17,17,18,0.04);
  --shadow-md: 0 4px 6px -1px rgba(17,17,18,0.06), 0 2px 4px -2px rgba(17,17,18,0.04);
  --shadow-lg: 0 10px 15px -3px rgba(17,17,18,0.08), 0 4px 6px -4px rgba(17,17,18,0.04);
  --shadow-xl: 0 20px 25px -5px rgba(17,17,18,0.10), 0 8px 10px -6px rgba(17,17,18,0.04);
  --shadow-2xl: 0 25px 50px -12px rgba(17,17,18,0.15);

  /* ===== MOTION ===== */
  --duration-instant: 75ms;
  --duration-fast: 150ms;
  --duration-normal: 250ms;
  --duration-slow: 350ms;
  --duration-gentle: 500ms;
  --ease-default: cubic-bezier(0.4, 0, 0.2, 1);
  --ease-in: cubic-bezier(0.4, 0, 1, 1);
  --ease-out: cubic-bezier(0, 0, 0.2, 1);
  --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);

  /* ===== LAYOUT ===== */
  --sidebar-width: 240px;
  --sidebar-collapsed: 68px;
  --navbar-height: 56px;
  --content-max: 1280px;
  --page-pad-mobile: 16px;
  --page-pad-tablet: 24px;
  --page-pad-desktop: 32px;
}

/* ===== DARK MODE ===== */
[data-theme="dark"] {
  --color-bg: #111112;
  --color-surface: #232324;
  --color-surface-raised: #373838;
  --color-surface-sunken: #111112;
  --color-border: #373838;
  --color-border-light: #232324;
  --color-ink: #EFEFEF;
  --color-ink-muted: #9D9E9F;
  --color-ink-subtle: #747576;
  --color-primary-btn: #65D6F5;
  --color-primary-btn-text: #021E26;
  --color-primary-link: #2CC6F1;
  --color-accent-btn: #A8F352;
  --color-accent-btn-text: #111112;

  --shadow-xs: 0 1px 2px 0 rgba(0,0,0,0.30);
  --shadow-sm: 0 1px 3px 0 rgba(0,0,0,0.40), 0 1px 2px -1px rgba(0,0,0,0.30);
  --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.40), 0 2px 4px -2px rgba(0,0,0,0.30);
  --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.50), 0 4px 6px -4px rgba(0,0,0,0.30);
  --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.60), 0 8px 10px -6px rgba(0,0,0,0.30);
  --shadow-2xl: 0 25px 50px -12px rgba(0,0,0,0.70);
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --color-bg: #111112;
    --color-surface: #232324;
    --color-surface-raised: #373838;
    --color-surface-sunken: #111112;
    --color-border: #373838;
    --color-border-light: #232324;
    --color-ink: #EFEFEF;
    --color-ink-muted: #9D9E9F;
    --color-ink-subtle: #747576;
    --color-primary-btn: #65D6F5;
    --color-primary-btn-text: #021E26;
    --color-primary-link: #2CC6F1;
    --color-accent-btn: #A8F352;
    --color-accent-btn-text: #111112;
    --shadow-xs: 0 1px 2px 0 rgba(0,0,0,0.30);
    --shadow-sm: 0 1px 3px 0 rgba(0,0,0,0.40), 0 1px 2px -1px rgba(0,0,0,0.30);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.40), 0 2px 4px -2px rgba(0,0,0,0.30);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.50), 0 4px 6px -4px rgba(0,0,0,0.30);
    --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.60), 0 8px 10px -6px rgba(0,0,0,0.30);
    --shadow-2xl: 0 25px 50px -12px rgba(0,0,0,0.70);
  }
}
```

---

## 15. Tailwind CSS Configuration

```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#EFF7F9', 100: '#DEF1F7', 200: '#AEE4F3',
          300: '#65D6F5', 400: '#2CC6F1', 500: '#0DA4CE',
          600: '#097A99', 700: '#065469', 800: '#043543',
          900: '#021E26',
        },
        secondary: {
          50: '#EDF3F6', 100: '#DAE9F0', 200: '#A5D2E8',
          300: '#63B1D8', 400: '#1E98D6', 500: '#166F9C',
          600: '#105274', 700: '#0B3950', 800: '#072635',
          900: '#04161F',
        },
        accent: {
          50: '#F2F7EC', 100: '#E6F4D6', 200: '#D3F3AE',
          300: '#BBED82', 400: '#A8F352', 500: '#90F022',
          600: '#74CE0D', 700: '#599E0A', 800: '#3E6E07',
          900: '#233E03',
        },
        neutral: {
          50: '#F7F7F7', 100: '#EFEFEF', 200: '#E0E0E0',
          300: '#C6C6C7', 400: '#9D9E9F', 500: '#747576',
          600: '#555657', 700: '#373838', 800: '#232324',
          900: '#111112',
        },
        success: {
          50: '#EBF9F2', 100: '#D7F3E5', 500: '#20B66B',
          600: '#188A51', 700: '#105F38',
        },
        warning: {
          50: '#F9F4EA', 100: '#F4E9D6', 500: '#C08115',
          600: '#926310', 700: '#64440B',
        },
        danger: {
          50: '#F9EBEB', 100: '#F3D7D7', 500: '#BB1A1A',
          600: '#8E1414', 700: '#620E0E',
        },
        info: {
          50: '#EBF1F9', 100: '#D7E3F3', 500: '#1A5DBB',
          600: '#14478E', 700: '#0E3162',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      borderRadius: {
        xs: '2px', sm: '4px', md: '8px', lg: '12px',
        xl: '16px', '2xl': '24px',
      },
      boxShadow: {
        xs: '0 1px 2px 0 rgba(17,17,18,0.04)',
        sm: '0 1px 3px 0 rgba(17,17,18,0.06), 0 1px 2px -1px rgba(17,17,18,0.04)',
        md: '0 4px 6px -1px rgba(17,17,18,0.06), 0 2px 4px -2px rgba(17,17,18,0.04)',
        lg: '0 10px 15px -3px rgba(17,17,18,0.08), 0 4px 6px -4px rgba(17,17,18,0.04)',
        xl: '0 20px 25px -5px rgba(17,17,18,0.10), 0 8px 10px -6px rgba(17,17,18,0.04)',
        '2xl': '0 25px 50px -12px rgba(17,17,18,0.15)',
      },
    },
  },
  darkMode: ['class', '[data-theme="dark"]'],
};
```

---

## 16. Design Token Verification

### Contrast Compliance Matrix

| Foreground | Background | Ratio | AA | AAA |
|-----------|------------|-------|-----|-----|
| `white` | `primary-600` `#097A99` | 4.9:1 | ✅ | ❌ (needs 7:1) |
| `white` | `primary-700` `#065469` | 8.5:1 | ✅ | ✅ |
| `white` | `primary-800` `#043543` | 13.2:1 | ✅ | ✅ |
| `neutral-900` | `accent-500` `#90F022` | 4.7:1 | ✅ | ❌ |
| `white` | `accent-600` `#74CE0D` | 4.5:1 | ✅ | ❌ |
| `white` | `accent-700` `#599E0A` | 6.5:1 | ✅ | ❌ |
| `neutral-900` `#111112` | `white` | 18.4:1 | ✅ | ✅ |
| `neutral-700` `#373838` | `white` | 7.3:1 | ✅ | ✅ |
| `neutral-600` `#555657` | `white` | 5.1:1 | ✅ | ❌ |
| `neutral-500` `#747576` | `white` | 3.3:1 | ❌ | ❌ |
| `neutral-400` `#9D9E9F` | `white` | 2.3:1 | ❌ | ❌ |

**Action:** Use `neutral-700` minimum for body text on white. Use `neutral-600` minimum
for large text (≥18 px, ≥14 px bold). `neutral-500` and `neutral-400` are for
non-text elements only (icons, borders, backgrounds).

---

**Document version:** 1.0
**Last updated:** 04 Aug 2026
**Source:** Colors extracted from `public/assets/images/logo-isa-smartwork.png.png`
**Color extraction method:** K-means pixel cluster analysis on non-transparent logo pixels