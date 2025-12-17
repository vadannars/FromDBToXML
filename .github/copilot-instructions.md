# Copilot Instructions for FromDBToXML

## Project Overview
This PHP application acts as a middleware between Libris and a library's Sierra API to fetch the status of books. It receives identifiers (ISBN, Libris ID, etc.) via URL parameters and returns an XML file with the status of found items.

## Architecture & Key Components
- **Entry Point:** `public/loanstatus.php` handles incoming HTTP requests.
- **Controllers:** `src/LoanStatusController.php` orchestrates request handling, validation, and response formatting.
- **API Client:** `src/SierraApiClient.php` manages communication with the Sierra API, using credentials and endpoints from configuration.
- **XML Generation:** `src/XmlGenerator.php` builds the XML response structure.
- **Configuration:**
  - `config/config.json` and environment variables (see `.env` or platform settings) control API keys, endpoints, and operational flags.
  - `src/Config.php` loads and provides access to configuration values.
- **Logging:** Uses Monolog (see `src/LoggerFactory.php`). Log destination and level are set via environment/config.
- **Testing:** PHPUnit tests are in `tests/`.

## Developer Workflows
- **Install dependencies:** `composer install`
- **Run locally:** `php -S 0.0.0.0:8080 -t public` (serves API at http://localhost:8080)
- **Run tests:** `./vendor/bin/phpunit`
- **Logs:** Default to file or `php://stderr` if not writable (see `LOG_DESTINATION` env var).

## Project-Specific Patterns & Conventions
- **Configuration precedence:** Environment variables override `config/config.json`.
- **API endpoints:** All Sierra API calls are abstracted via `SierraApiClientInterface`.
- **Error handling:** Errors are logged and returned as XML error responses.
- **Allowed origins:** CORS is enforced via the `ALLOWED_ORIGINS` env/config variable.
- **Field mapping:** Sierra API field tags (e.g., `tag:j`, `tag:i`, `marcTag:022`) are mapped in config and used in queries.

## Integration & External Dependencies
- **Sierra API:** Requires valid API credentials and endpoint URLs.
- **Monolog:** For logging (configured in `LoggerFactory.php`).
- **Guzzle:** For HTTP requests to external APIs.

## Example: Adding a New API Field
1. Update `config/config.json` and/or relevant env var.
2. Adjust `SierraApiClient.php` to fetch the new field.
3. Update `XmlGenerator.php` to include the field in the XML output.
4. Add/modify tests in `tests/`.

## References
- See `README.md` for setup, configuration, and environment variable details.
- See `phpunit.xml` for test configuration.
- See `phpcs.xml` for coding standards.

---

*Update this file if you introduce new major components, workflows, or conventions.*
