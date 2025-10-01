---
id: 9
group: 'mcp-discovery-endpoint'
dependencies: []
status: 'pending'
created: '2025-10-01'
'skills: ['drupal-plugins', 'jsonrpc-methods']
---

# Create Test Plugin in jsonrpc_mcp_test Module

## Objective

Create an example JSON-RPC method plugin in the test module that uses both `#[JsonRpcMethod]` and `#[McpTool]` attributes for use in kernel and functional tests.

## Skills Required

- Drupal plugin development (plugin class structure, namespacing)
- JSON-RPC method plugins (understanding JsonRpcMethodBase, parameter definitions)

## Acceptance Criteria

- [ ] Directory `tests/modules/jsonrpc_mcp_test/src/Plugin/jsonrpc/Method/` exists
- [ ] File `TestExampleMethod.php` exists in the plugin directory
- [ ] Class extends `JsonRpcMethodBase`
- [ ] Uses both `#[JsonRpcMethod]` and `#[McpTool]` attributes
- [ ] Defines at least one parameter with `JsonRpcParameterDefinition`
- [ ] Implements `execute()` method
- [ ] Implements static `outputSchema()` method
- [ ] Uses correct namespace: `Drupal\jsonrpc_mcp_test\Plugin\jsonrpc\Method`

## Technical Requirements

**File Location:** `tests/modules/jsonrpc_mcp_test/src/Plugin/jsonrpc/Method/TestExampleMethod.php`

**Class Structure:**

```php
<?php

namespace Drupal\jsonrpc_mcp_test\Plugin\jsonrpc\Method;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\Attribute\JsonRpcParameterDefinition;
use Drupal\jsonrpc_mcp\Attribute\McpTool;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;

#[JsonRpcMethod(
  id: "test.example",
  usage: new TranslatableMarkup("Test method for MCP discovery"),
  access: ["access content'],
  params: [
    'input' => new JsonRpcParameterDefinition(
      'input',
      ["type" => "string'],
      null,
      new TranslatableMarkup("Test input parameter"),
      true
    ),
  ]
)]
#[McpTool(
  title: "Test MCP Tool",
  annotations: ['category' => 'testing', 'test' => true]
)]
class TestExampleMethod extends JsonRpcMethodBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $params): array {
    return ['result' => $params->get('input')];
  }

  /**
   * {@inheritdoc}
   */
  public static function outputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'result' => ['type' => 'string'],
      ],
    ];
  }

}
```

**Plugin Details:**

- ID: `test.example`
- Permission: `access content` (widely available for testing)
- Parameter: `input` (required string)
- Output: Object with `result` property
- McpTool title: "Test MCP Tool"
- Annotations: category=testing, test=true

## Input Dependencies

- Plan 01 completed (McpTool attribute exists)
- Test module directory exists

## Output Artifacts

- `tests/modules/jsonrpc_mcp_test/src/Plugin/jsonrpc/Method/TestExampleMethod.php` - Test plugin
- Plugin discoverable by jsonrpc module after cache clear
- Used by kernel and functional tests

## Implementation Notes

- This plugin serves as a real example for testing
- Must follow JSON-RPC plugin conventions exactly
- Dual attributes demonstrate the pattern developers will use
- Simple implementation - just echoes input back
- Access permission should be permissive for testing
- Clear cache after creation: `vendor/bin/drush cache:rebuild`
