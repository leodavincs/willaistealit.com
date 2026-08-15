# Contributing

The whole site is JSON files in `data/jobs/`. One file, one profession, one URL.

## Before you write

Read [the methodology](https://willaistealit.com/methodology). Entries that ignore it get
rejected for the same reasons every time, and it is a five-minute read.

Two rules matter more than the rest:

- **Judge tasks, not titles.** If your entry cannot name 4–8 concrete tasks, you do not know the
  job well enough to write about it yet.
- **A red verdict still has to be useful.** Nobody should leave an entry with nothing to do.

## Fields

| Field | Required | What it is |
|-------|----------|------------|
| `slug` | yes | URL segment, `[a-z0-9-]`, must match the filename |
| `title` | yes | English job name, singular ("Accountant", not "Accountants") |
| `titleTr` | no | Turkish name, if there is a natural one |
| `category` | yes | One of: `tech`, `finance`, `legal`, `health`, `education`, `creative`, `trades`, `service`, `ops` |
| `verdict` | yes | `safe`, `shrinking`, or `on-the-menu` |
| `safeUntil` | for non-safe | Four-digit year. See below — this one gets argued about |
| `oneLiner` | yes | Under 120 characters. The sentence people screenshot |
| `summary` | recommended | 3–6 sentences of actual argument, not a restatement of the one-liner |
| `tasks` | yes | 4–8 tasks, each with `name`, `verdict` (`gone`/`going`/`safe`), `note`, and `tags` for safe ones |
| `resistanceTags` | yes | 1–3 tags, strongest first, from the list in `inc/config.php` |
| `whatSurvives` | recommended | One sentence naming what is actually left |
| `adaptPrompt` | yes | A prompt this professional can paste into an AI tool **today** |
| `adaptTools` | no | Named tools, real ones only |
| `sources` | strongly wanted | Links. Without these the entry is labelled a community draft |
| `geoAnswer` | no | Overrides the auto-generated dated summary paragraph. Only write one if the generated version reads badly |
| `lastReviewed` | yes | `YYYY-MM`. Shown on the page — this is a freshness signal, so keep it honest |

## The parts people get wrong

**`safeUntil`** is not "when the job disappears". It is the year the *core tasks* are expected to
be routinely machine-done in ordinary practice — after capability arrives, after organisations
adopt it, and after regulators allow it. Regulated work gets later years than raw task difficulty
suggests. Pick a year you would defend in an argument.

**`adaptPrompt`** is the reason most people visit. It must be specific to this profession, name
the inputs the person will paste, say what comes back, and include the rules that keep the model
honest. Generic prompts ("You are a helpful assistant for accountants") get rejected. Test it in a
real model before submitting.

**Tags** are structural walls, not capability gaps. "AI is not good enough at this yet" is a
countdown, not a tag — it means the task is `going`, with a year.

## Submitting

```bash
php tools/validate.php        # must be clean
php tools/build-index.php     # refresh search index, clear caches
php -S localhost:8000 router.php   # look at the page you just made
```

Then open a PR. In the description, tell us whether you actually do this job — that carries more
weight than anything else in the review.

## Task names get quoted

Task names are lifted into a generated summary sentence ("AI has already absorbed X, Y and Z"),
so write them as phrases that survive being dropped into prose. `Loading and securing freight`
works; `Loading, securing, unloading` produces a comma pile-up inside a list. Acronyms are left
alone — `CV screening` stays `CV screening`.

If the generated paragraph still reads badly for your entry, write `geoAnswer` yourself. Keep it
one paragraph, keep the date in it, and make sure it stands up quoted with no surrounding context —
that paragraph is what an answer engine will repeat.

## Changing an existing verdict

Best kind of PR. Change the field, update `lastReviewed`, **add an entry to
`data/changelog.json`**, and put the argument in the PR description: which task moved, and what
evidence moved it. "This feels wrong" is a discussion; "this task is now `going` and here is the
tool that does it" is a merge.

A changelog entry looks like this — newest first, `from: null` for a new profession:

```json
{
  "date": "2026-09-02",
  "slug": "radiologist",
  "title": "Radiologist",
  "from": "safe",
  "to": "shrinking",
  "why": "First-read triage moved from going to gone after regulatory approval in the EU."
}
```

`php tools/validate.php` checks the changelog too, and warns about any published entry with no
record in it.
