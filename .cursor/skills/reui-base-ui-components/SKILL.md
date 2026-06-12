---
name: reui-base-ui-components
description: >-
  Develops ReUI Base UI components — accessible, unstyled primitives from
  @base-ui-components/react with ReUI prop-based styling and Tailwind. Use when
  the user mentions ReUI, Base UI, unstyled components, accessible primitives,
  or works with base-* components (accordion, dialog, combobox, toast, etc.).
---

# ReUI Base UI Components

ReUI's **Base UI Components** section provides copy-and-paste React components built on [Base UI](https://base-ui.com/react/overview/quick-start) primitives (`@base-ui-components/react`), styled with Tailwind and ReUI's prop-based API. These differ from ReUI's Radix UI components — prefer Base UI when the docs slug starts with `base-`.

**Docs:** https://v1.reui.io/docs/ | **Component count:** 35

## When to Use

Activate this skill when:

- Adding, customizing, or debugging a ReUI `base-*` component
- Choosing between ReUI Radix vs Base UI variants (e.g. `dialog` vs `base-dialog`)
- Implementing accessible overlays, menus, form controls, or feedback UI with Base UI primitives
- Installing ReUI components via CLI or manual copy-paste

## Quick Start

### 1. Prerequisites

Follow [ReUI Installation](https://v1.reui.io/docs/installation) (Tailwind, shadcn-style `components/ui/`, path aliases).

### 2. Install primitive

Most components require:

```bash
npm install @base-ui-components/react
```

**Exceptions:**

| Component | Extra dependency |
| --- | --- |
| Phone Input | `react-phone-number-input` |
| Toolbar | `@base-ui-components/react/toolbar` (subpath import) |

### 3. Add component

**CLI:** Use the ReUI registry / shadcn CLI per [Registry docs](https://v1.reui.io/docs/registry).

```bash
npx shadcn@latest add @reui/base-dialog
```

**Manual:** Copy source from the component doc page into the target file (see [reference.md](reference.md) index for paths). Most files use `components/ui/base-<name>.tsx`; **Button** is the exception (`components/ui/button.tsx`).

### 4. Import and use

```tsx
import { Button } from '@/components/ui/button'
import { BaseDialog } from '@/components/ui/base-dialog'

<Button variant="primary" size="md">Save</Button>
```

Match import names to whatever the pasted source exports. Read the target file after install.

## Architecture

| Layer | Role |
| --- | --- |
| **Base UI primitive** | Unstyled, accessible behavior (focus trap, ARIA, keyboard nav) |
| **ReUI wrapper** | Tailwind styling, `variant`/`size`/`appearance` props, composition helpers |
| **Your app** | Business logic, data, layout |

ReUI Base UI components use **prop-based APIs** — prefer `variant="outline"` over long Tailwind class strings. Many support `asChild` / `render` for composition (see Badge, Button, Breadcrumb).

For primitive-level behavior not covered in ReUI docs, consult [base-ui.com](https://base-ui.com/react/components) for the matching component.

## Component Catalog

Grouped index of all 35 Base UI components. Full props, examples, and install paths: [reference.md](reference.md).

### Layout and structure

| Component | Slug | Notes |
| --- | --- | --- |
| Accordion | `base-accordion` | `variant`, `indicator` props |
| Collapsible | `base-collapsible` | Card-style panels |
| Separator | `base-separator` | Horizontal/vertical divider |
| Scroll Area | `base-scroll-area` | Custom scrollbars |
| Toolbar | `base-toolbar` | Groups controls; subpath import |

### Overlays and floating UI

| Component | Slug | Notes |
| --- | --- | --- |
| Dialog | `base-dialog` | Scroll, no overlay, fullscreen examples |
| Alert Dialog | `base-alert-dialog` | Requires user response |
| Sheet | `base-sheet` | Side slide-out panel |
| Popover | `base-popover` | Positioned floating panel |
| Tooltip | `base-tooltip` | Hover/focus hints |
| Preview Card | `base-preview-card` | Hover preview panel |

### Navigation and menus

| Component | Slug | Notes |
| --- | --- | --- |
| Breadcrumb | `base-breadcrumb` | Multi-part: List, Item, Link, Page, Separator |
| Navigation Menu | `base-navigation-menu` | Site nav with dropdowns |
| Menubar | `base-menubar` | App menu bar |
| Menu | `base-menu` | Dropdown actions; nested/checkbox/radio |
| Context Menu | `base-context-menu` | Right-click menu |
| Tabs | `base-tabs` | `TabsList` variants: default, button, line; pill shape |

### Form inputs and selection

| Component | Slug | Notes |
| --- | --- | --- |
| Input | `base-input` | Text, date, time, file, addon, icon, clear |
| Number Field | `base-number-field` | Increment/decrement, scrub |
| Phone Input | `base-phone-input` | Country selector + validation |
| Checkbox | `base-checkbox` | Indeterminate state |
| Radio Group | `base-radio-group` | Single selection |
| Switch | `base-switch` | On/off toggle |
| Slider | `base-slider` | Range, ticks, tooltip, vertical |
| Select | `base-select` | Single/multi, groups, avatars, badges |
| Combobox | `base-combobox` | Filterable select; multi, creatable, grouped |
| Autocomplete | `base-autocomplete` | Typeahead; async search |
| Toggle | `base-toggle` | Two-state button |
| Toggle Group | `base-toggle-group` | Single or multiple selection |

### Feedback and display

| Component | Slug | Notes |
| --- | --- | --- |
| Toast | `base-toast` | Positions, types, promise helper |
| Progress | `base-progress` | Status, sizes, animated |
| Meter | `base-meter` | Value within known range |
| Badge | `base-badge` | `useRender` + `asChild`; BadgeButton, BadgeDot |
| Avatar | `base-avatar` | Fallback, status, indicator, group |
| Button | `base-button` | Richest variant API; file is `button.tsx` |

## Common Prop Patterns

ReUI Base UI components share recurring prop shapes:

| Prop | Typical values | Used by |
| --- | --- | --- |
| `variant` | `primary`, `secondary`, `outline`, `ghost`, `destructive` | Button, Badge, Tabs |
| `appearance` | `default`, `light`, `outline`, `ghost` | Button, Badge |
| `size` | `xxs`, `xs`, `sm`, `md`, `lg` | Button, Input, Select, Switch, Badge |
| `shape` | `default`, `circle`, `pill` | Button, Badge, Tabs |
| `mode` | `default`, `icon`, `link` | Button |
| `asChild` | `boolean` | Button, Breadcrumb, Badge |
| `render` | `ReactElement` | Badge (Base UI useRender pattern) |
| `disabled` | `boolean` | Most form controls |

Always read the component's API Reference in [reference.md](reference.md) before assuming prop names — some components delegate to Base UI primitives with minimal ReUI customization.

## Implementation Workflow

1. **Identify the component** — Match user intent to slug (`base-<name>`). Check [reference.md](reference.md) index.
2. **Check examples** — Each doc lists variants (e.g. Combobox: Multi Select, Creatable, Grouped). Start from the closest example.
3. **Install dependencies** — `@base-ui-components/react` plus any extras (Phone Input, Toolbar).
4. **Copy or CLI-add** — Paste into the correct `components/ui/` path.
5. **Wire forms** — Many components have a **Form** example; integrate with React Hook Form + Zod per project conventions.
6. **Verify accessibility** — Base UI handles ARIA; preserve primitive part structure (e.g. `Tabs` > `TabsList` > `TabsTrigger` + `TabsContent`).
7. **Fall back to primitive docs** — If ReUI API is thin (Dialog, Toast, Select), read the linked Base UI reference.

## Radix vs Base UI in ReUI

ReUI ships parallel implementations for many patterns:

| Pattern | Radix slug | Base UI slug |
| --- | --- | --- |
| Dialog | `dialog` | `base-dialog` |
| Select | `select` | `base-select` |
| Combobox | `combobox` | `base-combobox` |
| Tooltip | `tooltip` | `base-tooltip` |
| Accordion | `accordion` | `base-accordion` |

Use **Base UI** when the user specifies Base UI, unstyled primitives, or `base-*` paths. Use **Radix** when matching existing Radix-based ReUI components in the project.

## Key Component Notes

### Button (`base-button` → `button.tsx`)

Most variants: `primary`, `mono`, `destructive`, `secondary`, `outline`, `dashed`, `ghost`, `dim`, `foreground`, `inverse`. Supports `mode="link"`, `mode="icon"`, loading state, badge, full width. See reference for full prop table.

### Tabs (`base-tabs`)

`TabsList` accepts `variant` (`default` | `button` | `line`), `shape` (`default` | `pill`), `size` (`xs` | `sm` | `md` | `lg`). Supports vertical orientation and disabled triggers.

### Combobox / Autocomplete / Select

- **Select** — Traditional dropdown; multi-select, groups, custom indicators
- **Combobox** — Filterable; creatable options, external tags
- **Autocomplete** — Free text with suggestions; async search

### Toast (`base-toast`)

Use for transient feedback. Examples cover positions, types, custom content, and promise-based toasts. Wire a provider at app root per pasted source.

### Phone Input (`base-phone-input`)

Requires `react-phone-number-input` alongside Base UI. Handles country selection and E.164 formatting.

## Troubleshooting

| Issue | Check |
| --- | --- |
| Missing styles | ReUI install/theming setup; Tailwind content paths include `components/ui/` |
| Import errors | Verify `@base-ui-components/react` installed; Toolbar uses `/toolbar` subpath |
| Focus trap / portal issues | Do not remove Base UI primitive wrappers (Dialog.Popup, Popover.Positioner, etc.) |
| Wrong component family | Confirm `base-*` slug vs Radix equivalent |
| Prop not recognized | Read API in [reference.md](reference.md); some props are on sub-parts not root |

## Additional Resources

- **Full component reference (props, examples, install paths):** [reference.md](reference.md)
- **ReUI docs:** https://v1.reui.io/docs/
- **Base UI primitives:** https://base-ui.com/react/components
- **ReUI llms.txt (machine-readable index):** https://v1.reui.io/llms.txt
