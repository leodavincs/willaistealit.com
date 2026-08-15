# willaistealit.com

Task-level verdicts on which jobs AI actually takes — and what survives.

Every entry is one JSON file. No database, no build step, no framework. Plain PHP 8.3 reading
files off disk, which is exactly as much machinery as this needs.

## Why it exists

Every "AI will replace X" listicle judges a job title. Job titles are not what gets automated —
tasks are. So each entry here splits a profession into 4–8 real tasks, gives each one its own
verdict, names the structural reason the survivors survive, and hands you a prompt you can use
today. A red verdict should still leave you with something to do.

## Verdicts

| Verdict | Meaning |
|---------|---------|
| 🟢 SAFE | The core of the job is structurally resistant. AI becomes a tool, not a replacement. |
| 🟡 SHRINKING | Significant parts are automating. The role narrows and shifts — it does not vanish. |
| 🔴 ON THE MENU | The core tasks are becoming machine-doable. A time horizon applies. |

The full reasoning, including what would make a verdict change, is at
[willaistealit.com/methodology](https://willaistealit.com/methodology).

## Run it locally

```bash
php -S localhost:8000 router.php
```

PHP 8.3+ with `gd` (share images) and `mbstring`. Nothing to install — `router.php` stands in for
`.htaccess` on the built-in server and is never used in production.

## Add or fix an entry

1. Copy `data/jobs/accountant.json` to `data/jobs/your-job.json` (filename = URL slug, `[a-z0-9-]`)
2. Fill it in — see [CONTRIBUTING.md](CONTRIBUTING.md) for what each field means
3. `php tools/validate.php` until it is clean
4. `php tools/build-index.php` to refresh the search index and clear caches
5. Open a PR and make your argument in the description

Disagreeing with a verdict is a contribution, not a complaint. If you actually do the job, you
are a better source than we are.

## Layout

```
index.php          home: search, category grid, verdict spread
job.php            entry template (reads data/jobs/{slug}.json)
og.php             share image, generated with GD and cached
methodology.php    how verdicts are decided
changelog.php      dated record of every verdict that moved
sponsor.php        waitlist while slots are closed
sitemap.php        served as /sitemap.xml — grows with every entry, no build step
llms.php           served as /llms.txt — the site summarised for language models
robots.txt         AI crawlers explicitly allowed (see below)
router.php         local dev only — production routes via .htaccess
inc/               config, helpers, header/footer, OG rendering
data/jobs/         one JSON file per profession — the content lives here
data/changelog.json  verdict change history
tools/             validate.php, build-index.php, build-og.php
cache/             generated pages and share images (gitignored)
```

## Found by both kinds of search

The site is written to be found by Google *and* quoted by answer engines, because those
reward different things.

**For search:** page titles and H1s use "Will AI replace X?" — the phrasing people actually
type. `/sitemap.xml` is generated on request, so it grows the moment a JSON file lands, with
no build step to forget. Every entry links to related professions by shared category and
shared resistance tags.

**For answer engines:** each entry opens with a dated, self-contained paragraph that still
makes sense pasted somewhere else, because that is the sentence a model will lift. Entries
carry `FAQPage`, `Occupation` and `BreadcrumbList` JSON-LD, a visible review date, and
`/llms.txt` describes the schema and methodology directly. `robots.txt` **allows** GPTBot,
ClaudeBot, PerplexityBot, Google-Extended and the rest — being quoted is the strategy, not
the leak. The verdict is what gets cited; the copy-ready prompt and the share card stay here,
which is what turns a citation into a visit.

Nothing here is generated ahead of time, so none of it can go stale against the data.

## Deploy

Push to `main`; Hostinger's Git deployment pulls into `public_html`.

Four constants in `inc/config.php` decide what the live site can do — `BUILD_KEY`,
`GITHUB_URL`, `CONTACT_EMAIL`, `ANALYTICS_DOMAIN`. Anything left blank degrades quietly
rather than breaking: no GitHub URL means the contribution links are hidden, no analytics
domain means no script is loaded, and an unchanged `BUILD_KEY` means `/tools/*.php` refuses
to run at all.

`sitemap.xml` and `llms.txt` are rendered on request and need no build step. Submit the
sitemap to Search Console once; it stays current on its own.

**Full checklist, including the URLs that fail silently: [DEPLOY.md](DEPLOY.md).**

## Licence

Code: MIT. Entry content: CC BY 4.0 — take it, just say where it came from.
