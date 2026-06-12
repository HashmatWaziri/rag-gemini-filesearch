# ReUI UI Components — Reference Catalog

Source: [ReUI v1 docs](https://v1.reui.io/docs/) — **UI Components** sidebar section only (19 components).
Excludes Radix UI, Base UI, and Animated component sections.

## Quick index

| Component | Install | Doc |
| --- | --- | --- |
| [Alert](#alert) | `@reui/alert` | [docs](https://v1.reui.io/docs/alert) |
| [Calendar](#calendar) | `@reui/calendar` | [docs](https://v1.reui.io/docs/calendar) |
| [Card](#card) | `@reui/card` | [docs](https://v1.reui.io/docs/card) |
| [Chart](#chart) | `@reui/chart` | [docs](https://v1.reui.io/docs/chart) |
| [Data Grid](#data-grid) | `@reui/data-grid` | [docs](https://v1.reui.io/docs/data-grid) |
| [Drawer](#drawer) | `@reui/drawer` | [docs](https://v1.reui.io/docs/drawer) |
| [File Upload](#file-upload) | `@reui/file-upload` | [docs](https://v1.reui.io/docs/file-upload) |
| [Kanban](#kanban) | `@reui/kanban` | [docs](https://v1.reui.io/docs/kanban) |
| [Kbd](#kbd) | `@reui/kbd` | [docs](https://v1.reui.io/docs/kbd) |
| [Pagination](#pagination) | `@reui/pagination` | [docs](https://v1.reui.io/docs/pagination) |
| [Resizable](#resizable) | `@reui/resizable` | [docs](https://v1.reui.io/docs/resizable) |
| [Rating](#rating) | `@reui/rating` | [docs](https://v1.reui.io/docs/rating) |
| [Stepper](#stepper) | `@reui/stepper` | [docs](https://v1.reui.io/docs/stepper) |
| [Scrollspy](#scrollspy) | `@reui/scrollspy` | [docs](https://v1.reui.io/docs/scrollspy) |
| [Skeleton](#skeleton) | `@reui/skeleton` | [docs](https://v1.reui.io/docs/skeleton) |
| [Sortable](#sortable) | `@reui/sortable` | [docs](https://v1.reui.io/docs/sortable) |
| [Sonner](#sonner) | `@reui/sonner` | [docs](https://v1.reui.io/docs/sonner) |
| [Table](#table) | `@reui/table` | [docs](https://v1.reui.io/docs/table) |
| [Textarea](#textarea) | `@reui/textarea` | [docs](https://v1.reui.io/docs/textarea) |

---

## Alert {#alert}

Display contextual feedback messages with multiple variants including success, warning, error, and info states.

- **Docs:** https://v1.reui.io/docs/alert
- **Install:** `pnpm dlx shadcn@latest add @reui/alert`
- **Examples:** actions, extended, light, mono, outline, size, solid
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/alert-<variant>`
- **Built with:** Radix UI Slot

### Key props

- `variant` (enum, default `default`)
- `appearance` (enum, default `solid`)
- `size` (enum, default `md`)
- `close` (boolean, default `true`)
- `asChild` (boolean, default `false`)

---

## Calendar {#calendar}

Interactive date picker with month navigation, date selection, and customizable date ranges for scheduling interfaces.

- **Docs:** https://v1.reui.io/docs/calendar
- **Install:** `pnpm dlx shadcn@latest add @reui/calendar`
- **Examples:** multiple-months
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/calendar-<variant>`
- **Built with:** React DayPicker

### Key props

- `showOutsideDays` (boolean, default `true`)

---

## Card {#card}

Flexible container component for displaying content with headers, footers, and customizable layouts.

- **Docs:** https://v1.reui.io/docs/card
- **Install:** `pnpm dlx shadcn@latest add @reui/card`
- **Examples:** accent
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/card-<variant>`

### Key props

- `variant` (enum, default `"default"`)

---

## Chart {#chart}

Data visualization component built on Recharts with support for line, bar, pie, and area charts.

- **Docs:** https://v1.reui.io/docs/chart
- **Install:** `pnpm dlx shadcn@latest add @reui/chart`
- **Built with:** Rechart, shadcn/ui Chart

### Key props

_See docs for full API._

---

## Data Grid {#data-grid}

Advanced managed table component build with TanStack Table with sorting, filtering, pagination, and row selection for complex data management.

- **Docs:** https://v1.reui.io/docs/data-grid
- **Install:** `pnpm dlx shadcn@latest add @reui/data-grid`
- **Examples:** auto-width, card, cell-border, column-controls, column-icons, columns-visibility, crud, dense, draggable-columns, draggable-rows, expandable-row, light
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/data-grid-<variant>`
- **Built with:** TanStack Table, DndKit

### Key props

- `isLoading` (boolean, default `false`)
- `loadingMode` (enum, default `skeleton`)
- `emptyMessage` (ReactNode \| string, default `"No data available"`)
- `dense` (boolean, default `false`)
- `cellBorder` (boolean, default `false`)
- `rowBorder` (boolean, default `true`)
- `rowRounded` (boolean, default `false`)
- `stripped` (boolean, default `false`)
- `headerBackground` (boolean, default `true`)
- `headerBorder` (boolean, default `true`)
- `headerSticky` (boolean, default `false`)
- `width` ('auto' \| 'fixed', default `'fixed'`)
- `columnsVisibility` (boolean, default `false`)
- `columnsResizable` (boolean, default `false`)
- `columnsPinnable` (boolean, default `false`)

---

## Drawer {#drawer}

Slide-out panel component that appears from the side with overlay and customizable positioning.

- **Docs:** https://v1.reui.io/docs/drawer
- **Install:** `pnpm dlx shadcn@latest add @reui/drawer`
- **Built with:** Vaul

### Key props

_See docs for full API._

---

## File Upload {#file-upload}

Drag-and-drop file upload component with progress indicators, file validation, and multiple file support.

- **Docs:** https://v1.reui.io/docs/file-upload
- **Install:** `pnpm dlx shadcn@latest add @reui/file-upload`
- **Examples:** avatar-upload, card-upload, compact-upload, cover-upload, gallery-upload, image-upload, progress-upload, sortable, table-upload
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/file-upload-<variant>`
- **Built with:** OriginUI File Upload Hook

### Key props

_See docs for full API._

---

## Kanban {#kanban}

Project management board with draggable cards, customizable columns, and real-time updates.

- **Docs:** https://v1.reui.io/docs/kanban
- **Install:** `pnpm dlx shadcn@latest add @reui/kanban`
- **Examples:** overlay
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/kanban-<variant>`
- **Built with:** DndKit

### Key props

- `disabled` (boolean, default `false`)
- `asChild` (boolean, default `false`)
- `cursor` (boolean, default `true`)

---

## Kbd {#kbd}

Keyboard key indicator component for displaying keyboard shortcuts and key combinations.

- **Docs:** https://v1.reui.io/docs/kbd
- **Install:** `pnpm dlx shadcn@latest add @reui/kbd`
- **Examples:** tooltip
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/kbd-<variant>`

### Key props

_See docs for full API._

---

## Pagination {#pagination}

Navigate through large datasets with page numbers, previous/next buttons, and customizable page sizes.

- **Docs:** https://v1.reui.io/docs/pagination
- **Install:** `pnpm dlx shadcn@latest add @reui/pagination`
- **Examples:** card, icon
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/pagination-<variant>`
- **Built with:** Radix UI Slot

### Key props

- `isActive` (boolean, default `false`)

---

## Resizable {#resizable}

Create resizable panels and layouts with drag handles and customizable resize constraints.

- **Docs:** https://v1.reui.io/docs/resizable
- **Install:** `pnpm dlx shadcn@latest add @reui/resizable`
- **Examples:** handle, vertical
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/resizable-<variant>`
- **Built with:** react-resizable-panels

### Key props

_See docs for full API._

---

## Rating {#rating}

Interactive star rating component for collecting user feedback with half-star support and custom icons.

- **Docs:** https://v1.reui.io/docs/rating
- **Install:** `pnpm dlx shadcn@latest add @reui/rating`
- **Examples:** decimal, editable, show-value, size, statistics
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/rating-<variant>`

### Key props

- `rating` (number, default `required`)
- `maxRating` (number, default `5`)
- `size` (enum, default `"md"`)
- `showValue` (boolean, default `false`)
- `editable` (boolean, default `false`)
- `onRatingChange` (function, default `undefined`)
- `starClassName` (string, default `undefined`)
- `className` (string, default `undefined`)

---

## Stepper {#stepper}

Multi-step process component with progress indicators, step validation, and customizable navigation.

- **Docs:** https://v1.reui.io/docs/stepper
- **Install:** `pnpm dlx shadcn@latest add @reui/stepper`
- **Examples:** controlled, indicators, inline-title, inline-title-description, progress, states, title, title-bar, title-description, title-status, vertical, vertical-title
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/stepper-<variant>`

### Key props

_See docs for full API._

---

## Scrollspy {#scrollspy}

Navigation component that highlights menu items based on scroll position for long-form content.

- **Docs:** https://v1.reui.io/docs/scrollspy
- **Install:** `pnpm dlx shadcn@latest add @reui/scrollspy`
- **Examples:** horizontal
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/scrollspy-<variant>`

### Key props

- `offset` (number, default `0`)
- `smooth` (boolean, default `true`)
- `dataAttribute` (string, default `'scrollspy'`)
- `history` (boolean, default `true`)

---

## Skeleton {#skeleton}

Loading placeholder component that mimics content structure while data is being fetched.

- **Docs:** https://v1.reui.io/docs/skeleton
- **Install:** `pnpm dlx shadcn@latest add @reui/skeleton`

### Key props

_See docs for full API._

---

## Sortable {#sortable}

Drag-and-drop list component for reordering items with visual feedback and accessibility support.

- **Docs:** https://v1.reui.io/docs/sortable
- **Install:** `pnpm dlx shadcn@latest add @reui/sortable`
- **Examples:** grid, nested
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/sortable-<variant>`
- **Built with:** DndKit

### Key props

- `strategy` ('horizontal' \| 'vertical' \| 'grid', default `'vertical'`)
- `asChild` (boolean, default `false`)
- `disabled` (boolean, default `false`)
- `cursor` (boolean, default `true`)

---

## Sonner {#sonner}

Toast notification system with multiple positions, types, and customizable animations.

- **Docs:** https://v1.reui.io/docs/sonner
- **Install:** `pnpm dlx shadcn@latest add @reui/sonner` (requires `npm i sonner`)
- **Examples:** variants
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/sonner-<variant>`
- **Built with:** Sonner

### Key props

_See docs for full API._

---

## Table {#table}

A responsive table component with support for headers, footers, and custom styling.

- **Docs:** https://v1.reui.io/docs/table
- **Install:** `pnpm dlx shadcn@latest add @reui/table`
- **Subcomponents:** Table, TableHeader, TableBody, TableFooter, TableRow, TableHead, TableCell, TableCaption

### Key props

- `className` (string, default `undefined`)

---

## Textarea {#textarea}

Multi-line text input component with auto-resize, character counting, and validation support.

- **Docs:** https://v1.reui.io/docs/textarea
- **Install:** `pnpm dlx shadcn@latest add @reui/textarea`
- **Examples:** disabled, readonly, size, form
- **Registry variants:** `pnpm dlx shadcn@latest add @reui/textarea-<variant>`
- **Subcomponents:** Textarea

### Key props

- `variant` (enum `"md" | "sm" | "xs"`, default `"md"`)
- `className` (string)

---
