# Code Review Workflow

This project includes **two** automated code review workflows:

1. **Local Pre-Push Hook** - Runs before every push on your machine
2. **GitHub Actions PR Review** - Runs automatically in pull requests

Both workflows use the same review criteria defined in [CLAUDE.md](./CLAUDE.md).

---

## 🔄 Local Pre-Push Hook

Every time you run `git push`, the pre-push hook automatically runs:

```bash
composer check-all
```

This includes:
- ✅ **PHP CS Fixer** - Code style checks
- ✅ **PHPStan** - Static analysis at level 5
- ✅ **PHPUnit** - Unit and integration tests

If any check fails, the push is blocked. You can bypass with:
```bash
git push --no-verify
```

## 🤖 Manual Code Review

For deeper architectural analysis, use the **senior-code-reviewer** agent:

### Quick Start (Recommended)
1. Make your code changes
2. Ask me: **"Review the changes I'm about to push"**
3. I'll launch the senior-code-reviewer agent automatically
4. Fix any issues found
5. Push with confidence: `git push`

### Using the Convenience Script
Run the interactive review script:
```bash
bin/review-before-push
```

This script:
- Runs automated quality checks
- Shows you what files changed
- Provides options for manual review
- Asks if you're ready before pushing

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
   - Name: `ANTHROPIC_API_KEY`
   - Value: Your Claude API key (get from https://console.anthropic.com/)

3. **Verify Permissions**:
   - GitHub App needs: **Contents** (read/write), **Pull requests** (read/write), **Issues** (read/write)

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

### Disable Pre-Push Hook Temporarily
```bash
git push --no-verify
```

### Skip Pre-Push Hook Permanently
```bash
rm .git/hooks/pre-push
```

### Re-Install Pre-Push Hook
```bash
chmod +x .git/hooks/pre-push
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