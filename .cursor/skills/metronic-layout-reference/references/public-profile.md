# Metronic Demo 7 — Public profile section + root dashboard

Catalog of the 19 `/public-profile/*` pages plus the root dashboard (`/`) of the
Metronic React Demo 7 theme (base `https://keenthemes.com/metronic/tailwind/react/demo7`,
demo login required). Verified against the live demo, logged-in, 1600px viewport.
Screenshots and raw batch summaries live in `.firecrawl/metronic/profile/`.
Classes quoted below are the demo's raw Tailwind; do not invent semantic-token mappings.

## Dashboard (`/`)
- Purpose: Demo 7 landing dashboard; defines the shell every other page inherits.
- Layout: header-first shell (no sidebar). Top header `lg:h-(--header-height)` with
  `[--header-height-default:95px]` shrinking to 60px on sticky
  (`data-[sticky-header=on]:[--header-height:60px]`, `transition-[height]`). Inside it one
  container `w-full mx-auto px-4 lg:px-6 max-w-[1320px]` (`data-slot="container"`, reused for
  every content band): logo, mega-menu (menubar: Home, Profiles, My Account, Network, Store,
  Authentication — dropdown panels), right cluster (usage counter, Upgrade button, avatar).
  Below: title/toolbar band (h1 + breadcrumbs left; Export button + date-range picker right).
  Body: `grid lg:grid-cols-3 gap-y-5 lg:gap-7.5` — 2/3 left as `grid md:grid-cols-2` feature
  cards, 1/3 right rail (My Balance chart card with `grid grid-cols-4` Today/Week/Month/Year
  toggle, Report Sharing radio list, Integrations, Block List, Teams). Plus a full-width CTA
  banner with avatar cluster.
- Cherry-pick: header-height CSS-var sticky trick, single `max-w-[1320px]` container, toolbar band, 2/3 + 1/3 dashboard split, segmented period toggle.
- Notes: every band (header, toolbar, content) repeats the same container div; 11 `data-slot=card` cards on this page alone.

## Profile variants (`/public-profile/profiles/*`)
All ten share the same skeleton: hero band (hexagon-pattern bg image, centered avatar
`size-[100px] rounded-full border-3 border-green-500`, name `text-lg font-semibold text-mono`
+ verified icon `text-primary`, meta row of icon+text pairs `text-secondary-foreground`),
then a tab nav strip (underline tabs + right action cluster: primary Connect button, icon
buttons), then a body grid `grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-7.5` split into
`col-span-1` sidebar + `col-span-2` main. Markup extracted below.

- `default` — reference variant. Sidebar: Community Badges, About (label/value table), Work Experience, Skills chips, Recent Uploads. Main: CTA banner card, Media Uploads chart, Contributors, Projects tables.
- `creator` — same split; main is blog-style feature post + Upcoming Events + Activities; sidebar adds Members card. 15 cards.
- `company` — `lg:grid-cols-3` with a full-width `col-span-1 lg:col-span-3` band; sidebar: Highlights, Open Jobs, Network, Tags; main: Company Profile, Headquarter/Locations (map), Projects + Members + Investments tables.
- `nft` — gallery-heavy: Created / Collected / 3d Art image grids (`grid grid-cols-2`), Assets + Badges sidebar; 22 cards, most card-dense variant.
- `blogger` — main = post feed ("Jenny's Posts", "Jenny's Replies", Activity); sidebar Profile/Collaborate/Skills.
- `crm` — contact-record flavor. Sidebar: General Info (label/value + status badge), Attributes, API Credentials, Skills. Main: Deals card with table (amount, status badge, duration, kebab) + "All Deals" footer link, Recent Activity timeline with "Auto refresh" switch, Recent Invoices. 3 tables.
- `gamer` — stats strip (`grid grid-cols-3 gap-2`), Favorite Games, Badges, team roster, tournaments table, Now Playing.
- `feeds` — main = post feed with composer (2 inputs); sidebar Profile / Open to work / Skills.
- `plain` — minimal `lg:grid-cols-2`: About Me text card + Profile/Skills; 6 cards, no hero stats.
- `modal` — `default` clone where editing happens in dialogs (modal contains `grid lg:grid-cols-3 gap-3` form layout).

Entry template for the family:
- Purpose: public profile pages for persons/companies in ten content flavors.
- Layout: hero band + tab nav + 1/3–2/3 two-column body (`xl:grid-cols-3`, sidebar `col-span-1`, main `col-span-2`).
- Cherry-pick: hero band, tab nav, About label/value table, deals/invoices card-with-table, feed card, gallery grid.
- Notes: gaps are consistently `gap-5 lg:gap-7.5`; sidebar cards stack with `space-y`/grid gap, never sticky.

