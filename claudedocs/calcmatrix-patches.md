# Custom Patches (vs upstream IMathAS)

Patches applied to this fork that are not in the upstream repo. After syncing from upstream, verify these are still present.

## 1. calcmatrix mixed number scoring without `-val` param

**File:** `assess2/questions/scorepart/CalculatedMatrixScorePart.php`
**Locations:** Three `evalMathParser()` call sites for student input (rescore path, individual cells, free-form matrix)
**Date:** 2026-03-11
**Branch:** `development/2026.03.09`

### Problem
When scoring `calcmatrix` answers via the server-side-only path (e.g., `scores.php` API without JavaScript preprocessing), mixed numbers like `1 1/2` in matrix cells are scored incorrectly. The browser path works because JS `singlevaleval()` converts `1 1/2` → `(1+1/2)` → `1.5` and sends it as the `-val` param. The server-side path fails because `MathParser`'s tokenizer skips spaces and `handleImplicit()` inserts implicit multiplication between consecutive numbers, so `1 1/2` evaluates as `1 * 1/2 = 0.5` instead of `1.5`.

### Fix
Added unconditional `preg_replace` before each `evalMathParser()` call on student input. Three locations:

**Rescore path (~line 74):**
```php
// Convert mixed numbers before evaluation (e.g., "1 1/2" -> "(1+1/2)")
$v = preg_replace('/(\d+)\s+(\d+)\s*\/\s*(\d+)/', '($1+$2/$3)', $v);
$givenanslistvals[$i] = evalMathParser($v);
```

**Individual cell inputs (~line 80):**
```php
// Convert mixed numbers before evaluation (e.g., "1 1/2" -> "(1+1/2)")
$cellval = preg_replace('/(\d+)\s+(\d+)\s*\/\s*(\d+)/', '($1+$2/$3)', $_POST["qn$qn-$i"]);
$givenanslistvals[$i] = evalMathParser($cellval);
```

**Free-form matrix elements (~line 108):**
```php
// Convert mixed numbers before evaluation (e.g., "1 1/2" -> "(1+1/2)")
$v = preg_replace('/(\d+)\s+(\d+)\s*\/\s*(\d+)/', '($1+$2/$3)', $v);
$givenanslistvals[$j] = evalMathParser($v);
```

### How to verify after upstream sync
```bash
grep -n "Convert mixed numbers before evaluation" assess2/questions/scorepart/CalculatedMatrixScorePart.php
```
Should show 3 matches. If missing, re-apply the `preg_replace` lines immediately before each `evalMathParser()` call on student input.

### Related code (for context)
- JS equivalent: `javascript/AMhelpers2.js` `singlevaleval()` (~line 2076) — gates on `format.indexOf('mixed')!=-1`, but only matters for browser path
- Similar fix in: `assess2/questions/scorepart/IntervalScorePart.php` (unconditional, before space-stripping)
- `CalculatedScorePart.php` (lines 135, 179, 212) uses a different approach — `preg_match` with format flag gating
- `NTupleScorePart`, `ComplexScorePart` have the same potential issue but are not patched (mixed numbers rare in those contexts)
