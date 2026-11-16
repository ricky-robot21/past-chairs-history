# Concept notes

Running log of decisions, skipped items, gaps, and questions. Newest section last.

## Setup (steps 1 and 2)

- **Local setup found:** Local by Flywheel is installed with several sites. No Docker, no global `wp` or `php`. Local ships its own PHP and WP-CLI (`/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/wp-cli.phar`).
- **Test site:** SBOTFAM (`sbotfam.test`, PHP 8.5.3, ACF Pro 6.8). Ricky chose it over Robot GeneratePress. It is a local copy of the live site, so it holds copied customer data. Only sample entries were added. The plugin folder is symlinked into its `wp-content/plugins`, and the symlink is listed in that repo's `.git/info/exclude` so it never gets committed. Remove the sample entries before any database push to a live or staging site.
- **WP-CLI:** `./wpl.sh <command>` runs WP-CLI against the SBOTFAM site with Local's PHP and its MySQL socket (site run folder `48ZlbwRuP`).
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

## Checked so far (CLI only, admin screens not yet viewed)

- Plugin activates with no PHP errors. Terms Chair and Milestone are created.
- Sample import: dry run and real run both work. Re-running skips all 14 entries.
- Decades assign correctly (1960s to 2020s) and re-assign when the year changes on save.
- Entry post type has `rewrite` false and `publicly_queryable` false.
- YouTube parsing accepts youtu.be and watch links and rejects other hosts.

## Skipped or not yet built

- Steps 3 to 11 except the importer.
- Optional CSV upload tools page (stretch).

## Questions for design and the director

- None yet.

## Real chair entries (loaded from the Past Chairs page)

- **Source:** the existing page at `/history/past-section-chairs/` (page ID 421) on sbotfam.test. A script parsed the names, cities, and years. 50 chairs, 1960 to 2026. All 50 were imported as published entries with no errors.
- **What each entry has:** name, start year, end year, and a short summary built only from the page: "Section Chair, Lubbock." Lisa Hoppes has no city on the page, so hers reads "Section Chair." No bios, photos, or other text were added. The committee will supply the rest.
- **Summary field is required**, so the city was used as a factual placeholder. Replace it when the client sends real text.
- **`data/real-chairs-seed.csv` is not committed.** It has cities as well as names and years, and the brief says to commit only public names and years. It is in `.gitignore`. Re-create it from the page, or ask Ricky for the file.
- **Hawthorne Phillips starts in 1960**, which is earlier than the "1961" in the brief. It is what the page says.
- **Diana Friedman** is listed as "2012, 2013" on the page. Imported as 2012 to 2013. The page and the neighboring terms suggest a normal single-year term, so confirm with the client.
- **Sample entries are now drafts**, not deleted. They hold the photo, video, PDF, and milestone cases we need to test the dialog in step 8. Publish them to test, then draft them again. They must be deleted before launch.
- **Milestones:** none are real yet. The timeline is chairs only until the client sends some.

## Step 3: timeline template

- **Markup:** a section per decade (H2), an ordered list of entries inside it, one `<li>` per entry with an article card (H3 name). Each entry carries `data-type`, `data-decade`, `data-year-start`, `data-year-end`, and `data-search`.
- **Compact vs rich:** an entry is rich if it has body text, a photo, a video link, or a PDF. Rich entries get a photo block and a "Read more" button. Compact entries get neither, and have tighter padding.
- **Spine:** drawn with a left border on each decade section, with a marker on each entry (circle for chairs, diamond for milestones). Cards sit to the right of the spine on all screen sizes. No left and right alternating.
- **Assets** load only on pages that hold the shortcode (checked by `has_shortcode`), and the shortcode also enqueues them as a fallback for page builders.
- **Order attribute:** `order="desc"` reverses entries and decades on the server. `show_filters` is read but does nothing until step 6.
- **Base CSS** in this step is structure plus a plain first look. Step 4 matches the site's colors and fonts.
- **"Read more" does nothing yet.** The dialog is step 8.
- **Dev page:** `https://sbotfam.test/flhist-dev/` (page ID 6592) holds the shortcode. Delete it before any database push.
- **Theme issues seen on sbotfam.test, not from this plugin:** a PHP warning from the theme's `functions.php` line 43 ("Undefined variable $string") prints on pages, and the theme's top bar makes the page 20px wider than the screen at 375px. The timeline itself stays inside the screen.

