/* Mühle Prautitz – kleines, abhängigkeitsfreies Skript (progressive enhancement) */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const base = (() => {
    const css = $('link[rel="stylesheet"][href*="/assets/css/site.css"]');
    return css ? css.getAttribute('href').split('/assets/')[0] : '';
  })();
  const iconUrl = base + '/assets/img/icons.svg';
  const icon = (n) => `<svg class="icon" aria-hidden="true"><use href="${iconUrl}#${n}"></use></svg>`;

  /* Navigation (mobil) */
  const toggle = $('[data-nav-toggle]');
  const nav = $('[data-nav]');
  if (toggle && nav) {
    const set = (open) => { toggle.setAttribute('aria-expanded', String(open)); nav.classList.toggle('is-open', open); };
    toggle.addEventListener('click', () => set(toggle.getAttribute('aria-expanded') !== 'true'));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && nav.classList.contains('is-open')) { set(false); toggle.focus(); } });
  }

  /* Abgelaufene / noch nicht gültige Hinweise ausblenden (falls Seite seit dem Datum nicht neu erzeugt wurde) */
  const today = new Date().toISOString().slice(0, 10);
  $$('[data-until],[data-from]').forEach((el) => {
    const until = el.dataset.until, from = el.dataset.from;
    if ((until && until < today) || (from && from > today)) el.hidden = true;
  });
  $$('.news').forEach((s) => { if (!$$('.news-item:not([hidden])', s).length) s.hidden = true; });

  /* Lightbox für Galerien */
  const links = $$('[data-lightbox]');
  if (links.length && 'HTMLDialogElement' in window) {
    const dlg = document.createElement('dialog');
    dlg.className = 'lightbox';
    dlg.setAttribute('aria-label', 'Bildansicht');
    dlg.innerHTML = `<div class="lightbox__inner"><img alt=""><p class="lightbox__caption"></p></div>
      <button type="button" class="lightbox__close" aria-label="Schließen">${icon('close')}</button>
      <button type="button" class="lightbox__prev" aria-label="Vorheriges Bild">${icon('chevron-left')}</button>
      <button type="button" class="lightbox__next" aria-label="Nächstes Bild">${icon('chevron-right')}</button>`;
    document.body.append(dlg);
    const img = $('img', dlg), cap = $('.lightbox__caption', dlg);
    let group = [], idx = 0;
    const show = (i) => {
      idx = (i + group.length) % group.length;
      const a = group[idx];
      img.src = a.getAttribute('href');
      img.alt = ($('img', a) || {}).alt || '';
      cap.textContent = a.dataset.caption || '';
    };
    links.forEach((a) => a.addEventListener('click', (e) => {
      e.preventDefault();
      group = $$('[data-lightbox]', a.closest('[data-gallery]') || document);
      show(group.indexOf(a));
      dlg.showModal();
    }));
    $('.lightbox__close', dlg).addEventListener('click', () => dlg.close());
    $('.lightbox__prev', dlg).addEventListener('click', () => show(idx - 1));
    $('.lightbox__next', dlg).addEventListener('click', () => show(idx + 1));
    dlg.addEventListener('click', (e) => { if (e.target === dlg || e.target.classList.contains('lightbox__inner')) dlg.close(); });
    dlg.addEventListener('keydown', (e) => { if (e.key === 'ArrowLeft') show(idx - 1); if (e.key === 'ArrowRight') show(idx + 1); });
  }

  /* Video erst nach Klick laden (Datenschutz) */
  $$('[data-video]').forEach((box) => {
    const btn = $('[data-video-load]', box);
    btn && btn.addEventListener('click', () => {
      const f = document.createElement('iframe');
      f.src = box.dataset.src;
      f.title = 'Video';
      f.allow = 'autoplay; fullscreen; picture-in-picture';
      f.allowFullscreen = true;
      f.loading = 'lazy';
      box.append(f);
      box.classList.add('is-loaded');
    });
  });

  /* Kontaktformular */
  $$('[data-contact-form]').forEach((form) => {
    if (form.hasAttribute('data-disabled')) return;
    const status = $('[data-form-status]', form);
    const tokenField = $('[data-contact-token]', form);
    const setStatus = (msg, type) => { status.textContent = msg; status.className = 'form-status' + (type ? ' is-' + type : ''); };
    const loadToken = () => fetch(form.action + '?token=1', { credentials: 'same-origin' })
      .then((r) => r.ok ? r.json() : Promise.reject())
      .then((d) => { tokenField.value = d.token; })
      .catch(() => {});
    let tokenLoaded = false;
    form.addEventListener('focusin', () => { if (!tokenLoaded) { tokenLoaded = true; loadToken(); } });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      let firstInvalid = null;
      $$('input,textarea', form).forEach((f) => {
        const ok = f.checkValidity();
        f.setAttribute('aria-invalid', ok ? 'false' : 'true');
        if (!ok && !firstInvalid) firstInvalid = f;
      });
      if (firstInvalid) { setStatus('Bitte füllen Sie alle Pflichtfelder (*) korrekt aus.', 'error'); firstInvalid.focus(); return; }
      if (!tokenField.value) await loadToken();
      const btn = $('button[type="submit"]', form);
      btn.disabled = true;
      setStatus('Wird gesendet …');
      try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.ok) {
          form.reset();
          setStatus(data.message || 'Vielen Dank für Ihre Nachricht!', 'success');
        } else {
          setStatus(data.message || 'Das hat leider nicht geklappt. Bitte rufen Sie uns an oder schreiben Sie eine E-Mail.', 'error');
        }
      } catch (err) {
        setStatus('Keine Verbindung. Bitte versuchen Sie es später erneut.', 'error');
      } finally {
        btn.disabled = false;
        tokenField.value = '';
        loadToken();
      }
    });
  });

  /* Sanftes Einblenden beim Scrollen */
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const io = new IntersectionObserver((entries) => entries.forEach((en) => {
      if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
    }), { rootMargin: '0px 0px -8% 0px' });
    $$('.card, .timeline__item, .board, .gallery__item').forEach((el) => { el.classList.add('reveal'); io.observe(el); });
  }
})();
