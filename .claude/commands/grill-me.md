# Grill Me

Before we build anything, we need to be on the same wavelength.

## Your Job

Interview about this feature or task until we reach shared understanding.

Do **NOT** produce a PRD or code until the user explicitly says we are done (`done`, `that's enough`, `write the PRD`).

## Output: Always Write to a Doc (CRITICAL)

**Do not rely on chat as the source of truth.** Chat compacts; docs persist.

1. **Create or update** a grill doc at the start of the session:
   - Path: `docs/grills/grill-[short-topic].md` (kebab-case, e.g. `docs/grills/grill-articles-silent.md`)
2. **Every question and recommended answer** goes in that file — not only in the reply.
3. Structure each item with:
   - **Pertanyaan**
   - **Rekomendasi** (with brief reasoning)
   - **Keputusan user** (`[ ]` pending · `[x]` confirmed · `[~]` skipped)
   - **Catatan** (optional)
4. **Chat reply** stays short: status + link to the doc + what needs confirmation (1–3 bullets max).
5. After each user answer, **update the doc immediately** (check decisions, add notes).
6. When the session ends, add a **Log Keputusan** table and point to the next doc (`docs/prds/prd-[topic].md` via `/write-prd`).

If the user asks to **"drop all questions"** or **"langsung semua pertanyaan"**: put the full Q&A list in the grill doc in one pass; chat only links to the file.

## How To Do It

1. First, explore the codebase:
   - existing patterns relevant to this feature
   - current implementation of anything related
   - constraints from the stack or architecture
   - `AGENTS.md`, `CLAUDE.md`, and relevant `docs/`

2. Then interview:
   - **Default:** one **critical** question at a time — recommended answer first, wait for response, **update doc**, then next question.
   - **On user request:** all questions + recommendations in the grill doc at once; still no PRD/code until done.

3. **Question quality — ask only what matters:**
   - Skip questions already answered by repo, schema, prior grill/PRD, or project rules.
   - Record those in a **Keputusan implisit** section (table or bullets) instead of asking.
   - Do not ask obvious or low-stakes questions unless the user asks for full coverage.

4. Cover these areas **as needed** (not necessarily every area, not necessarily in this order):
   - **Scope**: what is in and out
   - **Users**: who uses this and what they need
   - **Data**: source, shape, lifecycle
   - **Behavior**: main, alternate, and failure paths
   - **Edge cases**: empty states, permissions, concurrency, stale/partial data
   - **Dependencies**: APIs, schemas, roles, integrations
   - **Definition of done**: human-checkable acceptance criteria
   - **Constraints**: security, performance, compatibility, deployment
   - **Non-goals**: what this work intentionally does not solve

## Rules

- **Doc is canonical** — if chat and doc disagree, the doc wins after user confirms.
- Always give your recommendation first, then ask if they agree (or record recommendation in doc when batching).
- Do not default to agreement — test the user's premise, name tradeoffs, say when you would not ship the proposed path.
- If user says **"skip"** or **"next"**, move on; note `[~]` skipped in the doc.
- If answer is vague, one concise follow-up; record clarification in **Catatan**.
- Keep going until user says **"done"**, **"that's enough"**, or **"write the PRD"**.
- Flag future risks in the doc:

  `⚠️ Potential issue: [what] → [why it matters] → [suggested fix]`

- Respect project-local rules in `AGENTS.md` and `.cursor/rules/` over generic advice.

## Related

| After grill | Rule / output |
|-------------|----------------|
| Requirements doc | `/write-prd` → `docs/prds/prd-[topic].md` |
| Backlog | `/prd-to-issues` |
| Implementation standards | `.cursor/rules/`, `CLAUDE.md` |
