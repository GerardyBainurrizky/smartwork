# 07 — Branding Guidelines

> Brand identity system for ISA SmartWork. Every visual decision flows from this
> document. Deviations are design debt.

**Logo file:** `public/assets/images/logo-isa-smartwork.png.png`
> ⚠ **Note:** The file has a double `.png.png` extension. Rename to
> `logo-isa-smartwork.png` before production use.
> 
> **Colors validated:** The palette below is derived from algorithmic pixel-cluster
> analysis of the actual logo. Primary: cyan-blue `#0DA4CE` (H=193°). Accent: lime
> green `#90F022` (H=88°). See `08-design-system.md` for the full token system.

---

## 1. Logo

### Logo usage rules

| Context | Logo variant | Size | Alignment |
|---------|-------------|------|-----------|
| **Login page** | Full logo (mark + wordmark) | Max 180 px wide, centered above form | Center |
| **Navbar** | Full logo (mark + wordmark) | 32 px height, left-aligned | Left |
| **Sidebar (expanded)** | Full logo (mark + wordmark) | 28 px height, center with 24 px padding | Center |
| **Sidebar (collapsed)** | Mark only (icon) | 24 × 24 px, centered | Center |
| **Dashboard** | Mark only, 16×16 px | Inline with page title | Left |
| **Favicon** | Mark only | 32×32 px, square, no wordmark | N/A |
| **PWA icon** | Mark only on brand background | 192×192 px + 512×512 px, 48 px safe zone | N/A |
| **Reports / PDF** | Full logo | 120 px wide, header left | Top-left |
| **Email signature** | Full logo | 120 px wide | Footer |

### Clear space

Minimum clear space around the logo = 1× the mark height on all sides. Never place
text, icons, or UI elements within this zone.

### Logo misuses

- Do not stretch, rotate, or skew.
- Do not recolor the mark (except white monochrome on dark bg).
- Do not use the wordmark without the mark.
- Do not place on busy backgrounds; use a white/surface container.

---

## 2. Colour system

### 2.1 Core palette — extracted from logo

> Colors extracted via algorithmic pixel-cluster analysis of the actual logo file.
> Primary hue family: cyan-blue (H=193°). Accent: lime green (H=88°). Neutral base
> derived from logo's dark pixels.

| Token | Hex | Purpose |
|-------|-----|---------|
| `--color-primary` | `#0DA4CE` | Main brand cyan-blue — active states, links, progress |
| `--color-primary-hover` | `#097A99` | Hover/press states, interactive elements |
| `--color-primary-light` | `#DEF1F7` | Light backgrounds, selected rows, badges fill |
| `--color-primary-ultralight` | `#EFF7F9` | Subtle highlight backgrounds |
| `--color-primary-contrast` | `#FFFFFF` | Text on primary bg (use with `primary-600` or darker) |

### 2.2 Accent (lime green — from logo)

| Token | Hex | Purpose |
|-------|-----|---------|
| `--color-accent` | `#90F022` | Primary CTA, feature highlight, brand accent |
| `--color-accent-hover` | `#74CE0D` | Hover/press |
| `--color-accent-light` | `#F2F7EC` | Accent backgrounds, success fills |

### 2.3 Neutral scale (from logo dark)

| Token | Hex | Purpose |
|-------|-----|---------|
| `--color-ink` | `#111112` | Primary text (headings, body) |
| `--color-ink-muted` | `#555657` | Secondary text, labels, captions |
| `--color-ink-subtle` | `#9D9E9F` | Placeholder, disabled, tertiary |
| `--color-border` | `#C6C6C7` | Default borders |
| `--color-border-light` | `#E0E0E0` | Subtle dividers, table stripes |
| `--color-surface` | `#FFFFFF` | Card, modal, sheet backgrounds |
| `--color-surface-raised` | `#FFFFFF` | Elevated surfaces (shadow) |
| `--color-surface-sunken` | `#F7F7F7` | Page background, input fills |
| `--color-surface-hover` | `#EFEFEF` | Table row hover, button hover |

### 2.4 Semantic

