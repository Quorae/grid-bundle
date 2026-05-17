# Deep Review — grid-bundle

**Date**: 2026-05-17
**Scope**: Entire quorae/grid-bundle (src/, templates/, assets/, tests/, config/)
**Files reviewed**: 90
**Mode**: read-only, 5-axis parallel specialists
**Profile**: stack-neutral fallback (no .claude/profile.yaml)

## Executive Summary
- Severity counts: Critical 0 / High 6 / Medium 9 / Low 8 / Info 6
- Top concerns:
  1. `flashTypeForException` re-threw `AccessDenied`/`UnknownAction` as unhandled 500s — **FIXED**
  2. Live mode `select` filter bound to wrong data-model path, making select filters non-functional — **FIXED**
  3. `BulkActionException` messages leaked internal role names and grid names — **FIXED**
- Recommended priority: Critical+High before merge; Medium next sprint; Low/Info backlog.

## Status: All actionable findings fixed

The following findings were identified and fixed in this review pass:

### Fixed (High)
| ID | Title | Fix |
|----|-------|-----|
| MERGED-01 | `flashTypeForException` re-throws on `AccessDenied`/`UnknownAction` | Added explicit match arms for all `BulkActionErrorKind` cases |
| BUGS-02 | Live mode `select` filter uses wrong `data-model` | Changed to `data-model="criteria[{{ filter.propertyName }}]"` |
| SEC-02 | `accessDenied` message leaks Symfony role name | Genericized to user-facing French message |
| SEC-05 | `unknownAction` message leaks grid name | Genericized to `'Action non reconnue.'` |
| RULES-01 | `AbstractBundle` bypassed by manual `getContainerExtension()` | Migrated to `loadExtension()`, deleted `QuoraeGridExtension` |
| READ-01 | Double docblock on `RenderGridHandler` | Merged into single docblock, made class `final` |

### Fixed (Medium)
| ID | Title | Fix |
|----|-------|-----|
| BUGS-04 | `montantFr` masks non-numeric strings as `—` | Added `is_numeric()` guard before cast |
| BUGS-03 | `colspan` on expand panel hardcodes formula | Replaced with `_colSpan` variable |
| BUGS-08 | `_isGrouped` strict null check misses empty strings | Changed to null-or-empty check |
| SEC-07 | No upper bound on `$q` search length | Capped at 255 chars via `mb_substr` |
| RULES-03 | `phpstan.dist.neon` excludes tests | Added `tests` to paths with targeted ignores |
| RULES-04 | `RenderGridHandler` non-`final` for test convenience | Made `final` |
| RULES-05 | Redundant `is defined` guards on readonly DTO | Removed from both templates |
| READ-08 | `resolveRuntimeDefinition` name too broad | Renamed to `resolveRuntimeChoices` |

### Fixed (Low)
| ID | Title | Fix |
|----|-------|-----|
| READ-05 | French word `souverain` in English docblock | Changed to `sovereign` |
| READ-07 | Typo `LivePropp` in template comment | Fixed to `LiveProp` |
| RULES-06 | `.gitignore` covers `.DS_Store` only at root | Changed to `**/.DS_Store` |
| RULES-07 | `Grid` component has no `mount()` method | Added typed `mount(GridView $view)` |
| BUGS-06 | `camelToSnake` unreachable null fallback | Removed dead `?? $value` |

### Not fixed (architectural / API-breaking — deferred)
| ID | Severity | Title | Reason |
|----|----------|-------|--------|
| ARCH-02 | High | `GridRegistry` mutable cache under shared runtimes | Benign under PHP-FPM; real risk only with Swoole/FrankenPHP |
| ARCH-03 | Medium | `extraContext` primitive obsession | API-breaking change |
| ARCH-04 | Medium | Template business logic in `_filter_bar.html.twig` | Complex template refactor |
| ARCH-05 | Medium | `GridDefinition.withFilters()` fragile wither | Needs PHP 8.4 `clone with` |
| ARCH-06 | Low | `GridDefinitionResolver` bypasses DI for stateless helpers | Low impact |
| ARCH-07 | Low | `FilterHydrator` stdClass fallback accepts any key | Design choice |
| SEC-04 | Medium | `column.template` no path validation | Developer-only input |
| READ-02 | Medium | Four filter-list iterations in `_filter_bar` | Complex template refactor |
| READ-03 | Medium | `resetSelectionOnNavigation` naming | LiveComponent hook constraint |
| READ-04 | Medium | Flash type magic literals | Low impact |
| BUGS-07 | Low | `defaultFor` exception swallowing in hydrator | Edge case, complex to separate |

## Per-axis summaries

### Architecture (8 findings: 0 Critical, 2 High, 3 Medium, 2 Low, 1 Info)
Well-structured bundle with clear SRP separation (Handler/Registry/Definition/Dto/Contract layers). Main concerns: `extraContext` untyped bag propagating as hidden contract, template-level business logic in filter bar, mutable cache in `GridRegistry` under shared runtimes.

### Readability (10 findings: 0 Critical, 1 High, 3 Medium, 4 Low, 2 Info)
Code is generally clean and well-named. Fixed double docblock, French word, typo, and overly broad method name. Remaining concerns are template-level (filter loop repetition, filterType extraction duplication).

### Bugs (10 findings: 0 Critical, 2 High, 3 Medium, 3 Low, 2 Info)
Fixed the two most impactful bugs: `flashTypeForException` re-throwing `AccessDenied` as 500, and select filter data-model binding in Live mode. Also fixed `montantFr` non-numeric handling, colspan calculation, grouping null check, and dead code.

### Security (10 findings: 0 Critical, 2 High, 3 Medium, 3 Low, 2 Info)
No critical vulnerabilities. Fixed information disclosure in exception messages (role names, grid names, action names). Added `$q` length cap. Remaining concerns: `extraContext` security depends on `APP_SECRET` HMAC, `expandedRowId` IDOR requires host controller authorization.

### Project Rules (9 findings: 0 Critical, 2 High, 3 Medium, 2 Low, 2 Info)
Fixed `AbstractBundle` contract violation, made `RenderGridHandler` final, added tests to PHPStan scope, removed redundant `is defined` guards, fixed `.gitignore`, added `mount()` to `Grid` component. No profile was available; review was conventions-only.

## Appendix A — File list
All files in `src/`, `templates/`, `assets/`, `tests/`, `config/`, plus `composer.json`, `phpstan.dist.neon`, `phpunit.dist.xml`, `.gitignore`.
