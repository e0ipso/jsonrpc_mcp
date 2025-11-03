---
id: 2
group: "event-integration"
dependencies: [1]
status: "completed"
created: "2025-11-03"
skills:
  - drupal-backend
  - oauth2
---
# Implement Metadata Extraction and RFC 9728 Field Contribution

## Objective

Implement the core logic to extract MCP tool metadata and contribute RFC 9728-compliant fields to the OAuth 2.0 Protected Resource Metadata response.

## Skills Required

- **drupal-backend**: Drupal plugin discovery, attribute reading via reflection
- **oauth2**: RFC 9728 Protected Resource Metadata specification and field semantics

## Acceptance Criteria

- [ ] `onBuildResourceMetadata()` method fully implemented
- [ ] Extracts scopes from all discovered MCP tools
- [ ] Aggregates and deduplicates scopes into `scopes_supported` field
- [ ] Adds `bearer_methods_supported` field with value `["header"]`
- [ ] Adds `authorization_details_types_supported` with tool names
- [ ] Handles empty tool discovery gracefully (no errors, no fields added)
- [ ] Handles tools without auth annotations gracefully
- [ ] PHPStan level 5 passes with zero errors
- [ ] Drupal coding standards pass (phpcs)

Use your internal Todo tool to track these and keep on track.

## Technical Requirements

**RFC 9728 Field Specifications:**
- `scopes_supported`: JSON array of scope strings (e.g., `["mcp_tools", "content_management"]`)
- `bearer_methods_supported`: JSON array with single element `["header"]` (MCP spec requirement)
- `authorization_details_types_supported`: JSON array of tool IDs (e.g., `["cache.rebuild", "node.create"]`)

**Metadata Extraction Pattern:**
```php
// Discover all tools
$tools = $this->toolDiscovery->discoverTools();

// Extract scopes from #[McpTool] annotations
foreach ($tools as $tool_name => $method) {
  $mcp_data = $this->extractMcpToolData($method);
  $scopes = $mcp_data['annotations']['auth']['scopes'] ?? [];
  // Aggregate scopes...
}
```

## Input Dependencies

- Task 1: ResourceMetadataSubscriber class structure
- Existing `McpToolDiscoveryService::discoverTools()` method
- Existing `McpToolRoutes::extractMcpToolData()` reflection logic (reference for attribute reading)

## Output Artifacts

- Completed `onBuildResourceMetadata()` implementation in ResourceMetadataSubscriber
- Private helper methods for metadata extraction and aggregation

## Implementation Notes

<details>
<summary>Detailed Implementation Guide</summary>

### Implementation Logic

**Step 1: Discover Tools**
```php
public function onBuildResourceMetadata(ResourceMetadataEvent $event): void {
  $tools = $this->toolDiscovery->discoverTools();

  if (empty($tools)) {
    return; // No tools = no metadata to contribute
  }

  // Continue with metadata extraction...
}
```

**Step 2: Extract and Aggregate Scopes**
```php
$all_scopes = [];
$tool_names = [];

foreach ($tools as $tool_name => $method) {
  $mcp_data = $this->extractMcpToolData($method);

  // Collect scopes
  $scopes = $mcp_data['annotations']['auth']['scopes'] ?? [];
  if (is_array($scopes)) {
    $all_scopes = array_merge($all_scopes, $scopes);
  }

  // Collect tool names for authorization details
  $tool_names[] = $tool_name;
}

// Deduplicate and sort scopes
$unique_scopes = array_unique($all_scopes);
sort($unique_scopes);
```

**Step 3: Contribute RFC 9728 Fields**
```php
// Only add scopes_supported if we found scopes
if (!empty($unique_scopes)) {
  $event->addMetadataField('scopes_supported', array_values($unique_scopes));
}

// MCP spec requires header-based bearer tokens
$event->addMetadataField('bearer_methods_supported', ['header']);

// Add tool names as authorization details types
if (!empty($tool_names)) {
  sort($tool_names);
  $event->addMetadataField('authorization_details_types_supported', $tool_names);
}
```

