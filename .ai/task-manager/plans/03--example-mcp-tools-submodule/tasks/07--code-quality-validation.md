---
id: 7
group: 'quality-assurance'
dependencies: [2, 3, 4, 5, 6]
status: 'pending'
created: '2025-10-01'
skills: ['code-quality']
---

# Code Quality Validation and Standards Compliance

## Objective

Ensure all example code meets Drupal coding standards, passes static analysis, and follows best practices for documentation and formatting.

## Skills Required

- `drupal-backend`: Drupal coding standards, PHPStan configuration, documentation conventions

## Acceptance Criteria

- [ ] All code passes `phpcs` with Drupal and DrupalPractice standards
- [ ] All code passes `phpstan` analysis at level 5
- [ ] All classes have proper PHPDoc comments
- [ ] All methods have parameter and return type documentation
- [ ] No trailing whitespace in any files
- [ ] All files end with a newline character
- [ ] Code follows declare(strict_types=1) pattern

## Technical Requirements

- PHPCS standards: Drupal, DrupalPractice
- PHPStan level: 5
- Paths to check: `modules/jsonrpc_mcp_examples/src/`, `modules/jsonrpc_mcp_examples/tests/`
- Auto-fix where possible with `phpcbf`

## Input Dependencies

- Task 2: ArticleToMarkdown implementation
- Task 3: ListContentTypes implementation
- Task 4: ListArticles implementation
- Task 5: Kernel tests
- Task 6: Unit tests

## Output Artifacts

- Clean code that passes all linters
- No outstanding coding standard violations

<details>
<summary>Implementation Notes</summary>

### Validation Commands

Run these commands from the Drupal root directory:

```bash
# Check coding standards
vendor/bin/phpcs --standard=Drupal,DrupalPractice \
  modules/jsonrpc_mcp_examples/src/ \
  modules/jsonrpc_mcp_examples/tests/

# Auto-fix coding standards where possible
vendor/bin/phpcbf --standard=Drupal,DrupalPractice \
  modules/jsonrpc_mcp_examples/src/ \
  modules/jsonrpc_mcp_examples/tests/

# Run static analysis
vendor/bin/phpstan analyse \
  modules/jsonrpc_mcp_examples/src/ \
  --level=5

# Run all tests to ensure nothing broke
vendor/bin/phpunit --group jsonrpc_mcp_examples
```

### Common Issues to Fix

1. **Missing PHPDoc Comments**:

```php
// Bad
public function execute(ParameterBag $params): string {

// Good
/**
 * {@inheritdoc}
 */
public function execute(ParameterBag $params): string {
```

2. **Trailing Whitespace**:
   - Remove all trailing spaces at end of lines
   - Ensure single newline at end of each file

3. **Line Length**:
   - Keep lines under 80 characters where practical
   - Long attribute definitions can be split across lines

4. **Type Hints**:
   - Ensure all parameters have type hints
   - Ensure all return types are documented

5. **Imports**:
   - Remove unused use statements
   - Alphabetize use statements
   - Group by namespace (Drupal\Core, Drupal\jsonrpc, etc.)

### PHPStan Common Issues

1. **Property Type Declarations**:

```php
// Add promoted property types in constructor
public function __construct(
  array $configuration,
  string $plugin_id,
  $plugin_definition,
  protected EntityTypeManagerInterface $entityTypeManager,
) {
```

2. **Nullable Returns**:
   - Document when methods might return null
   - Use union types where appropriate (PHP 8.0+)

3. **Array Shapes**:
   - PHPStan may complain about array structures
   - Add `@return` annotations with array shapes if needed

### Documentation Standards

Each class should have:

- One-line summary
- Longer description if needed
- `@group` annotation for test classes

Each method should have:

- PHPDoc comment (or `{@inheritdoc}` for overrides)
- `@param` tags for complex parameters
- `@return` tag for non-obvious return types
- `@throws` tag if exceptions are thrown

### Checklist

Before marking this task complete:

- [ ] Run phpcs - zero violations
- [ ] Run phpcbf - apply auto-fixes
- [ ] Run phpstan - zero errors at level 5
- [ ] Run all tests - all passing
- [ ] Manual review: check all files have proper headers
- [ ] Manual review: verify no trailing whitespace
- [ ] Manual review: verify all files end with newline

### Iterative Fixing Process

1. Run phpcs, fix violations
2. Run phpcbf to auto-fix what's possible
3. Run phpcs again to verify
4. Run phpstan, fix errors
5. Run tests to ensure nothing broke
6. Repeat until clean

### Expected Output

All commands should complete with zero violations:

```bash
$ vendor/bin/phpcs --standard=Drupal modules/jsonrpc_mcp_examples/src/
$ # (no output = success)

$ vendor/bin/phpstan analyse modules/jsonrpc_mcp_examples/src/ --level=5
 [OK] No errors

$ vendor/bin/phpunit --group jsonrpc_mcp_examples
OK (10 tests, 25 assertions)
```

</details>
