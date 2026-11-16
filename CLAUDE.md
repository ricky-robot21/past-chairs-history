# Past Chairs History Page (Texas Family Law Section)

Persistent project context for Claude Code. Read this first, then read SPEC.md for the full build spec.

## What we are building

An interactive history page for the Texas Family Law Section website (State Bar of Texas section). It replaces the plain past-chairs list with a timeline that honors about 47 past chairs (1961 to today) and key section milestones. Visitors can scan it in 30 seconds or dig into a single entry.

Core features:

- One timeline that mixes chairs and milestones in date order, grouped by decade, with a decade jump bar.
- Live search (name and text) plus filters for entry type and decade.
- A detail view for each entry with text, photo, YouTube video, and PDFs. Early entries may only have a name and a short snippet, and must look good that way.
- Entries managed in the WordPress admin like posts, so the History and Archives committee can add the next chair in minutes.
- Mobile-first, accessible (WCAG 2.1 AA).

Inspiration site from the client: https://iconicwomen.tyla.org/ (profiles with video, but no search or filters, which is the gap we fill).

## Who and why

- Agency: Robot Creative (San Antonio, TX), interactive department. Estimated at about 49.5 hours total, $9,692.50, under the client's $10,000 budget. Hours matter. Prefer the simple solution over the clever one.
- This is built "on spec" by the developer first. The designer and director review the working concept afterward and send changes. So build something that makes sense, make it easy to restyle, and write down your decisions.
- The client's committee supplies all content. We do not source or write history content.

## Hard rules

1. Build a self-contained WordPress plugin. It must work with any theme or page builder. The live theme is not known yet, so do not depend on a theme or builder.
2. No build step, no jQuery, no external CDNs, no npm runtime dependencies. Plain PHP, vanilla JavaScript, plain CSS. Dev tooling (Playwright, axe) is fine as devDependencies outside the plugin folder.
3. Prefix everything with `flhist_` (functions, CPT, taxonomies, options, shortcode) and `flhist-` (CSS classes). Do not use `fls_`. The same client has another build (the Section Report) that uses `fls_` names, including a CPT whose rewrite slug already caused a URL conflict.
4. The entry post type must have `rewrite => false` and `publicly_queryable => false`. Entries show in the timeline page and its dialog only. No single entry URLs, no archive. This avoids rewrite conflicts with the existing History pages.
5. All CSS is scoped under `.flhist` and driven by CSS custom properties, so the designer can retheme by changing tokens.
6. Accessibility is required, not optional. Keyboard operable, visible focus, proper dialog semantics, `prefers-reduced-motion` respected, contrast of 4.5:1 for text.
7. Copy style for every user-facing string, admin label, and README: plain, direct language. No em dashes. Nothing that sounds like AI marketing copy.
8. Placeholder content only. Never invent biographies or facts about real people. Use clearly labeled sample entries.
9. Local work only. Do not touch any live or staging site. No credentials, license keys, or client data in the repo.
10. Sanitize on input, escape on output, use nonces and capability checks in admin code.

## Stack and tools

- WordPress plugin, PHP 7.4+ compatible (8.x fine), current WordPress.
- Custom fields: ACF with local field groups registered in PHP (the client already uses ACF on the Section Report). Do not use the ACF repeater, since it needs ACF Pro. Use fixed fields.
- Search and filters: vanilla JS over server-rendered entries. The proposal mentioned Search & Filter Pro (which the client already licenses), but with about 50 entries a JS filter is faster to build, needs no license locally, and avoids a plugin dependency. Keep data attributes clean so Search & Filter Pro could be swapped in later. Flag this decision in CONCEPT-NOTES.md.
- Local WordPress: use whatever Ricky already runs (Local, WordPress Studio, wp-env). If nothing is set up, use `@wordpress/env` (needs Docker) with a default block theme such as Twenty Twenty-Five as the test theme.

## Things Ricky needs to confirm or provide

Ask for these if they are not already in the repo. Do not block on them. Use the defaults noted.

- Live site theme and page builder (default: assume unknown, stay theme-agnostic).
- URL of the existing Past Chairs list page and the History section, so you can pull names, years, and brand tokens (colors, fonts) from the public site.
- Whether ACF is installed on the live site (default: yes, free version).
- The client's chosen direction on layout, if it has arrived (default: vertical timeline with a left spine, card per entry, like the examples in SPEC.md section 13).
- Where this will deploy (not needed for the concept).

## Working agreements

- Initialize git in the project folder. Small commits with plain messages.
- Work in the order given in SPEC.md section 11. Stop at the checkpoint and report before moving on.
- Keep a running `CONCEPT-NOTES.md` in the repo root: decisions made, anything skipped, known gaps, and questions for design and the director.
- When the concept is done, produce screenshots at 375, 768, and 1280 px wide for review.
- If a requirement conflicts with the hours budget, pick the simpler option, note it, and keep moving.

## Where things live

```
flhist-history/            plugin root (what gets zipped and deployed)
  flhist-history.php
  includes/
  templates/
  assets/css/
  assets/js/
  data/
  README.md
CLAUDE.md                  this file (repo root, outside the plugin)
SPEC.md                    full spec
CONCEPT-NOTES.md           running decisions and questions
```
