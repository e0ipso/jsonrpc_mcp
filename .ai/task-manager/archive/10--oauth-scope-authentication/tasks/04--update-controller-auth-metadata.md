---
id: 4
group: "api-endpoints"
dependencies: [3]
status: "completed"
created: "2025-10-19"
skills:
  - php
  - drupal-backend
---
# Update McpToolsController to Include Auth Metadata

## Objective
Modify the `/mcp/tools/list` endpoint to include authentication metadata in tool annotations, enabling MCP clients to determine required OAuth scopes.

## Skills Required
- **php**: Editing existing controller methods
- **drupal-backend**: Understanding of Drupal controllers and MCP tool normalization

## Acceptance Criteria
- [ ] File `src/Controller/McpToolsController.php` is updated
- [ ] Method `list()` extracts auth metadata from tool instances
- [ ] Auth metadata is included in tool annotations when present
- [ ] Existing cache strategy is maintained (tags, contexts, max-age)
- [ ] Code follows Drupal coding standards
- [ ] No breaking changes to existing response structure

Use your internal Todo tool to track these and keep on track.

## Technical Requirements
- **File**: `src/Controller/McpToolsController.php`
- **Method**: `list(Request $request)`
- **Logic**: Call `$tool->getAuthMetadata()` and add to annotations if non-null
- **Cache**: Maintain existing PERMANENT cache with discovery tags

## Input Dependencies
- Task 3: Requires McpToolBase with getAuthMetadata() method implemented

## Output Artifacts
- Updated `src/Controller/McpToolsController.php` - Enhanced list endpoint with auth metadata

<details>
<summary>Implementation Notes</summary>

### Current Code Location
The controller already exists at `src/Controller/McpToolsController.php`. You need to modify the `list()` method around line 89-92 where annotations are built.

### Code Changes
Locate the annotation building section in the `list()` method and add auth metadata extraction:

```php
// Existing code around line 89-92:
$normalized_tools = [];
foreach ($page_tools as $method) {
  $normalized_tools[] = $this->normalizer->normalize($method);
}
```

However, the normalization happens in the normalizer. Check if auth metadata should be added there or in the controller. Based on the plan, the controller should expose it, so we need to examine the normalizer's output.

**Actually**, looking at the existing controller code more carefully, the normalization is delegated to `McpToolNormalizer`. We should modify the normalizer instead, not the controller directly.

**Wait**, re-reading the plan Component 3: "Modifies `McpToolsController::list()` to extract and include auth metadata from tool definitions in the response annotations."

Let me check if the normalizer or controller handles annotation building. Looking at typical MCP tool normalization, the annotations are likely built in the normalizer.

**Clarification needed**: The task should actually modify `src/Normalizer/McpToolNormalizer.php` to include auth metadata in the normalized output. However, the plan specifically mentions the controller.

Let me provide implementation for both approaches:

### Approach 1: Modify Normalizer (Recommended)
Edit `src/Normalizer/McpToolNormalizer.php` in the `normalize()` method to extract and include auth metadata:

```php
// In normalize() method, after building base tool definition:
$tool_data = [
  'name' => $method->id(),
  'description' => (string) $method->usage(),
  'inputSchema' => $this->buildInputSchema($method->params()),
];

// Add annotations
$annotations = [];

// ... existing annotation building logic ...

// Add auth metadata to annotations
if (method_exists($method, 'getAuthMetadata')) {
  $auth_metadata = $method->getAuthMetadata();
  if ($auth_metadata !== NULL) {
    $annotations['auth'] = $auth_metadata;
  }
}

if (!empty($annotations)) {
  $tool_data['annotations'] = $annotations;
}
```

### Approach 2: Verify Method Availability
Since the controller uses `$this->normalizer->normalize($method)`, check if the method passed is a `MethodInterface` or actual tool instance. If it's a MethodInterface, we need to access the underlying plugin.

**Best approach**: Modify the normalizer to check for and include auth metadata from the method object.

### Implementation Steps
1. Open `src/Normalizer/McpToolNormalizer.php`
2. Locate the `normalize()` method
3. Find where annotations are built
4. Add auth metadata extraction using reflection or method call
5. Test that auth metadata appears in `/mcp/tools/list` response

### Verification
After modification:
1. Run `vendor/bin/phpcs --standard=Drupal,DrupalPractice src/Normalizer/McpToolNormalizer.php`
2. Test endpoint: `curl http://site/mcp/tools/list | jq '.tools[0].annotations.auth'`
3. Verify cache headers remain unchanged
4. Check that tools without auth metadata don't have auth annotation
</details>
