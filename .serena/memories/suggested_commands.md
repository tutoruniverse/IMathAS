# Suggested Commands

## PHP Backend
```bash
# Install dependencies (run inside Docker container)
docker exec -w /var/www/html imathas-php-fpm-1 php composer.phar install --no-interaction --ignore-platform-reqs

# Dev server (host machine)
composer start    # PHP dev server at localhost:3000
```

## Testing (must run inside Docker)
```bash
# Verify container is running
docker ps --filter name=imathas

# Run all unit tests
docker exec -w /var/www/html imathas-php-fpm-1 php vendor/bin/codecept run unit

# Run single test file
docker exec -w /var/www/html imathas-php-fpm-1 php vendor/bin/codecept run unit SomeTestCest

# Run with coverage
docker exec -w /var/www/html imathas-php-fpm-1 php vendor/bin/codecept run --coverage --coverage-html
```

## Vue.js Assessment Player (assess2/vue-src/)
```bash
cd assess2/vue-src
npm install
npm run serve     # Dev server with hot-reload (localhost:8080)
npm run build     # Production build to assess2/vue/
npm run lint      # Lint and fix
./buildmin.sh     # Rebuild minified external JS files
```

## After JS Changes
- After modifying any JS file with a `_min.js` version: run `assess2/vue-src/buildmin.sh`
- After changing TinyMCE plugins: run `tinymce4/maketinymcebundle.php`

## Git / System (Darwin)
```bash
git log --oneline
git diff <commit>..HEAD -- ':!config.php'
git diff --stat <commit>..HEAD
ls, find, grep, cat  # standard macOS utils
```

## Notes
- PHP is NOT installed on the host — tests MUST run inside `imathas-php-fpm-1` Docker container
- Use `sh -c '...'` not `bash -c` inside the container (no bash available)
- Use `php composer.phar` not `composer` inside the container