| Token | Hex | Purpose |
|-------|-----|---------|
| `--color-success` | `#20B66B` | Done, approved, online |
| `--color-success-light` | `#EBF9F2` | Success backgrounds |
| `--color-warning` | `#C08115` | Pending, warning, offline |
| `--color-warning-light` | `#F9F4EA` | Warning backgrounds |
| `--color-danger` | `#BB1A1A` | Error, reject, delete |
| `--color-danger-light` | `#F9EBEB` | Error backgrounds |
| `--color-info` | `#1A5DBB` | Information, help |
| `--color-info-light` | `#EBF1F9` | Info backgrounds |

---

## 3. Typography

### 3.1 Font stack

```css
/* UI font — primary */
--font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;

/* Data / numbers — tabular figures */
--font-mono: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
```

### 3.2 Type scale

| Token | Size | Line-height | Weight | Usage |
|-------|------|-------------|--------|-------|
| `text-xs` | 12 px | 16 px | 400 | Badges, tiny labels, captions |
| `text-sm` | 14 px | 20 px | 400 | Body copy, table cells, form labels |
| `text-base` | 16 px | 24 px | 400 | Primary body, input values |
| `text-lg` | 18 px | 28 px | 500 | Card titles, section headers |
| `text-xl` | 20 px | 28 px | 600 | Page titles (mobile) |
| `text-2xl` | 24 px | 32 px | 600 | Page titles (desktop), modal titles |
| `text-3xl` | 30 px | 36 px | 700 | Hero numbers (dashboard KPI) |
| `text-4xl` | 36 px | 40 px | 700 | Login page title |

**Weight scale:** 400 (regular), 500 (medium), 600 (semibold), 700 (bold).

### 3.3 Typographic rules

- **Headings:** Inter, weight 600, tracking -0.02em.
- **Body:** Inter, weight 400, tracking 0.
- **Numbers/data:** JetBrains Mono, tabular-nums, tracking 0.
- **Line height:** 1.5 for body; 1.3 for headings; 1.2 for hero numbers.
- **Max paragraph width:** 72 characters (approximately 640 px at 16 px).
- **Never** use all-caps; use `text-sm` + `font-medium` instead for section labels.

---

## 4. Spacing scale

Based on a 4 px grid. Use these tokens only — no arbitrary values.

| Token | Value | Usage |
|-------|-------|-------|
| `space-0` | 0 | No gap |
| `space-1` | 4 px | Icon-text gap, badge padding |
| `space-2` | 8 px | Compact card padding, inline gaps |
| `space-3` | 12 px | Form group gap, list item padding |
| `space-4` | 16 px | Default card padding, section gap |
| `space-6` | 24 px | Section spacing, modal padding |
| `space-8` | 32 px | Page section divider |
| `space-12` | 48 px | Hero section padding |
| `space-16` | 64 px | Large layout gaps |
| `space-24` | 96 px | Reserved for major layout |

### Component spacing

- **Button (md):** 10 px vertical, 16 px horizontal padding.
- **Button (lg):** 12 px vertical, 24 px horizontal.
- **Input:** 10 px vertical, 12 px horizontal.
- **Card:** 16 px padding (mobile), 24 px (desktop).
- **Table cell:** 12 px vertical, 16 px horizontal.

---

## 5. Border radius

| Token | Value | Usage |
|-------|-------|-------|
| `radius-none` | 0 | Tables, dividers, tooltips |
| `radius-sm` | 4 px | Inputs, badges, small buttons |
| `radius-md` | 8 px | Cards, modals, buttons (default) |
| `radius-lg` | 12 px | Large cards, sheets, dialogs |
| `radius-xl` | 16 px | Hero sections, featured cards |
| `radius-full` | 9999 px | Pills, avatars, status dots |

---

## 6. Elevation (shadows)

| Token | Value | Usage |
|-------|-------|-------|
| `shadow-none` | none | Default |
| `shadow-xs` | `0 1px 2px rgba(0,0,0,0.04)` | Subtle card lift, table headers |
| `shadow-sm` | `0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)` | Cards, dropdowns |
| `shadow-md` | `0 4px 6px -1px rgba(0,0,0,0.06), 0 2px 4px -2px rgba(0,0,0,0.04)` | Modals, sheets |
| `shadow-lg` | `0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04)` | Elevated dialogs |
| `shadow-xl` | `0 20px 25px -5px rgba(0,0,0,0.10), 0 8px 10px -6px rgba(0,0,0,0.04)` | Top-level modals |

---

## 7. Motion

### 7.1 Duration tokens

