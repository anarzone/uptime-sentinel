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
