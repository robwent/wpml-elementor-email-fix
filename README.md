# WPML Elementor Email Fix

Prevents WPML from translating Elementor Pro form email notification fields, which corrupts shortcodes like `[all-fields]`.

## The problem

WPML registers Elementor's Form widget email fields as translatable. When using **Translate Everything** or automatic translation, WPML translates the shortcodes in these fields into the target language — for example, `[all-fields]` becomes `[tous-les-champs]` in French.

Elementor does not recognise the translated shortcodes, so they render **literally** in notification emails instead of outputting the submitted form data.

The corrupted values are stored directly in the `_elementor_data` post meta on the translated page and do not appear in WPML's String Translation admin, making the issue difficult to spot until emails start arriving with broken content.

## The fix

This plugin hooks into the `wpml_elementor_widgets_to_translate` filter and removes the following email-related fields from translation:

- `email_subject`
- `email_from_name`
- `email_content`
- `email_subject_2`
- `email_content_2`

These fields contain shortcodes and technical values rather than user-facing prose, so translating them is almost never desirable.

## Requirements

- WordPress 5.8+
- PHP 7.4+
- WPML (Sitepress Multilingual CMS)
- Elementor Pro

The plugin only runs when both WPML and Elementor Pro are active. If either is deactivated, the plugin does nothing.

## Installation

1. Download or clone this repository into `wp-content/plugins/wpml-elementor-email-fix/`.
2. Activate the plugin from **Plugins > Installed Plugins**.
3. That's it — no configuration needed.

## Fixing already-corrupted translations

This plugin **prevents** future corruption but does not repair pages that have already been translated with broken shortcodes. To fix those, you have two options:

### Option 1: Edit in Elementor (recommended for a few pages)

1. Open the translated page in Elementor.
2. Select the Form widget.
3. Go to **Actions After Submit > Email** (and **Email 2** if used).
4. Replace the corrupted shortcodes (e.g. `[tous-les-champs]`) with the original English shortcodes (e.g. `[all-fields]`).
5. Save the page.

### Option 2: Database search and replace (for many pages)

Run a search and replace on the `wp_postmeta` table where `meta_key = '_elementor_data'`. Replace the translated shortcodes with the originals.

**Example using WP-CLI:**

```bash
wp search-replace '[tous-les-champs]' '[all-fields]' wp_postmeta --include-columns=meta_value --dry-run
```

Remove `--dry-run` once you've confirmed the matches are correct. Always back up your database first.

### Option 3: Direct SQL via phpMyAdmin

First, check which pages are affected:

```sql
SELECT post_id, meta_value
FROM wp_postmeta
WHERE meta_key = '_elementor_data'
AND meta_value LIKE '%tous-les-champs%';
```

Then fix them:

```sql
UPDATE wp_postmeta
SET meta_value = REPLACE(meta_value, '[tous-les-champs]', '[all-fields]')
WHERE meta_key = '_elementor_data'
AND meta_value LIKE '%tous-les-champs%';
```

Adjust the translated shortcode for your language. Always back up your database first.

## License

GPL-2.0-or-later
