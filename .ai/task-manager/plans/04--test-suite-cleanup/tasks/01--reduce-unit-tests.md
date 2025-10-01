---
id: 1
group: 'test-cleanup'
dependencies: []
status: 'pending'
created: '2025-10-01'
skills:
  - phpunit
  - drupal-backend
---

# Reduce Unit Tests to Meaningful Coverage

## Objective

Remove trivial and redundant unit tests from McpToolTest.php and McpToolNormalizerTest.php while preserving tests that validate actual module business logic and transformation behavior.

## Skills Required

- **phpunit**: Understanding PHPUnit testing patterns and test removal
- **drupal-backend**: Knowledge of Drupal module testing conventions

## Acceptance Criteria

- [ ] McpToolTest.php reduced from 15 to ~3 test methods
- [ ] McpToolNormalizerTest.php reduced from 25 to ~8 test methods
- [ ] Removed all tests validating PHP language features (array handling, string operations)
- [ ] Removed all tests validating type enforcement (already guaranteed by type hints)
- [ ] Removed all mock verification tests (tests that just verify mocks return mocked values)
- [ ] Removed all trivial validation tests (empty strings, special characters)
- [ ] Retained tests for associative vs indexed array validation (business rule)
- [ ] Retained tests for JSON-RPC to MCP schema transformation
- [ ] Retained tests for parameter mapping (required vs optional)
- [ ] Retained tests for TranslatableMarkup conversion
- [ ] All remaining tests pass: `vendor/bin/phpunit --group jsonrpc_mcp --testsuite=unit`

## Technical Requirements

**Files to modify:**

- `tests/src/Unit/Attribute/McpToolTest.php`
- `tests/src/Unit/Normalizer/McpToolNormalizerTest.php`

**Test removal categories (from plan):**

1. Type Enforcement Tests
2. Language Feature Tests
3. Mock Verification Tests
4. Trivial Validation Tests
5. Redundant Coverage

## Input Dependencies

None - this task can start immediately

## Output Artifacts

- Streamlined `McpToolTest.php` with ~3 meaningful test methods
- Streamlined `McpToolNormalizerTest.php` with ~8 meaningful test methods
- Test fixture files remain unchanged (may be used by remaining tests)

## Implementation Notes

<details>
<summary>Detailed implementation guidance</summary>

### McpToolTest.php Reduction Strategy

**KEEP these test methods (~3 total):**

1. Test for associative vs indexed array validation - this is a business rule enforced by the constructor
2. Basic attribute instantiation test with both parameters
3. Test reading attribute from a class (validates annotation system integration)

**REMOVE these test methods (~12 total):**

- `testDefaultConstruction()` - trivial
- `testWithTitle()` - trivial getter test
- `testWithAnnotations()` - trivial getter test
- `testWithEmptyTitle()` - tests that empty string works (language feature)
- `testRejectsEmptyArrayAnnotations()` - keep validation logic, but may consolidate
- `testRejectsListAnnotations()` - keep validation logic, but may consolidate
- `testAcceptsNonSequentialNumericKeys()` - language feature test
- `testAcceptsAssociativeAnnotations()` - language feature test
- `testAcceptsNestedAnnotations()` - language feature test
- `testRejectsNumericStringKeys()` - language feature test
- `testAcceptsSpecialCharactersInKeys()` - language feature test
- `testTitleWithSpecialCharacters()` - language feature test
- `testAttributeOnClassWithDefaults()` - redundant with basic instantiation
- `testWithLargeAnnotationsArray()` - completely meaningless (tests that arrays work)

### McpToolNormalizerTest.php Reduction Strategy

**KEEP these test methods (~8 total):**

1. `testNormalizeWithMinimalMethod()` - validates basic transformation
2. `testNormalizeWithTitle()` - validates title mapping from McpTool attribute
3. `testNormalizeWithAnnotations()` - validates annotations mapping
4. `testNormalizeWithOutputSchema()` - validates outputSchema inclusion
5. `testBuildInputSchemaWithRequiredParams()` - validates required parameter mapping
6. `testBuildInputSchemaWithOptionalParams()` - validates optional vs required distinction
7. `testTranslatableMarkupConversion()` - validates TranslatableMarkup to string conversion
8. `testNormalizeWithAllFeatures()` - comprehensive integration test

**REMOVE these test methods (~17 total):**

- `testBuildInputSchemaEmpty()` - redundant with minimal method test
- `testBuildInputSchemaWithDescriptions()` - covered by comprehensive test
- `testNestedParameterSchemas()` - language feature (nested arrays work)
- `testMethodWithoutMcpToolAttribute()` - trivial fallback behavior
- `testNoOutputSchemaWhenNotImplemented()` - testing absence is low value
- `testNoOutputSchemaWhenNull()` - testing absence is low value
- `testParameterWithoutDescription()` - trivial
- `testArrayParameterSchema()` - covered by comprehensive test
- `testMultipleParametersMixedRequirements()` - redundant with required/optional tests
- `testNullMethodClass()` - testing null handling is framework behavior
- All mock verification tests that just set up mocks and verify they return what you told them to return

### Execution Steps

1. Open `tests/src/Unit/Attribute/McpToolTest.php`
2. Remove test methods identified above (keep only ~3)
3. Run tests: `vendor/bin/phpunit tests/src/Unit/Attribute/McpToolTest.php`
4. Fix any issues
5. Open `tests/src/Unit/Normalizer/McpToolNormalizerTest.php`
6. Remove test methods identified above (keep only ~8)
7. Run tests: `vendor/bin/phpunit tests/src/Unit/Normalizer/McpToolNormalizerTest.php`
8. Fix any issues
9. Run full unit suite: `vendor/bin/phpunit --group jsonrpc_mcp --testsuite=unit`
10. Verify all tests pass

### Guiding Principle

For each test, ask: "If I remove this test, could a real bug in my module's logic go undetected?"

- If the answer is "No, because this tests PHP/Drupal framework behavior" → Remove it
- If the answer is "No, because type hints already enforce this" → Remove it
- If the answer is "Yes, this validates our transformation/discovery/endpoint logic" → Keep it

</details>
