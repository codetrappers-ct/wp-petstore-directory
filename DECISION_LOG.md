# Decision Log

## a. Tradeoffs noticed in the requirements, and how I resolved them

1. **Caching (#5) vs. graceful error handling (#6)** — the headline tension.
   When a fetch fails but a cached copy exists, showing an error contradicts the
   point of caching. Resolved with **stale-while-error**: a 15-minute fresh
   transient answers most reads, a 7-day fallback copy is served when the network
   fails, and the error message appears only on a cold cache. Given the API's
   data churns constantly (counts swung 269→320 across probes), nobody needs
   second-fresh data.
2. **Global settings (#4) vs. per-widget controls (#3)** — status is configurable
   in both places. Resolved by making the admin value the **default** and giving
   the widget an *"Inherit (use global default)"* option plus explicit overrides.
   API base URL stays global-only (an environment concern).
3. **"rows-per-page" listed as a *style* control, but it's behavioural** — and
   the API has no `limit`/`offset` (verified). Resolved by placing it in the
   **Content** tab and implementing **client-side pagination**; only genuinely
   stylistic controls (border/header colours) live in Style, via Elementor
   selectors.
4. **Caching vs. dynamic controls** — the cache key depends only on base URL +
   status, so changing colours or page size never triggers a refetch.

## b. Why this caching approach over alternatives

WordPress **transients** were required, but the design choice was *what* to store.
A single-TTL transient forces a binary: serve stale or error. Splitting into a
short **fresh** window plus a long **fallback** copy lets the plugin avoid API
calls on the hot path *and* survive outages without showing visitors an error —
the best of both, at the cost of one extra option row per status. I rejected
object-cache-only approaches (not guaranteed present) and caching post-pagination
(would multiply cache entries per widget config for no benefit).

## c. First thing that would break under real-world load

The `available` set is ~270–320 records (~50 KB) and pagination is **client-side**,
so the **entire result set is shipped to every browser** and the DOM holds every
row. That's fine at demo scale, but if the directory grew to thousands of pets the
page weight and DOM size would degrade first — long before the cached API call
became a problem. The API's lack of `limit`/`offset` is the root cause.

## d. What I'd do differently with more time

- **Server-side pagination/capping** once the upstream API (or a proxy) supports
  it, to fix the load issue above.
- **Background cache refresh** (WP-Cron) so no visitor ever pays the ~1.5s fetch.
- **Automated tests** (PHPUnit + WP test suite) in place of the standalone
  verification harnesses used during development.
- **Multi-status support** (the API accepts repeated `status` params) and an
  image placeholder for the ~76% of records whose photo field isn't a real URL.
