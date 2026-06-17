# Metronic Demo 7 — Network section (user directories)

12 pages under `/network/*` (base `https://keenthemes.com/metronic/tailwind/react/demo7`,
demo login required). Two families: **card grid** pages (`user-cards/*`) and **data table**
pages (`user-table/*`). Every page uses the same page header (H1 title + breadcrumb on the
left, `Export` button + date-range picker button on the right). All table pages append a
two-column FAQ + "Questions? / Contact Support" block below the table card.

Screenshots: `.firecrawl/metronic/network-auth/mini-cards.png`, `saas-users.png`.

## Get Started (`/network/get-started`)
- Purpose: launcher hub linking to the network sub-pages.
- Layout: card grid, `grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-7.5`, 5 feature cards (User Cards, User Base, Cooperators, Community Engagement, Donatiors).
- Cherry-pick: feature-tile launcher grid.
- Notes: same grid recipe as the dashboard get-started pages; cards are plain `data-slot=card`.

## Mini Cards (`/network/user-cards/mini-cards`)
- Purpose: compact user directory, avatar-first.
- Layout: toolbar ("Showing 15 Users" H3 left; `Active` select, `Latest` select, `Filters` primary button, search input right) + card grid `grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 lg:gap-7.5`, 15 cards.
- Cherry-pick: compact user card (snippet below), toolbar row with count + filters, presence dot on avatar.
- Notes: card is `flex flex-col items-center p-5 lg:py-10`; name link `text-mono font-medium hover:text-primary-active`, handle `text-sm text-secondary-foreground`.

## Team Crew cards (`/network/user-cards/team-crew`)
- Purpose: richer team-member cards with connect actions.
- Layout: same toolbar + card grid `grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5`, 6 cards; inner stat list uses `grid grid-cols-1 gap-1.5`.
- Cherry-pick: Connect/Connected toggle button pair, label/value stat rows inside a card.
- Notes: per-card primary action switches text+variant by state (`Connect` vs `Connected`).

## Author (`/network/user-cards/author`)
- Purpose: contributor/author profiles with marketplace actions.
- Layout: toolbar + card grid `grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5`, 4 large cards (each contains nested sub-cards — 20 `data-slot=card` total).
- Cherry-pick: two-action card footer (`Work with me` + `View Profile`), card-in-card composition.
- Notes: heaviest card variant in the section; good reference for profile + portfolio combos.

## NFT (`/network/user-cards/nft`)
- Purpose: showcase cards with cover art styling.
- Layout: toolbar + card grid `grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5`, 9 cards; inner `grid grid-cols-1 gap-1.5` stat list.
- Cherry-pick: cover-image card with single `View NFT's` CTA.
- Notes: only the art treatment differs from team-crew; structure is the same.

## Social (`/network/user-cards/social`)
- Purpose: social profile cards with message action.
- Layout: toolbar + card grid `grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5`, 9 cards.
- Cherry-pick: profile card with `Message` CTA and social-link row.
- Notes: closest match for a staff/teacher directory card.

## User-table pages (6 variants, shared shell)

All six share one shell: page header, then a **data table card** (toolbar header, sortable
thead, checkbox column, pagination footer), then the FAQ block (`grid lg:grid-cols-2
gap-5 lg:gap-7.5`). Only the column set differs:

| Page | Columns |
|------|---------|
| `/network/user-table/team-crew` | Member, Role, Status, Location, Activity (+ `Status` filter) |
| `/network/user-table/app-roster` | Users, Phone, Branch, Connected Apps, Tags (+ `Status` filter) |
| `/network/user-table/market-authors` | Author, Earnings, Team, Products, Rating, Social Profiles |
| `/network/user-table/saas-users` | Subscriber, Products, License, Latest Payment, Enforce 2FA (switch), Invoices (Download link) |
| `/network/user-table/store-clients` | Member, Client ID, Orders Value, Location, Activity (+ `Activity` filter) |
| `/network/user-table/visitors` | User, Browser, IP Address, Location, Activity (+ `Activity` filter) |

- Purpose: filterable, sortable user/entity directories.
- Layout: data table card — toolbar (search input + popover filter buttons left; `Filters` primary + `Columns` buttons right), thead with per-column sort dropdowns, body rows with avatar+name+email identity cell, footer with rows-per-page select + range text + pager.
- Cherry-pick: entire data table card shell (snippets below), identity cell (avatar + stacked name/email links), badge chips for tags/products, inline switch column (saas-users), per-row Download link column.
- Notes: thead row is `bg-muted/40`, cells `h-10 px-4 border-e font-normal text-accent-foreground`; body rows `hover:bg-muted/40 data-[state=selected]:bg-muted/50 border-b border-border`.

## Reusable markup snippets

Compact user card (mini-cards), trimmed:

