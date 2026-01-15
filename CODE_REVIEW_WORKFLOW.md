# Code Review Workflow

This project includes **two** automated code review workflows:

1. **Local Pre-Push Hook** - Runs before every push on your machine
2. **GitHub Actions PR Review** - Runs automatically in pull requests

Both workflows use the same review criteria defined in [CLAUDE.md](./CLAUDE.md).

---

## 🔄 Local Pre-Push Hook

Every time you run `git push`, the pre-push hook **automatically** runs the **senior-code-reviewer agent** (if enabled):

### What Runs When

**Pre-Commit Hook** (`.husky/pre-commit`) - Runs before every commit:
- ✅ **PHP CS Fixer** - Code style checks
- ✅ **PHPStan** - Static analysis at level 5
- ✅ **PHPUnit** - Unit and integration tests

**Pre-Push Hook** (`.husky/pre-push`) - Runs before every push:
- 🤖 **Senior Code Reviewer Agent** - Reviews ALL files changed in the push

### Pre-Push: Senior Code Reviewer Agent (Automatic)

The hook automatically launches the **senior-code-reviewer agent** to review **all changed files**:

- 📝 **Reviews ALL file types**: PHP, YAML, Markdown, config files, workflows, etc.
- 🔍 **Checks for**:
  - Critical security issues
  - DDD violations (for PHP)
  - Code quality issues
  - Configuration errors (YAML syntax, secrets detection)
  - Documentation completeness
  - Design system consistency
  - CI/CD workflow correctness
- 🚫 **Blocks push** if critical issues found
- ⚡ **Fast feedback** before code leaves your machine
- ⚙️ **Configurable** via `.husky/pre-push-config.yml`

**Configuration** (`.husky/pre-push-config.yml`):
```yaml
# Enable/disable automatic code review
auto_review: true

# Block push if critical issues found
block_on_issues: true

# Max files to review (larger pushes use PR workflow)
max_files: 20
```

### Bypassing the Hook

If any check fails, the push is blocked. You can bypass with:
```bash
git push --no-verify
```

**Note**: Use `--no-verify` sparingly. The hook catches issues early!

## 🤖 Manual Code Review (Optional)

**Note**: The pre-push hook now runs automatic code review! Manual review is optional for deeper analysis.

For deeper architectural analysis beyond the automatic review, use the **senior-code-reviewer** agent:

### Quick Start
1. Make your code changes
2. Ask me: **"Review the changes I'm about to push"**
3. I'll launch the senior-code-reviewer agent automatically
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

## 📋 Review Checklist

The senior-code-reviewer agent evaluates:

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

## 🚀 Workflow Example

```bash
# 1. Make your changes
git checkout -b feature/monitoring-dispatcher
# ... write code ...

# 2. Run automated checks
composer check-all

# 3. Request code review
# Ask: "Review my changes for the monitoring dispatcher"

# 4. Fix issues based on feedback
# ... make fixes ...

# 5. Commit and push
git add .
git commit -m "feat: Implement monitoring dispatcher"
git push origin feature/monitoring-dispatcher
# Pre-push hook runs automatically ✅

# 6. Create PR if needed
gh pr create --title "feat: Implement monitoring dispatcher"
```

## 🔧 GitHub Actions Pull Request Review

Automated code review runs directly in your pull requests using the official **Claude Code Action**.

### How It Works

1. **Automatic Reviews**: Every PR triggers automatic code review
2. **Interactive Reviews**: Mention `@claude` in any PR comment for on-demand review
3. **Manual Triggers**: Run from GitHub Actions UI with custom focus

### Automatic PR Reviews

When you create or update a pull request, Claude automatically:

- Reviews all changes against [CLAUDE.md](./CLAUDE.md) guidelines
- Evaluates architectural alignment (DDD principles)
- Checks code quality, security, performance, and testing
- Posts feedback as a PR comment
- Provides specific, actionable suggestions

**Example PR workflow**:
```bash
# 1. Create PR
git checkout -b feature/telemetry-ingestor
# ... make changes ...
git push origin feature/telemetry-ingestor
gh pr create --title "feat: Add telemetry ingestor service"

# 2. Claude automatically reviews your PR ✅
# Check the PR comments for detailed feedback

# 3. Address the feedback
# ... make fixes ...
git add .
git commit -m "fix: Address code review feedback"
git push

# 4. Claude re-reviews on update ✅
```

### Interactive PR Reviews with @claude

You can ask Claude to review specific aspects by commenting in your PR:

```markdown
@claude review my changes for security issues
@claude check the performance of this query
@claude does this follow DDD principles?
@claude suggest tests for this feature
@claude refactor this for better readability
```

Claude will:
- Analyze your specific request
- Provide focused feedback
- Suggest code improvements with examples
- Answer questions about architecture or implementation

### Manual Review Triggers

From GitHub Actions UI:
1. Go to **Actions** tab
2. Select **"Claude Code Review"** workflow
3. Click **"Run workflow"**
4. Choose review type:
   - **full**: Complete review (all aspects)
   - **security**: Security-focused review
   - **performance**: Performance analysis
   - **architecture**: Architectural evaluation

### Required Setup

Before PR reviews work, you need to:

