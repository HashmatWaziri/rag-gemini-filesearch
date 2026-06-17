# Metronic React Demo 7 — full layout URL index

Base: `https://keenthemes.com/metronic/tailwind/react/demo7`
All pages except `auth/*` and `error/*` require the demo login (`demo@kt.com` / `demo123`).
Harvested from the live mega-menu (logged-in session). Paths below are relative to the base.

## Dashboard

| Path | Layout |
|------|--------|
| `/` | Demo 7 landing/dashboard shell (header-first, mega-menu, toolbar) |

## Store — client (e-commerce flows)

| Path | Layout |
|------|--------|
| `/store-client/home` | Storefront home: category tiles, product card grids, promo banners |
| `/store-client/search-results-grid` | Filter sidebar + product card grid + toolbar (sort, view toggle) |
| `/store-client/search-results-list` | Filter sidebar + horizontal product list rows |
| `/store-client/product-details` | Gallery + buy box, spec tabs, related items |
| `/store-client/wishlist` | Saved-items list with row actions |
| `/store-client/my-orders` | Repeating order blocks: facts strip + item rows per order |
| `/store-client/order-receipt` | Centered receipt card, gradient top border, facts strip |
| `/store-client/checkout/order-summary` | Checkout step 1: pill stepper + items + price sidebar |
| `/store-client/checkout/shipping-info` | Checkout step 2: address cards grid + price sidebar |
| `/store-client/checkout/payment-method` | Checkout step 3: payment options + price sidebar |
| `/store-client/checkout/order-placed` | Checkout done: all-steps-complete stepper, facts strip |

## Account (settings & self-service)

| Path | Layout |
|------|--------|
| `/account/home/get-started` | Feature tile launcher grid |
| `/account/home/user-profile` | Personal settings: stacked detail cards |
| `/account/home/company-profile` | Company settings variant |
| `/account/home/settings-sidebar` | Settings with sticky anchor-nav sidebar (scrollspy) |
| `/account/home/settings-enterprise` | Dense enterprise settings, grouped sections |
| `/account/home/settings-plain` | Single-column plain settings |
| `/account/home/settings-modal` | Settings edited via dialogs/modals |
| `/account/billing/basic` | Plan summary card + payment methods + invoices table |
| `/account/billing/enterprise` | Enterprise billing variant |
| `/account/billing/plans` | Plan comparison/pricing table with toggle |
| `/account/billing/history` | Invoice history data table |
| `/account/security/get-started` | Security feature launcher |
| `/account/security/overview` | Security status summary cards |
| `/account/security/allowed-ip-addresses` | CRUD table with inline actions |
| `/account/security/privacy-settings` | Toggle-list settings card |
| `/account/security/device-management` | Device sessions table with revoke actions |
| `/account/security/backup-and-recovery` | Recovery options cards |
| `/account/security/current-sessions` | Active sessions table |
| `/account/security/security-log` | Audit-log style table |
| `/account/members/team-starter` | Empty-state / onboarding card |
| `/account/members/teams` | Teams card grid |
| `/account/members/team-info` | Team detail layout |
| `/account/members/members-starter` | Members empty state |
| `/account/members/team-members` | Members table with roles and actions |
| `/account/members/import-members` | Import wizard / upload flow |
| `/account/members/roles` | Role cards with permission summaries |
| `/account/members/permissions-toggle` | Permission matrix with toggles |
| `/account/members/permissions-check` | Permission matrix with checkboxes |
| `/account/integrations` | Integration cards grid with connect toggles |
| `/account/notifications` | Notification channel/preference matrix |
| `/account/api-keys` | API keys table + create flow |
| `/account/appearance` | Theme/appearance options with previews |
| `/account/invite-a-friend` | Invite form + referral list |
| `/account/activity` | Activity timeline |

## Network (user directories)

| Path | Layout |
|------|--------|
| `/network/get-started` | Network feature launcher |
| `/network/user-cards/mini-cards` | Compact user card grid |
| `/network/user-cards/team-crew` | Team member cards |
| `/network/user-cards/author` | Author/contributor cards |
| `/network/user-cards/nft` | Showcase cards (NFT styling) |
| `/network/user-cards/social` | Social profile cards |
| `/network/user-table/team-crew` | User data table variant |
| `/network/user-table/app-roster` | Roster table with status columns |
| `/network/user-table/market-authors` | Authors table with metrics |
| `/network/user-table/saas-users` | SaaS users table (plan, usage columns) |
| `/network/user-table/store-clients` | Clients table (orders, spend columns) |
| `/network/user-table/visitors` | Visitors table (sessions, sources) |

## Public profile

| Path | Layout |
|------|--------|
| `/public-profile/profiles/default` | Profile hero + two/three-column body |
| `/public-profile/profiles/creator` | Creator profile variant |
| `/public-profile/profiles/company` | Company profile variant |
| `/public-profile/profiles/nft` | NFT showcase profile |
| `/public-profile/profiles/blogger` | Blogger profile (post cards) |
| `/public-profile/profiles/crm` | CRM contact profile (activity + details) |
| `/public-profile/profiles/gamer` | Gamer profile variant |
| `/public-profile/profiles/feeds` | Feed-centric profile |
| `/public-profile/profiles/plain` | Minimal profile |
| `/public-profile/profiles/modal` | Profile with modal-driven editing |
| `/public-profile/projects/2-columns` | Project cards, 2-col grid |
| `/public-profile/projects/3-columns` | Project cards, 3-col grid |
| `/public-profile/campaigns/card` | Campaign card grid |
| `/public-profile/campaigns/list` | Campaign list rows |
| `/public-profile/activity` | Timeline page |
| `/public-profile/network` | Connections grid |
| `/public-profile/teams` | Teams grid |
| `/public-profile/works` | Works/portfolio grid |
| `/public-profile/empty` | Empty-state page |

## Authentication & errors (no login needed)

| Path | Layout |
|------|--------|
| `/auth/signin` | Branded split: form + gradient brand panel |
| `/auth/signup` | Branded signup |
| `/auth/2fa` | Branded 2FA code entry |
| `/auth/check-email` | Branded confirmation notice |
| `/auth/reset-password` | Branded reset request |
| `/auth/reset-password/check-email` | Branded reset notice |
| `/auth/reset-password/changed` | Branded reset success |
| `/auth/classic/signin` | Classic centered-card signin |
| `/auth/classic/signup` | Classic signup |
| `/auth/classic/2fa` | Classic 2FA |
| `/auth/classic/check-email` | Classic notice |
| `/auth/classic/reset-password` | Classic reset request |
| `/auth/classic/reset-password/check-email` | Classic reset notice |
| `/auth/classic/reset-password/changed` | Classic reset success |
| `/auth/welcome-message` | Post-signup welcome screen |
| `/auth/account-deactivated` | Deactivation notice |
| `/error/404` | 404 page |
| `/error/500` | 500 page |
