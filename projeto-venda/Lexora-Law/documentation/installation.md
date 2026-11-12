# Installation

## Local Preview

1. Extract the ZIP archive completely.
2. Open the project root folder in your preferred code editor.
3. Start a simple static server:

   ```bash
   # Python 3
   python -m http.server 8000

   # or Node (if you have npx)
   npx serve .
   ```

4. Open `http://localhost:8000` in your browser.

> Always use an HTTP server. Opening `index.html` via `file://` can break relative paths and some browser features.

### WAMP / XAMPP / MAMP

Copy the entire extracted folder into the `www`, `htdocs`, or `Sites` directory and access it through `http://localhost/your-folder-name`.

## Production Upload

1. Replace all fictional content (see customization guide).
2. Connect forms to a real service.
3. Update meta tags, sitemap, and robots.txt with your domain.
4. Upload the complete folder structure preserving directories.
5. Configure the host to serve `index.html` as the default document and `404.html` for missing pages.
6. Enable HTTPS.
7. Test every page, the mobile menu, theme toggle, search, forms, and 404 behavior on the live domain.

No build step or package installation is required.