**Helper Method: Extract McpTool Data**

Reuse the pattern from `McpToolRoutes::extractMcpToolData()`:

```php
/**
 * Extracts McpTool attribute data via reflection.
 *
 * @param \Drupal\jsonrpc\MethodInterface $method
 *   The JSON-RPC method.
 *
 * @return array
 *   Associative array with 'title' and 'annotations' keys.
 */
protected function extractMcpToolData($method): array {
  $class = $method->getClass();

  if (!$class) {
    return ['title' => NULL, 'annotations' => NULL];
  }

  $reflection = new \ReflectionClass($class);
  $attributes = $reflection->getAttributes(\Drupal\jsonrpc_mcp\Attribute\McpTool::class);

  if (empty($attributes)) {
    return ['title' => NULL, 'annotations' => NULL];
  }

  $mcp_tool = $attributes[0]->newInstance();

  return [
    'title' => $mcp_tool->title,
    'annotations' => $mcp_tool->annotations,
  ];
}
```

**Complete Implementation:**

```php
public function onBuildResourceMetadata(ResourceMetadataEvent $event): void {
  $tools = $this->toolDiscovery->discoverTools();

  if (empty($tools)) {
    // No tools discovered - nothing to contribute
    return;
  }

  $all_scopes = [];
  $tool_names = [];

  foreach ($tools as $tool_name => $method) {
    $mcp_data = $this->extractMcpToolData($method);

    // Extract scopes from auth annotations
    $scopes = $mcp_data['annotations']['auth']['scopes'] ?? [];
    if (is_array($scopes)) {
      $all_scopes = array_merge($all_scopes, $scopes);
    }

    // Collect tool names for authorization details
    $tool_names[] = $tool_name;
  }

  // Deduplicate and sort scopes
  $unique_scopes = array_unique($all_scopes);
  sort($unique_scopes);

  // Contribute RFC 9728 fields
  if (!empty($unique_scopes)) {
    $event->addMetadataField('scopes_supported', array_values($unique_scopes));
  }

  // MCP specification requires header-based Bearer token authentication
  $event->addMetadataField('bearer_methods_supported', ['header']);

  // Add tool names as authorization details types (RFC 9396)
  if (!empty($tool_names)) {
    sort($tool_names);
    $event->addMetadataField('authorization_details_types_supported', $tool_names);
  }
}
```

### Edge Cases and Error Handling

**Case 1: No Tools Discovered**
- Return early without adding any fields
- Prevents adding empty arrays to metadata

**Case 2: Tools Without Auth Annotations**
- `$scopes` will be empty array
- Still contribute `bearer_methods_supported` and `authorization_details_types_supported`
- This is correct: unauthenticated tools are still discoverable

**Case 3: Invalid Scope Data**
- Check `is_array($scopes)` before merging
- Prevents errors if annotation is malformed

**Case 4: simple_oauth_server_metadata Not Installed**
- Drupal's event system automatically handles this
- Event subscriber will never be called
- No code changes needed

### Validation Commands

```bash
# Code quality checks
vendor/bin/phpstan analyse src/EventSubscriber/
vendor/bin/phpcs --standard=Drupal,DrupalPractice src/EventSubscriber/

# Manual testing - requires simple_oauth_server_metadata to be installed
curl https://drupal-site/.well-known/oauth-protected-resource | jq

# Expected output structure:
# {
#   "resource": "https://drupal-site",
#   "authorization_servers": ["https://drupal-site"],
#   "bearer_methods_supported": ["header"],
#   "scopes_supported": ["mcp_tools", "cache_management", ...],
#   "authorization_details_types_supported": ["cache.rebuild", ...]
# }
```

### Performance Considerations

- `discoverTools()` is already cached by McpToolDiscoveryService
- Reflection is only performed once per tool during event dispatch
- Event subscriber runs during cacheable response generation
- No database queries or expensive operations
- Expected overhead: <5ms for typical tool counts (10-50 tools)

</details>
