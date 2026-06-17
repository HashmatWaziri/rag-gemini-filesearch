# Metronic Demo 7 — Store-client section (e-commerce flows)

Catalog of the 11 `/store-client/*` pages (base
`https://keenthemes.com/metronic/tailwind/react/demo7`, demo login required).
Captured live 2026-06-12 via firecrawl browser session; screenshots in
`.firecrawl/metronic/store/`. Demo product domain is sneakers — ignore content,
steal layout. Demo classes use raw `zinc-*` / `green-500` etc.; this project maps
them to semantic tokens (`text-mono`, `text-secondary-foreground`, `border-border`,
`bg-muted/30`, `bg-primary/10`) — do not invent new mappings.

Recurring vocabulary: **pill stepper** (checkout progress), **facts strip**
(label-over-value columns), **price sidebar** (Order Summary / Price Details
column), **product card grid**.

The checkout pill stepper was already extracted and re-implemented in this repo at
`resources/js/pages/glc/staff/process-steps.tsx` (`StepPill`: done = green corner
check badge on a `bg-muted/30` pill, current = `bg-primary/10 text-primary` pill,
dashed `border-zinc-300` connectors). Not re-extracted here.

## Home (`/store-client/home`)
- Purpose: Storefront landing — search hero, brand tiles, promo offers, product rows.
- Layout: stacked full-width sections inside `mx-auto max-w-[1320px]`: search hero card (centered input on patterned `bg-muted` band) → brand tile row `grid sm:grid-cols-4 xl:grid-cols-7 gap-5` → "Special Offers" `grid xl:grid-cols-2 gap-5` (one wide feature card + `grid sm:grid-cols-2 gap-5` of small promos) → more `grid sm:grid-cols-2 xl:grid-cols-4 gap-5` product rows.
- Cherry-pick: brand tile row (label-over-image mini cards), feature-promo + small-promo split, pastel promo cards with SAVE badge.
- Notes: section header pattern = `h2`-ish label left + "See All" link right; promo cards use soft pastel backgrounds with product image bottom-right.

## Search Results — Grid (`/store-client/search-results-grid`)
- Purpose: Product search with grid presentation.
- Layout: full-width search input + Filter button → results toolbar (count line "1 – 12 over 280 results", sort Select, Today/Week/Month/All toggle-group, grid/list view toggle) → product card grid `grid sm:grid-cols-4 gap-5` → pagination.
- Cherry-pick: results toolbar, product card (extracted below), filter sheet (extracted below).
- Notes: no inline filter sidebar — filters live in a right-side sheet (`sm:w-[320px]`, `inset-5 rounded-lg`) opened by the Filter button. Count line highlights the query term in red.

## Search Results — List (`/store-client/search-results-list`)
- Purpose: Same search flow with horizontal row presentation.
- Layout: identical chrome to grid; rows in `grid grid-cols-1 gap-5`, each row a card with `card-content` = `grow flex items-center flex-wrap justify-between p-2 pe-5 gap-4.5` (thumb | title + rating + sku/category metadata | price + Add button).
- Cherry-pick: horizontal list-row card (thumbnail left, meta middle, actions right) — good for any entity list with image.
- Notes: same card shell as grid (`rounded-xl bg-card border border-border shadow-xs`); only `card-content` flex direction differs. Toolbar view toggle switches grid/list routes.

## Product Details (`/store-client/product-details`)
- Purpose: Product quick-view — not a standalone page.
- Layout: search-results-grid rendered underneath + right-side overlay sheet (~420px) titled "Product Details": image card (`bg-accent/50`, SAVE badge) → title → description → label/value spec rows (Availability with `In Stock` badge, SKU, Category, Rating, More Info) → strikethrough old price + price → full-width primary "Add to Cart" footer button.
- Cherry-pick: detail sheet with spec label/value rows, sticky full-width action footer in a sheet.
- Notes: spec rows are two-column label/value (label `text-secondary-foreground`, value `text-mono`) — same typography pairing as the facts strip.

## Wishlist (`/store-client/wishlist`)
- Purpose: Saved items — also a sheet, not a standalone page.
- Layout: search grid underneath + right sheet titled "Wishlist": stack of compact row cards (thumb | name, rating, brand | old/new price, Add button, trash icon-button) → full-width "Remove all" outline footer button.
- Cherry-pick: compact saved-item row card, destructive icon-button placement, sheet footer action.
- Notes: each row is its own bordered card; SAVE badge floats on the row's top-right.

