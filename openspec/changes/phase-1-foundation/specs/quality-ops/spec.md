## ADDED Requirements

### Requirement: Code Formatting Tooling Available
The project MUST include Laravel Pint for code formatting with a project `.pint.json` file. Formatting SHOULD run clean on dirty diffs.

#### Scenario: Pint present and configured
- GIVEN `composer.json` and the repository root
- WHEN inspecting dev dependencies and dotfiles
- THEN `laravel/pint` is required in `require-dev` and `.pint.json` exists.

