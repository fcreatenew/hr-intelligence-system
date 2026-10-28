# Component license and provenance matrix

This file is a tracking aid, not a legal opinion or a claim that every artifact is cleared for redistribution. Verify each row against the exact file, release, dependency lockfile, and upstream terms before packaging or publishing.

| Component/artifact | Repository location | Current tracking status | Required action before redistribution |
|---|---|---|---|
| First-party source code | Repository root and application modules | Root `LICENSE` contains GNU AGPL v3 text; historical component notices and contributor provenance still require review. | Complete `LICENSE_MIGRATION_CHECKLIST.md`; verify copyright ownership and compatibility for every affected file. |
| Documentation | `*.md`, `DOCUMENTATION_LICENSE.md` | Documentation license is described separately from source code. Confirm file scope and attribution. | Check attribution and whether third-party excerpts/assets have separate terms. |
| Python dependencies | `*/requirements*.txt` | Not individually cleared by this matrix. | Generate a versioned dependency inventory/SBOM and review licenses, notices, and transitive dependencies. |
| Dashboard dependencies | `hr_dashboard/package-lock.json` | Not individually cleared by this matrix. | Generate and review npm dependency inventory and license notices. |
| PHP dependencies | `hr_application/composer.lock` | Not individually cleared by this matrix. | Generate and review Composer dependency inventory and license notices. |
| Model weights and tokenizers | `models/`, external model hub | Do not assume root source-code license applies to weights or tokenizers. | Record artifact hash, upstream source, license, restrictions, training provenance, and redistribution permission per model version. |
| Datasets | `data/`, MLOps and external datasets | Synthetic/real status and permissions must be recorded per dataset; never publish real employee records. | Record provenance, terms, lawful permission, privacy review, and redistribution rights. |
| Generated reports and sample data | `models/*report*.json`, fixtures | Inspect for embedded sensitive data and identify generated-vs-third-party origin. | Verify no personal or confidential information is included and state generation method. |

## Release gate

- [ ] Copyright and contributor provenance reviewed for all first-party files.
- [ ] Historical license notices and prior release terms preserved and explained.
- [ ] Dependency inventories and required notices reviewed.
- [ ] Each published model/weight/tokenizer has its own license and provenance record.
- [ ] Each published dataset has a documented license/permission and privacy review.
- [ ] No secrets, real employee records, or confidential payroll information are included.
- [ ] Unresolved entries are explicitly withheld from redistribution.

Do not check these boxes merely because a license file exists. Where rights are uncertain, obtain appropriate legal review before relicensing or redistributing the affected component.