## My Orders (`/store-client/my-orders`)
- Purpose: Order history as repeating order blocks.
- Layout: `grid xl:grid-cols-1 gap-5 lg:gap-9` of order cards; each card = facts strip header (extracted below) + item rows + per-order action buttons.
- Cherry-pick: facts strip card header, repeating block-per-record layout (vs. a table).
- Notes: facts strip is a `card-header` with `bg-muted/70 border-b border-border gap-9 py-5`; columns are Order ID / Order placed / Total / Ship to / Estimated Delivery.

## Order Receipt (`/store-client/order-receipt`)
- Purpose: Printable receipt/confirmation view.
- Layout: single centered column `max-w-[800px] mx-auto`; "Order Confirmation" receipt card with facts strip, item lines, totals; print/download actions.
- Cherry-pick: narrow centered document card — good for PDFs-on-screen (placement result preview).
- Notes: same facts-strip typography; receipt card sits on `py-10` page padding, gradient accent on the card's top border.

## Checkout — Order Summary (`/store-client/checkout/order-summary`)
- Purpose: Checkout step 1 — review cart items.
- Layout: centered pill stepper (4 steps: Order Summary / Shipping Info / Payment Method / Order Placed) → page heading + "View Cart" action → `grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-9`: items list (2 cols) + price sidebar (`col-span-1`) → footer `flex justify-end gap-3` with Cancel / "Shipping Info" next button.
- Cherry-pick: pill stepper (already in repo, see header), price sidebar, step footer nav buttons.
- Notes: price sidebar = card `bg-accent/50 rounded-xl border border-border` with `h4` "Price Details" and `space-y-2` label/value lines; whole checkout shares this 3-col shell.

## Checkout — Shipping Info (`/store-client/checkout/shipping-info`)
- Purpose: Checkout step 2 — pick a shipping address.
- Layout: same checkout shell; main column = "Add Address" action + address cards `grid sm:grid-cols-2 gap-5` (named cards with Edit/Remove + "Select Address" buttons; selected card highlighted) + price sidebar; footer Back ("Order Summary") / Next ("Payment Method").
- Cherry-pick: selectable record card grid (select + edit/remove per card) — reusable for any pick-one-of-N entity.
- Notes: selection state lives on the card border/background, not a radio input.

## Checkout — Payment Method (`/store-client/checkout/payment-method`)
- Purpose: Checkout step 3 — pick a stored payment method.
- Layout: identical to shipping-info but with payment cards ("Jeroen's Visa", "Sophie's iDeal"…) and "Select Card" buttons; footer Back ("Shipping Info") / "Place Order" primary.
- Cherry-pick: same selectable card grid; terminal primary action in step footer.
- Notes: structural twin of shipping-info — build one selectable-card component, reuse.

## Checkout — Order Placed (`/store-client/checkout/order-placed`)
- Purpose: Checkout done — confirmation with all stepper pills complete.
- Layout: pill stepper all-done → confirmation heading → facts strip → `grid grid-cols-2 gap-5 lg:gap-9` Payment / Delivery-to cards → order summary items + price sidebar → "My Orders" + "Continue Shopping" buttons.
- Cherry-pick: all-steps-done stepper state, facts strip reuse, two-up Payment/Delivery info cards.
- Notes: same facts strip component as my-orders; good model for "submission received" confirmation screens.

## Top patterns worth stealing

- **Facts strip**: `card-header` with `bg-muted/70 border-b border-border gap-9 py-5`, columns of `text-xs text-secondary-foreground` label over `text-sm font-medium text-mono` value. Used on my-orders, order-receipt, order-placed.
- **Product card**: card shell `rounded-xl bg-card border border-border shadow-xs` + inner image card `bg-accent/50 h-[180px] rounded-xl`; footer row with rating badge left, price + action right. Grid `sm:grid-cols-4 gap-5`.
- **List-row variant**: same card shell, `card-content` switched to `flex items-center justify-between p-2 pe-5 gap-4.5` — grid/list toggle costs only one flex change.
- **Right-side filter sheet** instead of an inline sidebar: `sm:w-[320px] inset-5 rounded-lg`, sections split by `border-b border-border`, toggle-group `grid grid-cols-4` for status, min/max price inputs with `bg-muted` currency addons.
- **Detail/quick-view sheet** (product-details, wishlist): keeps the list in context; spec label/value rows + full-width action footer.
- **Checkout shell**: stepper → heading + actions → `grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-9` (main spans 2, price sidebar `col-span-1` with a `bg-accent/50` "Price Details" card) → footer `flex justify-end gap-3` Back/Next.
- **Selectable record card grid** (`sm:grid-cols-2 gap-5`, per-card Edit/Remove/Select) for pick-one-of-N flows.
- **Centered document card** `max-w-[800px] mx-auto` for receipt-like read-only views.

