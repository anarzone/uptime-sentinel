# Code Review Workflow

This project uses a **local pre-push code review system** designed to catch issues early before they leave your machine.

---

## 🎯 Local Pre-Push Code Review

### Overview

**Purpose**: Fast local feedback, catch issues before code leaves your machine

**When**: Before every `git push` (via `.husky/pre-push`)

**Method**: `claude ask` command with senior code reviewer prompt

**Scope**: All changed files (PHP, YAML, Markdown, configs, workflows)

**Benefits**:
- ✅ **Fast feedback** (runs on your machine)
- ✅ **Catches issues early** (before pushing)
- ✅ **No API costs** (uses local Claude CLI)
- ✅ **Blocks bad code** (prevents broken code from leaving your machine)

---

## 🔄 Local Pre-Push Hook

Every time you run `git push`, the pre-push hook **automatically** runs code review on all changed files.

### What Runs When

**Pre-Commit Hook** (`.husky/pre-commit`) - Runs before every commit:
- ✅ **PHP CS Fixer** - Code style checks
- ✅ **PHPStan** - Static analysis at level 5
- ✅ **PHPUnit** - Unit and integration tests

**Pre-Push Hook** (`.husky/pre-push`) - Runs before every push:
- 🤖 **Code Review** - Reviews ALL files changed in the push

### Pre-Push: Interactive Code Review

The hook automatically runs code review to review **all changed files**:

- 📝 **Reviews ALL file types**: PHP, YAML, Markdown, config files, workflows, etc.
- 🔍 **Checks for**:
  - Critical security issues
  - DDD violations (for PHP)
  - Code quality issues
  - Configuration errors (YAML syntax, secrets detection)
  - Documentation completeness
  - Design system consistency
  - CI/CD workflow correctness
- 🤔 **Interactive prompt** if critical issues found
  - Asks if you want to continue or abort
  - Gives you control over whether to fix issues first
  - Forces acknowledgment before proceeding with issues
- ⚡ **Fast feedback** before code leaves your machine
- ⚙️ **Configurable** via `.husky/pre-push-config.yml`

**Configuration** (`.husky/pre-push-config.yml`):
```yaml
# Enable/disable automatic code review
auto_review: true

# Interactive mode: Ask before proceeding with issues
# When true, the hook will prompt you to continue or abort if issues are found
# When false, the hook will proceed automatically without prompting
interactive: true

# Max files to review (larger pushes are reviewed but may take longer)
max_files: 20
```

### Interactive Workflow Example

When you push code with issues, you'll see:

```bash
$ git push origin feature-branch

🤖 Running pre-push code review...

📝 Reviewing 3 file(s):
   - src/Monitor/MonitorService.php
   - config/services.yaml
   - README.md

🤖 Calling Claude Code senior-code-reviewer agent...

═══════════════════════════════════════════════════════
📊 CODE REVIEW RESULTS
═══════════════════════════════════════════════════════

BLOCKING ISSUES FOUND:
1. SQL injection vulnerability in MonitorService.php:42
   - Concatenating user input into query string
   - Use parameterized queries instead

═══════════════════════════════════════════════════════

⚠️  Critical issues found
⚠️  Review the issues above before proceeding

Continue with push anyway? (y/N): [Type 'y' to proceed, any other key to abort]
```

### Bypassing the Hook

If you need to skip the review entirely:
```bash
git push --no-verify
```

**Note**: Use `--no-verify` sparingly. The interactive prompt is designed to give you control while still catching issues early!

---

## 📋 Review Checklist

The automatic code review evaluates:

1. **Architectural Alignment**
   - Separation of concerns
   - Design patterns
   - Scalability considerations

2. **Code Quality**
   - Readability and maintainability
   - SOLID principles
   - Error handling

3. **Performance**
   - Algorithmic complexity
   - Database queries (N+1 problems)
   - Caching strategies

4. **Security**
   - Input validation
   - SQL injection, XSS, CSRF
   - Authentication/authorization

5. **Testing**
   - Test coverage
   - Test quality
   - Edge cases

6. **Documentation**
   - Inline comments
   - API documentation
   - Architectural decisions

---

## 🚀 Workflow Example

