# AI Collaboration Record

**Tool used:** Claude Code (Claude Opus 4.8), driven interactively from the
project directory.

> **Note on this document.** The section below is a *summary* of how the AI
> collaboration actually went, written at the end of the session. It is **not**
> a verbatim chat export. The raw prompt/response history must be exported from
> the Claude Code client and pasted into the "Raw transcript" section at the
> bottom (or attached alongside this file) — see the instructions there.

---

## How the work was driven

The session ran as a directed build, not a single "write me a plugin" prompt.
Roughly: inspect the environment → read the brief → produce a plan → challenge
the plan's assumptions against the real API → implement in reviewed increments
→ debug a real failure → triage a linter → package.

## Points where AI output was verified, corrected, or pushed back on

These are the moments that materially changed the result.

**1. Assumptions challenged before any code was written.**
The first implementation plan was written from knowledge of the Swagger Petstore
API, not from evidence. The reviewer pushed back directly — *"have you actually
checked the apis or you are assuming?"* — and asked for concrete probes: record
counts per status, latency, `limit`/`offset` support, invalid-status behaviour,
and how often fields are populated.

Running those probes **overturned three assumptions** and changed the design:

| Assumption | Reality (measured) | Design consequence |
|---|---|---|
| Server-side paging is available | `limit`/`offset` silently **ignored** | Client-side pagination became the only option, not a preference |
| Invalid status returns an error | Returns **HTTP 200 + `[]`**, and is case-sensitive | "Empty" became a first-class state distinct from "error"; status inputs became whitelisted selects |
| `photoUrls` contains URLs | Only **~24%** are valid URLs (`"kangs url"`, `"string"`) | Added URL validation + fallback; naive `esc_url` would have emitted ~240 broken `<img>` per page |

The lesson applied thereafter: probe first, then design.

**2. Repeated re-verification rather than trusting a single result.**
API counts were re-checked several times and shown to drift (320 → 269 → 270
across runs), which is what justified the caching strategy and the decision not
to rely on stable counts.

**3. Logic verified with standalone harnesses, not assertion.**
Rather than claiming the code worked, throwaway PHP harnesses (stubbing the
WordPress functions) exercised the real classes against the live API and against
adversarial input. These confirmed: cache hit at ~0.01 ms, stale-while-error
serving 318 records during a simulated outage, `WP_Error` on cold cache, and
that `javascript:`, non-URLs, `banana` and `<script>` are all rejected by the
settings sanitizer. Template rendering was checked for XSS escaping, em-dash
fallbacks and the empty state (11/11 checks).

**4. A real bug found and fixed after a failure report.**
The widget did not appear in the Elementor panel ("No widget found for 'pet'").
Root cause was a hook-ordering defect in the AI-written bootstrap: Elementor
loads alphabetically first and fires `elementor/loaded` during its own
`plugins_loaded` callback, so subscribing to that action later never fired, and
the widget never registered. Fixed by detecting the already-fired state
(`did_action()` / `class_exists()`) and initialising immediately, keeping the
hook only as a fallback.

**5. Work already done was reported as done, not silently redone.**
When asked to "build the plugin header, activation/deactivation hooks, bootstrap
classes, direct-access guards", those already existed from the scaffold commit.
The response enumerated where each lived with file/line evidence instead of
regenerating duplicate code.

**6. Linter findings triaged rather than blanket-applied.**
Plugin Check produced 15 findings. They were sorted into real code issues
(template variable prefixing — a partial false positive, since the variables are
method-scoped, but prefixed anyway as the accepted convention; plus genuine dead
code removed), distribution hygiene (`readme.txt` headers, `.distignore`,
`.DS_Store` removal), and **not-applicable** rules. The "wp" trademarked-term
warnings and the `.gitignore` "hidden file" warning were explicitly **not**
fixed, with reasoning: they are WordPress.org-directory submission rules, and
this plugin is not a .org submission — "fixing" the former would mean renaming
the slug, text domain and main file for zero functional gain.

**7. Scope and process kept visible.**
The AI flagged when commits landed directly on `main` instead of going through a
pull request, and surfaced an unexpected merge commit and a second, malformed
zip (no top-level folder, dev files bundled) that it had not created — rather
than quietly working around them.

## Honest limitations

- The tradeoff analysis and caching design were AI-proposed; they were accepted
  after review, not independently derived by the reviewer.
- Verification was done with bespoke harnesses, not a real WordPress test suite
  (noted in `DECISION_LOG.md` as future work).
- The plugin was not exercised end-to-end in a browser during the session beyond
  the reported widget-registration failure and its fix.

---

## Raw transcript

<!--
  PASTE THE RAW CHAT EXPORT BELOW.

  From the Claude Code client, export this session's full prompt/response
  history and paste it here (or attach the exported file alongside this
  document and link it from here).

  This placeholder is intentionally left empty rather than reconstructed from
  memory, so that nothing in this file is presented as verbatim when it is not.
-->

_Not yet attached — export from the Claude Code client and paste here._
