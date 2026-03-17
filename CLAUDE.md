# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

IMathAS (Internet Mathematics Assessment System) is a PHP web application for delivering and auto-grading math homework/tests with algorithmically generated questions. It powers MyOpenMath.com, WAMAP.org, Lumen OHM, and XYZhomework. Includes full LMS features: gradebook, forums, file management, calendar, and LTI integration.

## Requirements

- PHP 7.4+ with extensions: mbstring, pdo_mysql, gettext (required); gd, curl, openssl (recommended)
- MySQL 5.6+

## Common Commands

### PHP Backend
```bash
composer install                    # Install dependencies
composer start                      # Dev server at localhost:3000
composer test                       # Run tests with coverage
composer testv                      # Run tests with step output and coverage
vendor/bin/codecept run unit        # Run only unit tests
vendor/bin/codecept run unit SomeTestCest  # Run a single test file
```

### Vue.js Assessment Player (assess2/vue-src/)
```bash
cd assess2/vue-src
npm install
npm run serve     # Dev server with hot-reload (localhost:8080)
npm run build     # Production build to assess2/vue/
npm run lint      # Lint and fix
./buildmin.sh     # Rebuild minified external JS files
```

### Vue Dev Mode Setup
In `config.php`, add:
```php
$CFG['assess2-use-vue-dev'] = true;
$CFG['assess2-use-vue-dev-address'] = 'http://localhost:8080';
```
Create `assess2/vue-src/.env.local`:
```
VUE_APP_IMASROOT=http://localhost
VUE_APP_PROXY=http://localhost
```
CSRF protection (`$CFG['use_csrfp']`) must be disabled in dev mode.

### Other Build Steps
- After modifying any JS file that has a `_min.js` version, run `assess2/vue-src/buildmin.sh`
- After changing TinyMCE plugins, run `tinymce4/maketinymcebundle.php` to regenerate `tinymce_bundled.js`

## Architecture

### Entry Points & Bootstrap
- `install.php` — First-run setup, writes `config.php`, creates DB tables
- `upgrade.php` — Runs database migrations after code updates
- `init.php` — Bootstrap: loads config, starts session, validates user
- `config.php` — All configuration via `$CFG` array (see README.md for options)
- `actions.php` — Central form action handler for course/user operations
- `index.php` — Main dashboard/home page

### External API Endpoints (used by external services)
Both endpoints use `AssessStandalone` (`assess2/AssessStandalone.php`) — a lightweight wrapper around the assessment engine that requires no user auth. Both bootstrap via `init_without_validate.php`, use a hardcoded question metadata template with `$qn = 27`, return JSON responses, and return 405 for non-POST requests.

#### `problems.php` — Question Rendering
- **Method:** POST with JSON body
- **Input:** `qtype`, `control`, `qtext`, `solution`, `seed` (optional, random if omitted), `stype` (optional, default `"template"`), `showplot` (optional, `"fn"` replaces `showplot` with `showplot_with_functions` in control code)
- **Response:** `{ question, originalSolution, solution, seed, jsparams, vars, answers }`
  - `solution` is the prettified version of `originalSolution` (backtick-wrapped expressions run through `makepretty`)
  - `stype:"template"` uses the question engine's solution; otherwise extracts generated vars and `eval()`s the solution PHP code
- **Custom macro:** Registers `showplot_with_functions` in `$allowedmacros` — parses graph settings and function definitions into a JSON config, returns an `<embed>` tag that calls `drawPicture()` client-side

#### `scores.php` — Question Scoring
- **Method:** POST with form-encoded body
- **Input:** `seed`, `qtype`, `control`, `answer`, `toscoreqn` (optional JSON string, e.g. `{"27": [0, 1]}` to score specific parts)
- **Response:** Result from `AssessStandalone::scoreQuestion()` — contains `scores` (per-part 0-1), `raw`, `errors`, `allans` (bool)