## Projects (`/public-profile/projects/2-columns`, `/3-columns`)
- Purpose: project portfolio grids.
- Layout: count heading ("6 Projects" / "12 Projects") + sort control, then card grid — 2-col: `grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5`; 3-col: `grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-7.5`.
- Cherry-pick: project card (logo + status badge, title + subtitle, start/end facts row, progress bar, avatar group) — markup below.
- Notes: same card in both densities; 3-col simply drops padding-heavy content. Count-heading + grid is the recurring listing pattern across this whole section.

## Campaigns (`/public-profile/campaigns/card`, `/list`)
- Purpose: same campaign dataset in two densities.
- Layout: card view = 2-col card grid (`lg:grid-cols-2`); list view = stacked full-width rows (no grid wrapper), one card per campaign with horizontal logo/title/stats/actions.
- Cherry-pick: card-vs-list toggle for the same collection, statistics strip inside cards.
- Notes: both render 6 `data-slot=card` items; list rows are just the card with a horizontal flex body.

## Activity (`/public-profile/activity`)
- Purpose: full activity timeline page.
- Layout: hero band + tab nav, page title, then one wide Activity card ("Auto refresh" switch in header) holding a vertical timeline; a year rail (2025…2018) floats at the right edge as a jump nav.
- Cherry-pick: timeline item (icon bullet + absolute connector line + text/timestamp, optional embedded event card with date block) — markup below.
- Notes: connector is `border-s border-s-input` absolutely positioned behind `size-9` icon bullets; embedded sub-cards (event with `Apr 02` date block) nest inside an item's content column.

## Network (`/public-profile/network`)
- Purpose: connections directory.
- Layout: "6 Connections" count heading + 2-col card grid (`lg:grid-cols-2`); each card = avatar, name, stats rows (`grid grid-cols-1 gap-1.5`), connect actions.
- Cherry-pick: person card with stats rows.

## Teams (`/public-profile/teams`)
- Purpose: teams directory.
- Layout: "9 Teams" + 3-col card grid (`grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-7.5`); team cards with icon, description, rating, avatar group.
- Cherry-pick: team card, avatar group.

## Works (`/public-profile/works`)
- Purpose: portfolio of works/screenshots.
- Layout: "8 Works" + same 3-col card grid as Teams; image-top cards with meta footer.
- Cherry-pick: image-top media card.

## Empty (`/public-profile/empty`)
- Purpose: empty-state template for any profile sub-page.
- Layout: hero band + tab nav, then a single card with centered illustration, message, and CTA.
- Cherry-pick: empty-state card.

## Top patterns worth stealing
- Hero band: full-bleed bg-image band + centered avatar/name/meta column, both hero and tab nav reusing the one `w-full mx-auto px-4 lg:px-6 max-w-[1320px]` container.
- Tab nav: menubar items `px-3 py-3.5 text-sm border-b-2 border-transparent data-[here=true]:text-primary data-[here=true]:border-primary` sharing a single `border-b border-border` wrapper with a right-aligned action cluster (`justify-between`).
- 1/3–2/3 body split: `grid grid-cols-1 xl:grid-cols-3` + `col-span-1`/`col-span-2`, gaps always `gap-5 lg:gap-7.5`.
- Timeline: absolutely-positioned `border-s border-s-input` connector behind `size-9` rounded icon bullets; content column `ps-2.5 mb-7 grow` so spacing lives on the item, not the list.
- Project/campaign card anatomy: logo-in-tile (`size-[50px] rounded-lg bg-accent/60`) + status badge, title + muted subtitle, facts row, `h-1.5` progress bar, overlapping avatar group `flex -space-x-2`.
- Card grid step-up: `grid-cols-1 lg:grid-cols-2 xl:grid-cols-3` for directories (teams/works/projects-3col); drop `xl` for 2-col listings.
- Listing header: count heading ("12 Projects") + sort select above every grid — cheap, consistent toolbar.
- Header-first shell: sticky header shrink via CSS vars (`[--header-height-default:95px]`, `data-[sticky-header=on]:[--header-height:60px]`, `lg:h-(--header-height)` + `transition-[height]`).

### Extracted markup (trimmed)

Profile hero band + tab nav (`/public-profile/profiles/default`):

