---
name: reui-radix-components
description: >-
  Guides use of ReUI Radix UI components (accordion, alert-dialog, avatar, badge,
  breadcrumb, button, carousel, checkbox, collapsible, command, combobox,
  context-menu, date-picker, dialog, dropdown-menu, form, filters, hover-card,
  input, label, menubar, navigation-menu, popover, progress, radio-group,
  scroll-area, select, separator, sheet, slider, sortable, switch, table, tabs,
  tooltip, toggle, toggle-group, tree) in GLC/Metronic Inertia React apps.
  Activates when the user mentions ReUI, Radix UI, Metronic, shadcn, copy-paste
  components, or any listed primitive by name while building or styling UI.
---

# ReUI Radix UI Components

ReUI v1 Radix components are copy-paste, prop-based shadcn registry items built on `@radix-ui/*` primitives, styled with Tailwind CSS v4 semantic tokens. GLC uses them via `resources/js/components/ui/` inside the Metronic Demo 7 shell.

**Docs:** [https://v1.reui.io/docs/](https://v1.reui.io/docs/) · **Full catalog:** [reference.md](reference.md)

## When to use this skill

- Adding or customizing UI in `resources/js/pages/glc/**` or GLC layouts
- Choosing between ReUI Radix vs Base UI vs Animated ReUI sections
- Installing a missing primitive or matching ReUI API props/variants
- Wiring navigation (mega-menu, accordion-menu, sheet), forms, dialogs, filters, or data tables

## GLC stack rules

| Layer | Location / convention |
|-------|----------------------|
| **Layout** | `GlcLayout` + Demo 7 header (`layouts/glc/*`) — not sidebar `AppLayout` |
| **Components** | `@/components/ui/<slug>` — check existing file before installing |
| **Container** | `@/components/common/container` (`max-w-[1320px]`) for page content |
| **CSS tokens** | `app.css` + `metronic.css` — see `DESIGN.md` |
| **Navigation** | `NavigationMenu` in `glc-mega-menu.tsx`; mobile uses `Sheet` |

### Install (new component)

```bash
pnpm dlx shadcn@latest add @reui/<slug>
```

Installs into `resources/js/components/ui/` per shadcn config. Variant blocks use `@reui/<slug>-<variant>` (e.g. `@reui/button-outline`).

### Import (GLC)

```tsx
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
```

Do **not** import from `@reui/*` at runtime — that namespace is the shadcn registry only.

## Styling (DESIGN.md alignment)

Use semantic tokens, not raw palette utilities on GLC pages:

| Use | Avoid |
|-----|-------|
| `bg-primary`, `text-primary`, `bg-primary/10` | `bg-emerald-*`, `text-emerald-*` |
| `text-mono`, `text-foreground` | `text-slate-900` |
| `text-secondary-foreground`, `text-muted-foreground` | `text-slate-600` |
| `border-border`, `bg-card`, `bg-muted` | `border-slate-*`, `bg-white` |

**Buttons:** `variant="default"` (primary), `outline`, `ghost`, `destructive`. **Focus:** `focus-visible:ring-ring/50`. **Cards:** `border-border`, minimal shadow. **Inputs:** `border-input bg-background`.

ReUI adds prop-based variants (`variant`, `appearance`, `size`, `shape`) — prefer props over extra Tailwind in JSX.

## Component selection

| Need | Component | Notes |
|------|-----------|-------|
| Page sections / FAQ | `Accordion`, `Collapsible` | Accordion = exclusive panels |
| Sidebar / nested nav | `AccordionMenu` | Router-aware `matchPath`, `selectedValue` |
| Confirm destructive action | `AlertDialog` | Blocks until user responds; `variant`, `dismissable` |
| Modal content | `Dialog` | General-purpose overlay |
| Mobile nav / drawer | `Sheet` | Dialog-based; `side`, `overlay` |
| Header mega-menu | `NavigationMenu` | Used in `glc-mega-menu.tsx` |
| User menu / actions | `DropdownMenu` | Checkbox & radio item variants |
| Right-click actions | `ContextMenu` | |
| Form fields | `Input`, `Label`, `Checkbox`, `Switch`, `Select`, `RadioGroup`, `Slider` | Wrap with `Form` + RHF + Zod |
| Searchable select | `Combobox` | Single/multi, badges, icons |
| Date / range | `DatePicker` | Popover + calendar; presets |
| Data filters | `Filters` | Operators, async, nuqs integration |
| Tables | `Table` | Pair with TanStack Table for data grids |
| Drag reorder | `Sortable` | dnd-kit based |
| Hierarchy | `Tree` | CRM-style nested lists |
| Loading / progress | `Progress` | Bar, circle, radial, spinner |
| Hints | `Tooltip`, `HoverCard`, `Popover` | Tooltip = brief; HoverCard = rich preview |
| Command palette | `Command` | Often inside `Dialog` |
| Tabs / toggles | `Tabs`, `Toggle`, `ToggleGroup` | Tabs: `variant`, `size` |
| Toast | Use `Sonner` (UI Components section, not Radix sidebar) | |

## Common patterns

### Controlled overlay

```tsx
const [open, setOpen] = useState(false);

<Dialog open={open} onOpenChange={setOpen}>
  <DialogTrigger asChild>
    <Button variant="outline">Open</Button>
  </DialogTrigger>
  <DialogContent>
    <DialogHeader>
      <DialogTitle>Title</DialogTitle>
    </DialogHeader>
    {/* body */}
  </DialogContent>
</Dialog>
```

### Dropdown actions (header topbar pattern)

```tsx
<DropdownMenu>
  <DropdownMenuTrigger asChild>
    <Button variant="ghost" size="icon">...</Button>
  </DropdownMenuTrigger>
  <DropdownMenuContent align="end">
    <DropdownMenuItem>Profile</DropdownMenuItem>
    <DropdownMenuSeparator />
    <DropdownMenuItem>Log out</DropdownMenuItem>
  </DropdownMenuContent>
</DropdownMenu>
```

### Form with ReUI + RHF

```tsx
<Form {...form}>
  <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
    <FormField
      control={form.control}
      name="email"
      render={({ field }) => (
        <FormItem>
          <FormLabel>Email</FormLabel>
          <FormControl>
            <Input placeholder="you@example.com" {...field} />
          </FormControl>
          <FormMessage />
        </FormItem>
      )}
    />
    <Button type="submit">Submit</Button>
  </form>
</Form>
```

### GLC navigation menu styling

Match existing mega-menu link classes (`text-secondary-foreground`, active `text-mono`, `border-mono`). See `glc-mega-menu.tsx` for `NavigationMenuLink` + Inertia `Link` wiring.

## Workflow

1. Check `resources/js/components/ui/<slug>.tsx` — reuse if present
2. If missing, install via `@reui/<slug>` CLI
3. Read [reference.md](reference.md) for props, subcomponents, doc URL
4. Apply semantic tokens from `DESIGN.md`; wrap in `GlcLayout` + `Container`
5. For forms, use `Form` + React Hook Form + Zod; link field examples on each component doc (`#form` anchors)

## Additional resources

- Component catalog with props and examples: [reference.md](reference.md)
- GLC design tokens and layout: `DESIGN.md`
- Scraped URL index: `.firecrawl/reui/radix-sidebar-urls.json`
