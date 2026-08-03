# Atelier — the KMS visual layer

`dist/` holds the deployed site as a prerendered build, not as source. This
folder is the design work that sits on top of it: two assets that load after the
build's own bundle and restyle it through the class names and design tokens the
build already emits. No markup is rewritten, so React keeps ownership of the DOM.

| file              | what it does                                                            |
| ----------------- | ----------------------------------------------------------------------- |
| `kms-atelier.css` | the design system — tokens, atmosphere, surfaces, motion                 |
| `kms-atelier.js`  | interaction only — cursor spotlight, scroll reveal, progress, count-ups  |
| `apply.mjs`       | hashes both, writes `.br`/`.gz` siblings, injects the tags into the HTML |

## Applying it

```bash
node design/apply.mjs           # defaults to ./dist
node design/apply.mjs some/dist # or point it somewhere else
```

Re-running is safe — earlier `kms-atelier-*` assets and their tags are removed
first. The emitted filenames carry a content hash because `.htaccess` marks
everything under `assets/` as immutable for a year; an unhashed name could never
be updated in a browser that had already cached it.

## Editing

Edit the two files in this folder, never the hashed copies in `dist/assets/`,
then re-run `apply.mjs`. If the upstream app is ever rebuilt, drop the new build
into `dist/` and run `apply.mjs` again.

## Design notes

- **One canvas.** The build alternates light and dark bands. Those are re-mapped
  to luminance tiers of a single dark surface, so the page reads as one
  continuous field rather than a stack of slabs. Every band carries its own
  bloom, drawn as background layers and rotated in hue and origin down the page.
- **Depth over borders.** Surfaces are glass: a sheen gradient, a 1px gradient
  frame drawn with a mask so it stays hairline at any radius, an inner top
  highlight, and a deep blue-biased shadow. Interactive tiles lift and warm
  their frame on hover; a cursor spotlight tracks the pointer.
- **Georgian first.** Display sizes carry less negative tracking and more
  leading for `:lang(ka)` than they would for Latin — Georgian glyphs have no
  ascenders/descenders to absorb tight leading.
- **Motion has a budget.** Scrolling stays at 60fps because nothing expensive
  runs per frame: no blend-mode overlay above content, no filters on the
  animated SVG nodes, no wide text-shadow on the animated gradient headline.
  The headline halo is painted into the hero background instead. Everything
  decorative is disabled under `prefers-reduced-motion`.
