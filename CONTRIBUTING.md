# Contributing to unraid-tailscale-utils

Thank you for your interest in contributing to **unraid-tailscale-utils**!

## How to Contribute

- **Bug Reports & Feature Requests:**  
  Please open an issue describing your bug or feature request with as much detail as possible.

- **Pull Requests:**  
  1. Fork the repository and create your branch from `main`.
  2. Make your changes, following the existing code style.
  3. Add or update tests as appropriate.
  4. Ensure your code passes all checks (see below).
  5. Submit a pull request with a clear description of your changes.

## Localization

New strings should be added to `src/usr/local/emhttp/plugins/tailscale/locales/en_US.json`.

Translations are managed via Crowdin (https://translate.edac.dev/)

## Code Quality & Checks

This repository uses automated code checks via GitHub Actions ([.github/workflows/lint.yml](.github/workflows/lint.yml)):

- **Static Analysis:**  
  Run `vendor/bin/phpstan` after running `./composer install` in the repository root.

- **Code Formatting:**  
  Run `vendor/bin/php-cs-fixer fix` to automatically apply formatting rules.

- **Commit Message Linting:**  
  All commits must follow the [Conventional Commits](https://www.conventionalcommits.org/) specification.  
  Example:  
  ```
  feat: add advanced log filtering
  fix: resolve colorization bug in syslog view
  ```

These checks are run automatically on every push and pull request. Please ensure your code passes locally before submitting.

## License

By contributing to this repository, you agree that your contributions will be licensed under the [GNU General Public License v3.0 or later](LICENSE).
