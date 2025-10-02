---
id: 1
group: 'response-migration'
dependencies: []
status: 'completed'
created: '2025-10-02'
skills:
  - drupal-backend
  - php
---

# Convert McpToolsController responses to CacheableJsonResponse

## Objective

Replace standard `JsonResponse` objects with `CacheableJsonResponse` in the `McpToolsController` class to enable cache metadata attachment while maintaining backward compatibility with existing API consumers.

## Skills Required

- **drupal-backend**: Understanding of Drupal's cache API and response objects
- **php**: Ability to refactor code and update class imports

## Acceptance Criteria

- [x] `McpToolsController` imports `CacheableJsonResponse` instead of `JsonResponse`
- [x] `list()` method returns `CacheableJsonResponse` instance
- [x] `describe()` method returns `CacheableJsonResponse` instance
- [x] `invoke()` method returns `CacheableJsonResponse` instance (for error responses)
- [x] JSON response structure remains unchanged (backward compatibility)
- [x] Method return type hints updated to `CacheableJsonResponse`

## Technical Requirements

**File to modify**: `src/Controller/McpToolsController.php`

**Import changes**:

- Add: `use Drupal\Core\Cache\CacheableJsonResponse;`
- Keep: `use Symfony\Component\HttpFoundation\JsonResponse;` (if needed for error responses in other parts)

**Return type updates**:

- `list(Request $request): CacheableJsonResponse`
- `describe(Request $request): CacheableJsonResponse`
- `invoke(Request $request): CacheableJsonResponse`

**Response object changes**:
Replace all instances of `new JsonResponse(...)` with `new CacheableJsonResponse(...)` including:

- Success responses
- Error responses (400, 404, 500 status codes)

## Input Dependencies

None - this is the foundational task that enables cache metadata attachment.

## Output Artifacts

- Modified `src/Controller/McpToolsController.php` with `CacheableJsonResponse` objects
- Controller methods ready for cache metadata attachment

## Implementation Notes

<details>
<summary>Detailed implementation steps</summary>

1. **Update imports** in `McpToolsController.php`:

   ```php
   use Drupal\Core\Cache\CacheableJsonResponse;
   ```

2. **Update method signatures**:

   ```php
   public function list(Request $request): CacheableJsonResponse {
   public function describe(Request $request): CacheableJsonResponse {
   public function invoke(Request $request): CacheableJsonResponse {
   ```

3. **Replace response objects** - Find and replace pattern:
   - Old: `new JsonResponse([...])`
   - New: `new CacheableJsonResponse([...])`

4. **Verify all response types** are updated:
   - Success responses with tool data
   - Error responses (missing_parameter, tool_not_found, execution_error, invalid_json)
   - Ensure HTTP status codes are preserved (200, 400, 404, 500)

5. **Test backward compatibility**:
   - Response structure should remain identical
   - `CacheableJsonResponse` extends `JsonResponse`, so API compatibility is maintained
   - No changes to JSON output format needed

**Important notes**:

- Do NOT add cache metadata in this task - that's handled in task 2
- Focus only on the response object type conversion
- Maintain all existing error handling logic
- Keep the same HTTP status codes for all responses
</details>