## Step 4: styling

- **Brand values** were read from the sbotfam.test theme with the browser: navy `#002a4d` (text and buttons), red `#a32327` (spine, chair markers, button hover), steel blue `#03466b` (milestones), cream page background `#fefcf2`, Lato from the theme. The site uses square corners and uppercase bold headings, so the card radius is 0 and decade and card titles are uppercase.
- **Only token values changed.** The rules still read from the tokens in `.flhist`, so the designer can retheme in one block at the top of `flhist.css`. Two tokens were added beyond the spec list: `--flhist-color-accent-hover` and `--flhist-color-spine`.
- **Fonts inherit from the theme.** `--flhist-font-body` and `--flhist-font-heading` are still `inherit`, so the page picks up Lato here and the right font on any other theme.
- **Chairs and milestones differ by more than color:** circle marker vs. diamond marker, plus the badge text.
- **Contrast measured in the browser:** card titles and summary 14.6:1, decade headings 14.2:1, year 8.2:1, chair badge 7.4:1, milestone badge 10.0:1, button text 14.6:1. All pass the 4.5:1 target. Button hover (white on red) is about 7.4:1.
- **Not yet checked:** the designer's direction. This is a first pass built from the site's existing look.

## Step 5: decade jump bar

- **Markup:** a `<nav aria-label="Jump to a decade">` with one link per decade, above the timeline. Without JavaScript the links still jump to the decade headings.
- **Current decade** is tracked with one `IntersectionObserver` (no scroll handlers) and marked with `aria-current="true"` plus an underline. On narrow screens the bar scrolls sideways and keeps the current decade centered. It never scrolls the page itself.
- **Click behavior:** scrolls to the decade (smooth, or instant with reduced motion), then moves focus to the decade heading so keyboard and screen reader users land there. The URL hash is not changed, so it will not clash with entry deep links (`#entry-slug`) in step 8.
- **Hiding empty decades:** `root.flhist.jump.refresh()` hides bar links and decade sections that have no visible entries. Step 6 calls it after each filter change. It is not wired to anything yet.
- **Offsets:** the bar sticks at `top: var(--flhist-sticky-offset)`. Headings and entries use the measured bar height (`--flhist-bar-height`, set by JS) in `scroll-margin-top`, so anchors do not land under the bar. New token: `--flhist-color-bar-bg`.

### Problem found: the sbotfam theme blocks sticky positioning

The theme sets `#sbotfam-wrap { overflow-x: hidden; }` (`style.css` line 563). With that, the browser treats the wrapper as the scroll container, and `position: sticky` on anything inside it never sticks. The jump bar scrolls away with the page.

I confirmed the cause: setting the wrapper to `overflow: visible` in the browser made the bar stick and the highlight work at 1280 and 375 px. The plugin cannot work around this cleanly. The fix is a one-line theme change: `overflow-x: clip` in place of `hidden` (works in Chrome 90+, Safari 16+, Firefox 81+). I did not change the theme. Needs a decision from Ricky or the director. The same issue will affect any sticky element in this theme, and the live theme may or may not behave this way.

**Update:** Ricky approved the theme change. In `sbotfam/style.css` the `#sbotfam-wrap` rule now uses `overflow-x: clip` in place of `overflow-x: hidden`. Checked in the browser on sbotfam.test: the jump bar sticks at the top and highlights the decade in view. The change is in the SBOTFAM site repo and is not committed there. If the live theme has the same rule, it needs the same one-line change before launch.

## Step 6: search and filters (the toolbar styling in step 7 is mostly done here)