```bash
# 1. Make your changes
git checkout -b feature/monitoring-dispatcher
# ... write code ...

# 2. Run automated checks (optional - runs automatically on commit)
composer check-all

# 3. Commit changes (pre-commit hook runs)
git add .
git commit -m "feat: Implement monitoring dispatcher"
# ✅ Pre-commit: PHP CS Fixer, PHPStan, PHPUnit run automatically

# 4. Push (pre-push hook runs interactive code review)
git push origin feature/monitoring-dispatcher
# ✅ Pre-push: Code review runs automatically
# ✅ If critical issues found: Interactive prompt asks to continue or abort
# ✅ If no issues: Push proceeds automatically

# 5a. If issues were found and you aborted:
#    - Fix the issues
#    - Commit again: git commit -am "fix: Address review feedback"
#    - Push again: git push origin feature/monitoring-dispatcher

# 5b. If you want to review PR after pushing:
gh pr create --title "feat: Implement monitoring dispatcher"
./scripts/review-pr.sh  # Manual PR review (optional)
```

---

## 🔧 Optional: Manual Code Review

For deeper architectural analysis beyond the automatic review, you can manually trigger a review:

### Quick Start
1. Make your code changes
2. Ask me: **"Review the changes I'm about to push"**
3. I'll run code review automatically
4. Fix any issues found
5. Push with confidence: `git push` (automatic review will run again)

### When to Use Manual Review
- **Before pushing** major refactors or new features
- **To explore** alternative implementations
- **To learn** from detailed architectural feedback
- **To review** code that exceeds `max_files` limit

### Review Specific Aspects
You can also ask for focused reviews:
- **"Review my changes for security issues"**
- **"Check my code for performance problems"**
- **"Review my architectural decisions"**
- **"Evaluate my code's test coverage"**

---

## 🔧 Local Workflow Configuration

### Configure Automatic Review

Edit `.husky/pre-push-config.yml`:

```yaml
# Disable automatic review (use only composer checks)
auto_review: false

# Disable interactive prompts (proceed automatically even with issues)
interactive: false

# Review up to 50 files (default: 20)
max_files: 50
```

### Disable Pre-Push Hook Temporarily
```bash
git push --no-verify
```

### Disable Automatic Review Only
If you want to keep composer checks but skip automatic review:
```yaml
# In .husky/pre-push-config.yml
auto_review: false
```

### Disable Interactive Prompts
If you want the hook to proceed automatically without prompting (not recommended):
```yaml
# In .husky/pre-push-config.yml
interactive: false
```

### Skip Pre-Push Hook Permanently
```bash
rm .husky/pre-push
```

### Re-Install Pre-Push Hook
```bash
chmod +x .husky/pre-push
```

---

## 💰 Cost & Performance

### Local Review

- **Cost**: Free (uses local Claude CLI)
- **Speed**: Fast (3-10 seconds depending on file count)
- **Resources**: Your local CPU
- **API Usage**: None (local Claude CLI)

### Performance Tips

1. **Review scope**: Automatically skips if >20 files changed (configurable)
2. **Review speed**: Typically 3-5 seconds for small changes
3. **No waiting**: Runs locally, no queue or external dependencies
4. **No limits**: Unlimited reviews, no API costs

---

## 📚 Resources

### Local Workflow
- [Pre-Push Hooks Documentation](https://git-scm.com/docs/githooks#_pre_push)
- [Composer Scripts](composer.json)
- [Claude Code CLI](https://code.claude.com)

### Project Standards
- [CLAUDE.md](CLAUDE.md) - Project coding standards and review criteria
- [PROJECT_BLUEPRINT.md](PROJECT_BLUEPRINT.md) - Architecture documentation
- [README.md](README.md) - Project overview and installation

---

## 🔄 Previous: Two-Tier System (Now Disabled)

This project previously used a **two-tier code review system** with both local pre-push hooks and remote GitHub Actions PR reviews. The remote tier has been disabled to use only the free local review.

**Why change?**
- Local review catches 100% of issues before pushing ✅
- Zero API costs ✅
- Faster feedback (3-5s vs 60s+) ✅
- No dependency on external services ✅

**To re-enable remote reviews** (if needed in future):
1. Edit `.github/workflows/code-review.yml`
2. Change `if: false` to `if: true` on line 32
3. Ensure `GLM_API_KEY` secret is set in GitHub repository settings

---

## 🎯 Summary

**Current Setup**: Local pre-push code review only (free, fast, interactive)

**What to expect**:
- Pre-commit hook: PHP CS Fixer, PHPStan, PHPUnit
- Pre-push hook: Automatic interactive code review (asks before proceeding with issues)
- Optional manual PR review: Use `./scripts/review-pr.sh` for deeper analysis

**Benefits**:
- ✅ Catch issues before they leave your machine
- ✅ Interactive control over whether to fix or proceed
- ✅ Zero API costs
- ✅ Fast feedback (3-5 seconds)
- ✅ Works offline
- ✅ No external dependencies
