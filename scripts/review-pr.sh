#!/bin/bash

# Local PR Review Script
# Usage: ./scripts/review-pr.sh [<PR_NUMBER>]
# If no PR number provided, reviews the PR for the current branch

set -e

# Colors
BLUE='\033[0;34m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Get PR number
if [ -n "$1" ]; then
    PR_NUMBER=$1
else
    # Try to get PR for current branch
    PR_NUMBER=$(gh pr view --json number --jq '.number' 2>/dev/null || echo "")

    if [ -z "$PR_NUMBER" ]; then
        echo -e "${YELLOW}No PR found for current branch${NC}"
        echo -e "${YELLOW}Usage: $0 <PR_NUMBER>${NC}"
        exit 1
    fi
fi

echo -e "${BLUE}📊 Fetching PR #$PR_NUMBER...${NC}"

# Get PR details
PR_TITLE=$(gh pr view $PR_NUMBER --json title --jq '.title')
PR_BRANCH=$(gh pr view $PR_NUMBER --json headRefName --jq '.headRefName')
PR_BASE=$(gh pr view $PR_NUMBER --json baseRefName --jq '.baseRefName')

echo -e "${BLUE}Title: $PR_TITLE${NC}"
echo -e "${BLUE}Branch: $PR_BRANCH → $PR_BASE${NC}"
echo ""

# Get changed files
echo -e "${BLUE}📝 Fetching changed files...${NC}"
CHANGED_FILES=$(gh pr diff $PR_NUMBER --name-only)
FILE_COUNT=$(echo "$CHANGED_FILES" | grep -v -e '^[[:space:]]*$' | wc -l | tr -d '[:space:]')

echo -e "${BLUE}Found $FILE_COUNT changed file(s)${NC}"
echo ""

# Create temp file with prompt
PROMPT_FILE=$(mktemp)
trap "rm -f $PROMPT_FILE" EXIT

cat > "$PROMPT_FILE" <<EOF
You are a senior code reviewer. Please review this pull request:

**PR #$PR_NUMBER: $PR_TITLE**

**Branch**: $PR_BRANCH → $PR_BASE

**Changed Files** ($FILE_COUNT files):
$(echo "$CHANGED_FILES" | while read -r file; do
    [ -n "$file" ] && echo "- $file"
done)

**Review Scope**: Review ALL files comprehensively:
- **PHP Files**: DDD violations, code quality, security, performance, testing
- **YAML Files**: Configuration correctness, syntax, best practices
- **Markdown/Docs**: Clarity, completeness, accuracy
- **Design System**: Consistency, proper patterns, accessibility
- **CI/CD Files**: Workflow correctness, security, best practices
- **Config Files**: Proper settings, no secrets, environment-specific checks

Focus on:
1. **Critical Issues**: Security vulnerabilities, breaking changes, obvious bugs
2. **Architecture**: DDD violations, design patterns, scalability
3. **Code Quality**: Standards violations per CLAUDE.md, maintainability
4. **Configuration**: YAML syntax, environment variables, secrets detection
5. **Documentation**: Completeness, clarity, accuracy
6. **Testing**: Missing tests for critical paths

**Important**:
- If you find CRITICAL issues (must-fix), start your response with exactly: "BLOCKING ISSUES FOUND:"
- If only suggestions, start with: "RECOMMENDATIONS:"
- Keep feedback concise and actionable
- Reference specific file:line numbers where possible
- Use gh pr view $PR_NUMBER to see the full diff if needed

Please review the PR now.
EOF

echo -e "${BLUE}🤖 Calling Claude CLI for code review...${NC}"
echo ""

# Run Claude CLI with the prompt
claude ask < "$PROMPT_FILE"

echo ""
echo -e "${GREEN}✅ Review complete!${NC}"
echo ""
echo -e "${YELLOW}💡 To view the PR diff: gh pr diff $PR_NUMBER${NC}"
echo -e "${YELLOW}💡 To merge: gh pr merge $PR_NUMBER --merge${NC}"
echo -e "${YELLOW}💡 To close: gh pr close $PR_NUMBER${NC}"
