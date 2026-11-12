/**
 * Lexora Law — Main JavaScript
 * Version: 1.2.0
 *
 * Vanilla ES6+ interactions for theme, navigation, search, forms, FAQ,
 * reading progress, back-to-top, and article table of contents.
 * No external dependencies.
 */
(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

  /* --------------------------------------------------------------------------
     Theme (light / dark) with localStorage preference
     -------------------------------------------------------------------------- */
  const storedTheme = (() => {
    try {
      return localStorage.getItem('lexora-theme');
    } catch {
      return null;
    }
  })();

  const preferred =
    window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
      ? 'dark'
      : 'light';

  document.documentElement.dataset.theme = storedTheme || preferred;

  const themeButton = $('[data-theme-toggle]');
  if (themeButton) {
    themeButton.addEventListener('click', () => {
      const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      document.documentElement.dataset.theme = next;
      try {
        localStorage.setItem('lexora-theme', next);
      } catch {
        /* storage may be blocked */
      }
      themeButton.setAttribute(
        'aria-label',
        `Switch to ${next === 'dark' ? 'light' : 'dark'} theme`
      );
    });
  }

  /* --------------------------------------------------------------------------
     Mobile navigation
     -------------------------------------------------------------------------- */
  const menuButton = $('[data-menu-toggle]');
  const nav = $('[data-main-nav]');
  if (menuButton && nav) {
    menuButton.addEventListener('click', () => {
      const expanded = menuButton.getAttribute('aria-expanded') === 'true';
      menuButton.setAttribute('aria-expanded', String(!expanded));
      nav.classList.toggle('open', !expanded);
    });
  }

  /* --------------------------------------------------------------------------
     Search overlay (demo index of sample articles)
     -------------------------------------------------------------------------- */
  const panel = $('[data-search-panel]');
  const openSearch = $('[data-search-open]');
  const closeSearch = $('[data-search-close]');
  const searchInput = $('[data-search-input]');
  const searchOutput = $('[data-search-results]');

  // Demo article index — replace or expand when connecting a real CMS
  // Paths are relative to the pages/ directory; base is resolved from body[data-base]
  const articles = [
    ['Understanding Business Law: A Practical Overview', 'Business Law', 'article-business-law.html'],
    ['What to Know Before Signing a Commercial Lease', 'Real Estate Law', 'article-commercial-lease.html'],
    ['A Guide to Estate Planning for Modern Families', 'Estate Planning', 'article-estate-planning.html'],
    ['Protecting Your Intellectual Property', 'Intellectual Property', 'article-intellectual-property.html'],
    ['Employment Agreements: Key Considerations', 'Employment Law', 'article-employment-agreements.html'],
  ];

  let lastFocus = null;

  function closePanel() {
    if (panel) {
      panel.classList.remove('open');
      panel.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus();
      }
    }
  }

  if (openSearch && panel) {
    openSearch.addEventListener('click', () => {
      lastFocus = document.activeElement;
      panel.classList.add('open');
      panel.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      setTimeout(() => searchInput?.focus(), 30);
    });
  }

  if (closeSearch) closeSearch.addEventListener('click', closePanel);
  if (panel) {
    panel.addEventListener('click', (e) => {
      if (e.target === panel) closePanel();
    });
  }
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && panel?.classList.contains('open')) {
      closePanel();
    }
  });

  if (searchInput && searchOutput) {
    searchInput.addEventListener('input', () => {
      const q = searchInput.value.trim().toLowerCase();
      const matches = articles.filter((a) => a.join(' ').toLowerCase().includes(q));
      searchOutput.replaceChildren();

      if (!q) {
        searchOutput.textContent = 'Start typing to search our sample insights.';
        return;
      }
      if (!matches.length) {
        searchOutput.textContent = 'No matching articles found. Try another search term.';
        return;
      }

      matches.forEach((a) => {
        const link = document.createElement('a');
        const base = document.body.dataset.base || '';
        // Homepage: data-base="" → pages/article-xxx.html
        // Inner pages: data-base="../" → ../article-xxx.html (siblings in pages/)
        const href = base === '' || base === './'
          ? `pages/${a[2]}`
          : `${base}${a[2]}`;
        link.href = href;
        link.textContent = `${a[0]} — ${a[1]}`;
        searchOutput.append(link);
      });
    });
  }

  /* --------------------------------------------------------------------------
     FAQ accordion
     -------------------------------------------------------------------------- */
  $$('[data-faq]').forEach((item) => {
    const button = $('button', item);
    button?.addEventListener('click', () => {
      const open = item.classList.toggle('open');
      button.setAttribute('aria-expanded', String(open));
    });
  });

  /* --------------------------------------------------------------------------
     Demo forms — client-side validation only
     Connect to Formspree, Netlify Forms, or your own backend before production.
     See documentation/customization.md for integration examples.
     -------------------------------------------------------------------------- */
  $$('form[data-demo-form]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const message = $('[data-form-message]', form);

      if (!form.checkValidity()) {
        form.reportValidity();
        if (message) {
          message.className = 'form-message show error';
          message.textContent = 'Please review the highlighted fields and try again.';
        }
        return;
      }

      if (message) {
        message.className = 'form-message show success';
        message.textContent =
          form.dataset.success ||
          'Your form has been validated. This demo does not transmit or store submissions. Connect a secure form service before launch.';
      }
      form.reset();
    });
  });

  /* --------------------------------------------------------------------------
     Back-to-top & reading progress
     -------------------------------------------------------------------------- */
  const backTop = $('[data-back-top]');
  const progress = $('[data-reading-progress]');

  const updateScroll = () => {
    if (backTop) backTop.classList.toggle('visible', window.scrollY > 500);
    if (progress) {
      const max = document.documentElement.scrollHeight - window.innerHeight;
      progress.style.width = (max > 0 ? (window.scrollY / max) * 100 : 0) + '%';
    }
  };

  window.addEventListener('scroll', updateScroll, { passive: true });
  updateScroll();

  backTop?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  /* --------------------------------------------------------------------------
     Copy-to-clipboard (style guide)
     -------------------------------------------------------------------------- */
  $$('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
      const code = $(button.dataset.copy);
      if (!code) return;
      try {
        await navigator.clipboard.writeText(code.innerText);
        button.textContent = 'Copied';
      } catch {
        button.textContent = 'Copy unavailable';
      }
      setTimeout(() => {
        button.textContent = 'Copy code';
      }, 1600);
    });
  });

  /* --------------------------------------------------------------------------
     Article table of contents (auto-generated from h2/h3)
     -------------------------------------------------------------------------- */
  const toc = $('[data-toc]');
  const articleBody = $('[data-article-body]');

  if (toc && articleBody) {
    const headings = $$('h2, h3', articleBody);
    if (headings.length) {
      toc.replaceChildren();
      headings.forEach((heading, index) => {
        if (!heading.id) {
          heading.id = `section-${index + 1}`;
        }
        const link = document.createElement('a');
        link.href = `#${heading.id}`;
        link.textContent = heading.textContent;
        if (heading.tagName === 'H3') {
          link.style.paddingLeft = '12px';
          link.style.fontSize = '0.82rem';
        }
        toc.append(link);
      });
    }
  }
})();
