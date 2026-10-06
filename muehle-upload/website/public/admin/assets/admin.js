/* Redaktionssystem Mühle Prautitz – Bedienlogik (ohne Fremdbibliotheken) */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const csrf = document.body.dataset.csrf || '';
  let uid = Date.now();
  const nextKey = () => 'n' + (uid++).toString(36);

  /* ---------- Seitenleiste mobil ---------- */
  const sbt = $('[data-sidebar-toggle]');
  if (sbt) sbt.addEventListener('click', () => {
    const open = sbt.getAttribute('aria-expanded') !== 'true';
    sbt.setAttribute('aria-expanded', String(open));
    $('#sidebar').classList.toggle('is-open', open);
  });

  /* ---------- Ungespeicherte Änderungen ---------- */
  let dirty = false;
  const markDirty = () => {
    if (dirty) return;
    dirty = true;
    $$('[data-dirty-hint]').forEach((h) => { h.textContent = 'Ungespeicherte Änderungen'; h.classList.add('is-dirty'); });
  };
  $$('form[data-unsaved-warning]').forEach((f) => {
    f.addEventListener('input', markDirty);
    f.addEventListener('change', markDirty);
    f.addEventListener('submit', () => { dirty = false; });
  });
  window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

  /* ---------- Bestätigungen ---------- */
  $$('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => {
    if (!window.confirm(f.dataset.confirm)) e.preventDefault();
  }));

  /* ---------- Bausteine & Listen ---------- */
  const fromTemplate = (tpl, replacements) => {
    let html = tpl.innerHTML;
    for (const [k, v] of Object.entries(replacements)) html = html.split(k).join(v);
    const wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    return wrap.firstElementChild;
  };

  document.addEventListener('click', (e) => {
    const t = e.target.closest('button');
    if (!t) return;

    if (t.dataset.addBlock) {
      const tpl = $(`template[data-block-template="${t.dataset.addBlock}"]`);
      const host = $('[data-blocks]');
      if (!tpl || !host) return;
      const el = fromTemplate(tpl, { __KEY__: nextKey() });
      const empty = $('[data-empty]', host);
      if (empty) empty.remove();
      if (t.hasAttribute('data-add-top')) host.prepend(el); else host.append(el);
      initFields(el);
      markDirty();
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      const first = $('input:not([type=hidden]):not([readonly]),textarea', el);
      if (first) setTimeout(() => first.focus({ preventScroll: true }), 300);
      return;
    }
    if (t.hasAttribute('data-list-add')) {
      const list = t.closest('[data-list]');
      const tpl = $(':scope > template[data-list-template]', list);
      const el = fromTemplate(tpl, { __IKEY__: nextKey() });
      $(':scope > [data-list-items]', list).append(el);
      initFields(el);
      markDirty();
      const first = $('input:not([type=hidden]):not([readonly]),textarea', el);
      if (first) first.focus();
      return;
    }
    if (t.dataset.remove) {
      const el = t.closest(t.dataset.remove === 'block' ? '[data-block]' : '[data-item]');
      const what = t.dataset.remove === 'block' ? 'diesen Baustein' : 'diesen Eintrag';
      if (el && window.confirm(`Wirklich ${what} entfernen? (Wird erst beim Speichern übernommen.)`)) { el.remove(); markDirty(); }
      return;
    }
    if (t.dataset.move) {
      const el = t.closest('[data-item]') && t.closest('.list-item__actions') ? t.closest('[data-item]') : t.closest('[data-block]');
      if (!el) return;
      if (t.dataset.move === 'up' && el.previousElementSibling) el.parentNode.insertBefore(el, el.previousElementSibling);
      if (t.dataset.move === 'down' && el.nextElementSibling) el.parentNode.insertBefore(el.nextElementSibling, el);
      t.focus();
      markDirty();
      return;
    }
    if (t.hasAttribute('data-block-toggle')) {
      const body = $('.block-editor__body', t.closest('[data-block]'));
      const open = t.getAttribute('aria-expanded') !== 'true';
      t.setAttribute('aria-expanded', String(open));
      if (body) body.hidden = !open;
      return;
    }
    if (t.dataset.copy) {
      navigator.clipboard && navigator.clipboard.writeText(t.dataset.copy).then(() => {
        const old = t.textContent; t.textContent = 'Kopiert ✓'; setTimeout(() => { t.textContent = old; }, 1500);
      });
      return;
    }
    if (t.hasAttribute('data-media-clear')) {
      const fld = t.closest('[data-media-field]');
      $('[data-media-value]', fld).value = '';
      $('[data-preview]', fld).innerHTML = '';
      markDirty();
      return;
    }
    if (t.hasAttribute('data-media-pick')) {
      openPicker(t.closest('[data-media-field]'));
      return;
    }
    if (t.dataset.md) {
      mdAction(t.dataset.md, $('textarea', t.closest('.fld')));
    }
  });

  /* ---------- Markdown-Werkzeugleiste ---------- */
  function mdAction(kind, ta) {
    if (!ta) return;
    const s = ta.selectionStart, en = ta.selectionEnd, v = ta.value, sel = v.slice(s, en);
    let ins = sel, cursor = null;
    if (kind === 'bold') ins = `**${sel || 'fetter Text'}**`;
    if (kind === 'italic') ins = `*${sel || 'kursiver Text'}*`;
    if (kind === 'link') {
      const url = window.prompt('Adresse des Links (z. B. /angelteich/ oder https://…):', 'https://');
      if (!url) return;
      ins = `[${sel || 'Linktext'}](${url})`;
    }
    if (kind === 'list') ins = (sel || 'Eintrag').split('\n').map((l) => '- ' + l.replace(/^[-*]\s+/, '')).join('\n');
    if (kind === 'h2') ins = '## ' + (sel || 'Zwischenüberschrift');
    if (kind === 'list' || kind === 'h2') {
      const before = v.slice(0, s);
      if (before && !before.endsWith('\n\n')) ins = (before.endsWith('\n') ? '\n' : '\n\n') + ins;
      ins += '\n\n';
    }
    ta.setRangeText(ins, s, en, 'end');
    if (cursor !== null) ta.selectionStart = ta.selectionEnd = cursor;
    ta.focus();
    markDirty();
  }

  /* ---------- Medienauswahl ---------- */
  const picker = $('[data-picker]');
  let pickTarget = null, pickType = 'image', pickItems = [];
  async function loadPicker() {
    const status = $('[data-picker-status]', picker);
    status.textContent = 'Lade …';
    try {
      const r = await fetch(`?r=media-json&type=${pickType}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      const d = await r.json();
      pickItems = d.items || [];
      status.textContent = pickItems.length ? '' : 'Noch keine Dateien vorhanden – laden Sie oben rechts welche hoch.';
      renderPicker();
    } catch (e) { status.textContent = 'Fehler beim Laden. Sind Sie noch angemeldet?'; }
  }
  function renderPicker() {
    const q = ($('[data-picker-search]', picker).value || '').toLowerCase();
    const grid = $('[data-picker-grid]', picker);
    grid.innerHTML = '';
    pickItems.filter((i) => !q || (i.path + ' ' + i.alt + ' ' + i.title).toLowerCase().includes(q)).forEach((i) => {
      const li = document.createElement('li');
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'picker__item';
      if (pickType === 'image') {
        const img = document.createElement('img');
        img.src = i.thumb; img.alt = ''; img.loading = 'lazy';
        b.append(img);
      } else {
        const span = document.createElement('span'); span.className = 'doc-badge doc-badge--big'; span.textContent = 'PDF'; b.append(span);
      }
      const cap = document.createElement('span');
      cap.className = 'picker__name';
      cap.textContent = i.path.split('/').pop();
      b.append(cap);
      b.addEventListener('click', () => choose(i));
      li.append(b);
      grid.append(li);
    });
  }
  function choose(i) {
    if (!pickTarget) return;
    $('[data-media-value]', pickTarget).value = i.path;
    const pv = $('[data-preview]', pickTarget);
    pv.innerHTML = '';
    if (pickType === 'image') { const img = document.createElement('img'); img.src = i.thumb; img.alt = ''; pv.append(img); }
    else { const s = document.createElement('span'); s.className = 'doc-badge'; s.textContent = 'PDF'; pv.append(s); }
    // Bildbeschreibung übernehmen, falls das Nachbarfeld leer ist
    const container = pickTarget.parentElement;
    if (pickType === 'image' && i.alt && container) {
      const altField = $$('input[name$="alt]"]', container).find((x) => x !== $('[data-media-value]', pickTarget));
      if (altField && !altField.value) altField.value = i.alt;
    }
    markDirty();
    picker.close();
  }
  function openPicker(field) {
    if (!picker) return;
    pickTarget = field;
    pickType = field.dataset.type === 'doc' ? 'doc' : 'image';
    $('[data-picker-title]', picker).textContent = pickType === 'doc' ? 'PDF-Dokument auswählen' : 'Bild auswählen';
    $('[data-picker-upload]', picker).accept = pickType === 'doc' ? 'application/pdf' : 'image/jpeg,image/png,image/webp';
    $('[data-picker-search]', picker).value = '';
    picker.showModal();
    loadPicker();
  }
  if (picker) {
    $('[data-picker-close]', picker).addEventListener('click', () => picker.close());
    $('[data-picker-search]', picker).addEventListener('input', renderPicker);
    $('[data-picker-upload]', picker).addEventListener('change', async (e) => {
      const files = e.target.files;
      if (!files.length) return;
      const fd = new FormData();
      fd.append('_csrf', csrf);
      Array.from(files).forEach((f) => fd.append('files[]', f));
      const status = $('[data-picker-status]', picker);
      status.textContent = 'Wird hochgeladen und optimiert …';
      try {
        const r = await fetch('?r=media', { method: 'POST', body: fd, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const d = await r.json();
        status.textContent = d.message || '';
        await loadPicker();
        if (d.files && d.files.length === 1) {
          const it = pickItems.find((x) => x.path === d.files[0].path);
          if (it) choose(it);
        }
      } catch (err) { status.textContent = 'Upload fehlgeschlagen.'; }
      e.target.value = '';
    });
  }

  /* ---------- Initialisierung pro Bereich ---------- */
  function initFields(root) {
    $$('textarea[data-md-input]', root).forEach((ta) => {
      const fit = () => { ta.style.height = 'auto'; ta.style.height = Math.min(600, ta.scrollHeight + 4) + 'px'; };
      ta.addEventListener('input', fit);
    });
  }
  initFields(document);

  /* ---------- Drag & Drop Upload ---------- */
  $$('[data-dropzone] .dropzone').forEach((dz) => {
    const input = $('input[type=file]', dz);
    const label = $('span', dz);
    ['dragenter', 'dragover'].forEach((ev) => dz.addEventListener(ev, (e) => { e.preventDefault(); dz.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach((ev) => dz.addEventListener(ev, () => dz.classList.remove('is-over')));
    dz.addEventListener('drop', (e) => { e.preventDefault(); input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change')); });
    input.addEventListener('change', () => {
      const n = input.files.length;
      if (n) label.innerHTML = `<strong>${n} Datei(en) ausgewählt</strong><br><small>${Array.from(input.files).map((f) => f.name.replace(/[<>&"]/g, '')).join(', ')}</small>`;
    });
  });

  /* ---------- Passwortstärke ---------- */
  $$('[data-pw-meter]').forEach((inp) => {
    const m = document.createElement('div');
    m.className = 'pw-meter';
    m.innerHTML = '<span></span>';
    inp.after(m);
    inp.addEventListener('input', () => {
      const v = inp.value;
      let s = Math.min(4, Math.floor(v.length / 4));
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s += 1;
      if (/\d/.test(v) || /[^\w]/.test(v)) s += 1;
      if (v.length < 12) s = Math.min(s, 2);
      m.dataset.level = String(Math.min(5, s));
    });
  });

  /* ---------- QR-Code (2FA) ---------- */
  const qrBox = $('[data-qr]');
  if (qrBox) {
    const sc = document.createElement('script');
    sc.src = 'assets/qrcode.js';
    sc.onload = () => {
      const qr = window.qrcode(0, 'M');
      qr.addData(qrBox.dataset.qr);
      qr.make();
      const img = new Image();
      img.src = qr.createDataURL(5, 2);
      img.alt = '';
      qrBox.append(img);
    };
    document.head.append(sc);
  }
})();
