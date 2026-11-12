# Changelog

All notable changes to Lexora Law are documented in this file.

## 1.2.0 — 2026-11-12

### Fixed
- Search result links now correctly point to individual article pages instead of non-existent anchors on the insights page
- Homepage article card links now target the correct article HTML files
- Removed bundled photographic assets that lacked verifiable commercial redistribution rights (replaced with documented CSS placeholders consistent with the rest of the package)
- Search overlay focus management: restores focus to the trigger on close and prevents background scroll while open
- Version identifiers synchronized across README, LICENSE, package-info, CSS, JS, documentation, and marketplace files

### Improved
- Search result path resolution respects `data-base` for correct relative URLs from both homepage and inner pages
- Accessibility of search panel (aria-hidden, focus return, Escape handling)
- Consistency between homepage article cards and insights page placeholders

### Documentation
- Updated CHANGELOG, README version badge, and related docs to 1.2.0
- Clarified that no third-party photography is included

## 1.1.0 — 2026-11-07

### Improved
- Fully reformatted and section-commented CSS (`assets/css/main.css`) for easier customization
- Clean, documented JavaScript with clearer structure and comments
- Expanded and professionalized documentation suite
- Finalized commercial license (Regular + Extended)
- Added dedicated Support Policy
- Improved README with quick-start, folder structure, and customization table
- HTML files pretty-printed for maintainability
- Form integration guidance (Formspree, Netlify Forms, custom backends)
- Better marketplace listing draft and screenshot plan

### Added
- `documentation/SUPPORT.md`
- Clear design-token documentation in CSS

## 1.0.0 — 2026-11-06

- Initial English law firm template release
- Responsive homepage, firm, practice, attorney, insights, contact, consultation, FAQ, legal example, style guide, and 404 pages
- Theme toggle, mobile navigation, sample search, form validation feedback, FAQ accordion, article TOC, reading progress, and back-to-top
- Installation, customization, deployment, feature, troubleshooting, and credits documentation
