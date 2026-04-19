# Renobattery — Fonts

Self-hosted woff2 font files go here. Binaries are **not** shipped in this
repository — drop them in during deployment.

## Required files (when Tesla mode is active)

| Filename | Weight | Source |
|---|---|---|
| `Inter-Regular.woff2`       | 400 | [rsms.me/inter](https://rsms.me/inter/) or [Google Fonts](https://fonts.google.com/specimen/Inter) |
| `Inter-Medium.woff2`        | 500 | same |
| `Inter-SemiBold.woff2`      | 600 | same |
| `InterDisplay-Medium.woff2` | 500 | [rsms.me/inter](https://rsms.me/inter/) (the `Inter Display` variant is bundled in the same download) |
| `InterDisplay-SemiBold.woff2` | 600 | same |

## Optional (Chinese, if not using system fonts)

| Filename | Weight | Source |
|---|---|---|
| `NotoSansSC-Regular.woff2` | 400 | [Google Fonts](https://fonts.google.com/noto/specimen/Noto+Sans+SC) |
| `NotoSansSC-SemiBold.woff2` | 600 | same |

By default the theme uses system Chinese fonts (PingFang SC on Apple,
Microsoft YaHei on Windows), which avoids the request entirely. Only host
Noto Sans SC if you need consistent Chinese rendering on Linux/older Android.

## Subsetting (recommended)

Subset to `latin`, `latin-ext` for Inter files to keep each woff2 under ~20 kB.
Use [Wakamai Fondue](https://wakamaifondue.com/) or `pyftsubset`:

```bash
pyftsubset Inter-SemiBold.ttf \
  --unicodes="U+0020-007F,U+00A0-00FF,U+0100-017F" \
  --output-file=Inter-SemiBold.woff2 \
  --flavor=woff2
```

## Preload hint

The theme preloads `Inter-SemiBold.woff2` via `inc/enqueue.php`
(`renobattery_preload_fonts()`) when it detects the file exists. No extra
configuration needed — just drop the file in.

## What happens if files are missing

Every `@font-face` rule uses `font-display: swap`. The browser falls back
through the CSS stack (`Inter → PingFang SC → system sans`) while the font
downloads, or indefinitely if the file 404s. **Missing files never break
the layout.**
