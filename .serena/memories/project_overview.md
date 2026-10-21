# IMathAS Project Overview

## Purpose
IMathAS (Internet Mathematics Assessment System) is a PHP web application for delivering and auto-grading math homework/tests with algorithmically generated questions. Powers MyOpenMath.com, WAMAP.org, Lumen OHM, and XYZhomework. Includes full LMS features: gradebook, forums, file management, calendar, and LTI integration.

## Tech Stack
- **Backend**: PHP 7.4+ (PDO/MySQL, mbstring, gettext, gd, curl, openssl)
- **Database**: MySQL 5.6+ — all tables prefixed with `imas_`
- **Frontend**: Vue.js SPA for the new assessment player (`assess2/vue-src/`)
- **Testing**: Codeception v2.4+ (unit, functional, acceptance suites)
- **Dependencies**: Managed via Composer
- **Deployment**: Docker (PHP-FPM + Nginx)

## Key Entry Points
- `install.php` — First-run setup
- `init.php` — Bootstrap: loads config, starts session, validates user
- `config.php` — All config via `$CFG` array
- `index.php` — Main dashboard
- `actions.php` — Central form action handler
- `problems.php` — External API: render a question (POST JSON)
- `scores.php` — External API: score a question (POST form-encoded)
- `StudentDrawFunction.php` — Custom API: return student draw functions as JSON (POST)

## Important Directories
- `assess2/` — Vue-based assessment player + PHP API backend
- `assessment/` — Legacy assessment rendering
- `course/` — Course management, gradebook
- `includes/` — Shared PHP utilities
- `lti/` — LTI 1.0 and 1.3 integration
- `filter/` — Graph rendering, chemistry notation
- `migrations/` — Numbered DB migration files
- `javascript/` — Client-side JS
- `tests/` — Codeception test suites
- `claudedocs/` — Dev notes, design decisions (check before making changes)
