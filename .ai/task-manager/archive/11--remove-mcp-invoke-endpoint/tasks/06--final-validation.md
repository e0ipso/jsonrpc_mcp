---
id: 6
group: 'validation'
dependencies: [1, 2, 3, 4, 5]
status: 'completed'
created: '2025-10-28'
completed: '2025-10-28'
skills:
  - qa-testing
  - drupal-backend
---

# Final Validation and Quality Checks

## Objective

Run comprehensive validation checks to ensure all invoke endpoint code and references have been successfully removed, the module still functions correctly, and all quality gates pass. This is the final verification before considering the plan complete.

## Skills Required

- **qa-testing**: Run comprehensive test suites and validation checks
- **drupal-backend**: Understand Drupal module quality standards

## Acceptance Criteria

- [ ] No references to `/mcp/tools/invoke` found in src/ or tests/ directories
- [ ] PHPUnit test suite passes completely
- [ ] PHPStan static analysis passes at level 5
- [ ] PHPCS coding standards pass
- [ ] Documentation spell check passes
- [ ] Manual verification: list and describe endpoints work correctly
- [ ] Manual verification: invoke endpoint returns 404

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**Validation commands from plan (Success Criteria section)**:

1. Test suite: `vendor/bin/phpunit --group jsonrpc_mcp`
2. Static analysis: `vendor/bin/phpstan analyze`
3. Coding standards: `vendor/bin/phpcs --standard=Drupal,DrupalPractice src/ tests/`
4. Grep for references: `grep -r "tools/invoke" src/ tests/`
5. Spell check: `npm run cspell:check`

**Manual endpoint verification**:

- `/mcp/tools/list` returns 200/403
- `/mcp/tools/describe?name=examples.contentTypes.list` returns 200/403
- `/mcp/tools/invoke` returns 404

## Input Dependencies

All previous tasks (1-5) must complete successfully before running final validation.

## Output Artifacts

- Validation report confirming all checks pass
- Documentation of any warnings or minor issues (if any)
- Confirmation that plan success criteria are met

## Implementation Notes

<details>
<summary>Detailed implementation guidance</summary>

### Step 1: Search for remaining references

Search for any remaining references to the invoke endpoint:

```bash
# Search in source and test files
grep -rn "tools/invoke" src/ tests/ --include="*.php"

# Search in documentation (should find AGENTS.md and README.md updates)
grep -rn "tools/invoke" *.md --exclude-dir=.ai

# Search for the route name
grep -rn "tools_invoke" . --include="*.yml" --include="*.php"

# Search for the method name
grep -rn "public function invoke" src/
```

Expected results:

- No matches in src/ or tests/
- Only historical references in .ai/task-manager/archive/
- Documentation should show JSON-RPC endpoint instead

### Step 2: Run PHPUnit test suite

```bash
vendor/bin/phpunit --group jsonrpc_mcp
```

Expected result: All tests pass, no failures or errors.

**What to check**:

- Number of tests should be 4 fewer than before (invoke tests removed)
- List endpoint tests still pass
- Describe endpoint tests still pass
- Permission tests still pass

### Step 3: Run PHPStan static analysis

```bash
vendor/bin/phpstan analyze
```

Expected result: No errors at level 5.

**Common issues to watch for**:

- Unused imports in McpToolsController
- Undefined methods or classes
- Type inconsistencies

### Step 4: Run coding standards check

```bash
vendor/bin/phpcs --standard=Drupal,DrupalPractice src/ tests/
```

Expected result: No violations.

**Common issues to watch for**:

- Missing docblocks
- Incorrect indentation
- Line length violations in updated docstrings

### Step 5: Run spell check

```bash
npm run cspell:check
```

Expected result: No spelling errors.

**What to check**:

- New documentation content is spell-checked
- Technical terms like "jsonrpc" may need to be added to dictionary

### Step 6: Manual endpoint verification

Rebuild cache first:

```bash
vendor/bin/drush cache:rebuild
```

Test invoke endpoint returns 404:

```bash
curl -I https://drupal-contrib.ddev.site/mcp/tools/invoke
```

Expected: `404 Not Found`

Test list endpoint still works:

```bash
curl -I https://drupal-contrib.ddev.site/mcp/tools/list
```

Expected: `200 OK` or `403 Forbidden` (if auth required)

Test describe endpoint still works:

```bash
curl -I "https://drupal-contrib.ddev.site/mcp/tools/describe?name=examples.contentTypes.list"
```

Expected: `200 OK` or `403 Forbidden` (if auth required)

### Step 7: Verify success criteria from plan

Check each criterion from plan (lines 189-203):

**Primary Success Criteria**:

1. ✓ `/mcp/tools/invoke` returns 404
2. ✓ No references in src/ or tests/ (verified by grep)
3. ✓ `invoke()` method doesn't exist (verified by grep)
4. ✓ Tests pass (verified by phpunit)
5. ✓ Documentation updated (verified by reading README.md)

**Quality Assurance Metrics**:

1. ✓ PHPUnit passes
2. ✓ PHPStan passes
3. ✓ PHPCS passes
4. ✓ No invoke references
5. ✓ Spell check passes

### Step 8: Document validation results

Create a summary of validation results:

```markdown
## Validation Results

### Code Quality

- PHPUnit: ✓ PASS (X tests, 0 failures)
- PHPStan: ✓ PASS (0 errors)
- PHPCS: ✓ PASS (0 violations)

### Reference Cleanup

- grep src/: ✓ No references found
- grep tests/: ✓ No references found

### Endpoint Verification

- /mcp/tools/invoke: ✓ 404 (removed)
- /mcp/tools/list: ✓ 200/403 (working)
- /mcp/tools/describe: ✓ 200/403 (working)

### Documentation

- Spell check: ✓ PASS
- JSON-RPC examples: ✓ Present
- Architecture updated: ✓ Complete
```

### Step 9: Report completion

If all checks pass, confirm that the plan is complete and ready for archival.

If any checks fail, document the failures and determine if they are:

- Blockers requiring fixes before plan completion
- Minor issues that can be addressed in follow-up
- False positives that can be ignored

</details>
