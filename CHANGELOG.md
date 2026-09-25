# Changelog

All notable changes to `northbees/cap-api` will be documented in this file.

## Unreleased

- Initial release: SOAP 1.1 transport over Laravel's HTTP client, typed DTOs, and resources for the DVLA, Vehicles, NVD, UsedValues, UsedValuesLive, FutureValues and VRM services, plus signed image URLs.
- Per-instance credentials via `Cap::withCredentials()`.
- `Cap::fake()` and `CapResponse` testing helpers.