## Extracted markup (trimmed)

### Product card (search-results-grid)

```html
<div data-slot="card" class="flex flex-col items-stretch text-card-foreground rounded-xl bg-card border border-border shadow-xs">
  <div data-slot="card-content" class="grow flex flex-col justify-between p-2.5 gap-4">
    <div class="mb-2.5">
      <div data-slot="card" class="flex items-center justify-center relative bg-accent/50 w-full h-[180px] mb-4 rounded-xl border border-border shadow-none">
        <img class="h-[180px] shrink-0 cursor-pointer" alt="image" src=".../8.png">
      </div>
      <div class="hover:text-primary text-sm font-medium text-mono px-2.5 leading-5.5 block cursor-pointer">Cloud Shift Lightweight Runner Pro Edition</div>
    </div>
    <div class="flex items-center flex-wrap justify-between gap-5 px-2.5 pb-1">
      <span data-slot="badge" class="inline-flex items-center justify-center bg-[var(--color-warning-accent,var(--color-yellow-500))] text-white px-[0.325rem] h-5 min-w-5 text-[0.6875rem] rounded-full gap-1">
        <svg class="lucide lucide-star -mt-0.5"><!-- star --></svg> 5.0
      </span>
      <div class="flex items-center flex-wrap gap-1.5">
        <span class="text-xs font-normal text-secondary-foreground line-through pt-[1px]"><!-- old price --></span>
        <span class="text-sm font-medium text-mono">$99.00</span>
        <button data-slot="button" class="h-7 rounded-md px-2.5 gap-1.25 text-xs bg-background border border-input hover:bg-accent shadow-xs ms-1">
          <svg class="lucide lucide-shopping-cart"><!-- cart --></svg> Add
        </button>
      </div>
    </div>
  </div>
</div>
```

### Facts strip (my-orders order-card header)

```html
<div data-slot="card-header" class="flex items-center flex-wrap px-5 min-h-14 border-b border-border justify-start bg-muted/70 gap-9 h-auto py-5">
  <div class="flex flex-col gap-1.5">
    <span class="text-xs font-normal text-secondary-foreground">Order ID</span>
    <span class="text-sm font-medium text-mono">X319330-S24</span>
  </div>
  <!-- repeat per fact: Order placed / Total / Ship to / Estimated Delivery -->
</div>
```

### Filter sheet (search-results, opened via Filter button)

```html
<div role="dialog" data-state="open" class="flex flex-col fixed z-50 gap-4 bg-background shadow-lg w-3/4 border-s sm:w-[320px] inset-5 start-auto h-auto rounded-lg p-0">
  <div data-slot="sheet-header" class="flex flex-col space-y-1 border-b py-3.5 px-5 border-border">
    <h2 data-slot="sheet-title" class="text-base font-semibold text-foreground">Filter</h2>
  </div>
  <div data-slot="sheet-body" class="py-0">
    <div data-slot="scroll-area" class="relative overflow-hidden h-[calc(100dvh-11.5rem)] pe-3 -me-3">
      <div class="flex items-center gap-1 mb-3 px-5">
        <span class="text-sm font-medium text-mono">Status</span>
        <svg class="lucide lucide-info text-muted-foreground size-4"><!-- tooltip trigger --></svg>
      </div>
      <div data-slot="toggle-group" data-variant="outline" class="grid grid-cols-4 mx-5 rounded-md data-[variant=outline]:gap-0 data-[variant=outline]:shadow-xs">
        <button data-state="off" role="radio" class="h-8.5 min-w-8.5 px-2 text-[0.8125rem] border border-input bg-transparent hover:bg-accent data-[state=on]:bg-accent data-[state=on]:text-accent-foreground data-[variant=outline]:rounded-none data-[variant=outline]:first:rounded-s-md data-[variant=outline]:last:rounded-e-md data-[variant=outline]:border-s-0 data-[variant=outline]:first:border-s">All</button>
        <!-- Sale / New / Trend -->
      </div>
      <div class="border-b border-border mb-4 mt-5"></div>
      <div class="flex flex-col gap-2.5 px-5">
        <span class="text-sm font-medium text-mono">Price</span>
        <div data-slot="input-group" class="flex items-stretch">
          <div data-slot="input-addon" class="flex items-center shrink-0 bg-muted border border-input rounded-md h-8.5 min-w-8.5 justify-center"><!-- $ icon --></div>
          <input data-slot="input" class="flex w-full bg-background border border-input h-8.5 px-3 text-[0.8125rem] rounded-md" value="60">
        </div>
        <!-- max-price input-group -->
      </div>
    </div>
  </div>
</div>
```
