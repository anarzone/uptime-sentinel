# Git Hooks

> **⚠️ MIGRATION NOTICE**: This directory has been migrated to [Husky](https://typicode.github.io/husky/).
> All git hooks are now managed in the `.husky/` directory.
> This directory is kept for historical reference only.
>
> **Current hooks location:** `.husky/pre-commit` and `.husky/commit-msg`

This directory previously contained git hooks for the UptimeSentinel project.

## Installation

To install these hooks in your local repository, run:

```bash
git config core.hooksPath .githooks
```

This tells git to use the hooks in this directory instead of `.git/hooks`.

## Verification

To verify the hooks are installed:

```bash
git config core.hooksPath
```

Should output: `.githooks`

## Pre-Commit Hook

The `pre-commit` hook automatically runs before each commit:

1. **PHP CS Fixer** - Checks code style (doesn't modify files)
2. **PHPStan** - Runs static analysis
3. **PHPUnit** - Runs test suite

If any check fails, the commit is blocked.

### Bypassing the Hook

If you need to bypass the hook (not recommended):

```bash
git commit --no-verify -m "Your message"
```

### Manual Checks

You can also run the checks manually:

```bash
# Check style only
composer check-style

# Fix style issues
composer format

# Run static analysis
composer analyze

# Run tests
composer test

# Run all checks
composer check-all
```

## Uninstalling

To uninstall the hooks and use git's default:

```bash
git config --unset core.hooksPath
```