1. **Install Claude GitHub App** (one-time):
   ```bash
   claude /install-github-app
   ```

   Or install manually at: https://github.com/apps/claude

2. **Add API Key to Secrets**:
   - Go to repository **Settings** → **Secrets and variables** → **Actions**
   - Click **"New repository secret"**
   - Name: `GLM_API_KEY`
   - Value: Your GLM API key (from Zhipu AI)

3. **Verify Permissions**:
   - GitHub App needs: **Contents** (read/write), **Pull requests** (read/write), **Issues** (read/write)

### GLM Model Configuration

This project uses **GLM-4 models** via the Zhipu AI proxy endpoint instead of the official Anthropic API.

#### Local Development

Your local environment is already configured in `~/.claude/settings.json`:

```json
{
  "env": {
    "ANTHROPIC_AUTH_TOKEN": "your-glm-api-key",
    "ANTHROPIC_BASE_URL": "https://api.z.ai/api/anthropic"
  }
}
```

#### GitHub Actions CI

The CI workflow uses the same GLM proxy endpoint. To set up:

1. **Add GitHub Secret**:
   - Navigate to: Repository Settings → Secrets and variables → Actions
   - Create a new secret named `GLM_API_KEY`
   - Paste your GLM API key (same format as your local token)

2. **Verify Configuration**:
   - The workflow automatically uses the GLM proxy endpoint
   - Model defaults to GLM-4 for optimal performance
   - No additional configuration needed

#### GLM API Key Format

Your GLM API key should be in the format:
```
sk-xxxxxxxx.OmQ93f0lzw7jJyUG
```

This is provided by Zhipu AI when you sign up for their service.

#### Why GLM Models?

- **Cost-effective**: More affordable per-token pricing
- **Performance**: GLM-4 offers competitive code analysis capabilities
- **Compatibility**: Works via Anthropic API-compatible proxy

### Cost Considerations

Using Claude Code GitHub Actions incurs costs:

- **GitHub Actions minutes**: Standard GitHub Actions usage
- **Claude API costs**: Token usage based on PR size and review complexity
- **Estimated cost**: ~$0.50-2.00 per PR review (varies by codebase size)

**Cost optimization tips**:
- Claude automatically reviews on PR open/update (no manual trigger needed)
- Use specific `@claude` commands for focused reviews (cheaper than full reviews)
- Enable only for main branch PRs (modify workflow triggers if needed)

### Review Criteria

Claude follows the guidelines defined in [CLAUDE.md](./CLAUDE.md), focusing on:

1. **DDD & Architecture**: Bounded contexts, domain entities, value objects
2. **Code Quality**: PHP CS Fixer, PHPStan compliance, SOLID principles
3. **Security**: Input validation, parameterized queries, no secrets in code
4. **Performance**: N+1 queries, covering indexes, bulk operations
5. **Scalability**: Thundering Herd handling, write buffering, partitioning
6. **Testing**: Test coverage, edge cases, independence
7. **Documentation**: PHPDoc, ADRs for major decisions

---

## 🔄 Complete Workflow Example

Combining both local and PR reviews:

```bash
# 1. Create feature branch
git checkout -b feature/bulk-ingestor

# 2. Make your changes
# ... write code ...

# 3. Local quality check
composer check-all

# 4. Local code review (optional)
# Ask: "Review my changes for the bulk ingestor"

# 5. Commit and push
git add .
git commit -m "feat: Implement bulk telemetry ingestor"
git push origin feature/bulk-ingestor
# Pre-push hook runs automatically ✅

# 6. Create PR
gh pr create --title "feat: Implement bulk telemetry ingestor"

# 7. Claude reviews in PR automatically ✅
# Check PR comments for detailed feedback

# 8. Address PR feedback
# ... make fixes ...
git add .
git commit -m "fix: Address PR review feedback"
git push

# 9. Claude re-reviews on update ✅

# 10. Merge after approval
gh pr merge --merge
```

---

## 🔧 Local Workflow Configuration

### Configure Automatic Review

Edit `.husky/pre-push-config.yml`:

```yaml
# Disable automatic review (use only composer checks)
auto_review: false

# Don't block push, just warn
block_on_issues: false

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

### Skip Pre-Push Hook Permanently
```bash
rm .husky/pre-push
```

### Re-Install Pre-Push Hook
```bash
chmod +x .husky/pre-push
```

## 📚 Resources

### Local Workflow
- [Pre-Push Hooks Documentation](https://git-scm.com/docs/githooks#_pre_push)
- [Senior Code Reviewer Agent](.claude/agents/senior-code-reviewer.md)
- [Composer Scripts](composer.json)

### GitHub Actions Workflow
- [Claude Code GitHub Actions Documentation](https://code.claude.com/docs/en/github-actions)
- [Claude Code Action Repository](https://github.com/anthropics/claude-code-action)
- [Setup Guide](https://github.com/anthropics/claude-code-action#quickstart)

### Project Standards
- [CLAUDE.md](CLAUDE.md) - Project coding standards and review criteria
- [PROJECT_BLUEPRINT.md](PROJECT_BLUEPRINT.md) - Architecture documentation
- [README.md](README.md) - Project overview and installation