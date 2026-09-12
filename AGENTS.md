# AGENTS.md

`dynamicaviation`: WordPress plugin for private charter flight operations and reservations.

- Before coding, read `../dynamicpackages/dy-core/docs/CODING.md`; its rules are mandatory project-wide. Resolve documentation links relative to their containing file; project paths start at this repository root.
- `submodules/dy-core/` is this project's shared library dependency. Never modify or delete its contents.
- Prefer targeted `rg` searches over recursive file reads

## Common Entry Points

- Main plugin bootstrap: `dynamicaviation.php`
- Admin screens: `admin/`
- Frontend booking flow: `public/`
- Shared helpers: `includes/`
- Canonical shared library: `submodules/dy-core/`
