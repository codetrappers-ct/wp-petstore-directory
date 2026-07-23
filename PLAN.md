# Implementation Plan — WP Petstore Directory

A custom WordPress plugin that pulls pet listings from the public Swagger Petstore
API and renders them as a table via a custom Elementor widget. Built per the DBSync
technical assessment (`docs/WP vendors assessment.pdf`).

## API reconnaissance (verified, not assumed)

Endpoint: `GET https://petstore.swagger.io/v2/pet/findByStatus?status={status}`

| status      | records (observed)      | time    | payload   |
|-------------|-------------------------|---------|-----------|
| `available` | ~269–320 (volatile)     | 1.0–1.8s | 45–53 KB |
| `pending`   | ~2–4                    | ~1.1s   | <1 KB     |
| `sold`      | ~5–9                    | ~1.0s   | <2 KB     |

Confirmed behaviours:

- **Live, mutating dataset.** Counts drift call-to-call (shared public DB). No
  real-time need → caching is safe and correct.
- **No `limit` / `offset` support.** Both are silently ignored; the full set is
  always returned. Pagination *must* be client-side.
- **Invalid status is not an error.** Returns `HTTP 200` + empty `[]`. Status is
  case-sensitive (`AVAILABLE` → `[]`). Multi-status via repeated param works.
  → "empty result" is a normal state, distinct from "API unreachable".
- **Dirty data.** `name` often `"doggie"` / `"TestPet_XML"`; `category.name`
  ~1% missing and frequently junk (`"string"`, lorem ipsum); `photoUrls` mostly
  present but frequently **not valid URLs** (`"kangs url"`, `"string"`).
  → Defensive normalization + URL validation with fallbacks required.

## File structure

```
wp-petstore-directory/
├── wp-petstore-directory.php     # Plugin header, constants, activation/deactivation, bootstrap
├── uninstall.php                 # Remove options + transients on uninstall
├── README.md                     # Setup / widget usage / config
├── PLAN.md                       # This document
├── includes/
│   ├── class-plugin.php          # Bootstrap singleton — wires all hooks
│   ├── class-api-client.php      # Fetch + transient cache + normalization + errors
│   ├── class-settings.php        # Admin settings page (WP Settings API)
│   └── class-elementor.php       # Registers custom category + the widget
├── widgets/
│   └── class-pet-table-widget.php# \Elementor\Widget_Base subclass (controls + render)
├── templates/
│   └── pet-table.php             # Presentation only — all output escaped
├── assets/
│   ├── css/pet-table.css         # Table + pagination styling
│   └── js/pet-table.js           # Client-side rows-per-page pagination
└── docs/
    └── WP vendors assessment.pdf
```

## Class responsibilities

- **`Plugin`** — singleton bootstrap; loads deps, instantiates Settings/Api_Client,
  hooks Elementor registration, enqueues assets, guards against Elementor inactive.
- **`Api_Client`** — `get_pets($status)`: build URL, `wp_remote_get`, check
  `is_wp_error` + HTTP code, decode, normalize to `{name, category, status,
  photo_url}`. Transient cache keyed on `base_url + status`. Serve-stale-on-error.
  `flush_cache()` for busting. Returns normalized array or `WP_Error`.
- **`Settings`** — options page via WP Settings API (`register_setting`,
  `settings_fields()` nonce, sanitize callbacks). Getters for base URL + default
  status. Flushes Api_Client cache on save.
- **`Elementor_Manager`** — adds `petstore` category + registers the widget.
- **`Pet_Table_Widget`** — extends `\Elementor\Widget_Base`;
  `get_categories() → ['petstore']`; Content controls (status override,
  rows-per-page) + Style controls (border colour, header background) via
  `selectors`; `render()` resolves status → Api_Client → template.
- **`templates/pet-table.php`** — no logic; `esc_html` / `esc_url` / `esc_attr`.

## Requirement tensions & resolutions (decision log a)

1. **Caching (#5) ⟷ Error handling (#6) — the headline tension.**
   *Call:* **stale-while-error.** Short fresh TTL (~15 min) + longer fallback copy.
   On fetch failure serve last-good data; the error message only shows on a cold
   cache. Makes the two requirements cooperate; given the API's constant churn,
   nobody needs second-fresh data.

2. **Global settings (#4) ⟷ per-widget controls (#3).**
   *Call:* admin status filter is the **default/fallback**; widget has an
   **"Inherit (use default)"** option plus overrides. API base URL stays
   **global-only** (environment concern).

3. **Caching (#5) ⟷ dynamic per-widget controls (#3).**
   *Call:* cache key is **only** what changes the fetch (`base_url + status`).
   Rows-per-page and styling apply to the cached payload at display time — never
   trigger a refetch.

4. **"rows-per-page" as a *style* control (#3) ⟷ reality.**
   *Call:* it is behavioural, and the API has no server paging → implement as
   **client-side pagination**, placed in the **Content** section. Genuinely
   stylistic controls (border colour, header background) live in **Style** via
   Elementor `selectors`.

## Process

- Feature branch (`feature/petstore-plugin`), incremental commits noting *why*,
  merged to `main` via PR.
- Build order: scaffold (header + hooks) → API client + caching → Elementor
  widget → settings page → README + decision log.
