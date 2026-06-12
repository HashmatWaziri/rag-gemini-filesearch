# ReUI Animated Components — Reference

Source: [https://v1.reui.io/docs/](https://v1.reui.io/docs/) (Animated Components accordion)  
Markdown endpoints: `https://v1.reui.io/docs/<slug>.md`  
Registry install: `pnpm dlx shadcn@latest add @reui/<slug>`

**Total components:** 14

---

## Catalog

| # | Component | Slug | Doc URL | Motion | Other deps |
|---|-----------|------|---------|--------|------------|
| 1 | Marquee | `marquee` | [docs/marquee](https://v1.reui.io/docs/marquee) | — | CSS keyframes |
| 2 | GitHub Button | `github-button` | [docs/github-button](https://v1.reui.io/docs/github-button) | ✓ | `radix-ui` (Slot) |
| 3 | Avatar Group | `avatar-group` | [docs/avatar-group](https://v1.reui.io/docs/avatar-group) | ✓ | `radix-ui` (Avatar) |
| 4 | Typing Text | `typing-text` | [docs/typing-text](https://v1.reui.io/docs/typing-text) | ✓ | — |
| 5 | Word Rotate | `word-rotate` | [docs/word-rotate](https://v1.reui.io/docs/word-rotate) | ✓ | — |
| 6 | Video Text | `video-text` | [docs/video-text](https://v1.reui.io/docs/video-text) | ✓ | — |
| 7 | SVG Text | `svg-text` | [docs/svg-text](https://v1.reui.io/docs/svg-text) | ✓ | — |
| 8 | Counting Number | `counting-number` | [docs/counting-number](https://v1.reui.io/docs/counting-number) | ✓ | — |
| 9 | Sliding Number | `sliding-number` | [docs/sliding-number](https://v1.reui.io/docs/sliding-number) | ✓ | — |
| 10 | Shimmering Text | `shimmering-text` | [docs/shimmering-text](https://v1.reui.io/docs/shimmering-text) | ✓ | — |
| 11 | Text Reveal | `text-reveal` | [docs/text-reveal](https://v1.reui.io/docs/text-reveal) | ✓ | — |
| 12 | Gradient Background | `gradient-background` | [docs/gradient-background](https://v1.reui.io/docs/gradient-background) | ✓ | — |
| 13 | Grid Background | `grid-background` | [docs/grid-background](https://v1.reui.io/docs/grid-background) | ✓ | — |
| 14 | Hover Background | `hover-background` | [docs/hover-background](https://v1.reui.io/docs/hover-background) | ✓ | — |

---

## Marquee

Scrolling content in an infinite loop. Horizontal or vertical.

**Install:** Copy to `components/ui/marquee.tsx`. **Requires global CSS** (no `motion` package):

```css
@theme inline {
  --animate-marquee: marquee var(--duration) infinite linear;
  --animate-marquee-vertical: marquee-vertical var(--duration) linear infinite;

  @keyframes marquee {
    from { transform: translateX(0); }
    to { transform: translateX(calc(-100% - var(--gap))); }
  }

  @keyframes marquee-vertical {
    from { transform: translateY(0); }
    to { transform: translateY(calc(-100% - var(--gap))); }
  }
}
```

**Examples:** Vertical, 3D

| Prop | Type | Default |
|------|------|---------|
| `className` | `string` | — |
| `reverse` | `boolean` | `false` |
| `pauseOnHover` | `boolean` | `false` |
| `children` | `ReactNode` | — |
| `vertical` | `boolean` | `false` |
| `repeat` | `number` | `4` |
| `autoFill` | `boolean` | — |
| `ariaLabel` | `string` | — |
| `ariaLive` | `'off' \| 'polite' \| 'assertive'` | `'off'` |
| `ariaRole` | `string` | `'marquee'` |

**Credits:** Inspired by [Magic UI Marquee](https://magicui.design/docs/components/marquee).

---

## GitHub Button

Animated GitHub repository button with star count.

**Deps:** `npm i radix-ui` + `motion`

**Examples:** Outline, Separator, Sizes

### GithubButton

| Prop | Type | Default |
|------|------|---------|
| `variant` | `'default' \| 'outline'` | `'default'` |
| `size` | `'md' \| 'sm' \| 'lg'` | `'md'` |
| `className` | `string` | — |
| `roundStars` | `boolean` | `false` |
| `fixedWidth` | `boolean` | `true` |
| `initialStars` | `number` | `0` |
| `starsClass` | `string` | — |
| `targetStars` | `number` | — |
| `separator` | `boolean` | `false` |
| `showStarIcon` | `boolean` | `true` |
| `animationDuration` | `number` (seconds) | `2` |
| `animationDelay` | `number` (seconds) | `0` |
| `autoAnimate` | `boolean` | `true` |
| `showGithubIcon` | `boolean` | `true` |
| `filled` | `boolean` | `false` |
| `repoUrl` | `string` | — |
| `label` | `string` | `'Star'` |
| `useInViewTrigger` | `boolean` | `false` |
| `inViewOptions` | `UseInViewOptions` | `{ once: true }` |
| `transition` | `SpringOptions` | — |

**Credits:** Radix Slot; inspired by [Animate UI GitHub Stars](https://animate-ui.com/docs/buttons/github-stars).

---

## Avatar Group

Composable avatar stack with tooltip transitions.

**Deps:** `npm i radix-ui motion`

**Subcomponents:** `AvatarGroup`, `AvatarGroupItem`, `AvatarGroupTooltip`

**Examples:** Flip Animation

### AvatarGroup

| Prop | Type | Default |
|------|------|---------|
| `children` | `ReactNode` | — |
| `className` | `string` | — |
| `tooltipClassName` | `string` | — |
| `animation` | `'default' \| 'flip' \| 'reveal'` | `'default'` |

### AvatarGroupItem

| Prop | Type | Default |
|------|------|---------|
| `children` | `ReactNode` | — |
| `className` | `string` | — |
| `tooltipClassName` | `string` | — |

### AvatarGroupTooltip

| Prop | Type | Default |
|------|------|---------|
| `children` | `ReactNode` | — |
| `className` | `string` | — |

**Credits:** Radix Avatar; inspired by [Aceternity Animated Tooltip](https://ui.aceternity.com/components/animated-tooltip).

---

## Typing Text

Typewriter animation with optional cursor and text cycling.

**Deps:** `npm i motion`

**Examples:** Loop, Fast, Slow, No Cursor

### TypingText

| Prop | Type | Default |
|------|------|---------|
| `text` | `string` | — |
| `texts` | `string[]` | — |
| `speed` | `number` (ms/char) | `100` |
| `delay` | `number` (ms) | `0` |
| `showCursor` | `boolean` | `true` |
| `cursor` | `string` | `"\|"` |
| `cursorClassName` | `string` | — |
| `loop` | `boolean` | `false` |
| `pauseDuration` | `number` (ms) | `2000` |
| `className` | `string` | — |
| `onComplete` | `() => void` | — |
| `startOnView` | `boolean` | `true` |
| `once` | `boolean` | `false` |
| `animation` | `"fadeIn" \| "blurIn" \| "blurInUp" \| "blurInDown" \| "slideUp" \| "slideDown" \| "slideLeft" \| "slideRight" \| "scaleUp" \| "scaleDown"` | — |
| `inViewMargin` | `string` | — |

---

## Word Rotate

Cycles through words with transition styles.

**Deps:** `npm i motion`

**Examples:** Slide, Flip, Scale

### WordRotate

| Prop | Type | Default |
|------|------|---------|
| `words` | `string[]` | — |
| `duration` | `number` (ms) | `1500` |
| `animationStyle` | `"fade" \| "slide-up" \| "slide-down" \| "scale" \| "flip"` | `"fade"` |
| `loop` | `boolean` | `true` |
| `pauseDuration` | `number` (ms) | `300` |
| `className` | `string` | — |
| `containerClassName` | `string` | — |
| `startOnView` | `boolean` | `true` |
| `once` | `boolean` | `false` |
| `inViewMargin` | `string` | — |

---

## Video Text

Text with video background mask.

**Deps:** `npm i motion`

### VideoText

| Prop | Type | Default |
|------|------|---------|
| `src` | `string \| string[]` | — |
| `children` | `ReactNode` | — |
| `className` | `string` | — |
| `autoPlay` | `boolean` | `true` |
| `muted` | `boolean` | `true` |
| `loop` | `boolean` | `true` |
| `preload` | `"auto" \| "metadata" \| "none"` | `"auto"` |
| `fontSize` | `string \| number` | `"20vw"` |
| `fontWeight` | `string \| number` | `"bold"` |
| `as` | `ElementType` | `"div"` |
| `onPlay` | `() => void` | — |
| `onPause` | `() => void` | — |
| `onEnded` | `() => void` | — |

**Credits:** Inspired by [Magic UI](https://magicui.design/).

---

## SVG Text

Text masked with animated SVG content.

**Deps:** `npm i motion`

### SVGText

| Prop | Type | Default |
|------|------|---------|
| `svg` | `ReactNode` | — |
| `children` | `ReactNode` | — |
| `className` | `string` | — |
| `fontSize` | `string \| number` | `"20vw"` |
| `fontWeight` | `string \| number` | `"bold"` |
| `as` | `ElementType` | `"div"` |

---

## Counting Number

Smooth count-up animation.

**Deps:** `npm i motion`

**Examples:** Decimal, Format

### CountingNumber

| Prop | Type | Default |
|------|------|---------|
| `from` | `number` | `0` |
| `to` | `number` | `100` |
| `duration` | `number` (seconds) | `2` |
| `delay` | `number` (ms) | `0` |
| `className` | `string` | — |
| `startOnView` | `boolean` | `true` |
| `once` | `boolean` | `false` |
| `inViewMargin` | `string` | — |
| `onComplete` | `() => void` | — |
| `format` | `(value: number) => string` | — |

---

## Sliding Number

Vertical digit-slide number transition.

**Deps:** `npm i motion`

**Examples:** Slider

### SlidingNumber

| Prop | Type | Default |
|------|------|---------|
| `from` | `number` | — |
| `to` | `number` | — |
| `duration` | `number` (seconds) | `2` |
| `delay` | `number` (seconds) | `0` |
| `startOnView` | `boolean` | `true` |
| `once` | `boolean` | `false` |
| `className` | `string` | — |
| `onComplete` | `function` | — |
| `digitHeight` | `number` (px) | `40` |

**Credits:** Inspired by [Motion Primitives Sliding Number](https://motion-primitives.com/docs/sliding-number).

---

## Shimmering Text

Gradient shimmer across text.

**Deps:** `npm i motion`

**Examples:** Color

### ShimmeringText

| Prop | Type | Default |
|------|------|---------|
| `text` | `string` | — |
| `duration` | `number` (seconds) | `2` |
| `delay` | `number` (seconds) | `0` |
| `repeat` | `boolean` | `true` |
| `repeatDelay` | `number` (seconds) | `0.5` |
| `className` | `string` | — |
| `startOnView` | `boolean` | `true` |
| `once` | `boolean` | `false` |
| `inViewMargin` | `string` | — |
| `spread` | `number` | `2` |
| `color` | `string` | — |
| `shimmerColor` | `string` | — |

---

## Text Reveal

Character- or word-level reveal with many variants.

**Deps:** `npm i motion`

**Examples:** Slide Variants, Scale, Blur, Typewriter, Wave, Stagger, Rotate, Elastic, Neon Glow, Fire Magic

### TextReveal

| Prop | Type | Default |
|------|------|---------|
| `children` | `string` | — |
| `variant` | `"fade" \| "slideUp" \| "slideDown" \| "slideLeft" \| "slideRight" \| "scale" \| "blur" \| "typewriter" \| "wave" \| "stagger" \| "rotate" \| "elastic"` | `"fade"` |
| `className` | `string` | — |
| `style` | `React.CSSProperties` | — |
| `delay` | `number` (seconds) | `0` |
| `duration` | `number` (seconds) | `0.6` |
| `staggerDelay` | `number` (seconds) | `0.03` |
| `once` | `boolean` | `true` |
| `startOnView` | `boolean` | `true` |
| `wordLevel` | `boolean` | `false` |
| `onComplete` | `() => void` | — |

---

## Gradient Background

Animated gradient color transitions.

**Deps:** `npm i motion`

**Examples:** Dark

### GradientBackground

| Prop | Type | Default |
|------|------|---------|
| `className` | `string` | — |
| `transition` | `Transition` | `{ duration: 10, ease: 'easeInOut', repeat: Infinity }` |

---

## Grid Background

Responsive grid with animated moving beams.

**Deps:** `npm i motion`

**Examples:** Hero

### GridBackground

| Prop | Type | Default |
|------|------|---------|
| `children` | `ReactNode` | — |
| `gridSize` | `"4:4" \| "5:5" \| "6:6" \| "6:8" \| "8:8" \| "8:12" \| "10:10" \| "12:12" \| "12:16" \| "16:16"` | `"8:8"` |
| `className` | `string` | — |
| `colors.background` | `string` | `"bg-slate-900"` |
| `colors.borderColor` | `string` | `"border-slate-700/50"` |
| `colors.borderSize` | `string` | `"1px"` |
| `colors.borderStyle` | `"solid" \| "dashed" \| "dotted"` | `"solid"` |
| `beams.count` | `number` | `12` |
| `beams.colors` | `string[]` | cyan/purple/fuchsia/… palette |
| `beams.shadow` | `string` | `"shadow-lg shadow-cyan-400/50 rounded-full"` |
| `beams.speed` | `number` (seconds) | `4` |

---

## Hover Background

Interactive background with hover-reactive animated objects.

**Deps:** `npm i motion`

**Examples:** Dark

### HoverBackground

| Prop | Type | Default |
|------|------|---------|
| `objectCount` | `1`–`12` | `12` |
| `children` | `ReactNode` | — |
| `className` | `string` | — |
| `colors.background` | `string` | `"bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900"` |
| `colors.objects` | `string[]` | cyan/purple/fuchsia/… at 20% opacity |
| `colors.glow` | `string` | `"shadow-cyan-400/50"` |

---

## Shared Motion patterns

Most animated components (except Marquee) use Motion and share:

- **`startOnView`** — trigger on viewport entry
- **`once`** — single play vs repeat on scroll
- **`inViewMargin`** — IntersectionObserver margin
- **`onComplete`** — callback when animation finishes

Install Motion once for the project:

```bash
npm i motion
# or
pnpm add motion
```

## Doc discovery notes

- Sidebar section: **Animated Components** on [v1.reui.io/docs](https://v1.reui.io/docs/)
- Machine-readable index: [v1.reui.io/llms.txt](https://v1.reui.io/llms.txt) (`## Animated Components`)
- Scraped markdown cache: `.firecrawl/reui-animated/<slug>.md`
