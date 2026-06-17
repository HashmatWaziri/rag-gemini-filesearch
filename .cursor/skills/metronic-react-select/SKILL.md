---
name: metronic-react-select
description: >-
  Implements Metronic 8 React-Select dropdowns in GLC Inertia pages using the
  shared MetronicSelect wrapper (react-select v5). Use when building or fixing
  select/filter/hierarchy dropdowns, when Radix Select misbehaves, or when the
  user mentions Metronic react-select, react-select-styled, or GLC select styling.
---

# Metronic React-Select (GLC)

Authoritative Metronic docs: [React-Select](https://preview.keenthemes.com/metronic8/react/docs/react-select)

GLC implementation: `resources/js/components/glc/metronic-select.tsx`

## When to use

| Use MetronicSelect | Keep Radix `@/components/ui/select` |
|--------------------|-------------------------------------|
| All `resources/js/pages/glc/**` dropdowns | Legacy Acara/health pages (optional) |
| Dependent hierarchy (course → level → unit) | Simple native `<select>` never |
| Filter bars with “All …” option | |

**Do not** add new Radix Select usage in GLC pages.

## Required API contract

Per Metronic docs, every instance needs:

```tsx
className="react-select-styled"
classNamePrefix="react-select"
```

`MetronicSelect` sets these automatically. Use `variant="solid"` (default) for GLC forms.

## Basic usage

```tsx
import { MetronicSelect, mapIdOptions } from '@/components/glc/metronic-select';

<MetronicSelect
  value={courseId ? String(courseId) : null}
  onChange={(value) => setCourseId(value ? Number(value) : '')}
  options={mapIdOptions(courses)}
  placeholder="Select course"
  isSearchable={false}
/>
```

### Rules

1. **Unset state = `null`**, not sentinel strings like `__empty__`.
2. **Placeholder** carries the empty label; avoid duplicate empty `SelectItem`.
3. **`isSearchable={false}`** for short lists (hierarchy, status filters).
4. **`isSearchable={true}`** only for long lists (audit action filter).
5. **`menuPortalTarget={document.body}`** is built-in — fixes z-index/overflow clipping under Metronic header and cards.

## Filter pattern (“All …”)

```tsx
const options = [
  { value: '', label: 'All statuses' },
  ...statuses.map((s) => ({ value: s.value, label: s.label })),
];

<MetronicSelect
  value={filters.status ?? ''}
  onChange={(value) => applyFilter('status', value ?? '')}
  options={options}
  placeholder="All statuses"
  isSearchable={false}
/>
```

## Dependent hierarchy

Reset children when parent changes:

```tsx
onChange={(value) => {
  onChange({
    course_id: value ?? '',
    course_level_id: '',
    course_unit_id: '',
    course_lesson_id: '',
  });
}}
```

Disable child selects when parent unset (`disabled={!courseId}`).

## Form pattern (no reload on change)

For setup/wizard forms, keep **local React state** and commit with a **Save** button — do not call `router.get` from every `onChange` (that reloads the page on each click).

```tsx
const [courseId, setCourseId] = useState<number | ''>(committedCourseId);
const [levelId, setLevelId] = useState<number | ''>(committedLevelId);
const [saving, setSaving] = useState(false);

<MetronicSelect
  value={courseId ? String(courseId) : null}
  onChange={(value) => {
    setCourseId(value ? Number(value) : '');
    setLevelId('');
    setUnitId('');
  }}
  options={mapIdOptions(courses)}
  placeholder="Select course"
  isSearchable={false}
/>

<Button
  type="button"
  disabled={!isDirty || saving}
  onClick={() => {
    setSaving(true);
    router.get(buildUrl({ courseId, levelId, unitId }), {}, {
      preserveScroll: true,
      onFinish: () => setSaving(false),
    });
  }}
>
  Save selection
</Button>
```

Sync local state from server props after save via `useEffect`. Disable downstream actions (e.g. assign) while `isDirty`.

## Multi-select (`isMulti`)

```tsx
<MetronicSelect
  isMulti
  value={selectedIds.map(String)}
  onChange={(values) => setSelectedIds(values.map(Number))}
  options={students.map((s) => ({ value: String(s.id), label: s.name }))}
  placeholder="Select students"
  isSearchable={false}
/>
```

Use local state + Save button; do not navigate on each tag add/remove.

## Variants and sizes (Metronic parity)

| Prop | Metronic class | When |
|------|----------------|------|
| `variant="solid"` | `react-select-solid` | Default GLC forms |
| `variant="default"` | `react-select-styled` | Transparent border on muted surfaces |
| `variant="transparent"` | `react-select-transparent` | Inline/toolbar filters |
| `size="sm"` | `react-select-sm` | Dense tables |
| `size="lg"` | `react-select-lg` | Hero/settings emphasis |

## Validation

```tsx
<MetronicSelect hasError={Boolean(errors.role)} ... />
```

Maps to Metronic `border-danger` / `is-invalid` pattern.

## Do not

- Use Radix `Select` + `SelectItem value=""` (empty string breaks Radix).
- Rely on Radix viewport inside `overflow-hidden` cards without portal.
- Import `react-select` directly in page files — use `MetronicSelect`.

## Verification

After select changes:

```bash
bun run build
php artisan test tests/Feature/Glc/<Domain>/ --compact
```

Manual check: open `/staff/tutor-setup`, click course/level/unit/student dropdowns — menu must render above header (body portal).

## Reference

Full Metronic doc scrape and class inventory: [reference.md](reference.md)
