# PRD to Issues

Turn the destination document into an actionable backlog.

## Your Job

Read the PRD and break it into independently grabbable issues
using vertical slices. Each issue must be implementable by one
developer (or agent) without blocking on another — except where
an explicit dependency exists.

## Steps

1. Read the PRD from `docs/prd-[feature].md` or `issues/prd.md`
2. Scan the codebase to understand the current structure of
   modules listed in the PRD
3. Draft vertical slice issues (see rules below)
4. Ask me ONE question if the slicing is unclear before finalizing
5. Save each issue as `issues/issue-XX-[short-name].md`
6. Output a dependency summary showing the execution order

## Vertical Slice Rules

A vertical slice cuts through ALL layers of the stack for one
piece of user-visible functionality.

✅ Good vertical slice:
- Touches DB schema + service/logic + API/controller + UI
- Produces something visible or testable at the end
- Can be QA'd by a human in isolation

❌ Bad (horizontal) slice:
- "Do all DB migrations first"
- "Build all API endpoints, then build UI"
- A slice that produces nothing visible until phase 3

**Test:** At the end of this issue, can someone open a browser
(or call an endpoint) and verify something works end-to-end?
If no → it's too horizontal. Split differently.

## Issue File Structure

```markdown
# Issue [XX]: [Short Title]

## Goal
One sentence: what does this issue deliver?

## Type
AFK (agent can run autonomously) or
Human-in-the-loop (requires review/decision mid-task)

## Blocked By
- Issue XX (reason)
- Issue XX (reason)
Or: "Nothing — can start immediately"

## User Stories Covered
List the user stories from the PRD this issue addresses.

## Scope

### Files to Create
- `path/to/new-file.ext` — purpose

### Files to Modify
- `path/to/existing-file.ext` — what changes

### Explicitly Out of Scope
What this issue does NOT handle (even if related).

## Tasks
Ordered implementation steps:
1. Write failing test for [X]
2. Implement [X] to make test pass
3. Add [Y] to UI
4. Run feedback loops (tests, types, lint)

## Acceptance Criteria
Checkable conditions. A human must be able to verify each one
in under 5 minutes.
- [ ] criterion 1
- [ ] criterion 2

## Definition of Done
- [ ] All acceptance criteria pass
- [ ] Tests written and passing
- [ ] No debug code left
- [ ] Feedback loops (tests/types/lint) green
- [ ] Something visible/testable end-to-end
```

## Dependency & Execution Order

After creating all issue files, output this summary:

```
Phase 1 (no dependencies):
  Issue 01 — [name]

Phase 2 (after Phase 1):
  Issue 02 — [name]
  Issue 03 — [name]  ← can run in parallel with 02

Phase 3 (after Phase 2):
  Issue 04 — [name]
```

## Rules

- Minimum slice size: something a focused dev finishes in half a day
- Maximum slice size: something a focused dev finishes in 2 days
- If a slice is too big, split it — never merge to reduce issue count
- First issue must ALWAYS be a tracer bullet:
  thin slice through all layers, produces something visible
- Label each issue AFK or Human-in-the-loop honestly:
  - AFK = clear requirements, no design decisions mid-task
  - Human = ambiguous choices, UI aesthetics, external dependencies
- After saving files, do NOT start implementing —
  wait for the user's instruction
