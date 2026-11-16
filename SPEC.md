# Build Spec: Past Chairs History Page

Companion to CLAUDE.md. CLAUDE.md has the rules. This file has the detail.

## 1. Goal

Replace the flat past-chairs list on the Texas Family Law Section site with an interactive timeline of chairs and milestones. It lives under the site's History section as a normal WordPress page that contains one shortcode: `[flhist_timeline]`.

Success looks like: a visitor can find a chair by name or year in under 10 seconds, scan the whole history in 30 seconds, and open any entry for the full story. The committee can add or edit entries without a developer.

## 2. Scope

In scope:

- Plugin with entry post type, taxonomies, fields, and admin screens.
- Shortcode that renders the timeline, jump bar, search, filters, and entry dialog.
- Responsive layout and accessibility.
- Sample data and a CSV import template with a WP-CLI importer.
- README for the committee on adding entries.

Out of scope:

- Sourcing or writing history content, scanning photos, producing video.
- Awards section (separate 2022 scope, not part of this).
- Self-hosted video. YouTube embeds only.
- Single entry pages, archives, RSS, or search engine landing pages per entry.
- Theme or builder specific templates.

## 3. Data model

Post type: `flhist_entry`

- `public` true, `show_ui` true, `show_in_rest` true
- `publicly_queryable` false, `rewrite` false, `has_archive` false, `exclude_from_search` true
- Supports: title, editor, thumbnail, page-attributes (for manual order tiebreaks)
- Menu label: "History Entries", icon `dashicons-backup`
- Title holds the chair name or the milestone title.

Taxonomy: `flhist_type` (not hierarchical, single select in admin)

- Terms created on activation: `Chair`, `Milestone`
- Required. Default to `Chair`.

Taxonomy: `flhist_decade` (not hierarchical)

- Auto-assigned on save from `flhist_year_start` (for example 1994 gets "1990s"). Admin cannot edit it by hand. Hide its meta box.
- Re-run for all entries on plugin activation and after import.

ACF local field group "History Entry", shown on `flhist_entry`:

| Field name | Type | Notes |
| --- | --- | --- |
| `flhist_year_start` | number | Required. 4 digits. Start of term or year of milestone. |
| `flhist_year_end` | number | Optional. End of a chair's term. Must be at least year_start. |
| `flhist_display_date` | text | Optional. Overrides the computed label, for example "2019 to 2020 (co-chairs)". |
| `flhist_snippet` | textarea | Required. 1 to 2 sentences, soft limit 200 characters. Shown on the card. |
| `flhist_video_url` | url | Optional. YouTube watch or youtu.be link only. Validate. |
| `flhist_doc_1_file`, `flhist_doc_1_label` | file (pdf), text | Optional. |
| `flhist_doc_2_file`, `flhist_doc_2_label` | file (pdf), text | Optional. |
| `flhist_doc_3_file`, `flhist_doc_3_label` | file (pdf), text | Optional. |

Other content:

- Photo: the featured image. Alt text comes from the media library. Warn in admin if a featured image has no alt text.
- Full text: the main editor. Optional. When empty, the entry is a "compact" entry (see 5.4).

Sorting: `flhist_year_start` ascending, then `menu_order`, then title.

If ACF is not active, show an admin notice explaining that ACF is required, and do not fatal.

## 4. Admin experience

- List table columns: title, type, years, decade, "has photo", "has video".
- Sortable by years. Filterable by type and decade.
- Short helper text under each field, in plain language.
- A "How to add an entry" help tab on the edit screen with 5 short steps.
- Capabilities: use standard `edit_posts` for the post type unless told otherwise.

## 5. Front end

Shortcode: `[flhist_timeline]`

Optional attributes: `order="asc|desc"` (default asc), `show_filters="true|false"` (default true).

All entries are rendered on the server in one pass, no AJAX. About 50 entries is small. Full entry content is placed in a `<template>` element per entry and cloned into the dialog on open.

### 5.1 Layout

- Reads as a real timeline: a vertical spine with year markers and decade headings. Not a flat list.
- Desktop: spine on the left, cards to the right, wide reading column. Do not alternate left and right unless it clearly works better (note the choice in CONCEPT-NOTES.md).
- Mobile: spine on the left edge, cards full width.
- Each decade has a heading (for example "1990s") that is an anchor target.

### 5.2 Entry card

Shows: year label, name or title, type badge (Chair or Milestone), thumbnail if present, snippet. Rich entries have a "Read more" button that opens the dialog. The whole card is not a link. The button is the control.

### 5.3 Decade jump bar

- A row of decade links (1960s ... 2020s) that scrolls to each decade heading.
- Sticky under the top of the viewport. Use `top: var(--flhist-sticky-offset, 0px)` so it can clear a theme's sticky header.
- Highlights the decade currently in view (IntersectionObserver). Does not use scroll event handlers.
- Decades with no visible entries after filtering are hidden from the bar and the timeline.
- On narrow screens the bar scrolls horizontally.

### 5.4 Compact entries