```html
<div data-slot="card" class="rounded-xl bg-card border border-border shadow-xs flex flex-col items-center p-5 lg:py-10">
  <div class="mb-3.5">
    <div class="size-20 relative">
      <img class="rounded-full" src=".../avatars/300-1.png" alt="">
      <div class="flex size-2.5 bg-green-500 rounded-full absolute bottom-0.5 start-16 -translate-y-1/2"></div>
    </div>
  </div>
  <div class="flex items-center justify-center gap-1.5 mb-2">
    <a class="hover:text-primary-active text-base leading-5 font-medium text-mono" href="#">Jenny Klabber</a>
    <svg class="text-primary" width="15" height="16"><!-- verified check --></svg>
  </div>
  <a class="text-secondary-foreground text-sm hover:text-primary-active" href="#">starlight.eth</a>
</div>
```

Data table card toolbar header (saas-users), trimmed:

```html
<div data-slot="card" class="flex flex-col items-stretch rounded-xl bg-card border border-border shadow-xs">
  <div data-slot="card-header" class="flex items-center justify-between flex-wrap px-5 min-h-14 gap-2.5 border-b border-border">
    <div class="flex items-center gap-2.5">
      <div class="relative">
        <svg class="size-4 text-muted-foreground absolute start-3 top-1/2 -translate-y-1/2"><!-- search --></svg>
        <input data-slot="input" class="h-8.5 px-3 rounded-md ps-9 w-40 bg-background border border-input shadow-xs" placeholder="Search Users...">
      </div>
      <button data-slot="popover-trigger" class="h-8.5 rounded-md px-3 gap-1.5 bg-background border border-input hover:bg-accent"><svg/><!-- funnel -->Status</button>
      <button data-slot="popover-trigger" class="h-8.5 rounded-md px-3 gap-1.5 bg-background border border-input hover:bg-accent"><svg/>Sort Order</button>
    </div>
    <!-- right side: primary "Filters" button + "Columns" button -->
  </div>
  <!-- table, then card-footer pagination -->
</div>
```

Sortable thead + identity body cell (saas-users), trimmed:

```html
<thead>
  <tr class="bg-muted/40 [&>th]:border-b">
    <th class="h-10 px-4 border-e align-middle font-normal text-accent-foreground" style="width:51px">
      <button role="checkbox" data-slot="checkbox" class="size-5 rounded-md border border-input" aria-label="Select all"></button>
    </th>
    <th class="h-10 px-4 border-e align-middle font-normal text-accent-foreground" style="width:300px">
      <div class="flex items-center h-full gap-1.5 justify-between">
        <button data-slot="dropdown-menu-trigger" class="-ms-2 px-2 h-7 rounded-md font-normal text-secondary-foreground hover:bg-secondary hover:text-foreground">
          Subscriber <svg class="size-[0.7rem]"><!-- arrow-up = sorted asc --></svg>
        </button>
      </div>
    </th>
  </tr>
</thead>
<tr class="hover:bg-muted/40 data-[state=selected]:bg-muted/50 border-b border-border">
  <td class="align-middle px-4 py-3 border-e">
    <div class="flex items-center gap-2.5">
      <img class="size-7 rounded-full shrink-0" src=".../300-13.png" alt="">
      <div class="flex flex-col">
        <a class="font-medium text-mono hover:text-primary-active mb-px" href="#">Benjamin Harris</a>
        <a class="text-sm text-secondary-foreground hover:text-primary-active" href="#">benjamin.harris@gmail.com</a>
      </div>
    </div>
  </td>
  <td class="align-middle px-4 py-3 border-e">
    <span data-slot="badge" class="bg-secondary text-secondary-foreground rounded-sm px-[0.325rem] h-5 min-w-5 text-[0.6875rem]">NFT</span>
  </td>
</tr>
```

Pagination footer (card-footer), trimmed: `px-5 min-h-14 border-t border-border`, left
"Rows per page" + compact `h-7 px-2.5 text-xs` select, right "1 - 5 of 31" + numbered
page buttons.

## Top patterns worth stealing

- **Data table card shell**: card with `card-header` toolbar (`px-5 min-h-14 border-b`), table, `card-footer` pagination (`px-5 min-h-14 border-t`) — one component handles all six table pages; ideal for GLC staff review queues and admin user lists.
- **Identity cell**: `size-7 rounded-full` avatar + stacked `text-mono` name / `text-sm text-secondary-foreground` email links — works in any people table.
- **Toolbar recipe**: icon-inside search input (`ps-9 w-40 h-8.5`) + bordered popover filter buttons left, primary `Filters` + `Columns` right; count heading ("Showing N Users") on card-grid pages.
- **Sortable column header as dropdown button**: `h-7 -ms-2 px-2 font-normal text-secondary-foreground hover:bg-secondary` with tiny sort-arrow icon, inside a `bg-muted/40` thead row.
- **Compact user card**: centered avatar with `bg-green-500` presence dot + verified-check name row + handle; 4-col grid at `xl`.
- **Card grid spacing constant**: every grid in the section uses `gap-5 lg:gap-7.5` — match it for visual consistency.
