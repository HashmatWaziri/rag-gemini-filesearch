# Metronic Demo 7 — Account section (settings & self-service)

Catalog of the 34 `/account/*` pages (base `https://keenthemes.com/metronic/tailwind/react/demo7`).
Every page shares the same shell: toolbar with H1 + breadcrumb (left) and Export button +
date-range picker (right), content inside a `max-w-[1320px]` container.

Two recurring page skeletons (referenced below as "main + FAQ rail" and "bottom FAQ row"):

- **Main + FAQ rail**: `grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-7.5` — main column spans 2,
  right rail stacks an FAQ accordion card plus "Questions? / Contact Support" promo cards.
- **Bottom FAQ row**: full-width main content, then `grid lg:grid-cols-2 gap-5 lg:gap-7.5` with
  the FAQ card and contact card side by side.

Screenshots: `.firecrawl/metronic/account/{settings-sidebar,billing-plans,permissions-toggle,notifications}.png`

## Get Started (`/account/home/get-started`)
- Purpose: feature launcher for the whole account area.
- Layout: card grid `grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-7.5`, 11 tiles.
- Cherry-pick: feature tile launcher grid.
- Notes: each tile is a `[data-slot=card]` with icon, title, blurb, link.

## User Profile / Company Profile (`/account/home/user-profile`, `/account/home/company-profile`)
- Purpose: personal vs. company detail editing.
- Layout: user = 2-col card masonry `grid grid-cols-1 xl:grid-cols-2 gap-5 lg:gap-7.5` (8 cards, 4 label/value tables); company = main + FAQ rail (6 cards, 3 tables).
- Cherry-pick: label/value detail table inside a card, stacked detail cards.
- Notes: detail rows are plain `<table>`s (label column + value column + edit action), not forms.

## Settings — With Sidebar (`/account/home/settings-sidebar`)
- Purpose: long settings form, one page, 13 sections.
- Layout: two-column: anchor-nav sidebar (`w-[230px] shrink-0`) + stacked cards in `flex grow gap-5 lg:gap-7.5`; 13 cards, 24 inputs, 16 switches.
- Cherry-pick: anchor-nav sidebar with scrollspy (snippet below), card-per-section settings form, label-left/control-right form rows.
- Notes: nav items are `div[data-scrollspy-anchor=<id>]`; active state via `data-active=true` → `bg-accent text-primary`; vertical guide line drawn with a `before:` pseudo-element on the nav column.

## Settings — Enterprise / Plain / Modal (`/account/home/settings-*`)
- Purpose: alternative settings page shapes.
- Layout: enterprise = dense 2-col card masonry `grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5` (12 cards, 5 tables); plain = single column, 4 stacked cards (general, file upload w/ progress, password, delete account); modal = profile-hero page (avatar, tab strip) where editing opens dialogs.
- Cherry-pick: 2-col settings masonry, single-column minimal settings, danger-zone "Delete Account" card.
- Notes: enterprise mixes form cards with small data tables (files, payment history) in one masonry.

## Billing — Basic / Enterprise (`/account/billing/basic`, `/account/billing/enterprise`)
- Purpose: current plan + payment self-service.
- Layout: basic = main + FAQ rail: plan summary card (H2 plan name, usage), payment methods list, billing details, invoices table; enterprise = 2-col `grid grid-cols-1 lg:grid-cols-2` with "Latest Payment" / "Next Payment" facts cards + payment methods + invoicing table, bottom FAQ row.
- Cherry-pick: plan summary card, facts strip (latest/next payment), invoices data table.
- Notes: invoice tables use badge cells for status.

## Plans (`/account/billing/plans`)
- Purpose: plan comparison & upgrade.
- Layout: single full-width comparison data table (`table-fixed border-separate border-spacing-0 min-w-[1000px]`), 12 rows; bottom FAQ row.
- Cherry-pick: plan comparison table header (snippet below), annual/monthly price toggle.
- Notes: no `<thead>` — first `<tr>` holds the "Annual Billing" switch cell + one header-like `<td>` per plan; current-plan column tinted `bg-muted/40`; "Current Plan" badge absolutely positioned overlapping the top border; prices swap via `data-plan-price-regular` / `data-plan-price-annual` attributes.

