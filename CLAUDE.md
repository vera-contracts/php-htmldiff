# CLAUDE.md

## Project Overview

Fork of caxy/php-htmldiff — a PHP library that compares two HTML strings and produces a merged HTML output highlighting differences with `<ins>`/`<del>` tags and CSS classes (`diffins`, `diffdel`, `diffmod`).

## Commands

```bash
# Install dependencies
composer install

# Run tests (excludes performance group by default)
./vendor/bin/phpunit

# Run a single test
./vendor/bin/phpunit --filter testHtmlDiff

# Run with a specific fixture
./vendor/bin/phpunit --filter "testHtmlDiff#3"

# Run performance tests (excluded by default)
./vendor/bin/phpunit --group performance

# Code style check (PSR-2)
./vendor/bin/phpcs
```

## Architecture

### Diff Pipeline

`HtmlDiff::build()` is the main entry point:
1. **Purify** — HTML is sanitized via HTMLPurifier (`AbstractDiff::prepare()`)
2. **Tokenize** — HTML is split into words and tags (`AbstractDiff::convertHtmlToListOfWords()`)
3. **Isolate special tags** — Tags like `<table>`, `<ul>`, `<ol>`, `<a>`, `<strong>` etc. are replaced with placeholders and diffed separately
4. **Index** — New words are indexed for fast lookup (`HtmlDiff::indexNewWords()`)
5. **Match** — Longest common subsequence matching finds `MatchingBlock`s between old/new word arrays
6. **Operations** — Matches are converted to `Operation` objects (equal/insert/delete/replace)
7. **Render** — Operations produce the final HTML with `<ins>`/`<del>` wrappers

### Class Hierarchy

- **`AbstractDiff`** — Base class with HTML purification, word tokenization, and config. All diff classes extend this.
- **`HtmlDiff`** — Core diff engine for general HTML content. Handles matching, operations, and rendering.
- **`ListDiffLines`** — Specialized diffing for `<ul>`, `<ol>`, `<dl>` lists. Uses DOM parsing (`DOMDocument`) and a `ListItemMatchStrategy` to match list items by content similarity.
- **`Table/TableDiff`** — Specialized diffing for `<table>` elements. Parses tables into `Table`/`TableRow`/`TableCell` objects, matches rows, then diffs individual cells using `HtmlDiff`.
- **`HtmlDiffConfig`** — All configuration (match threshold, encoding, isolated tags, purifier settings, cache provider). Passed through to sub-diffs.

### Test Structure

Functional tests use HTML fixture files in `tests/fixtures/HtmlDiff/`. Each fixture is a single `.html` file containing old HTML, new HTML, and expected output separated by markers. The `HtmlFileIterator` reads these fixtures as data providers. To add a test case, create a new `.html` fixture file following the existing format.

## Key Dependencies

- **ezyang/htmlpurifier** (required) — Sanitizes input HTML before diffing
- **doctrine/cache** (optional) — Caching layer for computed diffs via `DiffCache`
- PHP extensions: `dom`, `mbstring`
