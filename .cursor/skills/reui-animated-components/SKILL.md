---
name: reui-animated-components
description: >-
  Guides use of ReUI Animated Components (marquee, github-button, avatar-group,
  typing-text, word-rotate, video-text, svg-text, counting-number, sliding-number,
  shimmering-text, text-reveal, gradient-background, grid-background,
  hover-background) powered by Motion in Metronic/Inertia React apps. Activates
  when the user mentions ReUI, animated, Motion, transitions, Metronic, or any
  listed animated component by name while building hero sections, backgrounds,
  or text effects.
---

# ReUI Animated Components

ReUI v1 **Animated Components** are copy-paste, prop-based registry items built with [Motion](https://motion.dev/) (and Radix primitives where noted), styled with Tailwind CSS v4. Use them for hero text, backgrounds, counters, and decorative motion — not for core form/navigation primitives (see `reui-radix-components`).

**Docs:** [https://v1.reui.io/docs/](https://v1.reui.io/docs/) · **Full catalog:** [reference.md](reference.md) · **Component count:** 14

## When to use this skill

- Adding motion to landing pages, heroes, or marketing sections
- Choosing between text effects (typing, rotate, reveal, shimmer) or number animations
- Installing animated backgrounds (gradient, grid, hover) behind content
- Wiring `startOnView` / `once` scroll-triggered animations
- Integrating Motion-powered ReUI items in Metronic Demo 7 / GLC layouts

## Stack rules

| Layer | Convention |
|-------|------------|
| **Components** | `@/components/ui/<slug>` — check existing file before installing |
| **Registry** | `pnpm dlx shadcn@latest add @reui/<slug>` |
| **Motion dep** | Most components require `motion` (`npm i motion`) |
| **CSS** | Marquee needs `@theme inline` keyframes in global CSS — see [reference.md](reference.md#marquee) |
| **Runtime** | Do **not** import from `@reui/*` at runtime — registry namespace only |

### Install (new animated component)

```bash
pnpm dlx shadcn@latest add @reui/<slug>
```

Variant blocks use `@reui/<slug>-<variant>` when documented (e.g. `@reui/text-reveal-slide`).

### Import

```tsx
import { TypingText } from '@/components/ui/typing-text';
import { GridBackground } from '@/components/ui/grid-background';
import { TextReveal } from '@/components/ui/text-reveal';
```

## Motion prerequisites

| Component | Extra dependencies |
|-----------|-------------------|
| **Marquee** | CSS keyframes only (no `motion` package) |
| **GitHub Button** | `radix-ui` (Slot) + `motion` |
| **Avatar Group** | `radix-ui` (Avatar) + `motion` |
| **All others** | `motion` |

Ensure `motion` is in project dependencies before installing animated components.

## Component selection

| Need | Component | Notes |
|------|-----------|-------|
| Infinite scroll strip | `Marquee` | Horizontal/vertical, `pauseOnHover`, `autoFill` |
| GitHub star CTA | `GitHubButton` | Animated star count, `targetStars`, `repoUrl` |
| Stacked avatars + tooltip | `AvatarGroup` | `AvatarGroupItem`, `AvatarGroupTooltip`; `animation`: default \| flip \| reveal |
| Typewriter effect | `TypingText` | Single `text` or `texts[]` loop; `speed`, `showCursor` |
| Rotating headline words | `WordRotate` | `words[]`, `animationStyle`: fade \| slide-up \| slide-down \| scale \| flip |
| Video-filled text mask | `VideoText` | `src`, `fontSize`, `fontWeight` |
| SVG-filled text mask | `SVGText` | `svg` ReactNode + `children` text |
| Count-up stat | `CountingNumber` | `from`/`to`, `format`, decimal examples |
| Slot-machine digits | `SlidingNumber` | `digitHeight`, vertical slide per digit |
| Shimmer highlight | `ShimmeringText` | `shimmerColor`, `spread`, `repeat` |
| Scroll/entrance text | `TextReveal` | 11 variants: fade, slide*, scale, blur, typewriter, wave, stagger, rotate, elastic |
| Animated gradient fill | `GradientBackground` | `transition` for color cycle |
| Grid + moving beams | `GridBackground` | `gridSize`, `beams.count/colors/speed` |
| Cursor-reactive orbs | `HoverBackground` | `objectCount`, `colors.objects/glow` |

## Common patterns

### Scroll-triggered animation (shared props)

Most Motion components support viewport triggers:

```tsx
<TypingText
  text="Welcome to GLC"
  startOnView
  once
  inViewMargin="-100px"
/>
```

| Prop | Purpose |
|------|---------|
| `startOnView` | Start when element enters viewport (default `true` on most) |
| `once` | Run only first time in view |
| `inViewMargin` | IntersectionObserver `rootMargin` |

### Hero with animated background

```tsx
<GridBackground gridSize="12:16" className="min-h-[60vh]">
  <div className="relative z-10 flex flex-col items-center justify-center py-24">
    <TextReveal variant="slideUp" className="text-4xl font-bold">
      Global Learning Council
    </TextReveal>
    <WordRotate
      words={['Innovate', 'Educate', 'Transform']}
      animationStyle="flip"
      className="text-primary text-xl"
    />
  </div>
</GridBackground>
```

### Marquee logo strip (requires global CSS)

Add marquee keyframes to `app.css` (see reference), then:

```tsx
<Marquee pauseOnHover className="[--duration:20s] [--gap:1rem]">
  {logos.map((logo) => (
    <img key={logo.src} src={logo.src} alt={logo.alt} className="h-8" />
  ))}
</Marquee>
```

### Stat counter row

```tsx
<div className="flex gap-8">
  <CountingNumber from={0} to={1200} duration={2} format={(n) => `${Math.round(n)}+`} />
  <SlidingNumber from={0} to={98} duration={1.5} />
</div>
```

## Styling alignment

Animated components often ship with demo palette classes (`bg-slate-900`, `bg-cyan-400`). For GLC/Metronic pages, override via props:

- `GradientBackground` / `GridBackground` / `HoverBackground`: pass semantic `className` and `colors.*` props
- `ShimmeringText`: use `color` / `shimmerColor` or `className` with theme tokens
- Prefer `text-foreground`, `text-primary`, `bg-background` over raw slate/emerald utilities

## Workflow

1. Pick component from table above (or [reference.md](reference.md))
2. Check `resources/js/components/ui/<slug>.tsx` — reuse if present
3. Install: `pnpm dlx shadcn@latest add @reui/<slug>` + required deps (`motion`, etc.)
4. Add Marquee CSS to global stylesheet if using marquee
5. Apply `startOnView`/`once` for scroll sections; test reduced-motion if needed
6. Read live docs for variant blocks: `https://v1.reui.io/docs/<slug>`

## Additional resources

- Full props, examples, and doc URLs: [reference.md](reference.md)
- Radix/form primitives: `reui-radix-components` skill
- Scraped source: `.firecrawl/reui-animated/*.md`
