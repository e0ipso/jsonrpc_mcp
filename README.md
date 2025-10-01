# JSON-RPC MCP Bridge

A Drupal module that exposes JSON-RPC method plugins as MCP (Model Context Protocol) tools, enabling seamless integration between Drupal and MCP-compatible AI assistants like Claude Desktop.

## Overview

The Model Context Protocol (MCP) is an open standard introduced by Anthropic that enables AI systems to discover and interact with external tools and data sources. This module bridges Drupal's JSON-RPC infrastructure with MCP, allowing Drupal sites to be discovered and used as MCP servers.

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

| JSON-RPC Field | MCP Field | Description |
|----------------|-----------|-------------|
| `id` | `name` | Unique tool identifier |
| `usage` | `description` | Human-readable description |
| `params` | `inputSchema` | JSON Schema for parameters |
| `output` | `outputSchema` | JSON Schema for return value |
| (via `#[McpTool]`) | `title` | Display name for the tool |
| (via `#[McpTool]`) | `annotations` | MCP-specific metadata |

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

### Primary: `/mcp/tools/list`

MCP-compliant tool listing endpoint with pagination support:

**Request:**
```http
GET /mcp/tools/list
GET /mcp/tools/list?cursor=abc123
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
      },
      "outputSchema": {
        "type": "boolean"
      },
      "annotations": {
        "category": "system",
        "destructive": false
      }
    }
  ],
  "nextCursor": null
}
```

### Optional: `/.well-known/mcp.json`

Discovery endpoint for automatic MCP server registration:

**Request:**
```http
GET /.well-known/mcp.json
```

**Response:**
```json
{
  "name": "Drupal MCP Server",
  "version": "1.0.0",
  "capabilities": {
    "tools": true
  },
  "endpoints": {
    "tools": "/mcp/tools/list"
  }
}
```

## MCP Client Integration

### Claude Desktop

Add your Drupal site to Claude Desktop's MCP configuration:

**macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`

**Windows:** `%APPDATA%/Claude/claude_desktop_config.json`

```json
{
  "mcpServers": {
    "drupal": {
      "url": "https://your-drupal-site.com/mcp/tools/list",
      "headers": {
        "Authorization": "Bearer YOUR_API_TOKEN"
      }
    }
  }
}
```

### Testing with MCP Inspector

```bash
npx @modelcontextprotocol/inspector https://your-drupal-site.com/mcp/tools/list
```

## Security Considerations

### Access Control

MCP tool access is controlled by the JSON-RPC method's `access` parameter:

```php
#[JsonRpcMethod(
  id: "sensitive.operation",
  usage: new TranslatableMarkup("Performs sensitive operation"),
  access: ["administer site configuration", "access sensitive data"]
)]
```

Users must have **all** specified permissions to access the tool via MCP.

### Authentication

MCP clients must authenticate using standard Drupal authentication methods:

- **OAuth 2.0**: Recommended for production (requires contrib module)
- **Basic Auth**: For development/testing only
- **Session Cookies**: For same-domain requests

### Best Practices

1. **Principle of Least Privilege**: Only expose necessary methods via `#[McpTool]`
2. **Input Validation**: Always validate parameters in your method implementation
3. **Rate Limiting**: Consider rate limiting for MCP endpoints
4. **Audit Logging**: Log MCP tool invocations for security monitoring

## Standards Compliance

### MCP Specification

This module implements the Model Context Protocol specification version **2025-06-18**:

- ✅ `tools/list` endpoint with pagination
- ✅ JSON Schema for `inputSchema` and `outputSchema`
- ✅ Required fields: `name`, `description`, `inputSchema`
- ✅ Optional fields: `title`, `outputSchema`, `annotations`

### JSON Schema

All parameter and output schemas use JSON Schema Draft 7 format, ensuring compatibility with:

- MCP clients (Claude Desktop, etc.)
- OpenAPI/Swagger tools
- Schema validation libraries

### OAuth 2.0 Authorization

MCP clients can discover authorization endpoints via standard `.well-known` URIs:

- `/.well-known/oauth-authorization-server`
- `/.well-known/openid-configuration`

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

### Testing

```bash
# Run PHPUnit tests
vendor/bin/phpunit --group jsonrpc_mcp

# Test MCP endpoint
curl -X GET https://your-site.com/mcp/tools/list \
  -H "Accept: application/json"
```

## Roadmap

- [ ] Implement `#[McpTool]` PHP attribute
- [ ] Create MCP tool discovery service
- [ ] Build `/mcp/tools/list` controller
- [ ] Add MCP tool normalizer
- [ ] Implement `/.well-known/mcp.json` endpoint
- [ ] Add comprehensive test coverage
- [ ] Support for MCP resources (beyond tools)
- [ ] Support for MCP prompts
- [ ] WebSocket transport for real-time updates
- [ ] MCP Registry integration

## Contributing

This module follows Drupal coding standards and best practices:

```bash
# Check coding standards
vendor/bin/phpcs --standard=Drupal,DrupalPractice

# Fix coding standards
vendor/bin/phpcbf --standard=Drupal,DrupalPractice

# Static analysis
vendor/bin/phpstan analyze
```

## Resources

- [Model Context Protocol Specification](https://modelcontextprotocol.io/specification/2025-06-18)
- [MCP Documentation](https://docs.claude.com/en/docs/mcp)
- [JSON-RPC Module](https://www.drupal.org/project/jsonrpc)
- [MCP GitHub Repository](https://github.com/modelcontextprotocol)

## License

GPL-2.0-or-later

## Maintainers

- Your Name - [your-drupal-username](https://www.drupal.org/u/your-drupal-username)