| Token | Value | Usage |
|-------|-------|-------|
| `duration-instant` | 75ms | Micro-interactions (hover, focus) |
| `duration-fast` | 150ms | Button press, toggle, tabs |
| `duration-normal` | 250ms | Modal open/close, page transition |
| `duration-slow` | 350ms | Sheet open, expand, drawer |
| `duration-gentle` | 500ms | Chart animate-in, hero data reveal |

### 7.2 Easing

| Token | Value | Usage |
|-------|-------|-------|
| `ease-default` | `cubic-bezier(0.4, 0, 0.2, 1)` | Standard (Material standard) |
| `ease-in` | `cubic-bezier(0.4, 0, 1, 1)` | Entering screen |
| `ease-out` | `cubic-bezier(0, 0, 0.2, 1)` | Exiting screen |
| `ease-spring` | `cubic-bezier(0.34, 1.56, 0.64, 1)` | Bounce on check-in, success |

### 7.3 Motion rules

- **No motion for users who prefer reduced motion** (`prefers-reduced-motion: reduce`).
- Hover: 75ms color shift only (no scale/transform on interactive elements).
- Page transitions: subtle fade + slide-y (8 px) on desktop; slide-x on mobile.
- Data reveal: numbers animate on dashboard load (count-up, 350ms per card).
- Loading: skeleton pulse, not spinner.

---

## 8. Iconography

- **Library:** Lucide Icons (preferred) or Phosphor Icons. Single family across app.
- **Sizes:** 16 px (inline), 20 px (buttons, badges), 24 px (standalone, nav).
- **Stroke:** 1.75 px (adjusted to 2 px via `stroke-width` if needed).
- **Colour:** inherit from text context; use `--color-ink-muted` for secondary icons.
- **No filled icons** except for active nav states (where filled version indicates
  current section).

---

## 9. Component primitives

### 9.1 Buttons

| Variant | Background | Text | Border | Usage |
|---------|------------|------|--------|-------|
| **Primary** | `--color-primary` | `--color-primary-contrast` | none | Main CTA, submit |
| **Secondary** | transparent | `--color-primary` | `--color-primary` | Alternative action |
| **Ghost** | transparent | `--color-ink` | none | Low-priority, table actions |
| **Danger** | `--color-danger` | white | none | Delete, reject |
| **Danger-ghost** | transparent | `--color-danger` | none | Destructive secondary |

**Sizes:**
| Size | Height | Padding H | Font |
|------|--------|-----------|------|
| `sm` | 32 px | 12 px | `text-sm` |
| `md` | 40 px | 16 px | `text-sm` |
| `lg` | 48 px | 24 px | `text-base` |

### 9.2 Inputs

- Height: 40 px (default), with `radius-sm` (4 px).
- Border: 1.5 px `--color-border`, focus: 1.5 px `--color-primary`.
- Error: 1.5 px `--color-danger`, with inline error text below.
- Label: `text-sm`, `font-medium`, `--color-ink`, positioned above input.
- Helper: `text-xs`, `--color-ink-muted`, below input.
- Required: red asterisk after label.

### 9.3 Status pills

Labels that communicate object state at a glance. Always include text + icon.

| Status | Background | Text | Icon |
|--------|------------|------|------|
| **Active / Online / Done** | `--color-success-light` | `--color-success` | `check-circle` |
| **Pending / In Review** | `--color-warning-light` | `--color-warning` | `clock` |
| **Rejected / Error / Offline** | `--color-danger-light` | `--color-danger` | `x-circle` |
| **Draft / Inactive** | `--color-surface-sunken` | `--color-ink-muted` | `minus-circle` |

### 9.4 Cards

- Background: `--color-surface`, border: `--color-border-light`, radius: `radius-md`.
- Padding: 16 px (mobile), 24 px (desktop).
- Hover: subtle shadow lift (`shadow-sm`).
- Card actions: top-right corner, ghost buttons.

### 9.5 Tables

- Header: `text-xs`, `font-medium`, `--color-ink-muted`, uppercase=false, background `--color-surface-sunken`.
- Cell: `text-sm`, vertical padding 12 px, horizontal 16 px.
- Zebra: alternating rows with `--color-surface-hover` at 50% opacity.
- Row hover: `--color-surface-hover`.
- Sortable columns: header has arrow icon, clickable.
- Overflow: horizontal scroll on mobile, sticky first column (name/ID).

