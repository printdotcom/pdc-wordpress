# Testing Configuration

## API Client Configuration

The PDC Pod APIClient supports environment variable overrides for testing purposes. This allows you to test against mock APIs or different environments without modifying production code.

### Environment Variables

- `PDC_POD_API_BASE_URL`: Override the API base URL (e.g., `http://localhost:8001` for mock API)
- `PDC_POD_API_KEY`: Override the API key (e.g., `test_key_12345` for mock API)

### Usage Examples

#### Manual Testing with Mock API
```bash
# Start the mock API
bin/run-mock-api start

# Set environment variables and test manually
export PDC_POD_API_BASE_URL="http://localhost:8001"
export PDC_POD_API_KEY="test_key_12345"

# Your API calls will now use the mock API
```

#### E2E Testing
The `bin/test-e2e` script automatically detects if the mock API is running and configures these environment variables:

```bash
# Start mock API
bin/run-mock-api start

# Start WordPress
bin/run-wordpress 67 82

# Run e2e tests (automatically uses mock API if detected)
bin/test-e2e 67 82
```

#### Manual Environment Variable Setting
You can also manually set these for any testing scenario:

```bash
export PDC_POD_API_BASE_URL="https://api.stg.print.com"
export PDC_POD_API_KEY="your_staging_api_key"
```

## Mock API

The mock API provides realistic responses based on the actual Print.com API documentation:

- **URL**: `http://localhost:8001`
- **Test API Key**: `test_key_12345`
- **Admin Interface**: `http://localhost:8001/__admin`

See [`wiremock/README.md`](wiremock/README.md) for detailed mock API documentation.
