# Command Cheat Sheet

This document contains custom shell aliases configured in `~/.zshrc` for faster development workflow.

## Git Aliases

| Alias | Full Command | Description | Example Usage |
|-------|-------------|-------------|---------------|
| `ga` | `git add` | Stage files for commit | `ga .` or `ga file.php` |
| `gc` | `git commit -m` | Commit changes with message | `gc "feat: add user creation"` |
| `gp` | `git push` | Push commits to remote | `gp` |
| `gpo` | `git push origin` | Push to origin remote | `gpo main` |
| `gs` | `git status` | Show working tree status | `gs` |
| `glog` | `git log --oneline --graph` | Show commit history with graph | `glog` |

### Git Examples

```bash
# Check repository status
gs

# Stage all changes
ga .

# Stage specific file
ga src/UserController.php

# Commit with message
gc "fix: resolve UUID generation issue"

# Push to current branch
gp

# Push to origin (specific branch)
gpo feature/new-feature

# View commit history with graph
glog
```

## Symfony/Console Aliases

| Alias | Full Command | Description | Example Usage |
|-------|-------------|-------------|---------------|
| `bc` | `bin/console` | Run Symfony console commands | `bc cache:clear` |
| `dddf` | `bin/console doctrine:database:drop --force` | Drop database (forced, no confirmation) | `dddf` |
| `ddc` | `bin/console doctrine:database:create` | Create database | `ddc` |
| `dfl` | `bin/console doctrine:fixtures:load` | Load fixtures | `dfl` |
| `dmm` | `bin/console doctrine:migrations:migrate` | Run database migrations | `dmm` |
| `dmd` | `bin/console doctrine:migrations:diff` | Generate migration from schema changes | `dmd` |

### Symfony Examples

```bash
# Run any console command
bc cache:clear
bc doctrine:migrations:status

# Recreate database from scratch
dddf && ddc && dfl

# Load fixtures with confirmation
dfl

# Check database connection
bc doctrine:database:verify
```

## Common Workflows

### Git Workflow

```bash
# 1. Make changes to files
# 2. Stage changes
ga .

# 3. Commit changes
gc "feat: add new feature"

# 4. Push to remote
gp
```

### Database Reset Workflow

```bash
# Drop, create, and load fixtures in one command
dddf && ddc && dfl
```

### Migration Workflow

```bash
# Create migration from schema changes
dmd

# Run migrations
dmm

# Check migration status
bc doctrine:migrations:status
```

---

## GitHub CLI (gh) Commands

### Pull Request Management

| Command | Description | Example Usage |
|---------|-------------|---------------|
| `gh pr list` | List all open PRs | `gh pr list` |
| `gh pr view <num>` | View PR details | `gh pr view 2` |
| `gh pr create` | Create PR from current branch | `gh pr create --title "Title" --body "Description"` |
| `gh pr merge <num>` | Merge PR | `gh pr merge 2 --merge` |
| `gh pr close <num>` | Close PR without merging | `gh pr close 2` |
| `gh pr checks <num>` | View CI status for PR | `gh pr checks 2` |

### GitHub CLI Examples

```bash
# List all open PRs
gh pr list

# List all PRs (including closed)
gh pr list --state all

# View specific PR
gh pr view 2

# Create PR from current branch
gh pr create --title "feat: Add new feature" --body "Description here"

# Create PR as draft
gh pr create --draft --title "WIP: Feature" --body "Work in progress"

# Merge PR (default: merge commit)
gh pr merge 2

# Squash merge
gh pr merge 2 --squash

# Rebase merge
gh pr merge 2 --rebase

# Merge with branch deletion
gh pr merge 2 --merge --delete-branch

# Close PR without merging
gh pr close 2

# Check if PR is mergeable
gh pr view 2 --json mergeable

# Batch merge all open PRs (use with caution!)
gh pr list --json number --jq '.[].number' | xargs -I {} gh pr merge {} --merge
```

### Repository Management

```bash
# Set default repository for gh CLI
gh repo set-default anarzone/uptime-sentinel

# View repository info
gh repo view

# View repository with JSON output
gh repo view --json name,owner,defaultBranchRef
```

### Issue Management

```bash
# List issues
gh issue list

# Create issue
gh issue create --title "Bug: Error in login" --body "Description here"

# View issue
gh issue view 15

# Close issue
gh issue close 15 --comment "Fixed in PR #42"
```

### Workflow Run Management

```bash
# List recent workflow runs
gh run list

# View specific workflow run
gh run view 21031687322

# Watch workflow run in real-time
gh run watch

# Re-run failed workflow
gh run rerun 21031687322

# View workflow logs
gh run view 21031687322 --log
```

### GitHub CLI Workflow

```bash
# 1. Check for open PRs
gh pr list

# 2. Create feature branch
git checkout -b feature/new-feature

# 3. Make changes and commit
ga .
gc "feat: Add new feature"

# 4. Push branch
gp

# 5. Create PR
gh pr create --title "feat: Add new feature" --body "Description"

# 6. List PRs to find the number
gh pr list

# 7. Merge PR when ready (use the PR number from the list)
gh pr merge <PR_NUMBER> --merge --delete-branch

# OR: If your current branch has an open PR, you can merge without specifying the number
gh pr merge --merge --delete-branch
```

### How to Know Which PR to Merge

**Method 1: List PRs to see the numbers**
```bash
gh pr list
# Output:
# 2  feat: Add new feature        2 hours ago
# 3  fix: Resolve login issue     1 hour ago

# Then merge by number
gh pr merge 2 --merge
```

**Method 2: View PR for current branch**
```bash
# Shows PR for the current branch (if one exists)
gh pr view

# Then merge it
gh pr merge --merge
```

**Method 3: Search PRs by branch name**
```bash
# Find PR for a specific branch
gh pr list --head feature/new-feature

# Then merge by the number shown
gh pr merge 2 --merge
```

## Notes

- All aliases are automatically available in new terminal sessions
- To use in current session, run: `source ~/.zshrc`
- To see all active aliases, run: `alias`
- Git commands are shorter for faster daily workflow
- Symfony commands follow naming pattern:
  - `bc` = bin/console (general)
  - `dd` = doctrine database (ddc = create, dddf = drop force)
  - `dfl` = doctrine fixtures load
  - `dm` = doctrine migrations (dmm = migrate, dmd = diff)
- Database commands include safety flags where appropriate (e.g., `--force` for `dddf`)

## Adding New Aliases

To add new aliases, edit `~/.zshrc` and add them in the aliases section:

```bash
# Your new alias
alias alias_name='full command'
```

Then reload: `source ~/.zshrc`
