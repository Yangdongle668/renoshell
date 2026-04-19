# Elementor Templates

Two layers live here:

```
templates/elementor/
├── *.json              # source (human-edited, composition-friendly)
├── dist/*.json         # generated (importable into Elementor)
└── README.md
```

## Source vs. dist

Source JSONs use two renobattery-only extensions:

| Key | Meaning |
|---|---|
| `"_ref": "component-xxx.json"` | Inline the referenced component here |
| `"overrides": { "elements.0.settings.title": "..." }` | After inlining, overwrite fields via dot-path |

Elementor does NOT understand `_ref` or `overrides`. **Never import a source file directly.** Always import from `dist/`.

## Build the dist

```bash
php tools/flatten-elementor-templates.php
```

The flattener:
1. Resolves `_ref` recursively and applies `overrides`
2. Strips keys in the DENYLIST (invented / non-Elementor keys whose behavior is delivered via `assets/css/components.css` instead)
3. Writes flat JSON to `templates/elementor/dist/*.json`

Re-run after every source change. CI should fail if dist is stale.

## Import into WordPress

1. Install + activate the `renobattery` theme (see repo root `style.css`).
2. Install + activate **Elementor Pro** (≥ 3.21) and **ACF Pro**.
3. Go to **Templates → Saved Templates → Import Templates** and upload each `dist/*.json`.
4. Alternatively, use **WP-CLI**:
   ```bash
   wp elementor library import wp-content/themes/renobattery/templates/elementor/dist/page-home.json
   ```

## Template types (per file)

| File | `type` | Elementor destination |
|---|---|---|
| `component-navbar.json` | `header` | Theme Builder → Header |
| `component-footer.json` | `footer` | Theme Builder → Footer |
| `component-hero-fullscreen.json` | `section` | Saved Templates → Section |
| `component-product-card.json` | `section` | Saved Templates → Section (used as **Loop Item** — see B3 note) |
| `component-cta-section.json` | `section` | Saved Templates → Section |
| `page-home.json` | `page` | Pages → Home (assign template) |
| `archive-product.json` | `archive` | Theme Builder → Archive (condition: Product Archive + Product Categories) |
| `single-product.json` | `single` | Theme Builder → Single (condition: All Products) |

## B3 — Loop Grid / "product-card" skin note

`archive-product.json` and `single-product.json` use Elementor Pro's **Posts** widget with `"template": "product-card"`. Elementor Pro does not ship a skin by that exact name. Two options:

**Option A (recommended): Loop Grid template**

1. Import `dist/component-product-card.json` as a **Loop Item** saved template.
2. Note its template ID (visible in the Elementor template library).
3. In `archive-product.json` / `single-product.json` dist files, change each posts widget to use:
   ```json
   "widgetType": "loop-grid",
   "settings": { "template_id": "<numeric-id-from-step-2>" }
   ```
   Then re-import the affected templates. *This can be automated — see `tools/bind-loop-template.php` in a future task.*

**Option B: built-in skin**

Change `"skin": "cards"` and leave `"template"` out. You lose the renobattery card styling unless you add matching CSS.

We ship Option A as the default because it preserves every pixel of the `rb-card` component.

## CI check (optional)

Add to CI:
```bash
php tools/flatten-elementor-templates.php
git diff --exit-code templates/elementor/dist/ || {
  echo "Dist is stale. Run the flattener and commit."; exit 1;
}
```
