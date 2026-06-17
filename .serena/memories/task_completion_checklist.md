# Task Completion Checklist

When finishing any code change in IMathAS:

1. **Run unit tests** inside Docker:
   ```bash
   docker exec -w /var/www/html imathas-php-fpm-1 php vendor/bin/codecept run unit
   ```

2. **If JS files changed** (any file with a `_min.js` counterpart):
   ```bash
   cd assess2/vue-src && ./buildmin.sh
   ```

3. **If Vue source changed**:
   ```bash
   cd assess2/vue-src && npm run build
   ```

4. **If TinyMCE plugins changed**:
   ```bash
   php tinymce4/maketinymcebundle.php
   ```

5. **If DB schema changed**: add a migration file to `/migrations/`

6. **Check `claudedocs/`** for any relevant prior design notes

7. **No linter/formatter** is configured — match existing file style manually
