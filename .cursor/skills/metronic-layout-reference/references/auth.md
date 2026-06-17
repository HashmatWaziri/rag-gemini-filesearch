# Metronic Demo 7 — Authentication & error pages

18 pages: two parallel auth flows (**branded** split-grid at `/auth/*`, **classic**
centered-card at `/auth/classic/*`), two notice dialogs, two error pages. No login
needed. Both flows reuse the exact same form card (`max-w-[400px]`) — only the page
wrapper differs, so one `<AuthCard>` component can serve both.

Screenshots: `.firecrawl/metronic/network-auth/auth-signin.png`, `classic-signin.png`.

## Branded flow (`/auth/...`) — split grid

Wrapper: `grid lg:grid-cols-2 grow`. Left column (`order-2 lg:order-1`) centers the form
card with `flex justify-center items-center p-8 lg:p-10`; right column is a brand panel
(logo, H3 "Secure Dashboard Access", supporting copy, layered dashboard screenshot
collage on a light gradient). On mobile the brand panel stacks above the form.

- **Sign In (`/auth/signin`)** — Purpose: credential login. Layout: split grid; card = title/subtitle, info alert, Google button, "or" divider, email + password inputs, remember-me + forgot link row, primary submit, signup footer link. Cherry-pick: full form-card composition, divider-with-label, password input with inline eye toggle.
- **Sign Up (`/auth/signup`)** — Purpose: registration. Layout: split grid; card adds firstName/lastName pair, confirmPassword, terms checkbox; `Create Account` submit. Cherry-pick: two-column name field row inside the 400px card.
- **2FA (`/auth/2fa`)** — Purpose: code entry ("Verify your phone"). Layout: split grid; card with 6 single-character text inputs + `Continue`. Cherry-pick: 6-box OTP input row.
- **Check Email (`/auth/check-email`)** — Purpose: confirmation notice. Layout: split grid; static card (icon, "Check your email" H3, copy, link) — no inputs.
- **Reset Password (`/auth/reset-password`)** — Purpose: request reset link. Layout: split grid; single email input + `Send Reset Link`.
- **Reset Check Email (`/auth/reset-password/check-email`)** — Purpose: reset-sent notice. Layout: split grid; static notice card.
- **Password Changed (`/auth/reset-password/changed`)** — Purpose: success notice ("Your password is changed"). Layout: split grid; static notice card.

## Classic flow (`/auth/classic/...`) — centered card

Wrapper: `flex flex-col items-center justify-center grow bg-center bg-no-repeat page-bg`
(full-viewport textured background image, logo centered above the card). Card is identical
to the branded flow (`w-full max-w-[400px]`).

- **Sign In (`/auth/classic/signin`)** — same form card as branded signin, centered on `page-bg`.
- **Sign Up (`/auth/classic/signup`)** — classic registration; same fields as branded signup.
- **2FA (`/auth/classic/2fa`)** — classic OTP entry, 6-box input row.
- **Check Email (`/auth/classic/check-email`)** — classic notice card.
- **Reset Password (`/auth/classic/reset-password`)** — classic reset request.
- **Reset Check Email (`/auth/classic/reset-password/check-email`)** — classic reset-sent notice.
- **Password Changed (`/auth/classic/reset-password/changed`)** — classic success notice.

Cherry-pick from the flow as a whole: `page-bg` centered-card wrapper (cheapest auth shell
— no brand panel asset needed), logo-above-card stack.

## Notices

- **Welcome Message (`/auth/welcome-message`)** — Purpose: post-signup onboarding prompt. Layout: centered modal dialog over the app shell — `fixed z-50 left-[50%] top-[50%] translate-x/y-[-50%] w-full max-w-[500px] bg-background border border-border p-6 shadow-lg sm:rounded-lg` with zoom/fade `data-[state]` animations; content = "Welcome to Metronic" H3, copy, `Show me around` + `Skip the tour` buttons. Cherry-pick: dialog shell classes, primary/ghost button pair.
- **Account Deactivated (`/auth/account-deactivated`)** — Purpose: deactivation notice. Layout: same centered dialog pattern, "Account Deactivated" heading + copy + CTA.

## Errors

