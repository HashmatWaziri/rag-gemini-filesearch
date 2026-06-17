# Firecrawl harness for the Metronic demo (battle-tested)

Recipes for inspecting live Metronic Demo 7 pages. Plain `firecrawl scrape` returns
the sign-in page for everything behind the demo login — use a browser session.

## 1. Launch a session (always pass `--session` afterwards)

```bash
firecrawl --status   # check credits + concurrency first
firecrawl browser launch-session --ttl 2400 --ttl-inactivity 1200
# Note the printed Session ID; export it:
SID=<session-id>
```

When several agents work in parallel, each must launch its own session and pass
`--session $SID` on every command — the "last session" default is shared and will
be stomped by other agents.

## 2. Log in to the demo

```bash
firecrawl browser execute --session $SID --node 'await (async () => {
  globalThis.kt = await browser.contexts()[0].newPage();
  const pg = globalThis.kt;
  await pg.setViewportSize({ width: 1600, height: 1000 });
  await pg.goto("https://keenthemes.com/metronic/tailwind/react/demo7/auth/signin", { waitUntil: "networkidle", timeout: 45000 });
  await pg.fill("input[type=email]", "demo@kt.com");
  await pg.fill("input[type=password]", "demo123");
  await pg.click("button[type=submit]");
  await pg.waitForTimeout(4000);
  return pg.url();
})()'
```

Success = the returned URL is the demo7 root, not `/auth/signin`.

## 3. REPL gotchas (these will bite you)

- The `--node` REPL is **persistent per session**: top-level `const x` fails on reuse
  ("Identifier already declared"). Wrap everything in `await (async () => { ... })()`
  and keep durable handles on `globalThis` (e.g. `globalThis.kt`).
- The default `page` object is a blank tab, **not** the tab you navigated. Always use
  your `globalThis.kt` handle, or find the tab:
  `browser.contexts().flatMap(c => c.pages()).find(p => p.url().includes("keenthemes"))`.
- Each `execute` command times out around **30 seconds**. Batch at most 4–5 page
  visits per command.
- Account-wide rate limit is roughly **11 requests/minute**. Sleep ~8s between
  firecrawl commands; on "Rate limit exceeded", sleep 20s and retry.
- `-o file` is unreliable with `--node` (result often prints to stdout instead).
  Redirect in the shell: `firecrawl browser execute ... > out.json`.

## 4. Batch structural summary of pages

Visit several pages in one command and emit JSON per page:

```bash
firecrawl browser execute --session $SID --node 'await (async () => {
  const pg = globalThis.kt;
  const base = "https://keenthemes.com/metronic/tailwind/react/demo7";
  const paths = ["/store-client/home", "/store-client/wishlist"]; // 4-5 max
  const out = [];
  for (const path of paths) {
    await pg.goto(base + path, { waitUntil: "networkidle", timeout: 45000 }).catch(() => {});
    await pg.waitForTimeout(1200);
    out.push(await pg.evaluate(() => {
      const text = (el) => (el?.textContent || "").trim().slice(0, 80);
      const main = document.querySelector("[role=content]") || document.body;
      return {
        path: location.pathname,
        headings: [...main.querySelectorAll("h1,h2,h3,h4")].slice(0, 20).map(h => h.tagName + " " + text(h)),
        grids: [...new Set([...main.querySelectorAll("[class*=grid-cols]")].map(g =>
          [...g.classList].filter(c => c.includes("grid") || c.includes("col") || c.includes("gap")).join(" ")))].slice(0, 8),
        tables: main.querySelectorAll("table").length,
        cards: main.querySelectorAll("[data-slot=card]").length,
        dialogs: main.querySelectorAll("[data-slot=dialog-trigger], [aria-haspopup=dialog]").length,
        forms: main.querySelectorAll("input,select,textarea").length,
        sidebars: [...main.querySelectorAll("[class*=\"lg:w-[\"]")].slice(0, 4).map(e =>
          [...e.classList].filter(c => c.includes("w-") || c.includes("sticky") || c.includes("shrink")).join(" ")),
      };
    }));
  }
  return JSON.stringify(out, null, 1);
})()' > batch-summary.json
```

## 5. Extract exact markup of one component

Find the smallest element containing distinctive text, walk up to the pattern root,
and dump `outerHTML` (this is how the checkout stepper classes were captured):

```bash
firecrawl browser execute --session $SID --node 'await (async () => {
  const pg = globalThis.kt;
  return await pg.evaluate(() => {
    const leaf = [...document.querySelectorAll("*")]
      .filter(d => d.children.length === 0 && d.textContent.trim() === "DISTINCTIVE TEXT")[0];
    let node = leaf;
    while (node && !node.textContent.includes("OTHER TEXT IN SAME PATTERN")) node = node.parentElement;
    return node ? node.outerHTML.slice(0, 9000) : "NOT FOUND";
  });
})()' > component.html
```

## 6. Screenshots (visual confirmation)

Screenshots save inside the **remote sandbox**; pull them via base64:

```bash
firecrawl browser execute --session $SID --node 'await (async () => {
  await globalThis.kt.screenshot({ path: "/tmp/shot.png" }); return "ok";
})()'
firecrawl browser execute --session $SID --bash 'base64 -w0 /tmp/shot.png' \
  | grep -o '[A-Za-z0-9+/=]\{1000,\}' | head -1 | base64 -d > .firecrawl/metronic/shot.png
```

For mobile: `await globalThis.kt.setViewportSize({ width: 390, height: 844 })` first.

## 7. Clean up

```bash
firecrawl browser close --session $SID
```