## Billing History (`/account/billing/history`)
- Purpose: invoice archive.
- Layout: one full-width card with a data table ("Billing and Invoicing").
- Cherry-pick: plain table-in-card page.
- Notes: simplest page in the section; good baseline for index pages.

## Security — Get Started / Overview (`/account/security/...`)
- Purpose: security launcher and status summary.
- Layout: get-started = 3-col feature tile grid (7 tiles); overview = main + FAQ rail with toggle-list cards (Authentication: 6 switches), Login Sessions table, Trusted Devices table, tips card with `grid md:grid-cols-2 gap-2` checklist.
- Cherry-pick: status summary cards with embedded toggle rows and small tables.
- Notes: overview mixes 3 tables + 9 cards on one screen without feeling dense.

## Security tables (`allowed-ip-addresses`, `device-management`, `current-sessions`, `security-log`)
- Purpose: CRUD/audit tables for security records.
- Layout: each is one full-width data-table card + bottom FAQ row; allowed-ip adds an enable switch in the card header and a row-add dialog.
- Cherry-pick: data table with inline row actions, card-header switch, revoke/delete confirm dialogs.
- Notes: same table card recipe across all four — only columns change.

## Privacy Settings / Backup & Recovery (`/account/security/...`)
- Purpose: preference toggles and data management.
- Layout: main + FAQ rail; privacy = toggle-list cards ("Report Sharing", "Manage your Data", "Block List", 6 switches); backup = recovery option cards with 2 switches.
- Cherry-pick: toggle-list settings card (same row pattern as Notifications, snippet below).
- Notes: rows are `card-content` blocks separated by `border-b border-border`.

## Members — starters (`team-starter`, `members-starter`)
- Purpose: empty-state / onboarding screens.
- Layout: hero card (H2 headline, illustration, CTA) + bottom FAQ row.
- Cherry-pick: empty-state onboarding card.
- Notes: useful as the "no data yet" page template.

## Teams / Team Info / Team Members (`/account/members/...`)
- Purpose: team CRUD and membership.
- Layout: teams = data-table card of teams + bottom FAQ row; team-info = main + FAQ rail with Team Info detail table, Seats meter card ("14/49 Seats"), connected-profiles card; team-members = two invite cards ("Invite People", "Invite with Link") above a members data table.
- Cherry-pick: seats/usage meter card, invite-people + invite-link card pair, members table with role column.
- Notes: invite cards reappear verbatim on `/account/invite-a-friend`.

## Import Members (`/account/members/import-members`)
- Purpose: bulk import flow.
- Layout: main + FAQ rail; "Start Import" upload card.
- Cherry-pick: upload/dropzone card.
- Notes: single-step, not a wizard.

## Roles (`/account/members/roles`)
- Purpose: role catalog.
- Layout: card grid `grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-7.5`, 8 role cards + "add role" tile.
- Cherry-pick: role card with permission summary + member avatars.
- Notes: same grid recipe as the get-started launchers.

## Permissions — Toggler / Check (`/account/members/permissions-*`)
- Purpose: per-role permission matrix.
- Layout: toggler = one card containing `grid grid-cols-1 lg:grid-cols-2 gap-5` of bordered permission tiles (11 switches), footer "New Permission" button, then a Team Members data table; check = main + FAQ rail with checkbox table variant.
- Cherry-pick: permission tile (hexagon icon + title + description + switch), permission checkbox table.
- Notes: tile = `rounded-xl border p-4 flex items-center justify-between gap-2.5`; hexagon icon frame is an SVG with `stroke-input fill-muted/30` + centered lucide icon.

## Integrations (`/account/integrations`)
- Purpose: third-party connections.
- Layout: 3-col card grid of integration cards (logo, blurb, connect switch — 8 switches), "Add New Integration" banner, bottom FAQ row.
- Cherry-pick: integration card with connect switch.
- Notes: same toggle component everywhere (`h-5 w-8`, `data-[state=checked]:bg-primary`).

