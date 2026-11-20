# Fix: Print Format Answer Box Wiped on Large Questions

**File:** `assess2/questions/QuestionHtmlGenerator.php`
**Commit:** `630ccd5af`
**Date:** 2026-04-03
**Branch:** `production/2026.03.31-moon`

## Problem

When `problems.php` calls `AssessStandalone::displayQuestion()` with `printformat: true`, multipart questions with a `matching` answer type (or any answer type producing large HTML) would render with an **empty answer box**.

### Root Cause

In print format mode, `QuestionHtmlGenerator` runs `preg_replace` to convert:
- `<ul class="nomark">` → `<ol style="list-style-type:upper-alpha">` (for choices/multans/matching)
- `<ol class="lalpha">` → `<ol style="list-style-type:lower-alpha">` (for matching)

The original patterns used `.*?` with the `/s` (DOTALL) flag:

```php
$answerbox[$atIdx] = preg_replace('/<ul class="?nomark"?>(.*?)<\/ul>/s', '<ol style="list-style-type:upper-alpha">$1</ol>', $answerbox[$atIdx]);
```

For large matching questions (e.g. many items with many answer options), the answer box HTML can reach **~1MB**. PHP's `preg_replace` with `.*?` and `/s` causes PCRE to check `</ul>` at every character position, exhausting the default `pcre.backtrack_limit` (1,000,000). When PCRE fails, `preg_replace` returns `null`, silently wiping the entire answer box.

`preg_last_error()` confirmed: error code **2** = `PREG_BACKTRACK_LIMIT_ERROR`.

### Why `problems.php` but not `testquestion2.php`

`problems.php` hardcodes `'printformat' => true` in its `displayQuestion()` call (line 330). `testquestion2.php` does not pass `printformat`, so it defaults to `false` and never enters the problematic block.

## Fix

Replaced both `preg_replace` calls (multipart path ~line 621, non-multipart path ~line 716) with `str_replace`, which has no size limitations:

```php
if (strpos($answerbox[$atIdx], 'nomark') !== false) {
    $answerbox[$atIdx] = str_replace(
        ['<ul class="nomark">', '<ul class=nomark>'],
        '<ol style="list-style-type:upper-alpha">',
        $answerbox[$atIdx]
    );
    $answerbox[$atIdx] = str_replace('</ul>', '</ol>', $answerbox[$atIdx]);
}
if (strpos($answerbox[$atIdx], 'lalpha') !== false) {
    $answerbox[$atIdx] = str_replace(
        ['<ol class="lalpha"', '<ol class=lalpha'],
        '<ol style="list-style-type:lower-alpha"',
        $answerbox[$atIdx]
    );
}
```

The `strpos` guard is a fast pre-check that skips the replacements entirely when neither pattern is present (common for answer types like `number`, `calculated`, etc.).

The `</ul>` → `</ol>` replacement is safe because none of the answer box generators that produce `nomark` lists (`choices`, `multans`, `matching`) embed non-nomark `<ul>` elements inside their answer box HTML.

## Answer Types Affected

All three answer types that produce `nomark`/`lalpha` HTML are handled correctly:

| Type | `nomark` | `lalpha` |
|------|----------|----------|
| `choices` | ✓ | — |
| `multans` | ✓ | — |
| `matching` | ✓ | ✓ |
