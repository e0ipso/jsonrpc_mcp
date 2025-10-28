---
id: 5
group: 'code-removal'
dependencies: [4]
status: 'pending'
created: '2025-10-28'
skills:
  - php
  - drupal-backend
---

# Update or Remove OAuth Middleware Path Check

## Objective

Determine the appropriate action for the OAuthScopeValidator middleware now that the `/mcp/tools/invoke` endpoint no longer exists. Either remove the path-specific check if the middleware should apply to all requests, or remove the entire middleware if it was specific to the invoke endpoint.

## Skills Required

- **php**: Edit PHP middleware classes
- **drupal-backend**: Understand Drupal middleware and service patterns

## Acceptance Criteria

- [ ] Middleware purpose determined (invoke-specific or general-use)
- [ ] Path check removed OR entire middleware removed (based on purpose)
- [ ] If middleware preserved: Service definition updated if needed
- [ ] If middleware removed: Service definition removed from jsonrpc_mcp.services.yml
- [ ] PHPStan passes: `vendor/bin/phpstan analyze`
- [ ] Test suite passes: `vendor/bin/phpunit --group jsonrpc_mcp`

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**File**: `src/Middleware/OAuthScopeValidator.php`

**Current path check** (line 41):

```php
if ($request->getPathInfo() !== '/mcp/tools/invoke') {
  return $this->httpKernel->handle($request, $type, $catch);
}
```

**Decision matrix**:

- **If middleware is invoke-specific**: Remove entire class and service definition
- **If middleware has broader purpose**: Remove path check, let it run on all requests

**Service definition location**: `jsonrpc_mcp.services.yml` (if middleware removal needed)

## Input Dependencies

- Task 4 (route removal) must complete first so we're not checking for a route that exists
- Understanding of the OAuth scope validation requirements for this module

## Output Artifacts

- Either:
  - Updated OAuthScopeValidator.php with path check removed, OR
  - Deleted OAuthScopeValidator.php and updated services.yml
- Passing tests confirming no regression

## Implementation Notes

<details>
<summary>Detailed implementation guidance</summary>

### Step 1: Analyze middleware purpose

Read the full middleware class:

```bash
cat src/Middleware/OAuthScopeValidator.php
```

Look for:

- Comments explaining the middleware's purpose
- What validation logic it performs
- Whether the validation makes sense for list/describe endpoints

Check if there are any issues or documentation referencing OAuth scope validation:

```bash
grep -r "OAuthScope" . --include="*.md" --include="*.php"
```

### Step 2: Determine action based on analysis

**Option A: Middleware is invoke-specific**

Indicators:

- Comments mention only tool invocation/execution
- Validation logic only makes sense for write operations
- No plan or documentation suggests OAuth scopes for discovery endpoints

Action: Remove the entire middleware class and service definition

**Option B: Middleware has broader purpose**

Indicators:

- Comments suggest general OAuth scope validation
- Could apply to discovery endpoints
- Plan or architecture documents mention OAuth for all MCP endpoints

Action: Remove only the path-specific check, let middleware run on all requests

### Step 3a: If removing entire middleware

1. Delete the middleware class file:

```bash
rm src/Middleware/OAuthScopeValidator.php
```

2. Read services configuration:

```bash
cat jsonrpc_mcp.services.yml
```

3. Remove the middleware service definition (look for service with tag: `http_middleware`)

4. Rebuild cache:

```bash
vendor/bin/drush cache:rebuild
```

### Step 3b: If removing path check only

Use Edit tool to modify the middleware:

Remove or comment out the path check (lines 40-43):

```php
// Check if this is an invoke request
if ($request->getPathInfo() !== '/mcp/tools/invoke') {
  return $this->httpKernel->handle($request, $type, $catch);
}
```

This allows the middleware to run on all requests.

Update any docblocks that mention the invoke endpoint specifically.

### Step 4: Run tests

Run the full test suite to ensure no regression:

```bash
vendor/bin/phpunit --group jsonrpc_mcp
```

Pay special attention to:

- List endpoint tests
- Describe endpoint tests
- Permission tests

### Step 5: Verify with static analysis

```bash
vendor/bin/phpstan analyze
```

Ensure no errors related to undefined classes or methods.

### Recommendation

Based on the plan context (line 246-257), the middleware appears to be invoke-specific. The safe default is **Option A: Remove the entire middleware**. However, if unsure, remove only the path check (Option B) and document for future cleanup.

</details>
