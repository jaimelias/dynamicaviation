# AGENTS.md

## Description
`dynamicaviation` is a WordPress plugin for private charter flight operations and reservations.
It is part of the same ecosystem as `dynamicpackages`, `minimalizr`, and `dy-core`.
The project uses PHP 8.1+, modern JavaScript, WordPress APIs, and jQuery Slim-compatible frontend code.
Prefer code that is easy to understand at a glance. Favor the smallest correct implementation that satisfies the requested behavior.
Simplicity must never come at the cost of security, correctness, or backward compatibility.
---
## Ecosystem
`dynamicaviation` is part of a 4-component suite:
- `dynamicaviation`: the current WordPress plugin and primary working repository.
- `dynamicpackages`: a separate WordPress plugin for service reservations.
- `minimalizr`: the WordPress theme that supports the ecosystem.
- `dy-core`: the shared library consumed by all ecosystem applications.
In this project, `dy-core` is installed under:
`submodules/dy-core`
---
## Dependency Boundaries
There are no direct application-level dependencies between `dynamicaviation`, `dynamicpackages`, and `minimalizr`.
They must never call each other's functions, classes, or methods directly.
Shared behavior belongs in `dy-core`.
Do not create direct dependencies such as:
`dynamicaviation -> dynamicpackages`
`dynamicaviation -> minimalizr`
`dynamicpackages -> dynamicaviation`
`minimalizr -> dynamicaviation`
The intended architecture is:
`dynamicaviation -> dy-core`
`dynamicpackages -> dy-core`
`minimalizr -> dy-core`
If functionality is needed by more than one ecosystem application, the reusable behavior should live in `dy-core` rather than creating application-to-application dependencies.
---
## dy-core Ownership
The copy of `dy-core` inside this repository is installed under:
`submodules/dy-core`
This is a consumer copy of the shared library.
It is not the canonical editable source.
The canonical editable copy of `dy-core` lives in the `dynamicpackages` repository at:
`dynamicpackages/dy-core`
Do not modify `submodules/dy-core` as part of normal work in `dynamicaviation`.
If a requested change belongs in shared logic:
1. Identify that the appropriate implementation belongs in `dy-core`.
2. Do not implement a duplicate local version in `dynamicaviation`.
3. Do not edit the installed `submodules/dy-core` copy unless explicitly requested.
4. Mention that the canonical `dy-core` implementation must be changed from `dynamicpackages`.
5. Assume synchronization or publication of `dy-core` into this project is a separate manual release step.
Never create a local workaround merely to avoid changing shared behavior in `dy-core`.
---
## Repository Scope
By default, edits must remain inside the `dynamicaviation` repository.
Reading sibling ecosystem projects is allowed when necessary to understand integrations, compatibility, or existing behavior.
Do not modify sibling projects unless explicitly requested.
Never modify:
- `vendor/`
- `node_modules/`
- `submodules/dy-core/` unless explicitly requested
- WordPress core files
- third-party generated dependencies
Do not extensively scan unrelated directories when a targeted search can answer the question.
---
## Think Before Coding
Do not start by changing code when the requested behavior or existing implementation has not been understood.
Before implementing:
- Inspect the relevant existing code first.
- Identify existing helpers, abstractions, hooks, and conventions that already solve part of the problem.
- Check whether the required behavior already exists in `dy-core`.
- Do not assume undocumented behavior when it can be verified cheaply.
- Surface important assumptions.
- Surface meaningful tradeoffs when more than one valid implementation exists.
- Prefer the simpler implementation when it satisfies the same requirements.
- If an ambiguity materially affects correctness, identify it rather than silently choosing an interpretation.
- Do not invent requirements that were not requested.
When intended behavior can be reasonably inferred from the existing code and request, proceed with the smallest safe interpretation instead of blocking progress unnecessarily.
---
## Minimum Necessary Implementation
Implement the minimum code required to solve the requested problem correctly.
Do not add speculative functionality.
Do not add:
- features that were not requested
- abstractions for a single-use case without a concrete reuse need
- configuration options that were not requested
- unnecessary extensibility
- unnecessary wrappers
- defensive code for scenarios that cannot reasonably occur
- new dependencies without clear justification
Prefer a direct 50-line implementation over a generalized 200-line implementation when both satisfy the same requirements safely and maintainably.
Do not optimize for hypothetical future requirements.
---
## Surgical Changes
Touch only what is necessary for the requested change.
When editing existing code:
- Do not improve unrelated code.
- Do not reformat unrelated sections.
- Do not rewrite unrelated comments.
- Do not rename unrelated variables or methods.
- Do not refactor code merely because another style would be preferable.
- Match surrounding project conventions when they do not conflict with security or explicit project rules.
- If unrelated dead code is discovered, mention it rather than deleting it.
When the current change makes something unused, remove only imports, variables, methods, or other code made obsolete by the current change.
Do not remove pre-existing dead code unless explicitly requested.
Keep functional changes separate from unrelated cleanup.
---
## General Code Style
Write code that is easy to read and understand at a glance.
Prefer:
- explicit intent
- simple control flow
- meaningful names
- small focused methods
- early returns
- guard clauses
- existing project abstractions
- predictable behavior
Avoid unnecessary cleverness.
### Exception Handling
Minimize `try { } catch (...) { }` usage.
Do not wrap code in `try/catch` merely as defensive programming.
Prefer, when appropriate:
- input validation
- guard clauses
- explicit precondition checks
- safe defaults
- WordPress error APIs
- checking return values
Use exceptions when the called API genuinely communicates failures through exceptions or when exception semantics are appropriate for the domain.
Do not silently swallow exceptions.
If an exception is caught, there must be a concrete reason for handling it at that layer.
---
## PHP Requirements
All new or modified PHP code must be compatible with PHP 8.1 or newer.
Use modern PHP 8.1 syntax where appropriate.
Prefer:
- `[]` array syntax
- spread operators where they improve clarity
- parameter type declarations
- return type declarations
- property type declarations
- nullable and union types when semantically appropriate
- constructor property promotion when it improves the implementation
- strict comparisons when the expected type is known
- explicit visibility on methods and properties
### Typing
New PHP functions and methods should be typed.
New or modified method parameters should use appropriate type declarations when compatible with the surrounding API.
New or modified methods should declare return types when compatible with the surrounding API.
New properties should be typed when their type is known.
Do not remove useful type information.
Do not weaken a type merely to silence static analysis.
Do not introduce native type declarations that break:
- WordPress hooks
- WordPress callbacks
- inheritance contracts
- public APIs
- backward-compatible behavior
- integrations with aviation providers or other external services
When WordPress or an external API returns broader or dynamic values, model those values accurately instead of forcing incorrect narrow types.
### Visibility and Static Methods
Every class method must use explicit visibility:
- `public`
- `protected`
- `private`
Every class property must use explicit visibility.
Use `private` by default for implementation details that do not need subclass access.
Use `protected` only when subclass access is intentional.
Use `public` only for the intended external API.
Declare methods `static` only when their behavior does not depend on instance state and making them static is appropriate to the existing architecture.
Do not make methods static merely as a stylistic preference.
### Control Flow
Prefer early returns and guard clauses over deeply nested conditionals when they improve readability.
Avoid unnecessary type juggling.
Keep functions and methods focused.
Search for existing helpers before creating new ones.
Check `dy-core` before introducing shared helpers locally.
---
## JavaScript Requirements
Write modern ES6+ JavaScript.
Prefer:
- `const` by default
- `let` when reassignment is required
- arrow functions where appropriate
- template literals when they improve readability
- destructuring when it improves clarity
- array methods such as `map`, `filter`, `find`, and `some` when appropriate
Do not use `var`.
Prefer arrow functions over traditional function expressions unless JavaScript semantics require a dynamic `this`, `arguments`, constructor behavior, or another feature that arrow functions do not provide.
### jQuery Slim Compatibility
JavaScript must remain compatible with jQuery Slim when jQuery is used.
Do not assume APIs excluded from jQuery Slim are available.
Do not introduce dependencies on jQuery Ajax or jQuery effects APIs when the runtime only guarantees jQuery Slim.
Prefer native browser APIs where appropriate.
For HTTP requests, prefer existing project abstractions or `fetch()` when compatible with the existing implementation.
### Frontend Performance
Avoid render-blocking patterns.
Do not introduce unnecessary synchronous frontend work during initial page rendering.
Prefer:
- deferred execution when appropriate
- event-driven initialization
- loading code only where needed
- DOM queries scoped to the relevant component
- avoiding repeated DOM lookups inside loops
- avoiding unnecessary layout recalculations
- WordPress enqueue APIs for assets
Do not move code asynchronously when execution order is required for correctness.
---
## Aviation Domain Rules
`dynamicaviation` handles private charter flight functionality.
Changes involving aviation data should preserve existing domain contracts unless explicitly requested.
Be especially careful with:
- origin and destination data
- airport identifiers
- aircraft identifiers
- charter availability
- passenger counts
- pricing calculations
- flight duration
- schedule data
- quote data
- external aviation APIs
- booking or reservation state
Do not invent aviation business rules.
Do not silently reinterpret units, currencies, passenger capacity, airport identifiers, date/time values, or provider responses.
When modifying calculations or external aviation integrations, inspect the existing domain implementation before changing behavior.
Keep external provider-specific behavior isolated when an existing abstraction already exists.
Shared non-aviation-specific integrations and helpers may belong in `dy-core`.
---
## WordPress Development Rules
Use WordPress APIs instead of duplicating functionality already provided by WordPress.
Preserve existing:
- hooks
- filters
- public method signatures
- public function signatures
- option names
- metadata keys
- database schemas
- REST routes
- AJAX actions
- cron hooks
- shortcodes
- external contracts
unless the task explicitly requires changing them.
When handling external input, use appropriate validation and sanitization.
Escape output at the point of output using the appropriate WordPress escaping function.
For privileged operations, verify user capabilities and nonces where applicable.
Use `$wpdb->prepare()` or appropriate WordPress database APIs when SQL contains dynamic values.
Do not disable, bypass, or suppress security checks merely to make automated validation pass.
---
## WordPress Core Reference
Do not scan `wp-admin` or `wp-includes` extensively.
For questions about WordPress functions, classes, methods, hooks, filters, parameters, or return values, consult the official WordPress Developer Reference first:
`https://developer.wordpress.org/reference/`
Use local WordPress core only when necessary to verify implementation details or behavior specific to the installed version.
When inspecting local WordPress core:
- locate the relevant symbol first
- read only the relevant function, class, or nearby implementation
- avoid loading entire core files when a smaller section is sufficient
- avoid broad recursive scans of `wp-admin` or `wp-includes`
Do not load large sections of WordPress documentation or WordPress core into context when a targeted reference is sufficient.
---
## Polylang
Polylang is an optional dependency.
`dynamicaviation` must not assume Polylang is installed or active unless the relevant feature explicitly requires it.
Code integrating with Polylang must degrade safely when Polylang is unavailable.
Before using Polylang functions or APIs, verify availability where necessary.
For Polylang functions, consult:
`https://polylang.pro/documentation/support/developers/function-reference/`
For Polylang filters, consult:
`https://polylang.pro/documentation/support/developers/filter-reference/`
Prefer official Polylang developer documentation before inspecting plugin source code.
If local Polylang source inspection is necessary:
- locate the relevant symbol first
- inspect only the relevant implementation
- avoid scanning the entire plugin
- do not modify Polylang source
---
## Available CLI Tools
The project provides the following primary development tools:
- WP-CLI: `wp`
- Composer: `composer`
- PHPStan: `vendor/bin/phpstan`
- PHP_CodeSniffer: `vendor/bin/phpcs`
- PHP Code Beautifier and Fixer: `vendor/bin/phpcbf`
- PHP Parallel Lint: `vendor/bin/parallel-lint`
Prefer Composer scripts when an equivalent project command exists.
Prefer:
`composer phpstan`
over:
`vendor/bin/phpstan analyse`
Prefer:
`composer check`
when performing the complete standard PHP validation workflow.
---
## PHP Syntax Validation
After modifying PHP code, run:
`composer lint`
Syntax errors introduced by a change must always be fixed before the task is considered complete.
Syntax validation should normally be the first validation performed after PHP changes.
---
## PHPStan
PHPStan is the primary static analysis tool.
The project uses `szepeviktor/phpstan-wordpress` so PHPStan can understand WordPress-specific APIs and behavior.
The PHPStan configuration is stored in:
`phpstan.neon.dist`
Run:
`composer phpstan`
after modifying PHP code.
The installed `submodules/dy-core` directory may be available to PHPStan when required for symbol resolution, but it is not an editable source directory for normal `dynamicaviation` tasks.
New PHPStan errors introduced by a change must be fixed before the task is considered complete.
Do not:
- add broad `ignoreErrors` rules
- introduce unnecessary baselines
- suppress errors merely to make PHPStan pass
- weaken PHPStan configuration to hide a new problem
Prefer fixing the underlying type or control-flow issue.
---
## WordPress Coding Standards
PHP_CodeSniffer with WordPress Coding Standards is used for WordPress-specific code quality checks.
The PHPCS configuration is stored in:
`phpcs.xml.dist`
Run:
`composer phpcs`
after modifying PHP code.
The project uses:
- `WordPress-Core`
- `WordPress-Extra`
Do not mechanically suppress PHPCS rules merely to make validation pass.
### Automatic Fixes
PHP Code Beautifier and Fixer is available through:
`composer phpcbf`
Do not run project-wide automatic fixes without understanding the resulting scope.
Prefer targeted fixes to affected files.
Avoid unrelated formatting changes.
---
## Composer
Composer manages development tooling and PHP dependencies.
Do not manually modify files inside:
`vendor/`
When `composer.json` is modified, run:
`composer validate`
When dependencies are added, removed, or updated, also run:
`composer audit`
Commit `composer.lock` when dependency changes intentionally modify it.
Do not update unrelated dependencies as part of a focused task.
---
## Validation Workflow
For PHP changes, use the following validation order:
1. PHP syntax validation
2. PHPStan
3. WordPress Coding Standards
The standard commands are:
`composer lint`
`composer phpstan`
`composer phpcs`
For substantial PHP changes or before completing a task, prefer:
`composer check`
When iterating on a specific problem, the smallest relevant validation command may be executed first.
Before completing the task, all validation relevant to the change should pass.
---
## Validation Failures
Do not report a task as successfully completed when a relevant validation command is failing because of the current change.
If validation exposes an existing unrelated problem, distinguish clearly between:
- failures introduced by the current change
- failures that already existed
Do not fix unrelated existing failures unless they prevent completion of the requested task or the user explicitly asks for them to be fixed.
---
## Change Discipline
Keep changes scoped to the requested task.
Do not perform unrelated refactors while implementing a focused feature or bug fix.
Before introducing a new:
- class
- helper
- trait
- abstraction
- utility
- Composer dependency
search the existing codebase and `dy-core` for equivalent functionality.
Prefer extending existing architecture over building parallel implementations.
Preserve backward compatibility unless the requested change explicitly requires breaking it.
Do not modify `submodules/dy-core` automatically.
---
## Completion Criteria
For PHP work, a task is normally complete when:
- the requested behavior has been implemented
- only necessary files and code were changed
- existing public behavior has been preserved unless intentionally changed
- PHP syntax validation passes
- PHPStan passes for the affected code
- PHPCS passes or remaining findings are known pre-existing issues
- no new unnecessary abstractions or dependencies were introduced
- no unrelated cleanup was included
The standard final validation command is:
`composer check`
When reporting completion, state which validation commands were actually executed and their result.
If validation could not be executed, state that explicitly instead of implying that it passed.
If the requested change belongs in `dy-core`, explicitly state that the canonical implementation must be changed in `dynamicpackages/dy-core` and later published into this project's `submodules/dy-core`.
