/**
 * Commitlint Configuration
 *
 * Enforces conventional commit message format to ensure:
 * - Consistent commit history
 * - Automatic semantic-release versioning
 * - Clear changelog generation
 *
 * Commit Message Format: <type>: <subject>
 *
 * Valid Types:
 * - feat: New feature (triggers minor version bump)
 * - fix: Bug fix (triggers patch version bump)
 * - chore: Maintenance task (no version bump)
 * - test: Test changes (no version bump)
 * - wip: Work in progress (no version bump)
 *
 * Examples:
 * - feat: Add MonitorState value object for DDD compliance
 * - fix: Prevent N+1 queries with JOIN FETCH in AlertRuleRepository
 * - chore: Update dependencies
 *
 * @see CLAUDE.md - Full commit standards and guidelines
 */
export default {
    extends: ['@commitlint/config-conventional'],
    rules: {
        // Only allow project-specific commit types
        // These match semantic-release expectations
        'type-enum': [2, 'always', ['chore', 'feat', 'fix', 'test', 'wip']],

        // Type must be lowercase
        'type-case': [2, 'always', 'lower-case'],

        // Type cannot be empty
        'type-empty': [2, 'never'],

        // Subject cannot be empty
        'subject-empty': [2, 'never'],

        // Subject case: Disabled to allow flexibility
        // Project uses title case ("Add MonitorState...") which is acceptable
        // Set to [0] to disable, or [2, 'always', 'lower-case'] for consistency
        'subject-case': [0],

        // Subject should NOT end with period
        'subject-full-stop': [2, 'never', '.'],

        // Subject length limits (GitHub-friendly)
        'subject-max-length': [2, 'always', 72],
        'subject-min-length': [2, 'always', 10],

        // Overall header length (type + separator + subject)
        'header-max-length': [2, 'always', 100],

        // Body and footer line length
        'body-max-line-length': [2, 'always', 200],
        'footer-max-line-length': [2, 'always', 100]
    }
};