## Notifications (`/account/notifications`)
- Purpose: channel and event notification preferences.
- Layout: main + FAQ rail; main = "Notification Channels" toggle-list card (Email/Mobile/Slack/Desktop rows) + "Other Notifications" card; rail = "Do Not Disturb" card with "Pause Notifications" footer button + promo cards.
- Cherry-pick: toggle-list card row (snippet below), card-header label+switch ("Team-Wide Alerts"), Do Not Disturb card.
- Notes: rows mix trailing controls — switch, edit icon-button, or a regular button ("Connect Slack", "View Invoices") in the same row recipe.

## API Keys (`/account/api-keys`)
- Purpose: key management and webhooks.
- Layout: main + FAQ rail; Public API Key card (copy input), API Integrations toggle list, Webhooks card, Project API keys data table (13 switches total).
- Cherry-pick: copy-to-clipboard input row, keys table with masked values.
- Notes: combines every control type in the section on one page.

## Appearance (`/account/appearance`)
- Purpose: theme & branding preferences.
- Layout: main + FAQ rail; Theme picker cards with preview images in `grid grid-cols-1 lg:grid-cols-3`, Theme-mode radio cards, Branding upload card, Accessibility toggle card.
- Cherry-pick: image-preview radio cards for theme selection.
- Notes: "Disable default Branding" is a switch row appended to the branding card.

## Invite a Friend (`/account/invite-a-friend`)
- Purpose: referral invites.
- Layout: main + FAQ rail; "Invite People" form card (email + role select), "Invite with Link" copy-link card, invited-users table.
- Cherry-pick: invite form card, copy-link row.
- Notes: identical cards to team-members — one component reused.

## Activity (`/account/activity`)
- Purpose: account activity timeline.
- Layout: main + rail (`grid lg:grid-cols-2` bottom row); main = timeline of ~17 cards grouped under repeated "Activity" headings; rail = year filter with per-year switches.
- Cherry-pick: timeline with date-grouped cards, year-filter switch list.
- Notes: heaviest page in the section (17 cards).

## Top patterns worth stealing

- **Anchor-nav sidebar with scrollspy** (settings-sidebar): `w-[230px] shrink-0` rail beside `flex grow` card stack; active item highlight + pseudo-element guide line. Best pattern here for any long staff-settings or review page.
- **Toggle-list card row**: icon frame + title/subtitle stack + trailing switch/button, rows divided by `border-b border-border` — used on notifications, privacy, integrations, api-keys, security overview.
- **Main + FAQ rail skeleton**: `grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-7.5` with main spanning 2 — the default account page shape; rail degrades below `xl` by stacking.
- **Plan comparison table**: header row inside `<tbody>`, current-plan column tint `bg-muted/40`, overlapping "Current Plan" badge, price swap via data attributes driven by one switch.
- **Card grid launcher**: `grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-7.5` reused for get-started tiles, roles, integrations.
- **Card header with title + inline control**: `flex items-center justify-between flex-wrap px-5 min-h-14 border-b border-border` — title left, switch/label or button right.
- **Empty-state onboarding card** (team/members starters): headline + illustration + CTA, ready-made for "no data yet" pages.
- **Permission tile**: self-contained `rounded-xl border p-4` flex row with switch — denser alternative to full-width toggle rows.

### Snippet: anchor-nav sidebar with scrollspy (settings-sidebar)

```html
<div class="flex grow gap-5 lg:gap-7.5">
  <div class="w-[230px] shrink-0">
    <!-- nav column draws the vertical guide line via before: -->
    <div class="flex flex-col grow relative before:absolute before:start-[11px] before:top-0 before:bottom-0 before:border-s before:border-border text-sm">
      <!-- one item per section; data-active=true set by scrollspy -->
      <div data-scrollspy-anchor="basic_settings" data-active="true"
           class="cursor-pointer flex items-center rounded-lg ps-2.5 pe-2.5 py-1.5 border border-transparent text-accent-foreground hover:text-primary data-[active=true]:bg-accent data-[active=true]:text-primary data-[active=true]:font-medium gap-1.5">
        <span class="flex w-1.5 relative before:absolute start-px before:top-0 before:size-1.5 before:rounded-full before:-translate-x-2/4 before:-translate-y-2/4 [[data-active=true]>&]:before:bg-primary"></span>
        Basic Settings
      </div>
      <!-- ...12 more items, group headings are plain font-medium rows... -->
    </div>
  </div>
  <div class="flex flex-col gap-5 lg:gap-7.5 grow"><!-- section cards, ids match anchors --></div>
</div>
```