---

## 10. Favicon

| Context | File | Size | Notes |
|---------|------|------|-------|
| `favicon.ico` | `public/favicon.ico` | 16×16, 32×32, 48×48 (multi-res) | Fallback for old browsers |
| `favicon.svg` | `public/favicon.svg` | Scalable | Preferred, modern browsers |
| `apple-touch-icon` | `public/apple-touch-icon.png` | 180×180 px | iOS home screen |

> **Recommendation:** Extract the square mark from the logo (icon portion only, no
> wordmark). Place on `--color-primary` (#0DA4CE) background with 25% padding.
> Export as SVG for favicon, PNG for apple-touch-icon.

---

## 11. PWA manifest

```json
{
  "name": "ISA SmartWork",
  "short_name": "SmartWork",
  "description": "Enterprise Workforce & Field Operations Platform — PT ISA Tri Selaras Gemilang",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#0DA4CE",
  "theme_color": "#0DA4CE",
  "orientation": "portrait-primary",
  "icons": [
    { "src": "/pwa-192x192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/pwa-512x512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/pwa-512x512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ]
}
```

**PWA icon guidelines:**
- 192×192 px: mark centered on `--color-primary` background, 48 px safe zone.
- 512×512 px: same, with 120 px safe zone.
- Maskable: mark centered with 40% safe zone per Android adaptive icon spec.
- No rounded corners in the source; the OS applies the mask.

---

## 12. Login page layout

```
┌──────────────────────────────────────┐
│                                      │
│            [LOGO MARK]               │
│            ISA SmartWork             │
│                                      │
│    ┌──────────────────────────┐      │
│    │  Email / Username        │      │
│    │  ─────────────────────   │      │
│    │                          │      │
│    │  Password                │      │
│    │  ──────────────────────  │      │
│    │                          │      │
│    │  [     Sign In      ]    │      │
│    │                          │      │
│    │  Forgot password?        │      │
│    └──────────────────────────┘      │
│                                      │
│   PT ISA Tri Selaras Gemilang        │
│   Agricultural — Internal System     │
│                                      │
└──────────────────────────────────────┘
```

- Background: `--color-surface-sunken` (#F7F7F7) with subtle geometric pattern.
- Logo: full logo, 180 px max width, centered.
- Card: white, 400 px max width, centered, `shadow-md`, `radius-lg`.
- Form: single column, input height 48 px (larger on login for comfort).
- Footer: company name, `text-xs`, `--color-ink-subtle`, centered below card.

---

## 13. Reports header

Exported PDFs and Excel reports must carry the brand consistently:

```
┌──────────────────────────────────────────────┐
│  [LOGO]  ISA SmartWork                       │
│          PT ISA Tri Selaras Gemilang         │
│                                              │
│  Report: Employee Attendance                 │
│  Period: 2026 · Week 30                      │
│  Generated: 04 Aug 2026, 14:30 WIB           │
│  Generated by: HR Officer                    │
├──────────────────────────────────────────────┤
│  (report content)                            │
└──────────────────────────────────────────────┘
```

- Logo: 120 px wide, top-left.
- Title: 16 px, weight 600.
- Meta: 10 px, grey.
- Divider: 1 px `--color-border`.
- Footer: page number, "Confidential — PT ISA Tri Selaras Gemilang", 10 px.

---

## 14. Branding checklist

Before shipping, verify:

- [ ] Logo file renamed from `.png.png` to `.png`.
- [ ] Primary colour validated against the logo's actual colors ✅ — extracted: `#0DA4CE` (cyan-blue), `#90F022` (lime green)
- [ ] Login page uses the full logo, centered, 180 px.
- [ ] Navbar uses full logo, 32 px height, left-aligned.
- [ ] Sidebar uses full logo (expanded) / mark only (collapsed).
- [ ] Favicon: square mark extracted, SVG + ICO + PNG.
- [ ] PWA: manifest with 192 px + 512 px icons, theme color `#0DA4CE`.
- [ ] Reports: branded header with logo + meta.
- [ ] All CSS uses design tokens; no hardcoded hex values outside `:root`.
- [ ] Inter and JetBrains Mono fonts loaded (self-hosted, no Google Fonts CDN for
      enterprise security).