# Lexora Law — Premium Law Firm Website Template

**Version:** 1.2.0  
**Edition:** Static HTML  
**Language:** English  
**Stack:** HTML5 · CSS3 · Vanilla JavaScript (ES6+)

Lexora Law is a refined, responsive multi-page website template designed for law firms, attorneys, legal consultants, and independent legal practices. It delivers a confident editorial presence with practical frontend interactions and zero framework dependencies.

> All firm identities, attorney names, contact details, and legal content are fictional placeholders. Replace them before any public use.

---

## Features

- Editorial homepage with strong visual hierarchy
- Responsive navigation and mobile menu
- Six practice-area sections
- Attorney directory + individual profile pages
- Legal insights archive and five sample article pages
- Contact and consultation forms with client-side validation
- Demo search overlay over sample article data
- Light / dark theme with preference persistence
- FAQ accordion, article table of contents, reading progress bar, back-to-top
- Privacy, Terms, and Legal Disclaimer example pages
- Style guide page
- 404 page, sitemap.xml and robots.txt examples
- Keyboard focus states, skip link, reduced-motion support, semantic landmarks
- Clean, well-commented CSS with design tokens for easy theming

---

## Requirements

- A modern browser (Chrome, Firefox, Safari, Edge — current versions)
- Any static web host or local server

No package manager, build step, framework, database, or server-side runtime is required.

---

## Quick Start

1. Extract the ZIP archive.
2. Open the project folder in your code editor.
3. Start a local server from the project root:

   ```bash
   python -m http.server 8000
   ```

4. Visit `http://localhost:8000`.

> Prefer HTTP preview over opening files via `file://` for reliable relative paths and form behavior.

For WAMP/XAMPP users: copy the folder into the `www` or `htdocs` directory.

---

## Folder Structure

```
lexora-law/
├── index.html                 # Homepage
├── 404.html
├── assets/
│   ├── css/main.css           # Main stylesheet (organized + commented)
│   ├── js/main.js             # All interactions
│   └── icons/lexora-mark.svg
├── pages/                     # All inner pages
├── data/                      # Demo JSON (reference only)
├── documentation/             # Full documentation
├── marketplace/               # Listing & screenshot guidance
├── LICENSE.txt
├── README.md
├── CHANGELOG.md
├── robots.txt
└── sitemap.xml
```

---

## Customization Overview

| Task | Location |
|------|----------|
| Brand name & logo | Search/replace `Lexora` / `LEXORA` across HTML files; replace SVG mark |
| Colors | CSS custom properties in `assets/css/main.css` (`:root` and `[data-theme="dark"]`) |
| Practice areas | Homepage + `pages/practice-areas.html` |
| Attorneys | `pages/attorneys.html` + individual profile pages |
| Articles / Insights | `pages/insights.html` + article pages + search index in `main.js` |
| Forms | Connect to Formspree, Netlify Forms, or your backend (see docs) |
| Fonts | Currently Google Fonts — can be switched to self-hosted `@font-face` |

See **documentation/customization.md** for detailed steps and form integration examples.

---

## Forms

Forms validate in the browser only. They **do not** transmit or store data.

Before collecting real information:

1. Choose a form provider (Formspree, Netlify Forms, Basin, your own API, etc.).
2. Update the `action` attribute and remove the demo JavaScript handler if needed.
3. Add server-side validation, spam protection, and privacy disclosures.

Detailed integration examples are in `documentation/customization.md`.

---

## Documentation

| Document | Description |
|----------|-------------|
| [Installation](documentation/installation.md) | Local preview & upload steps |
| [Customization](documentation/customization.md) | Brand, colors, content, forms |
| [Deployment](documentation/deployment.md) | Hosting checklist |
| [Features](documentation/features.md) | Feature matrix & limitations |
| [Troubleshooting](documentation/troubleshooting.md) | Common issues |
| [Credits](documentation/credits.md) | Third-party assets |
| [QA Report](documentation/QA-REPORT.md) | Quality checks performed |
| [Support Policy](documentation/SUPPORT.md) | What is covered |

---

## Browser Support

Designed and tested for current versions of:

- Google Chrome
- Mozilla Firefox
- Apple Safari
- Microsoft Edge

Internet Explorer is not supported.

---

## License

See `LICENSE.txt` for the full commercial license terms (Regular and Extended).

This template is sold for creating end-client websites. Redistribution of the source as a template or theme is not permitted.

---

## Changelog

See `CHANGELOG.md` for version history.

---

## Support

Support is provided according to the policy in `documentation/SUPPORT.md` (typically 6 months of installation and defect support from the date of purchase).

For marketplace purchases, please use the official support channel of the platform.
