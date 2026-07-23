# WP Petstore Directory

A custom WordPress plugin that pulls pet listings from the public
[Swagger Petstore API](https://petstore.swagger.io/) and displays them as a
formatted, paginated table via a custom **Elementor** widget.

Built for the DBSync technical assessment. Requires free Elementor — no
Elementor Pro features are used.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Elementor (free) installed and active

## Setup

1. Copy the `wp-petstore-directory` folder into `wp-content/plugins/`, **or**
   install `dist/wp-petstore-directory-0.1.0.zip` via **Plugins → Add New →
   Upload Plugin**.
2. Activate **WP Petstore Directory** from the Plugins screen.
3. Make sure Elementor is active.
4. (Optional) Configure the API under **Settings → Petstore Directory**.

## Configuration

**Settings → Petstore Directory** (or the *Settings* link on the plugin row):

| Setting | Description |
|---|---|
| **API Base URL** | Base URL of the Petstore API. The plugin appends `/pet/findByStatus`. Default: `https://petstore.swagger.io/v2`. Validated as an http(s) URL. |
| **Default Status Filter** | `Available`, `Pending`, or `Sold`. Used by any widget set to *inherit*. |

Changing the base URL clears cached responses so stale data from the previous
host can't linger.

## Widget usage

1. Edit a page or template with Elementor.
2. In the widget panel, search **"Pet Table"** (under the **Petstore**
   category) and drag it onto the canvas.
3. Configure it in the panel:

**Content tab**

- **Status filter** — `Inherit (use global default)` or an explicit
  status. Inherit uses the value from the settings page; an explicit choice
  overrides it for this widget only.
- **Rows per page** — page size for the in-browser pagination (1–100).
- **Show photo column** — toggle the photo column on/off.

**Style tab**

- **Border color**, **Header background**, **Header text color** — applied
  live via Elementor selectors.

## How it works

- **Data**: `GET {base}/pet/findByStatus?status={status}`. Records are
  normalized to name, category, status and a validated photo URL. Dirty values
  from the public API (missing names/categories, non-URL "photo" strings) fall
  back to an em dash / no image.
- **Caching**: successful responses are cached in a WordPress transient for
  15 minutes, plus a 7-day fallback copy. If the API is unreachable, the
  fallback is served instead of an error (*stale-while-error*). Cache keys
  depend only on base URL + status, so styling and pagination never refetch.
- **States**: the widget renders one of three outcomes — the table, an empty
  message (a valid `HTTP 200 + []` result), or a graceful error message (only
  when the API fails with no cached fallback). In the Elementor editor, the
  empty and error messages include extra diagnostic detail.
- **Pagination**: performed in the browser, because the Petstore API supports
  no `limit`/`offset` parameters.

## Development notes

See [`PLAN.md`](PLAN.md) for the design and verified API findings, and
[`DECISION_LOG.md`](DECISION_LOG.md) for the key tradeoffs.

Build the installable zip:

```bash
bash bin/build-zip.sh
```

## License

GPL-2.0-or-later.
