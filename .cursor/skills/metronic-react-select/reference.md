# Metronic 8 React-Select — reference

Source: https://preview.keenthemes.com/metronic8/react/docs/react-select  
Local scrape: `.firecrawl/metronic-react-select.md`

## Plugin

Metronic 8 uses [react-select](https://react-select.com/home), **not** Radix Select.

```tsx
import Select from 'react-select';

<Select
  className="react-select-styled"
  classNamePrefix="react-select"
  options={options}
  placeholder="Select an option"
/>
```

## Metronic class variants

| Class | Purpose |
|-------|---------|
| `react-select-styled` | Base styled select (required) |
| `react-select-solid` | Filled/muted control background |
| `react-select-transparent` | Borderless/transparent control |
| `react-select-sm` | Small height |
| `react-select-lg` | Large height |
| `is-valid` / `border-success` | Valid state |
| `border-danger` | Invalid state |

## Common props (from Metronic examples)

| Prop | Example use |
|------|-------------|
| `isDisabled` | Disabled state |
| `isClearable` | Clear button |
| `isSearchable={false}` | Hide search input |
| `isMulti` | Tag multi-select |
| `defaultValue` | Uncontrolled default |
| `classNames.control` | Bootstrap input-group rounding |
| `styles.menu` | Custom menu width |

## GLC mapping

Metronic ships Bootstrap SCSS for `.react-select-*` classes. GLC Demo 7 (Tailwind) reimplements equivalent styling in `MetronicSelect` via:

- `unstyled` + Tailwind `classNames` on react-select v5
- Semantic tokens: `border-input`, `bg-popover`, `text-foreground`, `ring-ring/50`
- `menuPortalTarget={document.body}` + `z-[200]` (replaces Metronic overlay stacking)

## Why Radix Select failed in GLC

1. **Empty value items** — Radix forbids `SelectItem value=""`; pages used `__empty__` sentinels inconsistently.
2. **Viewport height** — shadcn viewport used `h-[var(--radix-select-trigger-height)]`, clipping the menu to one row.
3. **Z-index** — `z-50` sat under Metronic sticky header; menus appeared broken or unclickable.
4. **Metronic spec** — client theme docs specify react-select, not Radix.

## Pages migrated (GLC)

| Page | Selects |
|------|---------|
| `tutor/staff/setup.tsx` | Course, level, unit, student |
| `tutor/staff/students.tsx` | Assignment course/level/unit |
| `curriculum/components/hierarchy-picker.tsx` | Course, level, unit, lesson |
| `curriculum/index.tsx` | Status filter |
| `admin/users/user-fields.tsx` | Role |
| `admin/users/index.tsx` | Role filter |
| `admin/access-codes/index.tsx` | Status filter |
| `admin/audit/index.tsx` | Action filter |

## Future work

- Grouped options (`groupedOptions` in Metronic Example3)
- Country/user avatar options (Examples 11–13)
- Multi-select tags (`isMulti`) if product needs it
