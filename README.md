# JSON-RPC MCP Bridge

A Drupal module that exposes JSON-RPC method plugins as MCP ([Model Context Protocol](https://modelcontextprotocol.io/specification/2025-06-18/server/tools)) tools, enabling seamless integration between Drupal and MCP-compatible AI assistants like Claude Desktop.

## Overview

The [Model Context Protocol (MCP) specification (2025-06-18)](https://modelcontextprotocol.io/specification/2025-06-18/server/tools) is an open standard introduced by Anthropic that enables AI systems to discover and interact with external tools and data sources. This module bridges Drupal's JSON-RPC infrastructure with MCP, allowing Drupal sites to be discovered and used as MCP servers.

### Key Features

- 🔌 **Automatic Tool Discovery**: Expose existing JSON-RPC methods as MCP tools using a simple PHP attribute
- 📋 **MCP-Compliant Endpoints**: Provides `/mcp/tools/list` endpoint following MCP specification (2025-06-18)
- 🔍 **Auto-Discovery Support**: Optional `/.well-known/mcp.json` endpoint for automatic server discovery
- 🔐 **Security Built-in**: Inherits access control from JSON-RPC method permissions
- 📊 **JSON Schema Validation**: Automatic conversion of JSON-RPC schemas to MCP inputSchema/outputSchema

## Requirements

- Drupal 10.2+ or 11.x
- PHP 8.1+
- [JSON-RPC](https://www.drupal.org/project/jsonrpc) module (version 3.0.0-beta1 or higher)

## How It Works

### Architecture

```
Drupal JSON-RPC Method → #[McpTool] Attribute → MCP Tool Metadata → MCP Client (Claude, etc.)
```

The module uses PHP 8 attributes to mark JSON-RPC methods for MCP exposure. When an MCP client queries the discovery endpoint, the module:

1. Discovers all JSON-RPC methods marked with `#[McpTool]`
2. Converts JSON-RPC metadata to MCP tool schema format
3. Returns MCP-compliant tool definitions with proper JSON Schema

### Metadata Mapping

The module automatically maps JSON-RPC method metadata to MCP tool schema:

| JSON-RPC Field     | MCP Field      | Description                  |
| ------------------ | -------------- | ---------------------------- |
| `id`               | `name`         | Unique tool identifier       |
| `usage`            | `description`  | Human-readable description   |
| `params`           | `inputSchema`  | JSON Schema for parameters   |
| `output`           | `outputSchema` | JSON Schema for return value |
| (via `#[McpTool]`) | `title`        | Display name for the tool    |
| (via `#[McpTool]`) | `annotations`  | MCP-specific metadata        |

## Usage

### Marking Methods for MCP Exposure

Add the `#[McpTool]` attribute to any JSON-RPC method class:

```php
<?php

namespace Drupal\mymodule\Plugin\jsonrpc\Method;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonrpc\Attribute\JsonRpcMethod;
use Drupal\jsonrpc\Attribute\JsonRpcParameterDefinition;
use Drupal\jsonrpc_mcp\Attribute\McpTool;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\jsonrpc\JsonRpcObject\ParameterBag;

#[JsonRpcMethod(
  id: "cache.rebuild",
  usage: new TranslatableMarkup("Rebuilds the Drupal system cache."),
  access: ["administer site configuration"]
)]
#[McpTool(
  title: "Rebuild Drupal Cache",
  annotations: [
    'category' => 'system',
    'destructive' => false,
  ]
)]
class CacheRebuild extends JsonRpcMethodBase {

  public function execute(ParameterBag $params): bool {
    drupal_flush_all_caches();
    return true;
  }

  public static function outputSchema(): array {
    return ['type' => 'boolean'];
  }
}
```

### Example with Parameters

```php
#[JsonRpcMethod(
  id: "node.create",
  usage: new TranslatableMarkup("Creates a new content node."),
  access: ["create content"],
  params: [
    'title' => new JsonRpcParameterDefinition(
      'title',
      ["type" => "string"],
      null,
      new TranslatableMarkup("The node title"),
      true
    ),
    'type' => new JsonRpcParameterDefinition(
      'type',
      ["type" => "string"],
      null,
      new TranslatableMarkup("The content type machine name"),
      true
    ),
  ]
)]
#[McpTool(
  title: "Create Content Node"
)]
class NodeCreate extends JsonRpcMethodBase {
  // Implementation...
}
```

This automatically generates the MCP tool schema:

```json
{
  "name": "node.create",
  "title": "Create Content Node",
  "description": "Creates a new content node.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "title": {
        "type": "string",
        "description": "The node title"
      },
      "type": {
        "type": "string",
        "description": "The content type machine name"
      }
    },
    "required": ["title", "type"]
  }
}
```

## Discovery Endpoints

The module provides three complementary endpoints for MCP tool discovery and invocation following a standard workflow:

### Discovery Workflow

1. **List Tools** → Query `/mcp/tools/list` to discover all accessible tools
   - Supports pagination for sites with many tools
   - Returns tool names, descriptions, and input schemas

2. **Describe Tool (Optional)** → Query `/mcp/tools/describe?name=X` for detailed schema
   - Useful for dynamic form generation or validation
   - Returns complete input/output schemas and annotations

3. **Invoke Tool** → POST to `/mcp/tools/invoke` to execute the tool
   - Requires tool name and arguments
   - Returns results or error information

### When to Use Each Endpoint

**Use `/mcp/tools/list` when:**

- Discovering what tools are available
- Building a tool catalog
- The basic metadata (name, title, description) is sufficient

**Use `/mcp/tools/describe` when:**

- You need complete schema details
- Building dynamic forms or validation logic
- Generating client code or documentation

**Use `/mcp/tools/invoke` when:**

- Executing a tool with prepared arguments
- User/system is ready to perform the action

### Quick Examples

**List available tools:**

```bash
curl https://your-site.com/mcp/tools/list | jq
```

**Response:**

```json
{
  "tools": [
    {
      "name": "cache.rebuild",
      "title": "Rebuild Drupal Cache",
      "description": "Rebuilds the Drupal system cache.",
      "inputSchema": {
        "type": "object",
        "properties": {}
      }
    }
  ],
  "nextCursor": null
}
```

**Describe a specific tool:**

```bash
curl "https://your-site.com/mcp/tools/describe?name=cache.rebuild" | jq
```

**Invoke a tool:**

```bash
curl -X POST https://your-site.com/mcp/tools/invoke \
  -H "Content-Type: application/json" \
  -d '{"name": "cache.rebuild", "arguments": {}}'
```

**Response:**

```json
{
  "result": true
}
```

### Pagination

When dealing with many tools, use cursor-based pagination:

```bash
# First request
curl https://your-site.com/mcp/tools/list

# Response includes nextCursor
{
  "tools": [...],
  "nextCursor": "NTA="
}

# Follow nextCursor for more results
curl https://your-site.com/mcp/tools/list?cursor=NTA=

# Continue until nextCursor is null
{
  "tools": [...],
  "nextCursor": null
}
```

> 💡 **For complete API documentation** including all parameters, status codes, error formats, and security details, see the [API Reference](#api-reference) section below.

## API Reference

### Authentication

All MCP endpoints inherit access control from the underlying JSON-RPC methods:

- Authentication uses standard Drupal mechanisms (session cookies, OAuth, HTTP Basic)
- Permissions are inherited from the `access` parameter in `#[JsonRpcMethod]`
- All permissions in the `access` array must be satisfied (AND logic)
- Users must have appropriate permissions to discover or invoke tools

### GET /mcp/tools/list

Lists all available MCP tools with pagination support.

**Parameters:**

| Parameter | Type   | Required | Description                                             |
| --------- | ------ | -------- | ------------------------------------------------------- |
| `cursor`  | string | No       | Base64-encoded pagination cursor from previous response |

**Response (200 OK):**

```json
{
  "tools": [
    {
      "name": "string",
      "description": "string",
      "inputSchema": {},
      "title": "string (optional)",
      "outputSchema": {} | "optional)",
      "annotations": {}  | "(optional)"
    }
  ],
  "nextCursor": "string|null"
}
```

**Pagination:**

- Page size: 50 tools per request
- Cursor format: Base64-encoded integer offset
- `nextCursor` is `null` when no more results exist

**Status Codes:**

| Code | Description             |
| ---- | ----------------------- |
| 200  | Success                 |
| 400  | Invalid cursor format   |
| 401  | Authentication required |
| 403  | Access denied           |
| 500  | Server error            |

### GET /mcp/tools/describe

Returns detailed schema information for a specific tool.

**Parameters:**

| Parameter | Type   | Required | Description                       |
| --------- | ------ | -------- | --------------------------------- |
| `name`    | string | Yes      | Tool identifier (query parameter) |

**Response (200 OK):**

```json
{
  "tool": {
    "name": "string",
    "description": "string",
    "inputSchema": {},
    "outputSchema": {},
    "title": "string (optional)",
    "annotations": {} (optional)"
  }
}
```

**Error Response (400/404):**

```json
{
  "error": {
    "code": "missing_parameter|tool_not_found",
    "message": "Error description"
  }
}
```

**Status Codes:**

| Code | Description                         |
| ---- | ----------------------------------- |
| 200  | Success                             |
| 400  | Missing or invalid `name` parameter |
| 404  | Tool not found or access denied     |
| 500  | Server error                        |

### POST /mcp/tools/invoke

Invokes a tool with the provided arguments.

**Request Body:**

```json
{
  "name": "string",
  "arguments": {}
}
```

**Response (200 OK):**

```json
{
  "result": "any type matching tool's outputSchema"
}
```

**Error Response:**

```json
{
  "error": {
    "code": "invalid_json|missing_parameter|tool_not_found|execution_error",
    "message": "Error description"
  }
}
```

**Status Codes:**

| Code | Description                                        |
| ---- | -------------------------------------------------- |
| 200  | Success                                            |
| 400  | Invalid JSON, missing fields, or invalid arguments |
| 404  | Tool not found or access denied                    |
| 500  | Execution error                                    |

## Development

### Creating Custom MCP Tools

1. **Create a JSON-RPC Method Plugin**

   ```bash
   mkdir -p src/Plugin/jsonrpc/Method
   ```

2. **Add the Method Class**

   ```php
   namespace Drupal\mymodule\Plugin\jsonrpc\Method;

   use Drupal\jsonrpc\Attribute\JsonRpcMethod;
   use Drupal\jsonrpc_mcp\Attribute\McpTool;
   // ... implementation
   ```

3. **Clear Cache**

   ```bash
   drush cache:rebuild
   ```

4. **Verify Discovery**
   ```bash
   curl https://your-site.com/mcp/tools/list | jq '.tools[] | select(.name == "your.method")'
   ```

## References

- [Model Context Protocol Specification (2025-06-18)](https://modelcontextprotocol.io/specification/2025-06-18/server/tools) - Official MCP specification
- [MCP Server Tools](https://modelcontextprotocol.io/specification/2025-06-18/server/tools) - Server-side tool implementation guide
- [MCP Tool Discovery](https://modelcontextprotocol.io/specification/2025-06-18/server/tools) - Tool discovery protocol
- [JSON Schema Draft 7](https://json-schema.org/draft-07/schema) - Schema specification used by MCP
- [Drupal JSON-RPC Module](https://www.drupal.org/project/jsonrpc) - Base JSON-RPC infrastructure

## License

GPL-2.0-or-later

## Maintainers

- Your Name - [your-drupal-username](https://www.drupal.org/u/your-drupal-username)