### Snippet: toggle-list card row (notifications channel card)

```html
<div data-slot="card" class="flex flex-col items-stretch text-card-foreground rounded-xl bg-card border border-border shadow-xs">
  <div data-slot="card-header" class="flex items-center justify-between flex-wrap px-5 min-h-14 border-b border-border gap-2">
    <h3 data-slot="card-title" class="text-base font-semibold leading-none tracking-tight">Notification Channels</h3>
    <div class="flex items-center gap-2">
      <label class="font-medium text-sm">Team-Wide Alerts</label>
      <button role="switch" data-state="unchecked" class="rounded-full h-5 w-8 data-[state=unchecked]:bg-input data-[state=checked]:bg-primary">...</button>
    </div>
  </div>
  <!-- one row per channel -->
  <div data-slot="card-content" class="grow p-5 border-b border-border flex items-center justify-between py-4 gap-2.5">
    <div class="flex items-center gap-3.5">
      <div class="relative size-[50px] shrink-0">
        <svg class="w-full h-full stroke-input fill-muted/30"><!-- hexagon frame --></svg>
        <div class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4"><!-- lucide icon, text-muted-foreground --></div>
      </div>
      <div class="flex flex-col gap-0.5">
        <span class="leading-none font-medium text-sm text-mono">Email</span>
        <span class="text-sm text-secondary-foreground">jamescollins@ktstudio.com</span>
      </div>
    </div>
    <div class="flex items-center gap-5">
      <!-- optional edit icon-button -->
      <button role="switch" data-state="checked" class="rounded-full h-5 w-8 data-[state=checked]:bg-primary">...</button>
    </div>
  </div>
</div>
```

### Snippet: plan comparison table header row (billing/plans)

```html
<table class="w-full text-sm table-fixed border-separate border-spacing-0 min-w-[1000px] rounded-xl
              [&_tr:nth-of-type(2)>td]:border-t ltr:[&_tr:nth-of-type(2)>td:first-child]:rounded-tl-xl">
  <tbody>
    <tr data-slot="table-row">
      <!-- corner cell: billing-period toggle -->
      <td class="p-5! pt-7.5! pb-6! align-bottom border-b-0!">
        <div class="flex items-center space-x-2">
          <button role="switch" data-state="checked" class="rounded-full h-5 w-8 data-[state=checked]:bg-primary">...</button>
          <label class="font-medium text-sm">Annual Billing</label>
        </div>
      </td>
      <!-- current plan column: tinted + overlapping badge -->
      <td class="relative! border-t ltr:border-l bg-muted/40 p-5! pt-7.5!">
        <span data-slot="badge" class="absolute top-0 start-1/2 -translate-x-1/2 -translate-y-1/2 rounded-md px-[0.45rem] h-6 text-xs">Current Plan</span>
        <h3 class="text-lg text-mono font-medium pb-2">Basic</h3>
        <div class="text-secondary-foreground text-sm">Essential features for startups individuals</div>
        <div class="py-4"><h4 class="text-2xl text-mono font-semibold leading-none">Free</h4></div>
        <div><button class="w-full justify-center h-8.5 rounded-md border border-input">Switch to Team</button></div>
      </td>
      <!-- other plans: same cell minus tint; price swaps via data attrs -->
      <td class="border-t ltr:border-l p-5! pt-7.5!">
        <h3 class="text-lg text-mono font-medium pb-2">Pro</h3>
        <div class="flex items-end gap-1.5" data-plan-type="pro">
          <div class="text-2xl text-mono font-semibold leading-none" data-plan-price-regular="$99" data-plan-price-annual="$79">$99</div>
          <div class="text-secondary-foreground text-xs">per month</div>
        </div>
        <button class="w-full justify-center h-8.5 rounded-md bg-primary text-primary-foreground">Upgrade</button>
      </td>
      <!-- ...Premium, Enterprise... -->
    </tr>
    <!-- feature rows: label td + one td per plan (text or check icon) -->
  </tbody>
</table>
```