Entries with no body text, photo, video, or PDFs are compact: name, years, snippet, and nothing else. No "Read more" button, no empty image box, no extra spacing. Early years (1961 to the mid 1990s) will mostly be compact.

### 5.5 Search and filters

A toolbar above the timeline:

- Text search: matches name, snippet, and full text. Case insensitive, ignores accents. Updates as the user types (debounce about 150 ms).
- Type filter: All, Chairs, Milestones.
- Decade filter: All plus each decade that has entries.
- Result count, announced to screen readers through an `aria-live="polite"` region ("12 entries shown").
- "Clear filters" button, shown only when something is active.
- Empty state: plain message and a clear button ("No entries match. Try a different name or year.").
- Year search: typing "1994" matches entries whose year range includes 1994.

Implementation notes:

- Each entry element carries `data-type`, `data-decade`, `data-year-start`, `data-year-end`, and `data-search` (a lowercased, accent-stripped text blob built in PHP).
- Filtering toggles the `hidden` attribute. No re-rendering.
- Keep the filter state in the URL query string (`?q=`, `?type=`, `?decade=`) so results can be shared. Nice to have, drop it first if hours get tight.
- Keep the markup and data attributes clean so the team could later swap in Search & Filter Pro.

### 5.6 Entry dialog

- Native `<dialog>` opened with `showModal()`. Closes on Escape, close button, and backdrop click. Focus returns to the button that opened it.
- Contents: large photo (with caption if the media item has one), name, years, type badge, full text, video, PDF links.
- Video: do not load the iframe until the dialog opens. Use `youtube-nocookie.com`. Responsive 16:9. Stop playback when the dialog closes (remove the iframe).
- PDFs: a list of links with the label, file type, and file size. Open in a new tab with `rel="noopener"`.
- Previous and Next buttons move through the currently filtered entries.
- Deep links: opening an entry sets `#entry-{slug}`. Loading a page with that hash opens the dialog. Closing clears the hash.

### 5.7 Sort toggle

A small "Oldest first / Newest first" toggle. Default is oldest first. Reordering is done in JS by reversing DOM order within and across decade groups. Keep it simple.

## 6. Responsive and accessibility

Breakpoints (mobile first): base, 640px, 960px, 1200px.

Accessibility checklist:

- Landmarks and headings: the shortcode output does not add an H1. Decade headings are H2, entry names are H3 in the timeline, and the dialog title is H2 inside the dialog.
- Every control reachable and usable by keyboard, with a visible focus style.
- Dialog: labelled by its title, focus managed, background not scrollable.
- Filters: real `<label>` elements, native inputs and selects.
- Color contrast at least 4.5:1 for text and 3:1 for UI components.
- Respect `prefers-reduced-motion` (no smooth scroll, no animations).
- Images have alt text from the media library. Decorative images get empty alt.
- Run axe on the page with sample data. Fix all serious and critical issues.

## 7. Theming tokens

All design values are CSS custom properties on `.flhist`, so the designer can retheme in one place:

```css
.flhist {
  --flhist-font-body: inherit;
  --flhist-font-heading: inherit;
  --flhist-color-text: #1f2937;
  --flhist-color-muted: #4b5563;
  --flhist-color-bg: transparent;
  --flhist-color-surface: #ffffff;
  --flhist-color-border: #d1d5db;
  --flhist-color-accent: #1d4ed8;
  --flhist-color-accent-contrast: #ffffff;
  --flhist-color-chair: var(--flhist-color-accent);
  --flhist-color-milestone: #b45309;
  --flhist-radius: 8px;
  --flhist-space: 1rem;
  --flhist-spine-width: 3px;
  --flhist-sticky-offset: 0px;
  --flhist-max-width: 1000px;
}
```

Defaults inherit from the theme where possible. When the live site is available, replace the accent and font values with the site's real ones.

## 8. Import

- Provide `data/import-template.csv` and a WP-CLI command: `wp flhist import <file.csv> [--dry-run] [--update]`.
- Columns: `title, type, year_start, year_end, display_date, snippet, body, photo_url, video_url, doc_1_url, doc_1_label, doc_2_url, doc_2_label, doc_3_url, doc_3_label`.
- `type` is `Chair` or `Milestone`. `body` may contain basic HTML.
- Match existing entries by title plus year_start so re-running updates instead of duplicating (only with `--update`).
- `photo_url` and doc URLs are sideloaded into the media library. If a URL fails, log it and continue.
- `--dry-run` prints what would happen and changes nothing.
- Validate and report row errors at the end in plain language.
- Stretch (only if hours allow): a simple Tools page with a CSV upload that runs the same importer. Note in CONCEPT-NOTES.md whether it was built.

## 9. Sample data

Create `data/sample-entries.csv` with about 14 clearly fake entries so the concept can be judged:

- 8 compact chairs from 1961 to 1990, named "Sample Chair 1961" and so on.
- 3 rich chairs from the 1990s to now, with a photo (use a neutral placeholder image), full text of lorem-style filler, and one with a YouTube link.
- 3 milestones, for example "Sample milestone: first toolkit release" and "Sample milestone: 50th Advanced Family Law course". Use these only as labels. Do not write any facts about them.