```html
<div class="bg-center bg-cover bg-no-repeat hero-bg" style="background-image:url(bg-1.png)">
  <div class="w-full mx-auto px-4 lg:px-6 max-w-[1320px]">
    <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
      <img class="rounded-full border-3 border-green-500 size-[100px] shrink-0" src="avatar.png">
      <div class="flex items-center gap-1.5">
        <div class="text-lg leading-5 font-semibold text-mono">Jenny Klabber</div>
        <svg class="text-primary"><!-- verified check --></svg>
      </div>
      <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
        <div class="flex gap-1.25 items-center">
          <svg class="text-muted-foreground"><!-- lucide icon --></svg>
          <span class="text-secondary-foreground font-medium">KeenThemes</span>
        </div>
        <!-- repeat: location, email -->
      </div>
    </div>
  </div>
</div>
<div class="w-full mx-auto px-4 lg:px-6 max-w-[1320px]">
  <div class="flex items-center flex-wrap md:flex-nowrap lg:items-end justify-between border-b border-border gap-3 lg:gap-6 mb-5 lg:mb-10">
    <div role="menubar" class="flex items-stretch gap-3 border-none bg-transparent p-0 h-auto">
      <a class="flex items-center px-3 py-3.5 text-sm font-medium text-secondary-foreground rounded-none border-b-2 border-transparent hover:text-primary data-[here=true]:text-primary data-[here=true]:border-primary" href="/public-profile/works">Works</a>
      <!-- repeat per tab; dropdown tabs are <button> with chevron svg -->
    </div>
    <div class="flex items-center justify-end grow lg:grow-0 lg:pb-4 gap-2.5 mb-1.5 lg:mb-0">
      <button class="bg-primary text-primary-foreground hover:bg-primary/90 h-8.5 rounded-md px-3 gap-1.5 text-[0.8125rem] inline-flex items-center font-medium">Connect</button>
      <!-- icon-only ghost buttons -->
    </div>
  </div>
</div>
```

Timeline item (`/public-profile/activity`):

```html
<div class="flex items-start relative">
  <div class="w-9 start-0 top-9 absolute bottom-0 translate-x-1/2 rtl:-translate-x-1/2 border-s border-s-input"></div>
  <div class="flex items-center justify-center shrink-0 rounded-full bg-accent/60 border border-input size-9 text-secondary-foreground">
    <svg><!-- lucide icon --></svg>
  </div>
  <div class="ps-2.5 mb-7 text-base grow">
    <div class="flex flex-col">
      <div class="text-sm text-foreground">Posted a new article <a class="text-primary font-medium" href="...">Top 10 Tech Trends</a></div>
      <span class="text-xs text-secondary-foreground">Today, 9:00 AM</span>
    </div>
    <!-- optional embedded event/media card nests here -->
  </div>
</div>
```

Project card (`/public-profile/projects/3-columns`):

```html
<div data-slot="card" class="flex flex-col items-stretch rounded-xl bg-card border border-border shadow-xs p-7.5">
  <div class="flex items-center justify-between mb-3 lg:mb-6">
    <div class="flex items-center justify-center size-[50px] rounded-lg bg-accent/60"><img src="brand-logo.svg"></div>
    <span data-slot="badge" class="inline-flex items-center rounded-md px-[0.5rem] h-7 text-xs font-medium">In Progress</span>
  </div>
  <div class="flex flex-col mb-3 lg:mb-6">
    <a class="text-lg text-mono hover:text-primary-active mb-px" href="...">Phoenix SaaS</a>
    <span class="text-sm text-secondary-foreground">Real-time photo sharing app</span>
  </div>
  <div class="flex items-center gap-5 mb-3.5 lg:mb-7">
    <span class="text-sm text-secondary-foreground">Start: <span class="font-medium text-foreground">Mar 06</span></span>
    <span class="text-sm text-secondary-foreground">End: <span class="font-medium text-foreground">Dec 21</span></span>
  </div>
  <div data-slot="progress" class="relative w-full overflow-hidden rounded-full bg-secondary h-1.5 mb-4 lg:mb-8">
    <div data-slot="progress-indicator" class="h-full w-full bg-primary" style="transform:translateX(-45%)"></div>
  </div>
  <div class="flex -space-x-2">
    <span data-slot="avatar" class="relative flex shrink-0 size-[30px]">
      <div class="relative overflow-hidden rounded-full border-1 border-background hover:z-10"><img class="aspect-square h-full w-full" src="300-4.png"></div>
    </span>
    <!-- repeat avatars; fallback: -->
    <span data-slot="avatar-fallback" class="flex items-center justify-center rounded-full border-1 border-background text-[11px] size-[30px] text-primary-foreground bg-primary">S</span>
  </div>
</div>
```