- **404 (`/error/404`)** — Purpose: not-found page. Layout: full-viewport vertical center (`flex flex-col items-center justify-center grow h-[95%]`); light/dark illustration pair, outline badge "404 Error", H3, copy with inline link home. Cherry-pick: whole error-page stack (snippet below), `dark:hidden` / `hidden dark:block` illustration swap.
- **500 (`/error/500`)** — Purpose: server-error page. Layout: identical stack, "Internal Server Error" heading.

## Reusable markup snippets

Branded auth split-grid wrapper + form card (signin), trimmed:

```html
<div class="grid lg:grid-cols-2 grow">
  <div class="flex justify-center items-center p-8 lg:p-10 order-2 lg:order-1">
    <div data-slot="card" class="flex flex-col items-stretch rounded-xl bg-card border border-border shadow-xs w-full max-w-[400px]">
      <div data-slot="card-content" class="grow p-6">
        <form class="block w-full space-y-5">
          <div class="text-center space-y-1 pb-3">
            <h1 class="text-2xl font-semibold tracking-tight">Sign In</h1>
            <p class="text-sm text-muted-foreground">Welcome back! Log in with your credentials.</p>
          </div>
          <div class="flex flex-col gap-3.5">
            <button class="h-8.5 rounded-md px-3 gap-1.5 bg-background border border-input hover:bg-accent"><svg/>Sign in with Google</button>
          </div>
          <div class="relative py-1.5">
            <div class="absolute inset-0 flex items-center"><span class="w-full border-t"></span></div>
            <div class="relative flex justify-center text-xs uppercase"><span class="bg-background px-2 text-muted-foreground">or</span></div>
          </div>
          <div data-slot="form-item" class="flex flex-col gap-2.5">
            <label class="text-sm font-medium text-foreground">Email</label>
            <input class="h-8.5 px-3 rounded-md w-full bg-background border border-input shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/30" placeholder="Your email">
          </div>
          <div data-slot="form-item" class="flex flex-col gap-2.5">
            <label class="text-sm font-medium text-foreground">Password</label>
            <div class="relative">
              <input type="password" class="h-8.5 px-3 rounded-md w-full bg-background border border-input shadow-xs" placeholder="Your password">
              <button type="button" class="absolute right-0 top-0 h-full px-3 py-2 text-muted-foreground w-8.5 p-0"><svg/><!-- eye toggle --></button>
            </div>
          </div>
          <!-- remember-me + forgot link row, then full-width primary submit -->
        </form>
      </div>
    </div>
  </div>
  <!-- second column: brand panel (logo, heading, copy, screenshot collage) -->
</div>
```

Classic centered wrapper: replace the grid with
`<div class="flex flex-col items-center justify-center grow bg-center bg-no-repeat page-bg">`
and place the logo + same card inside.

Error-page stack (404), trimmed:

```html
<div class="flex flex-col items-center justify-center grow h-[95%]">
  <div class="mb-10">
    <img class="dark:hidden max-h-[160px]" src=".../illustrations/19.svg" alt="">
    <img class="hidden dark:block max-h-[160px]" src=".../illustrations/19-dark.svg" alt="">
  </div>
  <span class="badge badge-primary badge-outline mb-3">404 Error</span>
  <h3 class="text-2xl font-semibold text-mono text-center mb-2">We have lost this page</h3>
  <div class="text-base text-center text-secondary-foreground mb-10">
    The requested page is missing. Check the URL or
    <a class="text-primary font-medium hover:text-primary-active" href="/">return home</a>
  </div>
</div>
```

## Top patterns worth stealing

- **One form card, two shells**: the `max-w-[400px]` card is byte-identical across branded and classic flows — build the card once, swap the wrapper (`grid lg:grid-cols-2 grow` vs `page-bg` centered flex).
- **Split-grid responsive ordering**: `order-2 lg:order-1` puts the form below the brand panel on mobile, left of it on desktop — no duplicate markup.
- **"or" divider**: absolutely-positioned `border-t` line with a `bg-background px-2 text-muted-foreground` label floating over it.
- **Password input with inline eye toggle**: relative wrapper, ghost button absolutely pinned `right-0 top-0 h-full`.
- **Error-page stack**: centered illustration (with `dark:hidden` swap) + outline badge + `text-mono` heading + secondary copy — drop-in for GLC 404/500.
- **Notice dialog shell**: `fixed z-50` centered `max-w-[500px]` panel with `data-[state]` zoom/fade animations — reusable for welcome/consent/deactivation prompts.
