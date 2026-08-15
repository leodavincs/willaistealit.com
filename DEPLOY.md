# Deploy checklist

Run through this once. Everything here is a thing that silently looks fine and is not.

## Before the first push

1. **`inc/config.php` — fill in the four blanks.**

   | Constant | What happens if you leave it | Notes |
   |----------|------------------------------|-------|
   | `BUILD_KEY` | `/tools/*.php` refuses to run over the web, entirely | Set it to something random. Nothing rebuilds until you do |
   | `GITHUB_URL` | Every "contribute / open a PR / edit this entry" link is hidden | Hidden, not broken — safe to launch without it, but the contribution loop is dead |
   | `CONTACT_EMAIL` | The "rather just talk?" block on /sponsor disappears | Create the mailbox in hPanel *first*, then fill this in |
   | `ANALYTICS_DOMAIN` | No analytics script is loaded at all | Plausible domain, e.g. `willaistealit.com`. You will not know if launch worked without this |

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
   | `/` | Home, 15 jobs | — |
   | `/accountant` | Entry page | `.htaccess` rewrite is not active → check `mod_rewrite`/LiteSpeed |
   | `/og/accountant.png` | A PNG card | GD or FreeType missing, or `fonts/` did not deploy |
   | `/sitemap.xml` | XML, 19 URLs | Rewrite rule not firing |
   | `/llms.txt` | Plain text | Same |
   | `/robots.txt` | Plain text | Static file, should always work |
   | `/data/jobs/accountant.json` | **404** | If this returns JSON, `data/.htaccess` is not being read — stop and fix |
   | `/inc/config.php` | **404** | Same. This one leaks `BUILD_KEY` |
   | `/nonexistent-job` | 404 page | — |

7. **Warm the caches** (optional; the first visitor does it otherwise):
   `/tools/build-index.php?key=YOUR_KEY` then `/tools/build-og.php?key=YOUR_KEY`

8. **Google Search Console:** verify the domain, submit `/sitemap.xml`. Once. It stays current.

9. **Paste a live entry URL into X, LinkedIn and Slack** and confirm the share card renders.
   This is the single highest-leverage growth feature on the site — if it is broken, everything
   posted at launch is wasted.

## Whenever you add or change an entry

```bash
php tools/validate.php        # must be clean
php tools/build-index.php     # refresh index, clear stale caches
```

Then push. Caches also self-invalidate on file timestamps, so a missed build is not fatal —
it just means the first visitor pays for the render.
