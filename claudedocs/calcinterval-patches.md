# Custom Patches (vs upstream IMathAS)

Patches applied to this fork that are not in the upstream repo. After syncing from upstream, verify these are still present.

## 1. calcinterval mixed number scoring without `-val` param

**File:** `assess2/questions/scorepart/IntervalScorePart.php`
**Location:** Before the `str_replace(' ','',$givenans)` line (~line 162)
**Date:** 2026-03-10
**Branch:** `development/2026.03.09`

### Problem
When scoring `calcinterval` answers via the server-side-only path (e.g., `scores.php` API without JavaScript preprocessing), mixed numbers like `1 1/2` in intervals are scored incorrectly. The browser path works because JS converts `1 1/2` → `(1+1/2)` → `1.5` and sends it as the `-val` param. The server-side path fails because `str_replace(' ','',$givenans)` turns `1 1/2` into `11/2` (= 5.5, not 1.5).

### Fix
Added an unconditional `preg_replace` before the space-stripping line. The conversion applies regardless of `answerformat` flags, since the regex is specific enough to only match mixed number patterns and the format flags control validation/display requirements, not parsing ability:

```php
// Convert mixed numbers before space-stripping (e.g., "1 1/2" -> "(1+1/2)")
$givenans = preg_replace('/(\d+)\s+(\d+)\s*\/\s*(\d+)/', '($1+$2/$3)', $givenans);
```

### How to verify after upstream sync
```bash
grep -n "Convert mixed numbers before space-stripping" assess2/questions/scorepart/IntervalScorePart.php
```

If missing, re-apply the line immediately before:
```php
$givenans = str_replace(' ','',$givenans);
```

### Related code (for context)
- JS equivalent: `javascript/AMhelpers2.js` `singlevaleval()` (~line 2075) — gates on `format.indexOf('mixed')!=-1`, but this only matters for the browser path where JS sends the evaluated `-val` param
- Same pattern already in: `assess2/questions/scorepart/CalculatedScorePart.php` (lines 135, 179, 212)
- `NTupleScorePart`, `ComplexScorePart`, `CalculatedMatrixScorePart` do NOT need this — they don't support mixed numbers
