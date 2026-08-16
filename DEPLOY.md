# Deploy checklist

Run through this once. Everything here is a thing that silently looks fine and is not.

## Before the first push

1. **`inc/config.php` — fill in the four blanks.**

   | Constant | What happens if you leave it | Notes |
   |----------|------------------------------|-------|
   | `BUILD_KEY` | `/tools/*.php` refuses to run over the web, entirely | Set it to something random. Nothing rebuilds until you do |
   | `GITHUB_URL` | Every "contribute / open a PR / edit this entry" link is hidden | Hidden, not broken — safe to launch without it, but the contribution loop is dead |
   | `CONTACT_EMAIL` | The "rather just talk?" block on /sponsor disappears | Create the mailbox in hPanel *first*, then fill this in |
   | `MATOMO_SITE_ID` | No analytics script is loaded at all | Lives in `inc/config.php`, not `config.local.php` — a site ID is not a secret. Local dev is excluded by `is_live_host()`, not by leaving this blank |

2. **Check `SITE_URL`** matches the live domain exactly, including `https://` and no trailing slash.
   It is used for canonicals, OG images, sitemap and JSON-LD — wrong here means wrong everywhere.

## Push and connect

3. GitHub repo → hPanel → Advanced → GIT → connect, branch `main`, path `public_html`.
4. Push. Confirm the files landed in `public_html` (not `public_html/repo-name`).

## After the first deploy — verify, do not assume

5. **Permissions:** `cache/` and `data/` must be writable by PHP (755 is usually enough on
   Hostinger; if OG images 500, try 775).

6. **Open these URLs by hand.** Each one fails silently in a different way:

   | URL | Expected | If it fails |
   |-----|----------|-------------|
   | `/` | Home, 17 jobs | — |
   | `/accountant` | Entry page | `.htaccess` rewrite is not active → check `mod_rewrite`/LiteSpeed |
   | `/og/accountant.png` | A PNG card | GD or FreeType missing, or `fonts/` did not deploy |
   | `/sitemap.xml` | XML, 44 URLs (22 × 2 languages) | Rewrite rule not firing |
   | `/llms.txt` | Plain text | Same |
   | `/robots.txt` | Plain text | Static file, should always work |
   | `/data/jobs/accountant.json` | **404** | If this returns JSON, `data/.htaccess` is not being read — stop and fix |
   | `/inc/config.php` | **404** | Same. This one leaks `BUILD_KEY` |
   | `/cache/index-tr.json` | **404** | Same — the search index must not be readable from outside |
   | `/nonexistent-job` | 404 page | — |

   **Turkish (live since phase 4) — each of these is a separate failure mode:**

   | URL | Expected | If it fails |
   |-----|----------|-------------|
   | `/tr/` | Turkish home | `activeLangs` did not deploy, or `routes.json` is stale — run step 7 |
   | `/tr/kasiyer` | Turkish entry, canonical `/tr/kasiyer` | A canonical pointing at `/cashier` means the template lost `$lang` |
   | `/tr/metodoloji` · `/tr/zaman-cizelgesi` · `/tr/degisiklikler` · `/tr/sponsorluk` | All four Turkish, each canonical to its own `/tr/` path | An English heading here means the page never got localised |
   | `/tr/cashier` | **301** to `/tr/kasiyer` | Cross-language slug resolution broken |
   | `/og/tr/kasiyer.png` | PNG card reading **SIRADA**, with `Ü` and `İ` as letters not boxes | Font did not deploy, or `cache/og/tr/` is not writable |
   | `/es/` | **404** | Spanish stays closed until phase 5 |

   **On any English page:** the header must show `EN · TR`, and `<head>` must carry
   `hreflang="en"`, `hreflang="tr"` and `x-default`. No switcher means `activeLangs`
   is still `['en']` on the server — the deploy did not land.

7. **Build the generated files.** `cache/` is not in the repo, so a fresh deploy has none
   of them:

   `/tools/build-index.php?key=YOUR_KEY` then `/tools/build-og.php?key=YOUR_KEY`

   `build-index.php` writes four things: `routes.json`, `index-en.json`, **`index-tr.json`**
   and **`content-version.json`**. The site works without them — each is regenerated in
   memory on demand — but every request pays for it, and `content-version.json` is what
   stops the page cache serving a stale "related jobs" block. Run it after **every** deploy
   that touches `data/`.

   `build-og.php` now writes to `cache/og/<language>/` — check that both `cache/og/en/`
   and `cache/og/tr/` filled up.

8. **Google Search Console:** verify the domain, submit `/sitemap.xml`. **Once.**

   The sitemap is rendered on request, so Google gets the current version every time it
   re-crawls — you never resubmit after adding entries or a language. What you do instead is
   *check*: in the Sitemaps panel, "last read" should move past the deploy date and the
   discovered-URL count should climb (22 to 44 when Turkish went live). If it is stuck,
   "Resubmit" forces a re-crawl — but that is a fix, not routine.

   Indexing is the real signal and it lags by days or weeks. Watch the Pages report for
   `/tr/` URLs, not the Sitemaps panel.

9. **Paste a live entry URL into X, LinkedIn and Slack** and confirm the share card renders.
   This is the single highest-leverage growth feature on the site — if it is broken, everything
   posted at launch is wasted.

## Whenever you add or change an entry

```bash
php tools/validate.php        # must be clean
php tools/build-index.php     # both indexes + content-version, clears stale caches
./tools/smoke.sh              # matrix must be clean
./tools/golden.sh --check     # 19/19 — read the diff before capturing anything
```

Then push, and run `/tools/build-index.php?key=…` on the live host too.

An English entry with no `tr.json` is **not** a blocker: it simply does not appear in
Turkish, and `/tr/<slug>` returns 404 + `noindex` with links to the languages that do have
it. The validator warns, and a half-translated catalogue was ruled out for the launch
itself (spec §15).

## Matomo (stats.willaistealit.com)

Matomo `public_html/stats_matomo/` altinda duruyor. Hostinger bu planda subdomain
kokunu `public_html` disina almaya izin vermiyor — API sadece duz bir dizin adi
kabul ediyor, `../` reddediliyor.

Bunun iki sonucu var ve ikisi de ele alinmis durumda:

1. **Apex'ten erisim kapali.** `stats_matomo/.htaccess` icindeki host kontrolu
   yalnizca `stats.willaistealit.com` host'unu geciriyor; `willaistealit.com/stats_matomo/`
   404 dönüyor. Bu dosya Matomo guncellemesinde kaybolabilir — **guncelleme sonrasi
   `curl -o /dev/null -w '%{http_code}' https://willaistealit.com/stats_matomo/`
   calistir, 404 gelmiyorsa .htaccess'i geri koy.**

2. **Git deploy riski.** `stats_matomo/` repo'da takipli degil, dolayisiyla `git pull`
   onu silmez. Yine de ilk deploy'dan sonra `https://stats.willaistealit.com/` acilip
   kontrol edilmeli. Kaybolursa panik yok: Matomo'nun verisi MySQL'de
   (`u359064650_matomo`), dosyalar yeniden yuklenip ayni veritabanina baglanir.

Veritabani: `u359064650_matomo`, plan limiti 3 GB.
