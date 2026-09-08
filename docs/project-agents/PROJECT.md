# PROJECT.md

## Project Role
`dynamicaviation` is a WordPress plugin for private charter flight operations and reservations.
The project uses PHP 8.1+, modern JavaScript, WordPress APIs, and jQuery Slim-compatible frontend code.
Prefer code that is easy to understand at a glance. Favor the smallest correct implementation that satisfies the requested behavior. Simplicity must never come at the cost of security, correctness, or backward compatibility.
## Ecosystem
`dynamicaviation` is part of a four-component suite:
- `dynamicaviation`: the current WordPress plugin and primary working repository.
- `dynamicpackages`: a separate WordPress plugin for service reservations.
- `minimalizr`: the WordPress theme that supports the ecosystem.
- `dy-core`: the shared library consumed by ecosystem applications.
In this project, `dy-core` is installed under:
`submodules/dy-core`
## Dependency Boundaries
There are no direct application-level dependencies between `dynamicaviation`, `dynamicpackages`, and `minimalizr`.
They must never call each other's functions, classes, or methods directly.
Shared behavior belongs in `dy-core`.
Do not create direct dependencies such as:
- `dynamicaviation -> dynamicpackages`
- `dynamicaviation -> minimalizr`
- `dynamicpackages -> dynamicaviation`
- `minimalizr -> dynamicaviation`
The intended architecture is:
- `dynamicaviation -> dy-core`
- `dynamicpackages -> dy-core`
- `minimalizr -> dy-core`
If functionality is needed by more than one ecosystem application, the reusable behavior should live in `dy-core` rather than creating application-to-application dependencies.
## dy-core Ownership
The copy of `dy-core` under `submodules/dy-core` is a consumer copy. It is not the canonical editable source.
The canonical editable copy lives at:
`dynamicpackages/dy-core`
Do not modify `submodules/dy-core` as part of normal work in `dynamicaviation`.
If a requested change belongs in shared logic:
1. Identify that the implementation belongs in `dy-core`.
2. Do not implement a duplicate local workaround in `dynamicaviation`.
3. Do not edit `submodules/dy-core` unless explicitly requested.
4. State that the canonical implementation must be changed from `dynamicpackages`.
5. Treat synchronization or publication into this repository as a separate manual release step.
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
## Aviation Domain Rules
`dynamicaviation` handles private charter flight functionality.
Preserve existing aviation-domain contracts unless explicitly requested otherwise.
Be especially careful with:
- origin and destination data
- airport identifiers
- aircraft identifiers
- charter availability
- passenger counts
- pricing calculations
- currencies and units
- flight duration
- schedule and date/time data
- quote data
- booking or reservation state
- external aviation APIs and provider responses
Do not invent aviation business rules.
Do not silently reinterpret units, currencies, passenger capacity, airport identifiers, date/time values, or provider responses.
When modifying calculations or external aviation integrations, inspect the existing domain implementation before changing behavior.
Keep provider-specific behavior isolated when an existing abstraction already exists.
Shared non-aviation-specific integrations and helpers may belong in `dy-core`.
## Static Analysis Scope
The installed `submodules/dy-core` directory may be available to PHPStan when required for symbol resolution, but it is not an editable source directory for normal `dynamicaviation` tasks.
## Completion Notes
If the requested change belongs in `dy-core`, explicitly state that the canonical implementation must be changed in `dynamicpackages/dy-core` and later published into this project's `submodules/dy-core`.
