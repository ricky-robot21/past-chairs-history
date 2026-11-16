# Concept notes

Running log of decisions, skipped items, gaps, and questions. Newest section last.

## Setup (steps 1 and 2)

- **Local setup found:** Local by Flywheel is installed with several sites. No Docker, no global `wp` or `php`. Local ships its own PHP and WP-CLI (`/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/wp-cli.phar`).
- **Test site:** Robot GeneratePress (`robot-generatepress.local`, PHP 8.2, Twenty Twenty-Five and GeneratePress themes, ACF Pro 6.7). The plugin folder is symlinked into its `wp-content/plugins`, so edits in this repo show up there at once. The SBOTFAM site is not used for testing because it holds copied live customer data.
- **Project folder:** `~/past-chairs-history`, separate from the SBOTFAM site repo.

## Decisions

- **Type is an ACF taxonomy field (radio), not the default taxonomy box.** One required choice, defaults to Chair. Both taxonomies have `show_ui` off and `meta_box_cb` off. Neither is exposed in REST, so no taxonomy panel appears in the block editor.
- **Decade is set in PHP** on `acf/save_post` and `save_post_flhist_entry`, plus on activation and after import. It has no editing UI.
- **Importer was built early** (spec step 11) because sample data needs a loader. Step 11 time is mostly spent.
- **Importer photo and PDF paths:** a value that does not start with http is read as a path relative to the CSV. That lets the sample CSV use files in `data/`.
- **Imported photos have no alt text.** The CSV has no alt column, and I did not want to invent descriptions. The importer logs a note, and the edit screen warns. This also gives step 9 a real missing-alt case.
- **YouTube check** accepts watch, youtu.be, embed, and shorts links. The video ID is what gets used for the embed later.
- **Sample YouTube video** is the first video ever posted to YouTube (ID jNQXAC9IVRw), chosen as a neutral placeholder. Swap if the director prefers.
- **Shortcode output is a plain list for now.** Step 3 replaces it.
- **ACF Pro is on the test site, free ACF is the target.** No repeater is used, so both work. Should be re-checked on free ACF before launch.

## Skipped or not yet built

- Steps 3 to 11 except the importer.
- Optional CSV upload tools page (stretch).

## Questions for design and the director

- None yet.
