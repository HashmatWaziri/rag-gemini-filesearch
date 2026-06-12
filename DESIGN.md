# GLC Design System

GLC’s authenticated platform UI is built on **[Metronic React Demo 7](https://keenthemes.com/metronic/tailwind/react/demo7/)** — a header-first dashboard shell with horizontal mega-menu navigation, sticky header, and ReUI semantic tokens. Business features (placement, tutor, curriculum, admin) are unchanged; only presentation follows Metronic patterns.

**Stack:** Inertia React 19 · Tailwind CSS v4 (CSS-first) · shadcn/ui (New York) · semantic design tokens

---

## 1. Visual Theme & Atmosphere

The interface is **structured, professional, and product-focused** — similar to Metronic’s demo dashboards: clean zinc surfaces, blue primary actions, and emphasis typography via `text-mono` rather than heavy color blocks.

**Key characteristics:**
- **Blue primary** (`--primary`) for CTAs, active nav, and brand accents — Metronic/ReUI default
- **Zinc neutrals** for text hierarchy (`text-mono`, `text-secondary-foreground`, `text-muted-foreground`)
- **Inter** as the sole UI typeface (loaded via Bunny Fonts in `app.blade.php`)
- **Sticky header** that shrinks on scroll with backdrop blur
- **Horizontal mega-menu** on desktop; **sheet drawer** on mobile
- **Flat cards** with `border-border`; elevation only on hover/focus where needed
- **Dark mode** via `.dark` on `<html>` (existing appearance system)

> **Legacy note:** The older wellness/billing area (`AppLayout` sidebar) still uses the previous shadcn sidebar stack. All **GLC platform pages** under `resources/js/pages/glc/` use Metronic Demo 7 styling.

---

## 2. Architecture

### Layout shells

| Shell | File(s) | Used by |
|-------|---------|---------|
| **GLC authenticated** | `resources/js/layouts/glc-layout.tsx` + `layouts/glc/*` | Staff, student, admin Inertia pages |
| **Auth (Metronic branded)** | `resources/js/layouts/auth/auth-simple-layout.tsx` | Login, password reset, 2FA |
| **Placement candidate** | `resources/js/pages/glc/placement/components/candidate-shell.tsx` | Public placement flow (minimal chrome) |
| **Legacy wellness** | `resources/js/layouts/app-layout.tsx` | Health, billing, chat (not yet migrated) |

### GLC layout components

```
layouts/glc/
├── glc-header.tsx          # Sticky header + scroll shrink
├── glc-header-logo.tsx     # Logo + desktop nav / mobile sheet trigger
├── glc-mega-menu.tsx       # Desktop NavigationMenu mega-dropdowns
├── glc-mobile-nav.tsx      # Mobile collapsible sections
├── glc-header-topbar.tsx   # User avatar + logout dropdown
├── glc-toolbar.tsx         # Page title row (GlcLayout `title` prop)
├── glc-footer.tsx
└── nav-config.ts           # Role-based nav sections (pinned hrefs)
```

Navigation hrefs are **pinned** in `nav-config.ts` and must match backend routes (see `docs/glc-implementation-contract.md`).

### CSS entry points

| File | Purpose |
|------|---------|
| `resources/css/app.css` | Tailwind v4, shadcn theme variables, dark mode |
| `resources/css/metronic.css` | Demo 7 tokens: `--mono`, header heights, `text-2sm`, spacing `7.5` / `8.5` |

### Shared UI

| Layer | Location |
|-------|----------|
| shadcn primitives | `resources/js/components/ui/` |
| Metronic container | `resources/js/components/common/container.tsx` (`max-w-[1320px]`) |
| GLC admin page kit | `resources/js/pages/glc/admin/components.tsx` |
| Staff UI helpers | `resources/js/pages/glc/staff/ui.tsx` |
| Curriculum UI helpers | `resources/js/pages/glc/curriculum/components/ui.tsx` |

### Hooks

| Hook | File | Purpose |
|------|------|---------|
| `useBodyClass` | `resources/js/hooks/use-body-class.ts` | Header height CSS vars on `<body>` |
| `useScrollPosition` | `resources/js/hooks/use-scroll-position.ts` | Sticky header threshold |

---

## 3. Color Palette & Semantic Tokens

Use **CSS variables and Tailwind semantic classes** — not hardcoded hex or `emerald-*` / `slate-*` in GLC pages.

### Primary & surfaces (from `app.css`)

| Token / class | Role |
|---------------|------|
| `--primary` / `bg-primary`, `text-primary` | Primary buttons, active nav, logo circle |
| `--primary-foreground` | Text on primary backgrounds |
| `--background` / `bg-background` | Page background |
| `--foreground` / `text-foreground` | Default body text |
| `--card` / `bg-card` | Card surfaces |
| `--muted` / `bg-muted` | Subtle fills, table headers |
| `--accent` / `bg-accent` | Hover rows, nav item highlights |
| `--border` / `border-border` | Default borders, dividers |
| `--input` / `border-input` | Form field borders |
| `--ring` / `ring-ring` | Focus rings |
| `--destructive` | Errors, destructive actions |

### Metronic emphasis (from `metronic.css`)

| Token / class | Role |
|---------------|------|
| `--mono` / `text-mono` | Headings, active nav labels, strong emphasis |
| `--secondary-foreground` / `text-secondary-foreground` | Secondary copy, footer, nav defaults |
| `--muted-foreground` / `text-muted-foreground` | Captions, placeholders, hints |

### Status colors (keep as-is)

| Usage | Classes |
|-------|---------|
| Success / positive | `text-primary`, `bg-primary/10`, `border-primary/20` |
| Warning | Amber (`text-amber-*`, `bg-amber-*`) — placement integrity, guardian notices |
| Error | `text-destructive`, `bg-destructive/10` |

### Migration mapping (deprecated → use instead)

| Avoid in GLC pages | Use instead |
|--------------------|-------------|
| `bg-emerald-*`, `text-emerald-*` | `bg-primary`, `text-primary`, `bg-primary/10` |
| `text-slate-900`, `text-slate-700` | `text-mono`, `text-foreground` |
| `text-slate-600`, `text-slate-500` | `text-secondary-foreground`, `text-muted-foreground` |
| `border-slate-*`, `divide-slate-*` | `border-border`, `divide-border` |
| `bg-slate-50`, `bg-white` | `bg-muted/50`, `bg-background`, `bg-card` |

---

## 4. Typography

### Font families

```css
--font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif, ...;
--font-mono: 'JetBrains Mono', ui-monospace, ...;
```

Loaded in `resources/views/app.blade.php` via Bunny Fonts.

### Hierarchy (GLC shell)

| Role | Typical classes | Usage |
|------|-----------------|-------|
| **Page title** | `text-lg font-semibold text-mono` | `GlcToolbar` / `GlcLayout title` prop |
| **Section heading** | `text-base font-semibold text-mono` | Card headers, panel titles |
| **Body** | `text-sm` or `text-base` | Forms, tables, content |
| **Caption** | `text-xs text-muted-foreground` | Hints, timestamps, metadata |
| **Nav link** | `text-sm font-medium text-secondary-foreground` | Mega-menu triggers |
| **Nav active** | `text-mono font-semibold border-mono` | Active route |

Metronic extras: `text-2sm` (0.8125rem), `text-2xs` (0.6875rem) — defined in `metronic.css`.

### Principles

- Use **weight and `text-mono`** for hierarchy, not extra font families
- Body inputs stay **≥ 16px** on mobile for accessibility
- Do not use `text-muted-foreground` for primary readable content

---

## 5. Components

### Buttons

Prefer shadcn `Button` or shared classes from `admin/components.tsx`:

| Variant | Implementation |
|---------|----------------|
| **Primary** | `Button` default / `buttonPrimaryClass` → `bg-primary text-primary-foreground` |
| **Secondary** | `Button variant="outline"` / `buttonSecondaryClass` |
| **Danger** | `Button variant="destructive"` / `buttonDangerClass` |
| **Ghost** | `Button variant="ghost"` |

Focus: `focus-visible:ring-ring/50` (shadcn default).

### Cards

Use shadcn `Card`, `CardHeader`, `CardContent` for page sections. Default: `border-border`, no heavy shadow.

### Inputs

Use shadcn `Input` / `Label` or `inputClass` from admin components:

```
border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50
```

### Badges

Use shadcn `Badge` or `Badge` from admin/staff UI kits. Map semantic tones (`green`, `amber`, `red`, `slate`, `blue`) to variants — not raw emerald classes.

### Dialogs & modals

Use shadcn `Dialog` (admin `Modal`, `ConfirmDialog` wrap this). Do not hand-roll fixed overlays in new code.

### Tables

- Header: `bg-muted/50`, `text-muted-foreground` or `text-mono font-medium`
- Rows: `border-border`, `hover:bg-accent`
- Links: `text-primary hover:underline`

---

## 6. Layout (Demo 7)

### Container

- **Max width:** `1320px` (`Container` component)
- **Padding:** `px-4 lg:px-6`

### Header

- **Default height:** 95px (`--header-height-default`)
- **Sticky height:** 60px when `data-sticky-header="on"` on `<body>`
- **Sticky trigger:** scroll position > 200px
- **Sticky style:** `bg-background/70 backdrop-blur-md shadow-xs`

### Toolbar

Rendered when `GlcLayout` receives a `title` prop:

```
border-t → title row → border-b
```

### Navigation (role-based)

| Role | Nav pattern |
|------|-------------|
| **student** | Flat link: AI Tutor |
| **teacher** | Mega sections: Placement · Students & Tutor |
| **academic_supervisor** | + Content (Curriculum, Placement Test Content) |
| **admin** | + Administration (Users, Access Codes, Exports, Audit, Settings) |

### Auth layout

Split grid: **form card** (left on desktop) + **brand panel** (gradient `from-primary/10`, GLC copy). Matches Metronic branded auth pattern.

### Placement candidate

Separate minimal shell — no mega-menu. Uses same semantic tokens for consistency.

---

## 7. Spacing & Radius

### Spacing

- Metronic fractional spacing: `gap-7.5`, `mb-7.5`, `p-7.5` (1.875rem)
- Default section gaps: `space-4`–`space-6`
- Toolbar margins: `my-5`, `lg:mb-7.5`

### Border radius

From `--radius` in `app.css` (default **0.625rem**):

| Token | Usage |
|-------|-------|
| `rounded-md` | Buttons, inputs |
| `rounded-lg` | Cards, panels |
| `rounded-full` | Avatars, logo circle |

---

## 8. Motion

- **Header:** `transition-[height,box-shadow]` on sticky state
- **Nav / buttons:** shadcn + `tailwindcss-animate` (150–200ms)
- **Avoid:** bounce/elastic on standard UI
- **Prefer:** opacity, translate, backdrop-blur for sticky header

---

## 9. Dark Mode

Semantic tokens in `app.css` `:root` / `.dark` drive all surfaces. Key pairs:

| Element | Light | Dark |
|---------|-------|------|
| Background | near white | zinc-950 |
| Foreground / mono | zinc-950 | zinc-50 |
| Primary | blue-500 scale | blue-600 scale |
| Border | light gray | zinc-800 |
| Muted | zinc-100 | zinc-900 |

Primary actions stay **`bg-primary`** in both modes.

---

## 10. Do's and Don'ts

### Do

- Use **semantic tokens** (`text-mono`, `border-border`, `bg-primary`) in all GLC pages
- Wrap authenticated GLC pages in **`GlcLayout`** with a `title` when appropriate
- Use **shadcn components** from `@/components/ui/*` for new UI
- Reuse **`admin/components.tsx`** or domain `ui.tsx` helpers for forms/tables
- Keep **nav hrefs** aligned with `nav-config.ts` and backend routes
- Test **mobile sheet nav** and **desktop mega-menu** for new nav items

### Don't

- Don't add **`emerald-*` or `slate-*`** utility classes to GLC pages
- Don't change route paths when styling — presentation only
- Don't bypass **`GlcLayout`** for staff/student/admin pages (except placement candidate)
- Don't hand-roll modals — use shadcn `Dialog`
- Don't use large primary-colored background areas — primary is for actions and accents
- Don't edit shared backend contracts when doing UI work

---

## 11. Adding New GLC Pages

1. Create page under `resources/js/pages/glc/<domain>/`
2. Import `GlcLayout` from `@/layouts/glc-layout`
3. Pass `title` for toolbar heading
4. Use semantic tokens + shadcn `Card` / `Button` / `Input`
5. Add nav entry in `resources/js/layouts/glc/nav-config.ts` if needed (match backend route)
6. Run `npm run build` to verify TypeScript

---

## 12. Reference Implementation

Metronic source reference (read-only): `c:\laragon\www\vite\src\layouts\demo7/`

Official docs: [Metronic React — Layouts](https://docs.keenthemes.com/metronic-react/guides/layouts) · [Demo 7 preview](https://keenthemes.com/metronic/tailwind/react/demo7/)

---

## 13. Quick Reference

| Element | Value / class |
|---------|----------------|
| UI foundation | Metronic React Demo 7 |
| Primary action | `bg-primary text-primary-foreground` |
| Heading emphasis | `text-mono font-semibold` |
| Secondary text | `text-secondary-foreground` |
| Muted text | `text-muted-foreground` |
| Page background | `bg-background` |
| Card | `bg-card border-border` |
| Default border | `border-border` |
| Container max-width | `1320px` |
| Header height (default) | `95px` |
| Header height (sticky) | `60px` |
| Font | Inter |
| Component library | shadcn/ui + GLC domain kits |
| CSS entry | `app.css` + `metronic.css` |
