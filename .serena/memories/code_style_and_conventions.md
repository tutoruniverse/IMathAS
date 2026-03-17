# Code Style and Conventions

## PHP Style
- PHP 7.4+ compatible code
- No strict type hints enforced project-wide (legacy codebase)
- Procedural style in legacy files; OOP in `assess2/` subsystem
- Namespaces used in `assess2/` (e.g., `IMathAS\assess2\questions\scorepart`)
- No enforced PSR standard — follow existing file style

## Naming
- Classes: PascalCase (e.g., `DrawingScorePart`, `ScoreEngine`)
- Methods/functions: camelCase for OOP, snake_case for legacy procedural (e.g., `fans_polygons`)
- Variables: camelCase in OOP, snake_case in procedural
- DB tables: `imas_` prefix (e.g., `imas_questionset`)

## Configuration
- All config in `$CFG` array in `config.php`
- Do not hardcode config values — use `$CFG`

## Database
- PDO with MySQL
- All tables prefixed `imas_`
- Migrations in `/migrations/` — run via `upgrade.php`

## Security
- CSRF protection via csrfp library (`$CFG['use_csrfp']`)
- Passwords use bcrypt
- Sanitize all user input; use PDO prepared statements

## assess2 Architecture (OOP)
- `QuestionParams` / `ScoreQuestionParams` — input models (fluent setters)
- `Question` — output model
- `QuestionGenerator` — renders questions
- `ScoreEngine` — orchestrates scoring
- `ScorePartFactory` — maps answer types to `ScorePart` implementations
- State array uses 1-indexed keys for `stuanswers`/`stuanswersval`/`scorenonzero`/`scoreiscorrect`, 0-indexed for `partattemptn`/`rawscores`

## Documentation
- Check `claudedocs/` for design decisions and investigation notes before making changes
- `CLAUDE.md` has comprehensive architecture documentation
