---
name: reui-ui-components
description: >-
  Guides implementation of ReUI UI Components (Metronic/Keenthemes copy-paste React
  library): Alert, Calendar, Card, Chart, Data Grid, Drawer, File Upload, Kanban, Kbd,
  Pagination, Resizable, Rating, Stepper, Scrollspy, Skeleton, Sortable, Sonner, Table,
  Textarea. Use when the user mentions ReUI, @reui/, shadcn ReUI registry, Metronic UI,
  Keenthemes, or building card, table, data grid, kanban, toast, stepper, file upload,
  pagination, rating, chart, drawer, skeleton, or textarea UI in React + Tailwind.
---

# ReUI UI Components

Prop-based, copy-and-paste React components from [ReUI v1](https://v1.reui.io/docs/) (Keenthemes/Metronic ecosystem). Styled with Tailwind CSS; installed via the shadcn registry — not npm package imports.

**Scope:** UI Components accordion only (19 components). For Radix, Base UI, or Animated components, use separate skills or the live docs.

## When to apply

- User asks to add or style ReUI / Metronic / Keenthemes UI components
- Project uses `@reui/*` shadcn registry entries or ReUI file structure
- Building dashboards: cards, tables, data grids, kanban, charts, toasts, steppers, uploads

## Workflow

1. **Pick the component** from the catalog below or [reference.md](reference.md).
2. **Install** via shadcn CLI (adjust package manager if the project uses npm/yarn/bun):

```bash
pnpm dlx shadcn@latest add @reui/<component>
```

3. **Add a variant** when the user wants a specific demo from the docs:

```bash
pnpm dlx shadcn@latest add @reui/<component>-<variant>
```

4. **Read live docs** for previews, full prop tables, and credits: `https://v1.reui.io/docs/<component>`
5. **Match project conventions** — ReUI files land in `components/ui/`; extend via props (`variant`, `appearance`, `size`) rather than heavy inline Tailwind.

## Component catalog (19)

| Component | Use for | Doc |
| --- | --- | --- |
| Alert | Display contextual feedback messages with multiple variants including success, w… | [alert](https://v1.reui.io/docs/alert) |
| Calendar | Interactive date picker with month navigation, date selection, and customizable … | [calendar](https://v1.reui.io/docs/calendar) |
| Card | Flexible container component for displaying content with headers, footers, and c… | [card](https://v1.reui.io/docs/card) |
| Chart | Data visualization component built on Recharts with support for line, bar, pie, … | [chart](https://v1.reui.io/docs/chart) |
| Data Grid | Advanced managed table component build with TanStack Table with sorting, filteri… | [data-grid](https://v1.reui.io/docs/data-grid) |
| Drawer | Slide-out panel component that appears from the side with overlay and customizab… | [drawer](https://v1.reui.io/docs/drawer) |
| File Upload | Drag-and-drop file upload component with progress indicators, file validation, a… | [file-upload](https://v1.reui.io/docs/file-upload) |
| Kanban | Project management board with draggable cards, customizable columns, and real-ti… | [kanban](https://v1.reui.io/docs/kanban) |
| Kbd | Keyboard key indicator component for displaying keyboard shortcuts and key combi… | [kbd](https://v1.reui.io/docs/kbd) |
| Pagination | Navigate through large datasets with page numbers, previous/next buttons, and cu… | [pagination](https://v1.reui.io/docs/pagination) |
| Resizable | Create resizable panels and layouts with drag handles and customizable resize co… | [resizable](https://v1.reui.io/docs/resizable) |
| Rating | Interactive star rating component for collecting user feedback with half-star su… | [rating](https://v1.reui.io/docs/rating) |
| Stepper | Multi-step process component with progress indicators, step validation, and cust… | [stepper](https://v1.reui.io/docs/stepper) |
| Scrollspy | Navigation component that highlights menu items based on scroll position for lon… | [scrollspy](https://v1.reui.io/docs/scrollspy) |
| Skeleton | Loading placeholder component that mimics content structure while data is being … | [skeleton](https://v1.reui.io/docs/skeleton) |
| Sortable | Drag-and-drop list component for reordering items with visual feedback and acces… | [sortable](https://v1.reui.io/docs/sortable) |
| Sonner | Toast notification system with multiple positions, types, and customizable anim… | [sonner](https://v1.reui.io/docs/sonner) |
| Table | Structured data display component with sorting, filtering, and responsive design… | [table](https://v1.reui.io/docs/table) |
| Textarea | Multi-line text input component with auto-resize, character counting, and validati… | [textarea](https://v1.reui.io/docs/textarea) |

## ReUI conventions

- **Copy-and-paste:** Components are owned source files, not opaque npm UI kits.
- **Prop-based API:** Prefer component props over long `className` strings.
- **Registry IDs:** `@reui/<slug>` base; examples use `@reui/<slug>-<variant>`.
- **Stack:** React, Tailwind CSS, TypeScript; many components wrap TanStack Table, Recharts, Sonner, or dnd-kit (see per-component credits in reference).
- **Theming:** Global CSS variables — see [ReUI theming](https://v1.reui.io/docs/theming) and project `globals.css`.

## Related docs (outside this skill)

- [Installation](https://v1.reui.io/docs/installation) · [Registry](https://v1.reui.io/docs/registry) · [Theming](https://v1.reui.io/docs/theming)
- [Radix UI Components](https://v1.reui.io/docs/accordion) — Button, Input, Dialog, etc.
- [Base UI Components](https://v1.reui.io/docs/base-accordion) — unstyled primitives
- [Animated Components](https://v1.reui.io/docs/marquee) — Marquee, typing text, etc.
- Full catalog with props and variants: [reference.md](reference.md)