### Core Directories
| Directory | Purpose |
|-----------|---------|
| `assess2/` | New Vue-based assessment player (`vue-src/`) and its PHP API backend |
| `assessment/` | Legacy assessment rendering, math interpretation (`interpret5.php`), question display (`displayq2.php`) |
| `course/` | Course management, gradebook (`gradebook.php`, `gbsettings.php`), forums, calendar, item management |
| `admin/` | Admin functions: user/group management, diagnostics, LTI queue processing |
| `includes/` | Shared PHP utilities: sanitization, authentication, email, S3, JWT, OAuth, password handling |
| `lti/` | LTI 1.0 and 1.3 integration (producer/consumer) |
| `filter/` | Content filters: graph rendering (`graph/`), chemistry notation |
| `migrations/` | Numbered database migration files |
| `javascript/` | Client-side JS libraries and utilities |
| `i18n/` | Internationalization (gettext-based, `de` translation available) |

### Assessment System
The assessment system has two generations:
1. **Legacy**: `assessment/displayq2.php` (large file ~341KB) handles question rendering and grading
2. **New (assess2)**: Vue.js SPA in `assess2/vue-src/`, communicates with PHP API endpoints in `assess2/`. Production builds go to `assess2/vue/`

Key math processing files:
- `assessment/mathparser.php` — Math expression parser
- `assessment/interpret5.php` — Answer interpretation and validation
- `includes/macros.php` — Macro system for algorithmic question generation (~195KB)

### Assessment Engine (assess2) — Deep Dive
The assess2 engine used by `problems.php` and `scores.php` follows a clean pipeline architecture:

#### Key Files
| File | Purpose |
|------|---------|
| `assess2/AssessStandalone.php` | Lightweight wrapper — no auth required. Methods: `setQuestionData()`, `setState()`, `displayQuestion()`, `scoreQuestion()`, `getQuestion()` |
| `assess2/questions/QuestionGenerator.php` | Orchestrates question rendering: loads DB data, evals question control/qtext code, returns `Question` object |
| `assess2/questions/ScoreEngine.php` | Orchestrates scoring: evals control+answer code, delegates to type-specific `ScorePart` implementations |
| `assess2/questions/models/Question.php` | Output model with: `getQuestionContent()`, `getJsParams()`, `getSolutionContent()`, `getVarsOutput()`, `getCorrectAnswersForParts()`, `getAnswerPartWeights()`, `getErrors()` |
| `assess2/questions/models/QuestionParams.php` | Input params for generation (fluent setters): question identity, seed, student answer history, display options |
| `assess2/questions/models/ScoreQuestionParams.php` | Input params for scoring (fluent setters): given answer, attempt number, parts to score, point value |
| `assess2/questions/ScorePartFactory.php` | Factory that maps answer types to `ScorePart` implementations |

#### Question Generation Pipeline
```
QuestionParams → QuestionGenerator::getQuestion() → Question
```
1. `QuestionParams` is populated with question data, seed, student history, display options
2. `QuestionGenerator` evals the question's `control` (PHP setup/randomization) and `qtext` (template) code
3. Returns a `Question` with rendered HTML, jsparams, solution, correct answers, and generated variables

#### Scoring Pipeline
```
ScoreQuestionParams → ScoreEngine::scoreQuestion() → {scores, rawScores, errors, ...}
```
1. `ScoreQuestionParams` carries the student's given answer, question data, and seed
2. `ScoreEngine` re-evals question code to reconstruct correct answers and tolerances
3. `ScorePartFactory` selects a type-specific scorer based on answer type
4. Returns: `scores` (per-part 0-1), `rawScores`, `lastAnswerAsGiven`, `lastAnswerAsNumber`, `correctAnswerWrongFormat`, `answeights`, `errors`

#### Answer Types (ScorePart implementations)
`number`, `calculated`, `choices`, `multans`, `matching`, `numfunc`, `draw`, `ntuple`/`calcntuple`, `matrix`/`calcmatrix`, `complex`/`calccomplex`, `interval`/`calcinterval`, `essay`, `file`, `string`, `chemeqn`, `conditional`

