# ReUI Radix UI Components — Reference Catalog

**Source:** [ReUI v1 docs](https://v1.reui.io/docs/) · **Section:** Radix UI Components sidebar (39 items)  
**GLC import path:** `@/components/ui/<slug>` · **Install:** `pnpm dlx shadcn@latest add @reui/<slug>`

## Index

| # | Component | Slug | Doc URL |
|---|-----------|------|---------|
| 1 | Accordion | `accordion` | https://v1.reui.io/docs/accordion |
| 2 | Accordion Menu | `accordion-menu` | https://v1.reui.io/docs/accordion-menu |
| 3 | Alert Dialog | `alert-dialog` | https://v1.reui.io/docs/alert-dialog |
| 4 | Avatar | `avatar` | https://v1.reui.io/docs/avatar |
| 5 | Badge | `badge` | https://v1.reui.io/docs/badge |
| 6 | Breadcrumb | `breadcrumb` | https://v1.reui.io/docs/breadcrumb |
| 7 | Button | `button` | https://v1.reui.io/docs/button |
| 8 | Carousel | `carousel` | https://v1.reui.io/docs/carousel |
| 9 | Checkbox | `checkbox` | https://v1.reui.io/docs/checkbox |
| 10 | Collapsible | `collapsible` | https://v1.reui.io/docs/collapsible |
| 11 | Command | `command` | https://v1.reui.io/docs/command |
| 12 | Combobox | `combobox` | https://v1.reui.io/docs/combobox |
| 13 | Context Menu | `context-menu` | https://v1.reui.io/docs/context-menu |
| 14 | Date Picker | `date-picker` | https://v1.reui.io/docs/date-picker |
| 15 | Dialog | `dialog` | https://v1.reui.io/docs/dialog |
| 16 | Dropdown Menu | `dropdown-menu` | https://v1.reui.io/docs/dropdown-menu |
| 17 | Form | `form` | https://v1.reui.io/docs/form |
| 18 | Filters | `filters` | https://v1.reui.io/docs/filters |
| 19 | Hover Card | `hover-card` | https://v1.reui.io/docs/hover-card |
| 20 | Input | `input` | https://v1.reui.io/docs/input |
| 21 | Label | `label` | https://v1.reui.io/docs/label |
| 22 | Menubar | `menubar` | https://v1.reui.io/docs/menubar |
| 23 | Navigation Menu | `navigation-menu` | https://v1.reui.io/docs/navigation-menu |
| 24 | Popover | `popover` | https://v1.reui.io/docs/popover |
| 25 | Progress | `progress` | https://v1.reui.io/docs/progress |
| 26 | Radio Group | `radio-group` | https://v1.reui.io/docs/radio-group |
| 27 | Scroll Area | `scroll-area` | https://v1.reui.io/docs/scroll-area |
| 28 | Select | `select` | https://v1.reui.io/docs/select |
| 29 | Separator | `separator` | https://v1.reui.io/docs/separator |
| 30 | Sheet | `sheet` | https://v1.reui.io/docs/sheet |
| 31 | Slider | `slider` | https://v1.reui.io/docs/slider |
| 32 | Sortable | `sortable` | https://v1.reui.io/docs/sortable |
| 33 | Switch | `switch` | https://v1.reui.io/docs/switch |
| 34 | Table | `table` | https://v1.reui.io/docs/table |
| 35 | Tabs | `tabs` | https://v1.reui.io/docs/tabs |
| 36 | Tooltip | `tooltip` | https://v1.reui.io/docs/tooltip |
| 37 | Toggle | `toggle` | https://v1.reui.io/docs/toggle |
| 38 | Toggle Group | `toggle-group` | https://v1.reui.io/docs/toggle-group |
| 39 | Tree | `tree` | https://v1.reui.io/docs/tree |

---

## Accordion

**URL:** https://v1.reui.io/docs/accordion  
**Radix:** `@radix-ui/react-accordion`  
**Install:** `pnpm dlx shadcn@latest add @reui/accordion`

Collapsible panels; one or many open via `type="single"` / `type="multiple"`.

| Prop | Type | Default |
|------|------|---------|
| `variant` | enum | `default` |
| `indicator` | enum | `arrow` |

**Parts:** `Accordion`, `AccordionItem`, `AccordionTrigger`, `AccordionContent`

```tsx
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion';

<Accordion type="single" collapsible>
  <AccordionItem value="item-1">
    <AccordionTrigger>Section</AccordionTrigger>
    <AccordionContent>Content</AccordionContent>
  </AccordionItem>
</Accordion>
```

**Patterns:** outline variant, indicator (arrow/plus), nested items

---

## Accordion Menu

**URL:** https://v1.reui.io/docs/accordion-menu  
**Radix:** Accordion  
**Install:** `pnpm dlx shadcn@latest add @reui/accordion-menu`

Multi-level nav with router-aware active states.

| Prop | Type | Notes |
|------|------|-------|
| `selectedValue` | `string \| string[]` | Active route key(s) |
| `matchPath` | `(href: string) => boolean` | Custom active matcher |
| `classNames` | `AccordionMenuClassNames` | Slot styling |
| `variant` | enum | `default` |

**Parts:** `AccordionMenu`, `AccordionMenuGroup`, `AccordionMenuLabel`, `AccordionMenuSeparator`, `AccordionMenuItem`, `AccordionMenuSub`, `AccordionMenuSubTrigger`, `AccordionMenuSubContent`, `AccordionMenuIndicator`

```tsx
<AccordionMenu selectedValue={pathname} matchPath={(href) => pathname.startsWith(href)}>
  <AccordionMenuItem value="/dashboard">Dashboard</AccordionMenuItem>
</AccordionMenu>
```

**Patterns:** sidebar nav, nested routes, mobile drawer sections

---

## Alert Dialog

**URL:** https://v1.reui.io/docs/alert-dialog  
**Radix:** `@radix-ui/react-alert-dialog`  
**Install:** `pnpm dlx shadcn@latest add @reui/alert-dialog`

Modal that requires explicit confirmation; cannot dismiss casually.

| Prop | Type | Default |
|------|------|---------|
| `variant` | enum | `info` |
| `dismissable` | boolean | `true` |

**Parts:** `AlertDialog`, `AlertDialogTrigger`, `AlertDialogContent`, `AlertDialogHeader`, `AlertDialogTitle`, `AlertDialogDescription`, `AlertDialogFooter`, `AlertDialogAction`, `AlertDialogCancel`

```tsx
<AlertDialog>
  <AlertDialogTrigger asChild><Button variant="destructive">Delete</Button></AlertDialogTrigger>
  <AlertDialogContent>
    <AlertDialogHeader>
      <AlertDialogTitle>Are you sure?</AlertDialogTitle>
    </AlertDialogHeader>
    <AlertDialogFooter>
      <AlertDialogCancel>Cancel</AlertDialogCancel>
      <AlertDialogAction>Continue</AlertDialogAction>
    </AlertDialogFooter>
  </AlertDialogContent>
</AlertDialog>
```

**Patterns:** destructive confirm, info/warning variants

---

## Avatar

**URL:** https://v1.reui.io/docs/avatar  
**Radix:** `@radix-ui/react-avatar`  
**Install:** `pnpm dlx shadcn@latest add @reui/avatar`

Image with fallback initials; status indicators.

| Prop | Type | Default |
|------|------|---------|
| `variant` | enum | status variant e.g. `online` |

**Parts:** `Avatar`, `AvatarImage`, `AvatarFallback`, `AvatarIndicator`, `AvatarStatus`, `AvatarBadge`, `AvatarGroup`

```tsx
<Avatar>
  <AvatarImage src={user.avatar} alt={user.name} />
  <AvatarFallback>{initials}</AvatarFallback>
</Avatar>
```

**Patterns:** size, status dot, badge overlay, avatar group with tooltips

---

## Badge

**URL:** https://v1.reui.io/docs/badge  
**Radix:** Slot  
**Install:** `pnpm dlx shadcn@latest add @reui/badge`

Status, category, or count metadata.

| Prop | Type | Default |
|------|------|---------|
| `variant` | enum | `secondary` |
| `appearance` | enum | `default` |
| `size` | enum | `md` |
| `shape` | enum | `default` |
| `asChild` | boolean | `false` |

**Parts:** `Badge`, `BadgeButton`, `BadgeDot`

```tsx
<Badge variant="primary" appearance="light">Active</Badge>
```

**Patterns:** primary, outline, destructive, success, warning, info, removable button

---

## Breadcrumb

**URL:** https://v1.reui.io/docs/breadcrumb  
**Radix:** Slot  
**Install:** `pnpm dlx shadcn@latest add @reui/breadcrumb`

Hierarchy navigation for current page context.

| Prop | Type | Default |
|------|------|---------|
| `separator` | `ReactNode` | `<ChevronRight />` |

**Parts:** `Breadcrumb`, `BreadcrumbList`, `BreadcrumbItem`, `BreadcrumbLink`, `BreadcrumbPage`, `BreadcrumbSeparator`, `BreadcrumbEllipsis`

```tsx
<Breadcrumb>
  <BreadcrumbList>
    <BreadcrumbItem><BreadcrumbLink href="/">Home</BreadcrumbLink></BreadcrumbItem>
    <BreadcrumbSeparator />
    <BreadcrumbItem><BreadcrumbPage>Settings</BreadcrumbPage></BreadcrumbItem>
  </BreadcrumbList>
</Breadcrumb>
```

---

## Button

**URL:** https://v1.reui.io/docs/button  
**Radix:** `@radix-ui/react-slot`  
**Install:** `pnpm dlx shadcn@latest add @reui/button`

Primary interactive control; extensive ReUI variants.

| Prop | Type | Default |
|------|------|---------|
| `variant` | enum | `primary` |
| `appearance` | enum | — |
| `size` | enum | `md` |
| `shape` | enum | `default` |
| `mode` | enum | `default` |
| `asChild` | boolean | `false` |

**Variants (registry):** `@reui/button-outline`, `-ghost`, `-destructive`, `-mono`, `-with-icon`, `-loading`, `-icon`, `-link`, etc.

```tsx
import { Button } from '@/components/ui/button';

<Button variant="primary" size="md">Save</Button>
<Button variant="outline" asChild><Link href="/back">Back</Link></Button>
```

**GLC:** Prefer `default`/`outline`/`ghost`/`destructive` per `DESIGN.md`.

---

## Carousel

**URL:** https://v1.reui.io/docs/carousel  
**Radix:** — (Embla)  
**Install:** `pnpm dlx shadcn@latest add @reui/carousel`

Swipeable slide carousel with motion.

| Prop | Type | Default |
|------|------|---------|
| `orientation` | string | `horizontal` |
| `spacing` | string | `normal` |

**Parts:** `Carousel`, `CarouselContent`, `CarouselItem`, `CarouselPrevious`, `CarouselNext`

```tsx
<Carousel>
  <CarouselContent>
    <CarouselItem>Slide 1</CarouselItem>
  </CarouselContent>
  <CarouselPrevious /><CarouselNext />
</Carousel>
```

---

## Checkbox

**URL:** https://v1.reui.io/docs/checkbox  
**Radix:** `@radix-ui/react-checkbox`  
**Install:** `pnpm dlx shadcn@latest add @reui/checkbox`

Boolean toggle; supports indeterminate.

| Prop | Type | Default |
|------|------|---------|
| `size` | enum | `md` |
| `checked` | boolean | — |
| `indeterminate` | boolean | `false` |

```tsx
<Checkbox checked={checked} onCheckedChange={setChecked} />
```

**Patterns:** disabled, indeterminate, form integration — see `#form` on doc

---

## Collapsible

**URL:** https://v1.reui.io/docs/collapsible  
**Radix:** `@radix-ui/react-collapsible`  
**Install:** `pnpm dlx shadcn@latest add @reui/collapsible`

Single expand/collapse panel (non-accordion).

**Parts:** `Collapsible`, `CollapsibleTrigger`, `CollapsibleContent`

```tsx
<Collapsible open={open} onOpenChange={setOpen}>
  <CollapsibleTrigger>Toggle</CollapsibleTrigger>
  <CollapsibleContent>Hidden content</CollapsibleContent>
</Collapsible>
```

---

## Command

**URL:** https://v1.reui.io/docs/command  
**Radix:** — (cmdk)  
**Install:** `pnpm dlx shadcn@latest add @reui/command`

Command palette / searchable list.

**Parts:** `Command`, `CommandInput`, `CommandList`, `CommandEmpty`, `CommandGroup`, `CommandItem`, `CommandSeparator`

```tsx
<Command>
  <CommandInput placeholder="Search..." />
  <CommandList>
    <CommandEmpty>No results.</CommandEmpty>
    <CommandGroup heading="Actions">
      <CommandItem onSelect={() => navigate('/settings')}>Settings</CommandItem>
    </CommandGroup>
  </CommandList>
</Command>
```

**Patterns:** inside `Dialog` for ⌘K palette

---

## Combobox

**URL:** https://v1.reui.io/docs/combobox  
**Radix:** Popover + Command  
**Install:** `pnpm dlx shadcn@latest add @reui/combobox-default`

Autocomplete select with optional custom values.

| Prop | Type | Default |
|------|------|---------|
| `value` | string | — |
| `placeholder` | string | `Select an option` |

**Variants:** group, multi-select, badge, user picker, country, timezone, phone, form

```tsx
<Combobox
  value={city}
  onValueChange={setCity}
  options={cities}
  placeholder="Select city..."
/>
```

---

## Context Menu

**URL:** https://v1.reui.io/docs/context-menu  
**Radix:** `@radix-ui/react-context-menu`  
**Install:** `pnpm dlx shadcn@latest add @reui/context-menu`

Right-click (or long-press) action menu.

**Parts:** `ContextMenu`, `ContextMenuTrigger`, `ContextMenuContent`, `ContextMenuItem`, `ContextMenuSeparator`, `ContextMenuSub`, etc.

```tsx
<ContextMenu>
  <ContextMenuTrigger className="flex h-24 items-center justify-center border border-dashed">
    Right click here
  </ContextMenuTrigger>
  <ContextMenuContent>
    <ContextMenuItem>Edit</ContextMenuItem>
    <ContextMenuItem variant="destructive">Delete</ContextMenuItem>
  </ContextMenuContent>
</ContextMenu>
```

---

## Date Picker

**URL:** https://v1.reui.io/docs/date-picker  
**Radix:** Popover  
**Install:** `pnpm dlx shadcn@latest add @reui/date-picker-default`

Calendar popover + input; single, range, presets.

| Prop | Type | Default |
|------|------|---------|
| `asInput` | boolean | `false` |
| `placeholder` | string | `Pick a date` |
| `align` | enum | `center` |
| `presets` | `Array<{ label, range }>` | — |

**Parts:** `DatePicker`, `DatePickerTrigger`, `DatePickerContent`, `DatePickerPresets`

```tsx
<DatePicker value={date} onSelect={setDate} placeholder="Pick a date" />
```

**Patterns:** range, date-time, form — see doc `#form`

---

## Dialog

**URL:** https://v1.reui.io/docs/dialog  
**Radix:** `@radix-ui/react-dialog`  
**Install:** `pnpm dlx shadcn@latest add @reui/dialog`

General modal overlay.

| Prop | Type | Default |
|------|------|---------|
| `open` | boolean | — |
| `onOpenChange` | function | — |

**Parts:** `Dialog`, `DialogTrigger`, `DialogContent`, `DialogHeader`, `DialogTitle`, `DialogDescription`, `DialogFooter`, `DialogClose`

```tsx
<Dialog open={open} onOpenChange={setOpen}>
  <DialogTrigger asChild><Button>Open</Button></DialogTrigger>
  <DialogContent className="sm:max-w-md">
    <DialogHeader><DialogTitle>Edit profile</DialogTitle></DialogHeader>
  </DialogContent>
</Dialog>
```

**GLC:** Use for admin modals; prefer over hand-rolled overlays (`DESIGN.md`).

---

## Dropdown Menu

**URL:** https://v1.reui.io/docs/dropdown-menu  
**Radix:** `@radix-ui/react-dropdown-menu`  
**Install:** `pnpm dlx shadcn@latest add @reui/dropdown-menu`

Button-triggered action menu.

**Parts:** `DropdownMenu`, `DropdownMenuTrigger`, `DropdownMenuContent`, `DropdownMenuItem`, `DropdownMenuCheckboxItem`, `DropdownMenuRadioGroup`, `DropdownMenuLabel`, `DropdownMenuSeparator`, `DropdownMenuSub`

```tsx
<DropdownMenu>
  <DropdownMenuTrigger asChild><Button variant="ghost">Menu</Button></DropdownMenuTrigger>
  <DropdownMenuContent align="end">
    <DropdownMenuItem>Action</DropdownMenuItem>
  </DropdownMenuContent>
</DropdownMenu>
```

**GLC:** Used in `glc-header-topbar.tsx` for user menu.

---

## Form

**URL:** https://v1.reui.io/docs/form  
**Radix:** — (React Hook Form)  
**Install:** `pnpm dlx shadcn@latest add @reui/form`

RHF + Zod form field wrappers.

**Parts:** `Form`, `FormField`, `FormItem`, `FormLabel`, `FormControl`, `FormDescription`, `FormMessage`

```tsx
const form = useForm<z.infer<typeof schema>>({ resolver: zodResolver(schema) });

<Form {...form}>
  <form onSubmit={form.handleSubmit(onSubmit)}>
    <FormField control={form.control} name="email" render={({ field }) => (
      <FormItem>
        <FormLabel>Email</FormLabel>
        <FormControl><Input {...field} /></FormControl>
        <FormMessage />
      </FormItem>
    )} />
  </form>
</Form>
```

**Field examples:** checkbox, radio, select, switch, input, textarea, date-picker, combobox, slider (doc links under `#form`)

---

## Filters

**URL:** https://v1.reui.io/docs/filters  
**Install:** `pnpm dlx shadcn@latest add @reui/filters`

Advanced filter bar with operators for data grids.

| Prop | Type | Default |
|------|------|---------|
| `filters` | `Filter[]` | — |
| `fields` | `FilterFieldsConfig` | — |
| `onChange` | function | — |
| `variant` | enum | `outline` |
| `size` | enum | `md` |
| `showAddButton` | boolean | — |
| `allowMultiple` | boolean | `true` |
| `i18n` | `FilterI18nConfig` | — |

```tsx
<Filters fields={filterFields} filters={filters} onChange={setFilters} />
```

**Operators:** contains, is, is not, between, starts with, is empty, etc. **Patterns:** data grid, async, nuqs URL state

---

## Hover Card

**URL:** https://v1.reui.io/docs/hover-card  
**Radix:** `@radix-ui/react-hover-card`  
**Install:** `pnpm dlx shadcn@latest add @reui/hover-card`

Rich preview on hover/focus.

| Prop | Type | Default |
|------|------|---------|
| `openDelay` | number | `0` |
| `closeDelay` | number | `0` |

**Parts:** `HoverCard`, `HoverCardTrigger`, `HoverCardContent`

```tsx
<HoverCard>
  <HoverCardTrigger asChild><Link href="/user/1">@user</Link></HoverCardTrigger>
  <HoverCardContent>Profile preview</HoverCardContent>
</HoverCard>
```

---

## Input

**URL:** https://v1.reui.io/docs/input  
**Radix:** Slot  
**Install:** `pnpm dlx shadcn@latest add @reui/input`

Text field with ReUI variants (addon, icon, date, copy, clear).

| Prop | Type | Default |
|------|------|---------|
| `variant` | enum | `md` |

**Variants:** disabled, readonly, addon, icon, file, date, time, datetime, copy-to-clipboard, clear-button, form

```tsx
<Input placeholder="Enter email" type="email" />
```

**GLC:** Use `border-input bg-background focus-visible:ring-ring/50` (`DESIGN.md`).

---

## Label

**URL:** https://v1.reui.io/docs/label  
**Radix:** `@radix-ui/react-label`  
**Install:** `pnpm dlx shadcn@latest add @reui/label`

Accessible form label.

| Prop | Type | Default |
|------|------|---------|
| `variant` | enum | `primary` |

```tsx
<Label htmlFor="email">Email</Label>
<Input id="email" />
```

---

## Menubar

**URL:** https://v1.reui.io/docs/menubar  
**Radix:** `@radix-ui/react-menubar`  
**Install:** `pnpm dlx shadcn@latest add @reui/menubar`

Desktop-style persistent menu bar.

**Parts:** `Menubar`, `MenubarMenu`, `MenubarTrigger`, `MenubarContent`, `MenubarItem`, `MenubarSeparator`, `MenubarShortcut`

```tsx
<Menubar>
  <MenubarMenu>
    <MenubarTrigger>File</MenubarTrigger>
    <MenubarContent>
      <MenubarItem>New</MenubarItem>
    </MenubarContent>
  </MenubarMenu>
</Menubar>
```

---

## Navigation Menu

**URL:** https://v1.reui.io/docs/navigation-menu  
**Radix:** `@radix-ui/react-navigation-menu`  
**Install:** `pnpm dlx shadcn@latest add @reui/navigation-menu`

Horizontal nav with mega-menu dropdowns.

**Parts:** `NavigationMenu`, `NavigationMenuList`, `NavigationMenuItem`, `NavigationMenuTrigger`, `NavigationMenuContent`, `NavigationMenuLink`

```tsx
<NavigationMenu>
  <NavigationMenuList>
    <NavigationMenuItem>
      <NavigationMenuTrigger>Products</NavigationMenuTrigger>
      <NavigationMenuContent>{/* panel */}</NavigationMenuContent>
    </NavigationMenuItem>
  </NavigationMenuList>
</NavigationMenu>
```

**GLC:** `glc-mega-menu.tsx` — pair with Inertia `Link`, active `data-[active=true]` classes.

---

## Popover

**URL:** https://v1.reui.io/docs/popover  
**Radix:** `@radix-ui/react-popover`  
**Install:** `pnpm dlx shadcn@latest add @reui/popover`

Floating interactive content portal.

**Parts:** `Popover`, `PopoverTrigger`, `PopoverContent`, `PopoverAnchor`

```tsx
<Popover>
  <PopoverTrigger asChild><Button variant="outline">Open</Button></PopoverTrigger>
  <PopoverContent className="w-80">Content</PopoverContent>
</Popover>
```

---

## Progress

**URL:** https://v1.reui.io/docs/progress  
**Radix:** `@radix-ui/react-progress`  
**Install:** `pnpm dlx shadcn@latest add @reui/progress`

Task completion indicator.

| Prop | Type | Default |
|------|------|---------|
| `value` | number | `0` |
| `max` | number | `100` |

**Variants:** status, circle, radial, spinner

```tsx
<Progress value={progress} />
```

---

## Radio Group

**URL:** https://v1.reui.io/docs/radio-group  
**Radix:** `@radix-ui/react-radio-group`  
**Install:** `pnpm dlx shadcn@latest add @reui/radio-group`

Single selection from options.

**Parts:** `RadioGroup`, `RadioGroupItem`

```tsx
<RadioGroup value={plan} onValueChange={setPlan}>
  <RadioGroupItem value="free" id="free" />
  <Label htmlFor="free">Free</Label>
</RadioGroup>
```

**Patterns:** disabled, size, form

---

## Scroll Area

**URL:** https://v1.reui.io/docs/scroll-area  
**Radix:** `@radix-ui/react-scroll-area`  
**Install:** `pnpm dlx shadcn@latest add @reui/scroll-area`

Custom-styled scrollable region.

| Prop | Type |
|------|------|
| `viewportRef` | `Ref<HTMLDivElement>` |
| `viewportClassName` | string |

**Parts:** `ScrollArea`, `ScrollBar`

```tsx
<ScrollArea className="h-72">
  <div className="p-4">{longContent}</div>
</ScrollArea>
```

---

## Select

**URL:** https://v1.reui.io/docs/select  
**Radix:** `@radix-ui/react-select`  
**Install:** `pnpm dlx shadcn@latest add @reui/select`

Dropdown option picker.

| Prop | Type | Default |
|------|------|---------|
| `indicatorPosition` | enum | `left` |
| `indicatorVisibility` | boolean | `true` |
| `size` | enum | `md` |

**Parts:** `Select`, `SelectTrigger`, `SelectValue`, `SelectContent`, `SelectItem`, `SelectGroup`, `SelectLabel`

```tsx
<Select value={value} onValueChange={setValue}>
  <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
  <SelectContent>
    <SelectItem value="a">Option A</SelectItem>
  </SelectContent>
</Select>
```

**Patterns:** group, icon, avatar, badge, status, form

---

## Separator

**URL:** https://v1.reui.io/docs/separator  
**Radix:** `@radix-ui/react-separator`  
**Install:** `pnpm dlx shadcn@latest add @reui/separator`

Visual/semantic divider.

| Prop | Type | Default |
|------|------|---------|
| `orientation` | `horizontal \| vertical` | `horizontal` |

```tsx
<Separator className="my-4" />
<Separator orientation="vertical" className="h-4" />
```

---

## Sheet

**URL:** https://v1.reui.io/docs/sheet  
**Radix:** Dialog  
**Install:** `pnpm dlx shadcn@latest add @reui/sheet`

Edge drawer overlay.

| Prop | Type | Default |
|------|------|---------|
| `side` | enum | `right` |
| `overlay` | boolean | `true` |

**Parts:** `Sheet`, `SheetTrigger`, `SheetContent`, `SheetHeader`, `SheetTitle`, `SheetDescription`, `SheetFooter`, `SheetClose`

```tsx
<Sheet>
  <SheetTrigger asChild><Button variant="ghost">Menu</Button></SheetTrigger>
  <SheetContent side="left">Mobile nav</SheetContent>
</Sheet>
```

**GLC:** Mobile navigation drawer (`glc-mobile-nav.tsx` pattern).

---

## Slider

**URL:** https://v1.reui.io/docs/slider  
**Radix:** `@radix-ui/react-slider`  
**Install:** `pnpm dlx shadcn@latest add @reui/slider`

Range value input.

| Prop | Type | Default |
|------|------|---------|
| `min` | number | `0` |
| `max` | number | `100` |
| `step` | number | `1` |

**Variants:** range (dual thumb), tooltip, input sync, form

```tsx
<Slider value={[value]} onValueChange={([v]) => setValue(v)} max={100} step={1} />
```

---

## Sortable

**URL:** https://v1.reui.io/docs/sortable  
**Install:** `pnpm dlx shadcn@latest add @reui/sortable`

Drag-and-drop reorder (dnd-kit).

| Prop | Type | Default |
|------|------|---------|
| `value` | `T[]` | — |
| `onValueChange` | function | — |
| `getItemValue` | `(item: T) => string` | — |
| `strategy` | `horizontal \| vertical \| grid` | `vertical` |

**Parts:** `Sortable`, `SortableItem`, `SortableItemHandle`

```tsx
<Sortable value={items} onValueChange={setItems} getItemValue={(i) => i.id}>
  {items.map((item) => (
    <SortableItem key={item.id} value={item.id}>
      <SortableItemHandle />
      {item.label}
    </SortableItem>
  ))}
</Sortable>
```

---

## Switch

**URL:** https://v1.reui.io/docs/switch  
**Radix:** `@radix-ui/react-switch`  
**Install:** `pnpm dlx shadcn@latest add @reui/switch`

On/off toggle.

| Prop | Type | Default |
|------|------|---------|
| `shape` | enum | `pill` |
| `size` | enum | `md` |

**Parts:** `Switch`, `SwitchWrapper`, `SwitchIndicator`

```tsx
<Switch checked={enabled} onCheckedChange={setEnabled} />
```

**Patterns:** square, icon, advanced label, form

---

## Table

**URL:** https://v1.reui.io/docs/table  
**Install:** `pnpm dlx shadcn@latest add @reui/table`

Semantic HTML table primitives.

**Parts:** `Table`, `TableHeader`, `TableBody`, `TableFooter`, `TableRow`, `TableHead`, `TableCell`, `TableCaption`

```tsx
<Table>
  <TableHeader>
    <TableRow><TableHead>Name</TableHead></TableRow>
  </TableHeader>
  <TableBody>
    <TableRow><TableCell>{name}</TableCell></TableRow>
  </TableBody>
</Table>
```

**GLC:** Pair with TanStack Table for sortable/filterable admin grids.

---

## Tabs

**URL:** https://v1.reui.io/docs/tabs  
**Radix:** `@radix-ui/react-tabs`  
**Install:** `pnpm dlx shadcn@latest add @reui/tabs`

Layered tab panels.

| Prop | Type | Default |
|------|------|---------|
| `variant` | enum | `default` |
| `size` | enum | `md` |

**Parts:** `Tabs`, `TabsList`, `TabsTrigger`, `TabsContent`

```tsx
<Tabs defaultValue="tab1">
  <TabsList>
    <TabsTrigger value="tab1">Overview</TabsTrigger>
    <TabsTrigger value="tab2">Details</TabsTrigger>
  </TabsList>
  <TabsContent value="tab1">...</TabsContent>
</Tabs>
```

**Patterns:** pill, line, vertical, icon, badge, disabled

---

## Tooltip

**URL:** https://v1.reui.io/docs/tooltip  
**Radix:** `@radix-ui/react-tooltip`  
**Install:** `pnpm dlx shadcn@latest add @reui/tooltip`

Brief hover/focus hint.

**Parts:** `TooltipProvider`, `Tooltip`, `TooltipTrigger`, `TooltipContent`

```tsx
<TooltipProvider>
  <Tooltip>
    <TooltipTrigger asChild><Button variant="ghost" size="icon">?</Button></TooltipTrigger>
    <TooltipContent>Help text</TooltipContent>
  </Tooltip>
</TooltipProvider>
```

---

## Toggle

**URL:** https://v1.reui.io/docs/toggle  
**Radix:** `@radix-ui/react-toggle`  
**Install:** `pnpm dlx shadcn@latest add @reui/toggle`

Two-state pressable button.

| Prop | Type | Default |
|------|------|---------|
| `pressed` | boolean | — |
| `onPressedChange` | function | — |

**Variants:** text, outline, size

```tsx
<Toggle pressed={bold} onPressedChange={setBold} aria-label="Toggle bold">
  <BoldIcon />
</Toggle>
```

---

## Toggle Group

**URL:** https://v1.reui.io/docs/toggle-group  
**Radix:** `@radix-ui/react-toggle-group`  
**Install:** `pnpm dlx shadcn@latest add @reui/toggle-group`

Grouped toggle buttons; single or multiple selection.

**Parts:** `ToggleGroup`, `ToggleGroupItem`

```tsx
<ToggleGroup type="single" value={align} onValueChange={setAlign}>
  <ToggleGroupItem value="left">Left</ToggleGroupItem>
  <ToggleGroupItem value="center">Center</ToggleGroupItem>
</ToggleGroup>
```

**Variants:** single, outline, size

---

## Tree

**URL:** https://v1.reui.io/docs/tree  
**Install:** `pnpm dlx shadcn@latest add @reui/tree`

Expandable hierarchical list.

**Variants:** default, line, icon, plus-minus

```tsx
<Tree>
  <TreeItem value="accounts" label="Accounts">
    <TreeItem value="acme" label="Acme Corp" />
  </TreeItem>
</Tree>
```

**Patterns:** nested CRM folders, dynamic loading, custom icons

---

## Radix primitive map

| Component | Underlying primitive |
|-----------|---------------------|
| Accordion, Accordion Menu, Collapsible | `@radix-ui/react-accordion` / collapsible |
| Alert Dialog | `@radix-ui/react-alert-dialog` |
| Avatar | `@radix-ui/react-avatar` |
| Badge, Breadcrumb, Button, Input, Label | Slot / label |
| Checkbox | `@radix-ui/react-checkbox` |
| Context Menu | `@radix-ui/react-context-menu` |
| Dialog, Sheet | `@radix-ui/react-dialog` |
| Dropdown Menu | `@radix-ui/react-dropdown-menu` |
| Hover Card | `@radix-ui/react-hover-card` |
| Menubar | `@radix-ui/react-menubar` |
| Navigation Menu | `@radix-ui/react-navigation-menu` |
| Popover, Date Picker, Combobox | `@radix-ui/react-popover` (+ cmdk/calendar) |
| Progress | `@radix-ui/react-progress` |
| Radio Group | `@radix-ui/react-radio-group` |
| Scroll Area | `@radix-ui/react-scroll-area` |
| Select | `@radix-ui/react-select` |
| Separator | `@radix-ui/react-separator` |
| Slider | `@radix-ui/react-slider` |
| Switch | `@radix-ui/react-switch` |
| Tabs | `@radix-ui/react-tabs` |
| Toggle, Toggle Group | `@radix-ui/react-toggle` / toggle-group |
| Tooltip | `@radix-ui/react-tooltip` |
| Carousel, Command, Filters, Form, Sortable, Table, Tree | Third-party (Embla, cmdk, RHF, dnd-kit, etc.) |

For full Radix API beyond ReUI custom props, see [radix-ui.com/primitives](https://www.radix-ui.com/primitives).
