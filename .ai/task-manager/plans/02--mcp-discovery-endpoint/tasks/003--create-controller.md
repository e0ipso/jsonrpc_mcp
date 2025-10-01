---
id: 3
group: 'mcp-discovery-endpoint'
dependencies: [1, 2]
status: 'pending'
created: '2025-10-01'
skills: ['drupal-controller', 'symfony-http']
---

# Create McpToolsController

## Objective

Implement the controller that handles the `/mcp/tools/list` HTTP endpoint, coordinating discovery service and normalizer to return MCP-compliant JSON responses.

## Skills Required

- Drupal controller architecture (ControllerBase, dependency injection via create())
- Symfony HttpFoundation (Request, JsonResponse)
- Pagination logic (cursor-based pagination implementation)

## Acceptance Criteria

- [ ] File `src/Controller/McpToolsController.php` exists with correct namespace
- [ ] Extends `ControllerBase`
- [ ] Implements static `create()` method for dependency injection
- [ ] Injects `McpToolDiscoveryService` and `McpToolNormalizer`
- [ ] Implements `list(Request $request): JsonResponse` method
- [ ] Reads optional `cursor` query parameter
- [ ] Implements pagination with page size of 50
- [ ] Returns JSON with `tools` array and `nextCursor`
- [ ] Uses base64-encoded offset as cursor format
- [ ] Returns MCP-compliant JSON response structure

## Technical Requirements

**File Location:** `src/Controller/McpToolsController.php`

**Class Structure:**

```php
namespace Drupal\jsonrpc_mcp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jsonrpc_mcp\Normalizer\McpToolNormalizer;
use Drupal\jsonrpc_mcp\Service\McpToolDiscoveryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class McpToolsController extends ControllerBase {

  public function __construct(
    protected McpToolDiscoveryService $toolDiscovery,
    protected McpToolNormalizer $normalizer,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jsonrpc_mcp.tool_discovery'),
      $container->get('jsonrpc_mcp.tool_normalizer'),
    );
  }

  /**
   * Returns MCP-compliant tool list.
   */
  public function list(Request $request): JsonResponse {
    $cursor = $request->query->get('cursor');
    $tools = $this->toolDiscovery->discoverTools();

    // Apply pagination (simple offset-based for now)
    $page_size = 50;
    $offset = $cursor ? (int) base64_decode($cursor) : 0;
    $page_tools = array_slice($tools, $offset, $page_size, true);

    // Normalize to MCP format
    $normalized_tools = [];
    foreach ($page_tools as $method) {
      $normalized_tools[] = $this->normalizer->normalize($method);
    }

    // Calculate next cursor
    $next_cursor = null;
    if (count($tools) > $offset + $page_size) {
      $next_cursor = base64_encode((string) ($offset + $page_size));
    }

    return new JsonResponse([
      'tools' => $normalized_tools,
      'nextCursor' => $next_cursor,
    ]);
  }
}
```

**Pagination Logic:**

- Default page size: 50 tools per page
- Cursor format: base64-encoded offset (e.g., "50", "100")
- nextCursor is null when no more pages
- Use array_slice() with preserve_keys=true

**Response Format:**

```json
{
  "tools": [...],
  "nextCursor": "base64-encoded-offset" | null
}
```

## Input Dependencies

- Task 001 completed (McpToolDiscoveryService exists)
- Task 002 completed (McpToolNormalizer exists)

## Output Artifacts

- `src/Controller/McpToolsController.php` - Working controller
- Route will be defined in task 005 (routing.yml)

## Implementation Notes

- Controller uses dependency injection via create() method
- Pagination is simple offset-based - stable cursors can be added later
- Empty result set returns empty array with null cursor
- All tools are discovered first, then paginated (no lazy loading yet)
- JsonResponse automatically sets Content-Type header
