# Past Chairs History Timeline

A timeline of past chairs and Section milestones. Visitors can scan it by decade, search it, filter it, and open any entry for the full story.

## For the History and Archives committee

### Add an entry

1. In the WordPress menu, go to **History Entries**, then **Add New**.
2. Type the chair's name, or the milestone title, in the title box.
3. Choose **Chair** or **Milestone**. Enter the start year. For a chair, enter the end year too.
4. Write a short summary of one or two sentences. This is the only text an early entry needs.
5. Click **Publish**. The entry appears on the timeline in the right decade. You do not set the decade yourself.

An entry with only a name, years, and a summary is a complete entry. It shows as a compact card, and its **Details** button opens a short window with the same information. Every entry can be stepped through with Previous and Next.

### Add more to an entry

Each of these is optional. An entry that has any of them gets a **Read more** button instead of **Details**.

- **Other years.** For a chair whose term was not one run of years, such as 2013 and 2015, type `2013, 2015` in **Other years**. A visitor who searches for one of those years finds the entry. The card still shows the start and end year, or the Date label.
- **City.** Type the chair's city in the **City** box. It shows beside the years and can be searched.
- **Photo.** Use the **Photo** box on the right. Pick a picture that is at least 400 pixels wide. After you upload it, fill in the **Alt text** field in the media library. Alt text describes the picture for people who cannot see it. A warning shows on the edit screen if it is missing or if the photo is narrower than 400 pixels.
- **Full story.** Type it in the main text box.
- **Video.** Paste a YouTube link, such as `https://www.youtube.com/watch?v=...` or `https://youtu.be/...`. Other video sites do not work.
- **Video transcript.** If you add a video, also paste the words spoken in it into **Video transcript**. Visitors see it in a collapsible box under the video. The edit screen reminds you if it is missing.
- **PDFs.** Upload up to three. Give each one a label, such as "1994 Annual Report". Visitors see the label, the file type, and the size.

### Add many entries from a spreadsheet

1. Go to **History Entries**, then **Import**.
2. Download the CSV template, fill in one row per entry, and save it as CSV.
3. Choose the file and click **Preview**. Nothing is saved yet. Fix any rows it reports.
4. Choose the file again and click **Import now**.

Photos and PDFs in the file must be web addresses. Tick the box to update entries that already exist.

### Fix or reorder an entry

- Open it from **History Entries**, change what you need, and click **Update**.
- The list is sorted by start year. Two entries in the same year are sorted by the **Order** number (in the Page Attributes box), then by title.
- To take an entry off the timeline without deleting it, change its status to **Draft**.

### Things to know

- **View on timeline.** The Publish box on the edit screen, and each published entry in the History Entries list, has a **View on timeline** link. It opens the page that has the `[flhist_timeline]` shortcode, at that entry. Developers can choose a different page with the `flhist_timeline_page_id` filter.
- **Sharing and printing.** The window has a **Copy link** button, and the left and right arrow keys move between entries. Printing the page gives a clean list of years, names, and summaries without the search tools.
- **Date label.** If the years need special wording, such as "2019 to 2020 (co-chairs)", type it in the **Date label** box. It replaces the years on the card.
- **Entries have no page of their own.** They only show on the timeline page.
- **Deleting the plugin does not delete entries.**

## For the developer

### Requirements

WordPress 6.0 or newer, PHP 7.4 or newer, and the Advanced Custom Fields plugin. The free version is enough. The plugin shows an admin notice if ACF is missing.

### Put the timeline on a page

Add a normal page and put this in it:

```
[flhist_timeline]
```

Options:

- `order="asc"` or `order="desc"`. Oldest first is the default.
- `show_filters="false"` hides the search and filter toolbar.

The shortcode adds no H1. Decade headings are H2 and entry names are H3, so give the page its own H1. The CSS and JavaScript load only on pages that use the shortcode.

### Search engine data and Back to top

The timeline prints a small block of structured data (JSON-LD, an ItemList of every entry with its name, years, summary, and link) so search engines can read the list. It prints once per page. Page title, description, and share image for the page itself still come from the SEO plugin (Yoast on this site). Each decade ends with a **Back to top** link.

### Change the look

All colors, fonts, and sizes are CSS variables on `.flhist`, at the top of `assets/css/flhist.css`. Override them in the theme or in a page's CSS:

```css
.flhist {
	--flhist-color-accent: #0a5;
	--flhist-font-heading: Georgia, serif;
	--flhist-sticky-offset: 80px; /* Height of a fixed site header, so the decade bar sits below it. */
}
```

Heading color and the keyboard focus ring use `!important`, because some themes set those the same way.

### Import from a spreadsheet

Fill in `data/import-template.csv` (the `city`, `other_years`, and `transcript` columns are optional), then run:

```
wp flhist import path/to/file.csv --dry-run
wp flhist import path/to/file.csv
wp flhist import path/to/file.csv --update
```

`--dry-run` shows what would happen and changes nothing. Without `--update`, entries that already exist (same title and start year) are skipped. Photo and PDF columns take a web address, or a path relative to the CSV file. A row with a problem is reported at the end and the rest still import.

### Files

```
flhist-history.php          plugin header and start-up
includes/                   post type, fields, shortcode, importer, import page
templates/                  timeline, card, and dialog markup
assets/css/flhist.css       styles and tokens
assets/js/flhist.js         decade bar, search, filters, dialog
data/                       CSV template and sample entries
```
