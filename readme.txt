=== WP Petstore Directory ===
Contributors: codetrappers
Tags: elementor, api, directory, table, petstore
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pulls pet listings from the Swagger Petstore API and displays them as a table via a custom Elementor widget.

== Description ==

WP Petstore Directory adds a custom Elementor widget ("Pet Table", under the
"Petstore" category) that fetches pets from the Swagger Petstore API and renders
them as a formatted, paginated table.

* Fetches pets by status (available / pending / sold) from `GET /pet/findByStatus`.
* Custom Elementor widget with content controls (status override, rows per page,
  show/hide photo) and style controls (border colour, header background, header
  text colour) driven by live Elementor selectors.
* Responses are cached in transients with a stale-while-error strategy: a short
  fresh window avoids calling the API on every render, and a longer fallback copy
  is served if the API becomes unreachable.
* Graceful, distinct handling of the empty result and API-error states.
* Admin settings page (Settings → Petstore Directory) to configure the API base
  URL and default status filter without touching code.

== Installation ==

1. Upload the `wp-petstore-directory` folder to `/wp-content/plugins/`, or install
   the plugin zip through Plugins → Add New → Upload.
2. Activate the plugin through the Plugins menu.
3. Ensure Elementor (free) is installed and active.
4. Configure the API base URL and default status under Settings → Petstore Directory.
5. Edit a page with Elementor, search for "Pet Table" under the Petstore category,
   and drop it onto the page.

== Frequently Asked Questions ==

= Does this require Elementor Pro? =

No. It is built against free Elementor.

= How often is the data refreshed? =

Successful responses are cached for 15 minutes. If the API is unreachable, the
last good response is served for up to 7 days rather than showing an error.

== Changelog ==

= 0.1.0 =
* Initial version: API client with caching, admin settings page, and the Pet
  Table Elementor widget.
