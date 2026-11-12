# Customization Guide

## 1. Brand & Identity

Search and replace across all HTML files:

- `Lexora Law` → your firm name
- `LEXORA` → your short brand
- Attorney names, titles, and biographies
- Contact details (address, phone, email)
- Practice area descriptions
- Sample legal content on Privacy, Terms, and Disclaimer pages

Replace the SVG mark:

```
assets/icons/lexora-mark.svg
```

Update the favicon link in every page `<head>` if you change the file name.

## 2. Colors

All colors are controlled by CSS custom properties.

Open `assets/css/main.css` and edit the tokens under `:root` (light) and `[data-theme="dark"]` (dark):

```css
:root {
  --ink: #17232d;        /* main text */
  --muted: #66747e;      /* secondary text */
  --paper: #f7f7f4;      /* page background */
  --surface: #fff;       /* cards / panels */
  --line: #e3e6e3;       /* borders */
  --accent: #a77b4b;     /* primary accent (buttons, links) */
  --accent-dark: #805a32;
  --navy: #172b39;       /* hero & footer */
  --soft: #edf0ed;       /* subtle backgrounds */
  /* ... */
}
```

Change only the values — the rest of the stylesheet inherits them automatically.

## 3. Typography

Fonts currently load from Google Fonts:

- **DM Sans** — body / UI
- **Playfair Display** — headings

To self-host:

1. Download the font files.
2. Place them in `assets/fonts/`.
3. Replace the `@import` at the top of `main.css` with `@font-face` rules.
4. Update the `--serif` and `--sans` variables if needed.

## 4. Content Pages

| Content | Files to edit |
|---------|---------------|
| Homepage sections | `index.html` |
| About the firm | `pages/about.html` |
| Practice areas | `pages/practice-areas.html` + homepage cards |
| Attorney directory | `pages/attorneys.html` |
| Individual profiles | `pages/attorney-*.html` |
| Insights listing | `pages/insights.html` |
| Articles | `pages/article-*.html` |
| Contact / Consultation | `pages/contact.html`, `pages/consultation.html` |
| FAQ | `pages/faq.html` |

Header and footer markup is duplicated in every page (intentional for zero-build static delivery). After changing navigation links, update them in all pages or use a simple search-and-replace.

## 5. Search Index

The demo search is a fixed list inside `assets/js/main.js`:

```js
const articles = [
  ['Title', 'Category', 'slug.html'],
  // ...
];
```

Update this array when you add or rename articles. For a real CMS you will replace the entire search feature.

## 6. Forms — Connecting a Real Backend

The forms use `data-demo-form` and are handled only in the browser. Below are common integration patterns.

### Option A — Formspree

1. Create a form at [formspree.io](https://formspree.io).
2. Replace the form opening tag:

```html
<form action="https://formspree.io/f/your-form-id" method="POST">
```

3. Remove the `data-demo-form` attribute (or keep the JS if you still want client-side validation feedback before submission).
4. Add a honeypot field for spam protection if desired.

### Option B — Netlify Forms

1. Deploy to Netlify.
2. Add `netlify` (or `data-netlify="true"`) to the form tag:

```html
<form name="consultation" method="POST" data-netlify="true" netlify-honeypot="bot-field">
  <input type="hidden" name="form-name" value="consultation">
  <!-- fields -->
</form>
```

3. Remove or adapt the demo JavaScript submit handler.

### Option C — Custom endpoint

Point `action` to your API, keep client-side validation, and handle the response in JavaScript or let the browser navigate to a thank-you page.

**Always** add:

- Server-side validation
- Spam protection (honeypot, reCAPTCHA, Turnstile, etc.)
- Privacy policy disclosure about data handling
- HTTPS

## 7. Images

The template ships with CSS-generated monograms and gradient placeholders only.

Recommended approach:

1. Create `assets/images/` folder.
2. Add optimized WebP/JPEG photos of attorneys, office, and hero imagery.
3. Replace the `.portrait`, `.profile-art`, `.hero-monogram`, and `.article-art` placeholders with `<img>` tags that have meaningful `alt` text.
4. Ensure you hold commercial licenses for every photograph.

## 8. SEO & Meta

On every page update:

- `<title>`
- `<meta name="description">`
- Open Graph tags (`og:title`, `og:description`, `og:image`, `og:url`)
- Canonical URL if desired

Update `sitemap.xml` with your real domain and only the public URLs you want indexed. Adjust `robots.txt` accordingly.

## 9. Header / Footer Maintenance

Because pages are fully static, header and footer code is repeated. When you change navigation structure:

- Use your editor’s multi-file search & replace carefully, or
- Keep a single “master” copy of the header/footer and paste it when needed.

A future build step (Eleventy, 11ty, simple Node script, etc.) can be added by the buyer if preferred.
