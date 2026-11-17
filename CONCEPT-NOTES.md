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

## Step 9: edge cases and media checks

Tested with 10 temporary entries (since deleted): a very long name with accents and an apostrophe, photo only (40px), video only, PDF only with a long label, a wide photo with a long unbroken URL and a table in the body, a very tall photo, a photo whose file was deleted, an entry with no summary, a non-YouTube video link, and an entry with no year.

Fixed:
- **Tall photos stretched the card** to 1450px. Card photos now share one shape (4:5, cropped from the top, max 10rem wide on phones). The dialog still shows the whole photo.
- **Small photos stretched blurry** in the dialog. They now keep their size.
- **Missing photo file** left a broken image box. If the image fails to load, the script removes the box on the card, and the figure in the dialog.
- **Long words and URLs** now wrap in card titles, summaries, dialog titles, and body text. Wide images, iframes, and tables in the body fit inside the dialog.
- **Entries with no start year** are left off the timeline. They would have made a blank decade section. The year field is required in the admin, so this only happens through imports or direct database edits.
- **Names with an apostrophe** match with or without it ("obrien" and "o'brien" both find O'Brien). Accents were already ignored.
- **Without JavaScript** the toolbar, jump bar, and Read more buttons are hidden (they would do nothing). The cards and decade headings still read as a plain list. Rich entries lose their detail view in that case. Acceptable for this audience, but worth knowing.

Checked and fine as built:
- Entry with no summary shows name and years only, no empty paragraph.
- Video-only, PDF-only, and photo-only entries each open a dialog that shows the summary as lead text.
- A saved non-YouTube video link shows no video and the entry stays compact.
- Compact entries have no photo box, no button, and no extra space.

Known limits, not fixed:
- **Two timelines on one page** would repeat the decade anchor ids. The dialog ids are unique. Not expected.
- **Tiny photos** (about 40px) get upscaled and look soft on the card. The README for the committee should say photos should be at least 400px wide.
- **Photo files stored off the server** (offloaded media) work, because the check is "does the image load", not "does the file exist on disk".

## Step 10: mobile pass (375 and 768 px)

Checked at 375 and 768 with the sample entries published.

Bug found and fixed:
- **The sbotfam theme restyles every `<select>` with Selectric** (`js/selectric.js`: `jQuery('select').selectric()`). Picking a Type or Decade from the restyled menu changed the select but never ran the filter, because Selectric announces the change through jQuery, which does not reach plain listeners. The script now also listens through jQuery when jQuery is on the page, and refreshes the Selectric copy when it sets a value itself (Clear filters, filters loaded from the address). This is a compatibility hook only. The plugin does not need jQuery and works the same without it. Tested: picking from the restyled menus filters correctly, Clear filters resets both labels, and `?type=chair&decade=1990s` shows the right labels on load.
- **Tap targets:** Read more, Clear filters, and the sort toggle were 39 to 43px tall. All buttons and links in the plugin are now at least 44px tall. Checked: no control under 44px.

Checked and fine:
- 375px: toolbar stacks in one column, the jump bar scrolls sideways, cards run the full width next to the spine, the photo sits above the text at a fixed size, and the dialog scrolls inside its box with Previous and Next in view.
- 768px: toolbar shows Type and Decade side by side, and cards are wide with the photo beside the text.
- Nothing from the plugin overflows the screen at either width.

Not the plugin, so not fixed:
- **The theme footer is 20px wider than the screen at 375px**, which gives the whole page a small sideways scroll. The theme's top bar did the same earlier. Needs a theme fix.

### Sticky header on phones

The theme's header uses Headroom: it hides when you scroll down and slides in when you scroll up, on every width. My earlier plan of `--flhist-sticky-offset: 131px` is wrong for that. With a fixed 131px, the bar leaves a gap under the top of the screen whenever the header is hidden, which is most of the time and always on phones. The dev page is back to the default offset of 0. With 0, the bar sits at the top of the screen and the header slides over it while scrolling up. That is acceptable, but the designer may want something different (for example, keeping the bar under the header only on scroll up). Question for the designer.

## Checkpoint: concept complete

Built steps 1 to 11 (the importer was built early, in step 2). Screenshots are in `review/` (375, 768, 1280 px: top, full timeline, rich card, dialog, filtered). `review/twentytwentyfive/` has the same page on the default block theme. They are not committed (binary files). Re-create them with `node tools/review.mjs <page url>`. The sbotfam screenshots show a PHP warning from the theme (`functions.php` line 43, "Undefined variable $string"). That is the theme, not the plugin.

### Acceptance checklist (SPEC section 12)

| # | Item | Result |
| --- | --- | --- |
| 1 | Activates with no PHP warnings on a block theme and a classic theme | Pass. Twenty Twenty-Five (block) and the sbotfam theme (classic). No PHP messages from the plugin on either. |
| 2 | Adding an entry takes under 2 minutes and sets the decade | Partly checked. The save path sets the decade, and the admin list columns, filters and field group load without errors (12 fields). I could not open the admin screens in a browser because they need a login. Someone should add one entry by hand and time it. |
| 3 | Shortcode shows timeline, jump bar, toolbar | Pass |
| 4 | Compact entries look finished, no empty space | Pass |
| 5 | Search by name, text, year. Filters combine | Pass |
| 6 | Dialog opens, traps focus, closes on Escape, returns focus, stops video | Pass, tested by keyboard at all three widths |
| 7 | Deep link opens an entry on load | Pass |
| 8 | 375, 768, 1280 with no horizontal scroll | Pass for the plugin. On sbotfam at 375 the page scrolls 20px sideways because of the theme footer. 0px on Twenty Twenty-Five. |
| 9 | Keyboard-only use works for every feature | Pass. Every control in the first 14 tab stops has a visible focus ring, and the dialog flow works by keyboard. |
| 10 | axe: no serious or critical issues | Pass for the plugin (0 violations in `.flhist`, 22 rules passed). With the dialog open, axe flags 3 issues inside YouTube's own player iframe, which we do not control. Whole page on sbotfam: 4 links without names and a viewport meta that blocks zoom (both theme). Whole page on Twenty Twenty-Five: one `aria-hidden-focus` issue (theme). |
| 11 | Changing tokens restyles the page | Pass. Accent, heading font, text color, spine color, chair color, and radius all followed. |
| 12 | No console errors. No third-party requests until a video opens | Pass for the plugin: no console errors, and the plugin makes no third-party request until the dialog opens a video (then youtube-nocookie.com, google.com, ytimg.com). The sbotfam theme loads Google and Bunny fonts on every page. Twenty Twenty-Five loads Bunny fonts. |
| 13 | Importer dry run and real run work on the sample CSV | Pass. Also re-run with `--update`. |

### Decisions that need a look from design or the director

1. **Compact entries have no detail view.** The 50 real chairs are all compact, so the dialog and Previous/Next do nothing for them today. Should every entry open a dialog, even a thin one?
2. **Chair markers are circles, milestone markers are diamonds, chairs are red, milestones steel blue.** Shape carries the meaning, not only color.
3. **Cards sit to the right of the spine on every screen size.** No alternating left and right, as the brief preferred.
4. **Card photos are cropped to 4:5 from the top.** The dialog shows the whole photo.
5. **Sticky decade bar versus the theme header.** The header hides on scroll down and slides in on scroll up. The bar stays at the top and the header slides over it. Fine, or should the bar move down with the header?
6. **Selectric:** the theme restyles every select menu with it. The plugin puts the native menu back, for accessibility, and styles it with its own arrow. Designer may want the theme's look instead.
7. **Search & Filter Pro was not used.** A small script filters the page in place. 64 entries need no server round trip, no license, and no extra plugin. Data attributes are set up so Search & Filter Pro could replace it later.
8. **The summary field is required**, so the real chairs have "Section Chair, City." as a placeholder summary. Replace when real text arrives.
9. **`!important` is used in two places only:** heading color and the focus ring, because the sbotfam theme forces both.

### Known gaps and things not done

- **Admin screens not seen in a browser** (login needed). See checklist item 2.
- **Admin CSV upload page** (the stretch goal in SPEC section 8) was not built. The importer is WP-CLI only.
- **Real content:** only names, years, and cities from the existing page. No photos, text, videos, or PDFs yet. No real milestones.
- **Theme problems found on sbotfam**, not fixed except the first:
  - `#sbotfam-wrap { overflow-x: hidden }` blocked sticky positioning. Changed to `clip`, uncommitted in the SBOTFAM repo.
  - PHP warning in `functions.php` line 43.
  - Footer is 20px wider than a 375px screen.
- **Live theme not known.** Everything was tested on the local copy of sbotfam and on Twenty Twenty-Five. The live site may differ, especially for sticky behavior and select menus.
- **Free ACF not tested.** The test site has ACF Pro. No Pro-only features are used, so free ACF should work. Check before launch.
- **Other browsers:** tested in Chromium only (Playwright and the built-in browser). Safari and Firefox are step 13.
- **Two timelines on one page** would repeat decade anchor ids.
- **Cleanup before any database push from this site:** delete the dev page "History Timeline (dev)" (ID 6592), delete the 14 draft sample entries and their media, and remove the symlink `wp-content/plugins/flhist-history` if you do not want the plugin there.

### Questions for the client

- Is Diana Friedman's "2012, 2013" a two-year term like the others?
- Hawthorne Phillips is listed as 1960 to 1961. The brief said the list starts in 1961. Which is right?
- Does the live site have ACF, and which theme and page builder does it use?

## Post-checkpoint changes (round 2)
- Every entry now opens the dialog (compact ones say "Details", rich ones "Read more"), so Previous/Next steps through all 50 chairs. Cards for compact entries are unchanged.
- New `City` field (`flhist_city`, optional, in the search blob, shown beside the badge). Importer has an optional `city` column. The 50 real chairs had the city moved out of the summary; their summary is now "Section Chair." and the real seed CSV was updated.
- Jump bar tracks a pinned header: a scroll listener finds the fixed/sticky element at the top edge (theme-agnostic) and sets `--flhist-header-offset`; the bar's `top` is sticky-offset plus that. Verified on sbotfam.test: bar sits flush under the fixed header (131px). The header hiding on scroll-up/down was not reproducible in headless Chromium, so that half is untested. Check in a real browser.

- Admin import page (History Entries > Import, `includes/class-flhist-import-page.php`): CSV upload, Preview (dry run) and Import now, update checkbox, nonce, capability `edit_others_posts`, .csv and 2 MB limits. Tested over HTTP as admin on sbotfam.test (preview, import, bad row reported). Photos and PDFs must be URLs here (relative paths only work through WP-CLI).
- Fixed an importer bug found while testing: a CSV without the optional columns (body, video_url, and so on) reported a false "not a YouTube link" error. All columns now default to blank.

- Round 3: arrow keys step through entries in the dialog (ignored in inputs and the video); Copy link button with a polite status message; print stylesheet (hides toolbar, bar, buttons, photos); admin warning for photos under 400px wide. Tested in Chromium: keys, clipboard, print media. The size warning is untested in a live edit screen (needs a login), code only.

- Cache-busting: asset version is now plugin version plus file modified time, so browsers refetch CSS/JS after any change. No manual bump needed.
- Cross-browser (Playwright WebKit and Firefox, 375 and 1280): no errors, no overflow, search, type filter, dialog, arrow keys, Escape, sticky bar all work. Real Safari on iPhone and Edge still unchecked.

- Committee walkthrough on sbotfam.test (admin, Playwright): added a Chair with name, years, city, summary and published. It appeared in 2020s with city. Publish took about 12s locally. Photo warnings (no alt text, under 400px) showed on the edit screen. Added a City column to the admin list. Wrote GUIDE-committee.md. Test entry and test photo were deleted.

- Round 4: Other years field (flhist_other_years, comma-separated four-digit years, validated, in search and year matching, importer column other_years). Photo cropping hint under the Photo box. Tested: year search matches other years, bad input rejected in the admin. Diana Friedman's entry was NOT changed. How to enter her term is still a client question.

- Round 5: Back to top link at the end of each decade (moves focus to the timeline top, hidden in print, hidden with its decade when filtered). JSON-LD ItemList of all entries, printed once per page (verified valid, 50 items, alongside Yoast's graph). Per-entry links use #entry-slug, so shares of one entry show the page's title and image, not the entry's.