#### State Structure (used by both endpoints)
```php
$state = [
    'seeds' => [qn => seed],              // RNG seed per question
    'qsid' => [qn => qsetid],             // question set ID mapping
    'stuanswers' => [(qn+1) => answer],    // student answers (NOTE: 1-indexed)
    'stuanswersval' => [(qn+1) => val],    // numeric interpretation
    'scorenonzero' => [(qn+1) => flag],    // -1=unscored, 0=zero, 1=nonzero
    'scoreiscorrect' => [(qn+1) => flag],  // -1=unscored, 0=wrong, 1=correct (>.98)
    'partattemptn' => [qn => [pn => n]],   // attempt count per part
    'rawscores' => [qn => [pn => score]]   // raw scores per part
];
```
**Important:** `stuanswers`, `stuanswersval`, `scorenonzero`, `scoreiscorrect` are indexed by `qn+1`, while `partattemptn` and `rawscores` use `qn` directly.

#### Question Data (from imas_questionset table)
Key fields: `qtype` (question type), `control` (PHP setup code), `qtext` (question template), `answer` (correct answer code), `solution` (solution text), `extref` (help links), `solutionopts` (display options)

#### Hook Points
- `assess2/questions/score_engine` — hooks into scoring: `onBeforeScoreQuestion`, `onScoreQuestionResult`, `onScorePartMultiPart`, `onScorePartNonMultiPart`
- `assess2/assess_standalone` — hook into `AssessStandalone::scoreQuestion()`: `onScoreQuestionReturn`

### Database
- All tables use the `imas_` prefix
- PDO with MySQL for database access
- Migrations in `/migrations/` — run via `upgrade.php`

### Hook System
Extensibility via PHP hooks configured in `$CFG['hooks']`. Hook files are included relative to the main directory. See `hooks.md` for all available hook points (admin actions, course enrollment, login, LTI launch, etc.).

### Testing
Uses Codeception v2.4+ with unit, functional, and acceptance test suites. Tests are in `/tests/`. Coverage excludes vendor, javascript, katex, migrations, and tinymce4 directories.

#### Running Tests via Docker
PHP is not installed on the host machine. Tests must run inside the `imathas-php-fpm-1` Docker container.

1. **Verify the container is running:**
   ```bash
   docker ps --filter name=imathas
   ```
   You should see `imathas-php-fpm-1` (PHP-FPM) and `imathas-web-1` (Nginx). The app root inside the container is `/var/www/html`.

2. **Install Composer if missing** (first time only — not persisted across container rebuilds):
   ```bash
   docker exec -w /var/www/html imathas-php-fpm-1 sh -c \
     'php -r "copy(\"https://getcomposer.org/installer\", \"composer-setup.php\");" && php composer-setup.php --quiet'
   ```

3. **Install dependencies** (first time only — `vendor/` is volume-mounted so it persists):
   ```bash
   docker exec -w /var/www/html imathas-php-fpm-1 php composer.phar install --no-interaction --ignore-platform-reqs
   ```
   `--ignore-platform-reqs` is needed because the container lacks `ext-zip` (only required by the Selenium/WebDriver acceptance test driver, not unit tests).

4. **Run tests:**
   ```bash
   # All unit tests
   docker exec -w /var/www/html imathas-php-fpm-1 php vendor/bin/codecept run unit

   # Single test file
   docker exec -w /var/www/html imathas-php-fpm-1 php vendor/bin/codecept run unit SomeTestCest
   ```

**Notes:**
- The container uses PHP 7.4 and has no `bash` — use `sh -c '...'` for multi-command sequences.
- The container has no `composer` binary in PATH — use `php composer.phar` after installing it per step 2.

### Claude Documentation
The `claudedocs/` directory contains documentation generated during development — custom patches, design decisions, investigation notes, etc. Check relevant files there before making changes to understand prior context.

## Key Conventions
- Configuration is centralized in `$CFG` array in `config.php`
- Database tables prefixed with `imas_`
- User rights are numeric levels (e.g., 40 = Course Creator, higher = more rights)
- CSRF protection via csrfp library (toggle with `$CFG['use_csrfp']`)
- File storage: local by default, optional S3 via `$AWSkey`/`$AWSsecret`/`$AWSbucket`
- Passwords use bcrypt (`$CFG['GEN']['newpasswords'] = 'only'`)
