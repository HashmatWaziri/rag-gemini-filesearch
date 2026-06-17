---
name: metronic-layout-reference
description: Cherry-pick production-grade layout patterns from the purchased Metronic React Demo 7 theme when building or redesigning UI in this project. Contains a catalog of all ~95 demo7 layouts (store checkout steppers, account settings, user tables/cards, profiles, auth screens) with structure notes and extracted Tailwind markup, plus a battle-tested firecrawl browser harness to inspect any live demo page behind the demo login. Use when designing new pages, steppers, wizards, data tables, settings screens, profile pages, empty states, or when the user mentions Metronic, Keenthemes, demo7, or wants UI "like our theme".
---

# Metronic Layout Reference (Demo 7)

This project's authenticated UI is themed on **Metronic React Demo 7**
(see `DESIGN.md` for tokens and shell). When building a new screen, do not invent a
layout — find the closest Metronic demo7 page, study its structure, and adapt it
using this project's semantic tokens.

Live demo base: `https://keenthemes.com/metronic/tailwind/react/demo7`
Demo login (public demo credentials): `demo@kt.com` / `demo123`
Everything except `/auth/*` and `/error/*` sits behind that login, so plain scraping
returns the sign-in page — use the browser harness below.

## Workflow

1. **Pick candidate layouts** from [references/url-index.md](references/url-index.md)
   — the full catalog of every demo7 page, grouped by section.
2. **Read the matching section notes** (structure, cherry-pickable patterns,
   extracted markup):
   - [references/store-client.md](references/store-client.md) — e-commerce: checkout
     pill stepper, price/summary sidebar, facts strip, product grids, order history
   - [references/account.md](references/account.md) — settings screens, anchor-nav
     sidebar, toggle lists, billing/plan tables, member/permission matrices
   - [references/network.md](references/network.md) — user card grids and data-table
     variants (toolbars, filters, row actions)
   - [references/public-profile.md](references/public-profile.md) — profile heros,
     tab navs, timelines, project/campaign card grids, dashboard shell
   - [references/auth.md](references/auth.md) — branded split and classic centered
     auth flows, error and empty-state screens
3. **Only if the notes are not enough**, inspect the live page with the firecrawl
   browser: [references/firecrawl-harness.md](references/firecrawl-harness.md) has
   the exact login flow, REPL gotchas, batching, markup-extraction, and screenshot
   recipes (all battle-tested — do not improvise around them).
4. **Cherry-pick across pages.** The best results combine patterns: e.g. the staff
   review redesign used the checkout stepper + the order-placed facts strip + the
   Price Details sidebar from three different pages.
5. **Adapt, don't transplant.** Re-map Metronic's raw colors to this project's
   semantic tokens before shipping (see below). Keep business logic and routes
   untouched — presentation only.

## Adapting Metronic markup to this project

Per `DESIGN.md` (read it before any GLC UI work):

| Metronic demo markup | Use in GLC pages |
|----------------------|------------------|
| `text-zinc-950`, dark headings | `text-mono` |
| `text-zinc-600` secondary copy | `text-secondary-foreground` |
| `text-zinc-400` captions | `text-muted-foreground` |
| `border-zinc-200` etc. | `border-border` (dashed connectors may keep `border-zinc-300 dark:border-zinc-600`) |
| `bg-zinc-50` strips | `bg-muted/30` or `bg-muted/50` |
| Blue accents | `bg-primary` / `text-primary` / `bg-primary/10` |
| Green success check (`text-green-500`) | keep — matches the theme's done-state |
| Sizing utilities `h-8.5`, `text-2sm`, `text-2xs`, `gap-7.5` | available via `resources/css/metronic.css` |

Component stack for new pages: shadcn primitives in `resources/js/components/ui/`,
`Container` (`max-w-[1320px]`), `GlcLayout` shell, lucide-react icons (Metronic uses
lucide too — icon names in extracted markup transfer 1:1).

## Proven in-repo example

The placement review page is a full Metronic adaptation and the best starting point
for similar work:

- `resources/js/pages/glc/staff/process-steps.tsx` — checkout pill stepper
  (`StepPill`: done/current/upcoming states, green corner check badge, dashed
  connectors, mobile wrap behavior)
- `resources/js/pages/glc/staff/review-show.tsx` — heading + actions row, facts
  strip (`order-placed` pattern), two-column body with sticky 330px summary sidebar
  (`Price Details` pattern), checklist with green check circles

## Exploring beyond demo7

Other demos live at `.../metronic/tailwind/react/demo1` … `demo10` (different shells:
sidebars, dark headers). The same harness works — only the base URL changes. Prefer
demo7 patterns first; the project shell is demo7.
