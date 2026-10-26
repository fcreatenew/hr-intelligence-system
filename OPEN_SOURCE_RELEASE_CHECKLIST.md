# Open Source Release Readiness Checklist

Use this checklist before announcing the repository as a public open-source project.

## Repository visibility and presentation

- [ ] Change repository visibility to public when the owner is ready.
- [ ] Set the GitHub About description, website, and topics.
- [ ] Add a project logo/social preview image if available and owned by the project.
- [ ] Verify README links, badges, spelling, and rendered Markdown.
- [ ] Confirm the default branch and release/tag strategy.

## Legal and provenance

- [ ] Complete [LICENSE_MIGRATION_CHECKLIST.md](./LICENSE_MIGRATION_CHECKLIST.md) before adopting the proposed AGPL license.
- [ ] Confirm rights to all first-party code, docs, images, and assets.
- [ ] Audit dependency licenses and populate [THIRD_PARTY_NOTICES.md](./THIRD_PARTY_NOTICES.md).
- [ ] Verify model, weights, tokenizer, and dataset licenses independently.
- [ ] Remove secrets and sensitive data from current files and review Git history for accidentally committed secrets/data.
- [ ] Confirm no real employee or payroll records are included without a lawful basis and explicit permission.

## Engineering quality

- [ ] Ensure CI passes on the supported runtimes.
- [ ] Document tested versions and known limitations.
- [ ] Add setup, configuration, testing, and deployment instructions per component.
- [ ] Use synthetic/sample data in examples.
- [ ] Add vulnerability reporting and support guidance.
- [ ] Review GitHub issue forms and pull request template.
- [ ] Add release notes and version tags for published releases.

## Community

- [ ] Review [CONTRIBUTING.md](./CONTRIBUTING.md), [CODE_OF_CONDUCT.md](./CODE_OF_CONDUCT.md), and [SECURITY.md](./SECURITY.md).
- [ ] Add maintainers/contact details only where the owner consents to publishing them.
- [ ] Label issues by type, priority, component, and newcomer suitability.
- [ ] Explain how decisions, releases, and breaking changes are communicated.

## Suggested GitHub About metadata

**Description**
`Modular HRIS, people analytics, machine learning, AI assistant, and MLOps components for experimentation and responsible HR technology.`

**Website**
Use a maintained project documentation or demo URL only after verifying it is live and safe. Leave blank rather than adding a placeholder.

**Topics**
`human-resources`, `hris`, `people-analytics`, `machine-learning`, `artificial-intelligence`, `mlops`, `fastapi`, `laravel`, `react`, `python`

**Important:** GitHub About metadata and repository visibility are repository settings; they are not changed by editing README files. Confirm the repository is public before advertising it as publicly accessible open source.
