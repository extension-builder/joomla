# Joomla 6 compiler deprecations

Implementation branch: [deprecation/joomla6-native-compiler](https://github.com/extension-builder/joomla/tree/deprecation/joomla6-native-compiler).

This audit tracks confirmed deprecated APIs emitted by the Joomla 6 compiler. It supersedes the broader readiness audit for this implementation.

## Scope

- Database query creation and deprecated query limits.
- Configuration and session access.
- View data access through deprecated view proxies.
- Plugin construction and deprecated event registration.
- Installer loading and lifecycle contracts.

Changes belong in compiler source, with Joomla 6 implementations selected through dependency injection wherever shared behavior diverges. Joomla 3, 4 and 5 output must remain unchanged.

CSS and JavaScript loading are deferred. Dormant-code analysis, conditional-risk changes, generated application edits and general improvements are excluded. Existing compiler feature conditions remain authoritative.

## Status

Implementation is in progress. The final revision will record the changes, source locations, commits and validation results, including any deprecated behavior that could not safely be changed within this scope.
