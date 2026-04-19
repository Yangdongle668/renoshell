# Renobattery — Fonts

## What's shipped

| File | Size | Weights | License |
|---|---|---|---|
| `DMSans-Variable.woff2` | ~36 KB | 100 – 1000 (via `wght` axis) | [SIL Open Font License 1.1](./OFL.txt) |
| `OFL.txt` | 4.5 KB | — | License text |

DM Sans is a variable font — one file covers Regular (400), Medium (500),
SemiBold (600), Bold (700) and everything in between. The `@font-face`
declaration in `assets/css/tesla-refinement.css` exposes the full
`font-weight: 100 1000` range; CSS `font-weight` values select the right
axis instance at render time.

### Why DM Sans

Originally we considered Inter / Inter Display. The user asked for
Google Sans, which is **proprietary** and can't be redistributed with a
public theme. DM Sans (by Colophon Foundry, commissioned by Google,
released under SIL OFL 1.1) is the closest free alternative in feel —
geometric, modern, Google-adjacent but unambiguously licensed for this
purpose.

## What's preloaded

`inc/enqueue.php` → `renobattery_preload_fonts()` detects the file and
emits a single `<link rel="preload" as="font" type="font/woff2" crossorigin>`
into `<head>`. Preloading only one file (the variable font covers every
weight) avoids wasteful multi-preload.

## Activating DM Sans on the front-end

Always active — `tokens.css` puts DM Sans first in the `--rb-font-sans`
and `--rb-font-display` stacks. The theme does **not** require Tesla mode
for this; Tesla mode simply refines body weight / line-height on top.

```css
--rb-font-sans:    'DM Sans', 'Inter', 'PingFang SC', …, sans-serif;
--rb-font-display: 'DM Sans', 'Inter Display', 'Inter', 'PingFang SC', sans-serif;
```

Chinese locale override (`html[lang^="zh"]`) still prefers PingFang SC /
Noto Sans SC first; DM Sans handles any inline latin (model codes,
numbers) within a Chinese paragraph.

## If you want to replace the font

1. Drop a new `*.woff2` variable font into this directory.
2. Update the `@font-face` rule in `assets/css/tesla-refinement.css`.
3. Update the `$candidates` list in `inc/enqueue.php`
   (`renobattery_preload_fonts()`).
4. Update the `--rb-font-sans` / `--rb-font-display` stacks in
   `assets/css/tokens.css`.

All four steps are needed to swap cleanly.

## Optional supplementary fonts (not shipped)

Drop these in if you need them:

| Filename | Reason |
|---|---|
| `NotoSansSC-Regular.woff2` / `NotoSansSC-SemiBold.woff2` | Consistent Chinese rendering on Linux / older Android (Apple + Windows devices have PingFang / YaHei in the system stack) |

These are optional; the default stack already covers Chinese via system
fonts.

## What happens if DMSans-Variable.woff2 is missing

Every `@font-face` rule uses `font-display: swap`. The browser falls back
through the stack (`DM Sans → Inter → PingFang SC → system sans`) while
the font would have downloaded — or indefinitely if the file 404s.
**Missing fonts never break the layout.**
