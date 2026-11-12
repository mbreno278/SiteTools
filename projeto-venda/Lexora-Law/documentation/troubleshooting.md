# Troubleshooting

## Styles or scripts not loading

- Confirm you are viewing the site over `http://` or `https://`, not `file://`.
- Check the browser console for 404 errors on `assets/css/main.css` or `assets/js/main.js`.
- Verify the folder structure was preserved after upload.

## Theme does not persist

- Some browsers or privacy modes block `localStorage`. The theme still works for the current session.

## Mobile menu does not open

- Ensure `assets/js/main.js` is loaded.
- Check that the button has `data-menu-toggle` and the nav has `data-main-nav`.

## Forms do nothing after submit

- This is expected in the demo. The JavaScript prevents the default submission and only shows a success/error message.
- Connect a real form endpoint (see customization guide).

## Search returns no results

- The search only matches the five sample articles defined in `main.js`. Add new entries to the `articles` array when you create more content.

## Fonts look different offline

- Google Fonts require a network connection. For offline or restricted environments, self-host the font files (see customization guide).