- **Toolbar:** a search box, Type menu (All, Chairs, Milestones), Decade menu, a sort toggle, a Clear filters button, and a result count. All native inputs with real labels. Clear filters only shows when something is active.
- **Search:** case and accent insensitive, updates 150 ms after typing stops, matches name, city and summary text, full text, and the year label. Several words must all match. A four digit word also matches any entry whose years include it, so "1994" finds John Compere (1993 to 1994) and Brian Webb (1994 to 1995).
- **Filtering** only toggles the `hidden` attribute. Nothing is re-rendered. Decades with no visible entries disappear from the page and the jump bar.
- **Result count** sits in an `aria-live="polite"` region: "3 entries shown, oldest first". When nothing matches, an empty message and a Clear filters button show in place of the timeline.
- **Shareable links:** the filters are kept in the address as `?q=`, `?type=`, `?decade=`, and are read again on load. Other query parameters are left alone. Values that are not valid options are ignored. This is the "nice to have" in the spec. It was cheap, so it is in.
- **Sort toggle (spec 5.7):** "Oldest first / Newest first". It reverses the decades, the entries in each decade, and the jump bar in the page, so it works together with the filters. The shortcode's `order="desc"` sets the starting state.
- **Data attributes** on each entry are the same set Search & Filter Pro would need, so it could be swapped in later.
- **Tested in the browser** with the sample data published (64 entries): name, case, year, body text, multi-word city, no match, each filter alone and combined, Clear, loading from a URL, bad URL values, sort and back.

### Sticky header on sbotfam

The theme has a fixed header that appears after scrolling, about 131px tall. The jump bar sticks at `top: 0` by default, so it would sit under that header. The fix is the token that already exists: `--flhist-sticky-offset: 131px`. On the dev page I set it with a small style block. For the real page the designer can put it in the theme CSS: `.flhist { --flhist-sticky-offset: 131px; }`. The header height may differ on phones. Check in step 10.

The current-decade highlight now watches a thin band just below the sticky header and bar, using that offset, instead of a fixed share of the screen.

### Testing note

The browser pane in this tool does not draw frames until a screenshot is taken, which delays `IntersectionObserver` callbacks. A scripted scroll test can show no highlight until a frame is drawn. It worked once a frame was drawn.

## Step 8: entry dialog

- **Native `<dialog>`** opened with `showModal()`. One dialog per timeline. It closes on Escape, the visible Close button, and a click on the dark area. Focus starts on Close, stays inside while open (native), and returns to the entry's "Read more" button on close. The page behind is locked from scrolling, with the scrollbar width kept so the page does not jump.
- **Content** sits in a `<template>` inside each rich entry and is copied in on open, so photos and text do not load with the page. The dialog title is the H2 and PDF list heading is an H3, as in the spec.
- **Video:** no iframe exists until the dialog opens. It uses `youtube-nocookie.com`, 16:9, with a title. The iframe is removed on close or when stepping to another entry, which stops playback. No request to YouTube happens before then.
- **PDFs:** label, "(PDF, 609 B)" with type and size, new tab with `rel="noopener"`, and hidden text saying it opens in a new tab.
- **Photo caption** comes from the media library caption field. Photo alt comes from the alt field. A photo with no alt text shows as `alt=""`, which screen readers skip. That is the missing-alt case flagged in the admin.
- **Previous and Next** step through the entries the filters currently show, in the order on the page (so Newest first reverses them). Only entries with a dialog are included, since compact entries have no "Read more". Buttons disable at each end, and carry the neighbor's name for screen readers. After stepping, focus moves to the new title so the change is announced. This is a decision to confirm: stepping skips compact entries, so on the real data (all compact for now) it has nothing to step through.
- **Deep links:** opening sets `#entry-slug` without scrolling or adding history entries. Closing clears it. Loading a page with that hash opens the dialog. Changing the hash while open switches entry, and a hash that points somewhere else closes it. A hash for a compact entry scrolls to the card as normal.
- **Tested in the browser:** open, Escape, Close button, backdrop click, focus return, video present only while open, Previous and Next with filters, deep link on load, hash change while open, and the 375 px layout (content scrolls inside the box, Previous and Next stay in view).
- **Browser pane quirk again:** `close` events fire late in this pane until a frame is drawn, which exposed a re-entry bug when a script opened and closed fast. Fixed with two small guards. Real use is not affected.
