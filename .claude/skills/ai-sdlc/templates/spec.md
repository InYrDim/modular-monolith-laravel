# Spec: <feature name> (from intent.md <date>)

Status: draft | accepted. Approved by: <name>

## Requirements

1. <Testable requirement: "When X, the system Y.">

## Out of scope

- <Explicitly excluded>

## Design

- **Home:** host or `modules/<Name>`. Explain why (see `.ai/rules/modular-monolith.md`, "Adding a feature").
- **Data:** tables and columns, owning module, migrations (new only; committed migrations are immutable).
- **Cross-module:** contracts or events needed, and their consumer. Do not add a contract that has no consumer.
- **HTTP/UI:** routes (names), Form Requests, Inertia pages under `resources/js/pages/<area>/`.
- **Authorization:** policies or gates, and who can do what.

## Concerns (mandatory)

| Area                                   | Concern | Owner | Resolution |
| -------------------------------------- | ------- | ----- | ---------- |
| Security / Boundaries / Data / UX / Ops |         |       | open       |
