---
id: 2
group: 'cache-configuration'
dependencies: [1]
status: 'pending'
created: '2025-10-02'
skills:
  - drupal-backend
  - php
---

# Add cache metadata to discovery endpoint responses

## Objective

Attach appropriate cache tags, contexts, and max-age values to `CacheableJsonResponse` objects in the discovery endpoints (`list()` and `describe()`) using Drupal's `CacheableMetadata` API.

## Skills Required

- **drupal-backend**: Deep understanding of Drupal cache API (tags, contexts, max-age)
- **php**: Implementing cache metadata patterns

## Acceptance Criteria

- [ ] `list()` method attaches cache metadata with permanent max-age
- [ ] `describe()` method attaches cache metadata with permanent max-age
- [ ] Cache tags include `jsonrpc_mcp:discovery` and `user.permissions`
- [ ] `list()` includes cache contexts: `user`, `url.query_args:cursor`
- [ ] `describe()` includes cache contexts: `user`, `url.query_args:name`
- [ ] `invoke()` method has max-age of 0 (no caching)
- [ ] Error responses have appropriate cache metadata (max-age 0)

## Technical Requirements

**File to modify**: `src/Controller/McpToolsController.php`

**New imports needed**:

```php
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Cache;
```

**Cache strategy**:

- **Tags**: `['jsonrpc_mcp:discovery', 'user.permissions']`
- **Max-Age**: `Cache::PERMANENT` for discovery, `0` for invoke
- **Contexts**: `['user']` for all, plus query-specific contexts

## Input Dependencies

- Task 1: `McpToolsController` must return `CacheableJsonResponse` objects

## Output Artifacts

- Discovery endpoints with complete cache metadata
- Responses that are cacheable by Drupal's page cache system

## Implementation Notes

<details>
<summary>Detailed implementation steps</summary>

1. **Add imports** to `McpToolsController.php`:

   ```php
   use Drupal\Core\Cache\CacheableMetadata;
   use Drupal\Core\Cache\Cache;
   ```

2. **Update `list()` method** - Add before return statement:

   ```php
   $response = new CacheableJsonResponse([
     'tools' => $normalized_tools,
     'nextCursor' => $next_cursor,
   ]);

   $cache_metadata = new CacheableMetadata();
   $cache_metadata->setCacheMaxAge(Cache::PERMANENT);
   $cache_metadata->setCacheTags(['jsonrpc_mcp:discovery', 'user.permissions']);
   $cache_metadata->setCacheContexts(['user', 'url.query_args:cursor']);

   $response->addCacheableDependency($cache_metadata);

   return $response;
   ```

3. **Update `describe()` method** - Add before return statement:

   ```php
   $response = new CacheableJsonResponse([
     'tool' => $normalized_tool,
   ]);

   $cache_metadata = new CacheableMetadata();
   $cache_metadata->setCacheMaxAge(Cache::PERMANENT);
   $cache_metadata->setCacheTags(['jsonrpc_mcp:discovery', 'user.permissions']);
   $cache_metadata->setCacheContexts(['user', 'url.query_args:name']);

   $response->addCacheableDependency($cache_metadata);

   return $response;
   ```

4. **Update `invoke()` method** - Set no caching for action execution:

   ```php
   $response = new CacheableJsonResponse([
     'result' => $rpc_response->getResult(),
   ]);

   $cache_metadata = new CacheableMetadata();
   $cache_metadata->setCacheMaxAge(0); // Never cache invocations

   $response->addCacheableDependency($cache_metadata);

   return $response;
   ```

5. **Handle error responses** - Add cache metadata to error responses:

   ```php
   $response = new CacheableJsonResponse([
     'error' => [...],
   ], $status_code);

   $cache_metadata = new CacheableMetadata();
   $cache_metadata->setCacheMaxAge(0); // Don't cache errors

   $response->addCacheableDependency($cache_metadata);

   return $response;
   ```

**Cache tag explanation**:

- `jsonrpc_mcp:discovery`: Custom tag for module-level cache invalidation
- `user.permissions`: Automatically invalidated when permission system changes

**Cache context explanation**:

- `user`: Vary responses per user (different users see different tools based on permissions)
- `url.query_args:cursor`: Separate cache entries for different pagination cursors
- `url.query_args:name`: Separate cache entries for different tool names

**Important notes**:

- `Cache::PERMANENT` means cache until explicitly invalidated (not a time duration)
- Error responses should NOT be cached (max-age 0)
- The `invoke()` endpoint should never be cached (it executes actions)
</details>
