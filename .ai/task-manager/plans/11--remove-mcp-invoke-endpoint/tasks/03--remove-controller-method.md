---
id: 3
group: 'code-removal'
dependencies: [2]
status: 'pending'
created: '2025-10-28'
skills:
  - php
  - drupal-backend
---

# Remove Controller Invoke Method and Cleanup Imports

## Objective

Remove the `invoke()` method from McpToolsController.php and clean up unused imports that were only needed for the invoke functionality. Preserve the list() and describe() methods and overall class structure.

## Skills Required

- **php**: Edit PHP class files and manage imports
- **drupal-backend**: Understand Drupal controller patterns

## Acceptance Criteria

- [ ] `invoke()` method removed (lines 166-287)
- [ ] Unused imports removed: Json, JsonRpcException, ParameterBag, RpcRequest
- [ ] Class docblock updated to remove `/mcp/tools/invoke` reference
- [ ] Constructor preserved (HandlerInterface injection stays for now)
- [ ] list() and describe() methods remain intact
- [ ] PHPStan passes: `vendor/bin/phpstan analyze`
- [ ] Coding standards pass: `vendor/bin/phpcs --standard=Drupal,DrupalPractice src/`

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**File**: `src/Controller/McpToolsController.php`

**Method to remove**:

- `invoke()` method (lines 166-287, approximately 122 lines)

**Imports to remove** (if not used by list/describe methods):

- Line 7: `use Drupal\Component\Serialization\Json;`
- Line 12: `use Drupal\jsonrpc\Exception\JsonRpcException;`
- Line 14: `use Drupal\jsonrpc\JsonRpcObject\ParameterBag;`
- Line 15: `use Drupal\jsonrpc\JsonRpcObject\Request as RpcRequest;`

**Docblock to update**:

- Line 24: Class docblock mentions `/mcp/tools/invoke` endpoint

**Constructor note**:

- Keep HandlerInterface injection even though it's now unused (lines 43-44, 49, 62)
- Document as technical debt for future cleanup

## Input Dependencies

- Task 2 (test removal) should complete first to avoid test failures during development

## Output Artifacts

- Updated McpToolsController.php with invoke method removed
- Clean import statements (no unused imports)
- Updated class documentation
- Passing PHPStan and PHPCS checks

## Implementation Notes

<details>
<summary>Detailed implementation guidance</summary>

### Step 1: Read the controller file

Read the full controller to understand the structure:

```bash
cat src/Controller/McpToolsController.php
```

Identify:

- The exact bounds of the invoke() method
- Which imports are shared with list/describe methods
- Class docblock content

### Step 2: Remove invoke() method

The invoke() method starts around line 166 with docblock and extends to line 287 (closing brace). Remove the entire method including:

- Method docblock (lines 166-178)
- Method signature (line 179)
- Method body (lines 180-287)

### Step 3: Check import usage

**Verify each import is unused before removal:**

Check if `Json` is used by list/describe:

```bash
grep -n "Json::" src/Controller/McpToolsController.php
```

Check if `JsonRpcException` is used:

```bash
grep -n "JsonRpcException" src/Controller/McpToolsController.php
```

Check if `ParameterBag` is used:

```bash
grep -n "ParameterBag" src/Controller/McpToolsController.php
```

Check if `RpcRequest` is used:

```bash
grep -n "RpcRequest\|Request::" src/Controller/McpToolsController.php | grep -v "Symfony"
```

Remove only the imports that appear exclusively in the invoke() method.

### Step 4: Update class docblock

Current docblock (lines 21-33):

```php
/**
 * Controller for MCP tools discovery endpoint.
 *
 * This controller handles the /mcp/tools/list HTTP endpoint, providing
 * MCP-compliant tool discovery with cursor-based pagination. It coordinates
 * the McpToolDiscoveryService and McpToolNormalizer to return JSON-RPC
 * methods marked with the #[McpTool] attribute in MCP tool schema format.
 *
 * Cache tags used:
 * - jsonrpc_mcp:discovery: Invalidated when modules install/uninstall or
 *   when plugin definitions change.
 * - user.permissions: Automatically invalidated when permission system changes.
 */
```

Update to mention both list and describe endpoints:

```php
/**
 * Controller for MCP tools discovery endpoints.
 *
 * This controller handles the /mcp/tools/list and /mcp/tools/describe HTTP
 * endpoints, providing MCP-compliant tool discovery and detailed tool schemas.
 * It coordinates the McpToolDiscoveryService and McpToolNormalizer to return
 * JSON-RPC methods marked with the #[McpTool] attribute in MCP tool format.
 *
 * Cache tags used:
 * - jsonrpc_mcp:discovery: Invalidated when modules install/uninstall or
 *   when plugin definitions change.
 * - user.permissions: Automatically invalidated when permission system changes.
 */
```

### Step 5: Add constructor comment about HandlerInterface

Add a TODO comment in the constructor explaining that HandlerInterface is currently unused but preserved for potential future use:

```php
public function __construct(
  protected McpToolDiscoveryService $toolDiscovery,
  protected McpToolNormalizer $normalizer,
  // @todo Remove HandlerInterface if no longer needed after invoke() removal.
  protected HandlerInterface $handler,
) {}
```

### Step 6: Validate with tools

Run coding standards check:

```bash
vendor/bin/phpcs --standard=Drupal,DrupalPractice src/Controller/McpToolsController.php
```

Run static analysis:

```bash
vendor/bin/phpstan analyze src/Controller/McpToolsController.php
```

Fix any issues reported.

</details>