If the live Past Chairs page is reachable, also generate `data/real-chairs-seed.csv` with names and years only, as compact entries, for local review. Do not add bios. Do not commit it if it contains anything beyond public names and years.

## 10. Plugin structure

```
flhist-history/
  flhist-history.php        plugin header, constants, bootstrap
  includes/
    class-flhist-post-types.php     CPT and taxonomies, decade auto-assign
    class-flhist-fields.php         ACF local field group, admin columns, notices
    class-flhist-render.php         shortcode, data prep, search blob
    class-flhist-importer.php       WP-CLI command and CSV parsing
  templates/
    timeline.php
    entry-card.php
    entry-detail.php        content for the <template> used by the dialog
  assets/
    css/flhist.css
    js/flhist.js            filtering, jump bar, dialog, deep links, sort
  data/
    import-template.csv
    sample-entries.csv
  README.md                 for the committee: how to add and edit entries
```

Enqueue CSS and JS only on pages that contain the shortcode. Version assets with the plugin version. Include an uninstall routine that leaves content in place (do not delete entries on uninstall).

## 11. Build order and hours

Developer hours for the concept build total 35. Stop at the checkpoint.

| Step | Work | Hours |
| --- | --- | --- |
| 1 | Local WordPress, repo, plugin skeleton, post type, fields, notices | 3 |
| 2 | Taxonomies, decade auto-assign, sample data loaded | 2 |
| 3 | Timeline template with decade grouping, cards, compact entries | 6 |
| 4 | Styling to match the site and chosen direction, tokens | 7 |
| 5 | Decade jump bar with sticky and current-decade highlight | 3 |
| 6 | Search and filters (JS), result count, empty state | 4 |
| 7 | Filter bar styling and integration with the timeline | 2 |
| 8 | Entry dialog with photo, text, video, PDFs, previous and next | 3 |
| 9 | Edge cases (compact entries, missing alt text, long text) and media checks | 2 |
| 10 | Mobile layout pass | 2 |
| 11 | CSV template and WP-CLI importer | 1 |

CHECKPOINT: concept complete.

- Write CONCEPT-NOTES.md (decisions, skipped items, gaps, questions for design and the director).
- Take screenshots at 375, 768, and 1280 px wide with Playwright, saved to `review/`.
- Run axe and list results in the notes.
- Stop and report. The director and designer review the concept next.

After the review (not part of this first pass):

| Step | Work | Hours |
| --- | --- | --- |
| 12 | Revisions from design and director input | 3 |
| 13 | QA: responsive, accessibility, browsers | 3 |
| 14 | Client training | 1 |
| 15 | Launch and deployment | 2 |

## 12. Acceptance checklist for the concept

- [ ] Plugin activates cleanly with no PHP warnings, on a default block theme and one classic theme.
- [ ] Adding an entry in the admin takes under 2 minutes and the decade is set automatically.
- [ ] `[flhist_timeline]` on a normal page shows the timeline, jump bar, and toolbar.
- [ ] Compact entries look finished, with no empty space.
- [ ] Search finds by name, by text, and by year. Filters combine correctly.
- [ ] The dialog opens, traps focus, closes with Escape, returns focus, and stops video on close.
- [ ] Deep link to an entry works on page load.
- [ ] Works at 375, 768, and 1280 px with no horizontal scroll.
- [ ] Keyboard-only use works for every feature.
- [ ] axe reports no serious or critical issues.
- [ ] Changing the accent color and fonts in the tokens restyles the whole page.
- [ ] No console errors. No requests to third parties until a video is opened.
- [ ] Importer dry run and real run both work on the sample CSV.

## 13. Reference examples (also sent to the client)

Use these for layout and behavior ideas. Do not copy their code, text, or images.

- https://iconicwomen.tyla.org/ (client inspiration: profiles, jump links, Vimeo video, no search or filters)
- https://gradschool.duke.edu/centennial-home/our-history/ (interactive timeline, click or scroll to jump)
- https://stockton.edu/fifty/timeline.html (arrows to move through time, zoom by decade)
- https://www.oshkoshcorp.com/story/history (grouped by decade, decade link row, video per decade)
- https://www.annefrank.org/en/anne-frank/the-timeline/ (captioned photos, carousels, previous and next)
- https://www.cincybar.org/timeline (bar association timeline with thumbnails and media, no filters)
- https://www.sfbar.org/150th/ (past presidents with portraits and reflections)
- https://histography.io (category filters and zoom by decade)

## 14. Assumptions to confirm later

- Live theme and builder can host a shortcode on a normal page.
- ACF is available on the live site.
- Entries number about 47 chairs plus a handful of milestones, so one-page rendering is fine. If the count goes past about 150, revisit with pagination or lazy rendering.
- YouTube is the only video source.
- The client's History and Archives committee enters content, targeted for about March 2027.
