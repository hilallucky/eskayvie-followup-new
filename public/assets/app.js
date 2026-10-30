(() => {
  const csrf = document.querySelector('meta[name=csrf]')?.content ?? '';
  const flash = (el, cls) => { el.classList.add('ring-2', cls); setTimeout(() => el.classList.remove('ring-2', cls), 1200); };

  const sidebar = document.querySelector('[data-sidebar]');
  const sidebarToggle = document.querySelector('#sidebar-toggle');
  const sidebarBackdrop = document.querySelector('#sidebar-backdrop');
  const desktopLayout = window.matchMedia('(min-width: 768px)');

  if (desktopLayout.matches && sidebarToggle) {
    sidebarToggle.setAttribute('aria-expanded', 'true');
    sidebarToggle.setAttribute('aria-label', 'Hide menu');
  }

  function closeMobileSidebar() {
    sidebar?.setAttribute('data-open', 'false');
    sidebarBackdrop?.classList.add('hidden');
    sidebarToggle?.setAttribute('aria-expanded', 'false');
    sidebarToggle?.setAttribute('aria-label', 'Open menu');
  }

  sidebarToggle?.addEventListener('click', () => {
    if (!sidebar) return;
    if (desktopLayout.matches) {
      const collapsed = sidebar.dataset.collapsed !== 'true';
      sidebar.dataset.collapsed = String(collapsed);
      sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
      sidebarToggle.setAttribute('aria-label', collapsed ? 'Show menu' : 'Hide menu');
      return;
    }

    const open = sidebar.dataset.open !== 'true';
    sidebar.dataset.open = String(open);
    sidebarBackdrop?.classList.toggle('hidden', !open);
    sidebarToggle.setAttribute('aria-expanded', String(open));
    sidebarToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
  });

  sidebarBackdrop?.addEventListener('click', closeMobileSidebar);
  sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMobileSidebar));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sidebar?.dataset.open === 'true') closeMobileSidebar();
  });
  desktopLayout.addEventListener('change', (event) => {
    closeMobileSidebar();
    if (event.matches && sidebar) sidebar.dataset.collapsed = 'false';
  });

  const librarySidebar = document.querySelector('[data-library-sidebar]');
  const libraryToggle = document.querySelector('#library-sidebar-toggle');
  const libraryBackdrop = document.querySelector('#library-sidebar-backdrop');

  function setLibrarySidebarLabel(expanded, label) {
    libraryToggle?.setAttribute('aria-expanded', String(expanded));
    libraryToggle?.setAttribute('aria-label', label);
    libraryToggle?.setAttribute('title', label);
  }

  function closeLibrarySidebar() {
    if (!librarySidebar) return;
    librarySidebar.dataset.open = 'false';
    libraryBackdrop?.classList.add('hidden');
    setLibrarySidebarLabel(false, 'Show sidebar');
  }

  if (librarySidebar && libraryToggle) {
    if (desktopLayout.matches) {
      librarySidebar.dataset.hidden = 'false';
      setLibrarySidebarLabel(true, 'Hide sidebar');
    } else {
      librarySidebar.dataset.hidden = 'false';
      librarySidebar.dataset.open = 'false';
      setLibrarySidebarLabel(false, 'Show sidebar');
    }

    libraryToggle.addEventListener('click', () => {
      if (desktopLayout.matches) {
        const hidden = librarySidebar.dataset.hidden !== 'true';
        librarySidebar.dataset.hidden = String(hidden);
        setLibrarySidebarLabel(!hidden, hidden ? 'Show sidebar' : 'Hide sidebar');
        return;
      }

      const open = librarySidebar.dataset.open !== 'true';
      librarySidebar.dataset.hidden = 'false';
      librarySidebar.dataset.open = String(open);
      libraryBackdrop?.classList.toggle('hidden', !open);
      setLibrarySidebarLabel(open, open ? 'Close sidebar' : 'Show sidebar');
    });

    libraryBackdrop?.addEventListener('click', closeLibrarySidebar);
    librarySidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
      if (!desktopLayout.matches) closeLibrarySidebar();
    }));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && librarySidebar.dataset.open === 'true') closeLibrarySidebar();
    });
    desktopLayout.addEventListener('change', (event) => {
      librarySidebar.dataset.hidden = 'false';
      librarySidebar.dataset.open = 'false';
      libraryBackdrop?.classList.add('hidden');
      setLibrarySidebarLabel(event.matches, event.matches ? 'Hide sidebar' : 'Show sidebar');
    });
  }

  const contentUploadForm = document.querySelector('#content-upload-form');
  const contentFileInput = document.querySelector('#content-upload');
  const contentFolderInput = document.querySelector('#content-folder-upload');
  const contentDropZone = document.querySelector('#content-drop-zone');
  const contentSelection = document.querySelector('#content-upload-selection');
  const contentUploadSubmit = document.querySelector('#content-upload-submit');
  const contentRelativePaths = document.querySelector('#content-relative-paths');

  if (contentUploadForm && contentFileInput && contentFolderInput && contentDropZone && contentSelection && contentUploadSubmit && contentRelativePaths) {
    const setContentFiles = (files, paths) => {
      const transfer = new DataTransfer();
      for (const file of files) transfer.items.add(file);
      contentFileInput.files = transfer.files;
      contentRelativePaths.replaceChildren(...paths.map((path) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'relative_paths[]';
        input.value = path;
        return input;
      }));
      contentUploadSubmit.disabled = files.length === 0;
      contentUploadSubmit.classList.toggle('text-slate-400', files.length === 0);
      contentUploadSubmit.classList.toggle('text-brand-purple', files.length > 0);
      contentSelection.textContent = files.length
        ? `${files.length} file${files.length === 1 ? '' : 's'} selected`
        : 'or choose multiple files, or select a folder';
    };

    contentFileInput.addEventListener('change', () => {
      const files = [...contentFileInput.files];
      setContentFiles(files, files.map((file) => file.webkitRelativePath || file.name));
    });
    contentFolderInput.addEventListener('change', () => {
      const files = [...contentFolderInput.files];
      setContentFiles(files, files.map((file) => file.webkitRelativePath || file.name));
    });

    const entryFile = (entry) => new Promise((resolve, reject) => {
      entry.file(resolve, reject);
    });
    const readDirectory = async (entry) => {
      const reader = entry.createReader();
      const entries = [];
      while (true) {
        const batch = await new Promise((resolve, reject) => reader.readEntries(resolve, reject));
        if (!batch.length) break;
        entries.push(...batch);
      }
      return entries;
    };
    const readEntry = async (entry, parentPath = '') => {
      const entryPath = parentPath ? `${parentPath}/${entry.name}` : entry.name;
      if (entry.isFile) return [{ file: await entryFile(entry), path: entryPath }];
      if (!entry.isDirectory) return [];
      const children = await readDirectory(entry);
      const nested = await Promise.all(children.map((child) => readEntry(child, entryPath)));
      return nested.flat();
    };

    contentDropZone.addEventListener('dragover', (event) => {
      event.preventDefault();
      contentDropZone.classList.add('border-brand-purple', 'bg-purple-50');
    });
    contentDropZone.addEventListener('dragleave', (event) => {
      if (!contentDropZone.contains(event.relatedTarget)) contentDropZone.classList.remove('border-brand-purple', 'bg-purple-50');
    });
    contentDropZone.addEventListener('drop', async (event) => {
      event.preventDefault();
      contentDropZone.classList.remove('border-brand-purple', 'bg-purple-50');
      try {
        const entries = [...event.dataTransfer.items]
          .map((item) => item.webkitGetAsEntry?.())
          .filter(Boolean);
        const dropped = entries.length
          ? (await Promise.all(entries.map((entry) => readEntry(entry)))).flat()
          : [...event.dataTransfer.files].map((file) => ({ file, path: file.webkitRelativePath || file.name }));
        setContentFiles(dropped.map((item) => item.file), dropped.map((item) => item.path));
      } catch (error) {
        console.error('Could not read the dropped Bank Content files.', error);
        contentSelection.textContent = 'Could not read the dropped items. Please choose the files or folder instead.';
        contentUploadSubmit.disabled = true;
        alert('The selected files or folder could not be read. Please choose them using the browse buttons.');
      }
    });
  }

  async function save(el, field, value) {
    const code = el.closest('tr').dataset.code;
    try {
      const res = await fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ member_code: code, field, value }),
      });
      const j = await res.json().catch(() => ({}));
      if (!res.ok || !j.ok) throw new Error(j.error || 'Save failed');
      flash(el, 'ring-brand-green');
      return true;
    } catch (err) { flash(el, 'ring-red-500'); alert(err.message); return false; }
  }

  async function saveDailyTask(button, field, value) {
    const row = button.closest('[data-daily-task-row]');
    try {
      const res = await fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({
          daily_task: true,
          month: Number(row.dataset.month),
          year: Number(row.dataset.year),
          day: Number(row.dataset.day),
          field,
          value,
        }),
      });
      const result = await res.json().catch(() => ({}));
      if (!res.ok || !result.ok) throw new Error(result.error || 'Save failed');
      flash(button, 'ring-brand-green');
      return true;
    } catch (err) { flash(button, 'ring-red-500'); alert(err.message); return false; }
  }

  async function saveCustomer(el, field, value) {
    const customerId = el.closest('[data-customer-row]')?.dataset.customerId;
    if (!customerId) return false;
    try {
      const res = await fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ customer_id: customerId, field, value }),
      });
      const result = await res.json().catch(() => ({}));
      if (!res.ok || !result.ok) throw new Error(result.error || 'Save failed');
      flash(el, 'ring-brand-green');
      if (field === 'birth_date') {
        const row = el.closest('[data-customer-row]');
        const color = birthdayClass(value);
        row.classList.remove('bg-red-100', 'bg-green-100');
        if (color) row.classList.add(color);
      }
      return true;
    } catch (err) { flash(el, 'ring-red-500'); alert(err.message); return false; }
  }

  async function saveProspect(el, field, value) {
    const prospectId = el.closest('[data-prospect-row]')?.dataset.prospectId;
    if (!prospectId) return false;
    try {
      const res = await fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ prospect_id: prospectId, field, value }),
      });
      const result = await res.json().catch(() => ({}));
      if (!res.ok || !result.ok) throw new Error(result.error || 'Save failed');
      flash(el, 'ring-brand-green');
      return true;
    } catch (err) { flash(el, 'ring-red-500'); alert(err.message); return false; }
  }

  function birthdayClass(value) {
    if (!value) return '';
    const [year, month, day] = value.split('-').map(Number);
    if (!year || !month || !day) return '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    let nearest = Infinity;
    for (const birthdayYear of [today.getFullYear() - 1, today.getFullYear(), today.getFullYear() + 1]) {
      const birthday = new Date(birthdayYear, month - 1, Math.min(day, new Date(birthdayYear, month, 0).getDate()));
      nearest = Math.min(nearest, Math.abs(Math.round((birthday - today) / 86400000)));
    }
    return nearest === 0 ? 'bg-green-100' : nearest <= 7 ? 'bg-red-100' : '';
  }

  document.addEventListener('change', (event) => {
    const product = event.target.closest('[data-customer-product]');
    if (product) {
      const row = product.closest('[data-customer-row]');
      const selected = [...row.querySelectorAll('[data-customer-product]:checked')].map((item) => item.value);
      saveCustomer(product, 'product_codes', selected);
      return;
    }
    const field = event.target.closest('[data-customer-field]');
    if (field) {
      const displayField = field.dataset.autocompleteVisible
        ? document.getElementById(field.dataset.autocompleteVisible)
        : field;
      saveCustomer(displayField ?? field, field.dataset.customerField, field.value);
      return;
    }
    const prospect = event.target.closest('[data-prospect-field]');
    if (prospect) {
      const displayField = prospect.dataset.autocompleteVisible
        ? document.getElementById(prospect.dataset.autocompleteVisible)
        : prospect;
      saveProspect(displayField ?? prospect, prospect.dataset.prospectField, prospect.value);
    }
  });

  // ✓ / ✗ cells
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-toggle]');
    if (!b) return;
    const next = b.dataset.value === '1' ? 0 : 1;
    const row = b.closest('[data-daily-task-row]');
    const saved = row
      ? await saveDailyTask(b, b.dataset.toggle, next)
      : await save(b, b.dataset.toggle, next);
    if (saved) {
      b.dataset.value = next; b.textContent = next ? '✓' : '✗';
      b.classList.toggle('text-brand-forest', !!next); b.classList.toggle('text-gray-400', !next);
    }
  });

  // inputs / selects save on change (text inputs fire on blur)
  document.addEventListener('change', (e) => {
    const f = e.target.closest('[data-field]');
    if (f) {
      const displayField = f.dataset.autocompleteVisible
        ? document.getElementById(f.dataset.autocompleteVisible)
        : f;
      save(displayField ?? f, f.dataset.field, f.value);
    }
  });

  document.querySelector('[data-month-selector]')?.addEventListener('change', (event) => {
    if (event.target.matches('[data-month-field]')) event.currentTarget.requestSubmit();
  });

  const numberFields = document.querySelectorAll('[data-number-format]');
  const numberPattern = /^(?:\d+|\d{1,3}(?:,\d{3})+)$/;
  const formatGroupedNumber = (value) => value.replace(/,/g, '').replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  const updateNumberValidity = (field) => {
    field.setCustomValidity(field.value === '' || numberPattern.test(field.value) ? '' : 'Enter a whole number using digits only.');
  };
  numberFields.forEach((field) => {
    field.value = formatGroupedNumber(field.value);
    updateNumberValidity(field);
    field.addEventListener('input', () => updateNumberValidity(field));
  });
  document.addEventListener('input', (event) => {
    const field = event.target.closest('[data-number-format]');
    if (!field) return;
    if (field.value !== '' && !/^[\d,]+$/.test(field.value)) return;
    const digitsBeforeCursor = field.value.slice(0, field.selectionStart).replace(/\D/g, '').length;
    field.value = formatGroupedNumber(field.value);
    updateNumberValidity(field);
    let cursor = 0;
    let digitsSeen = 0;
    while (cursor < field.value.length && digitsSeen < digitsBeforeCursor) {
      if (/\d/.test(field.value[cursor])) digitsSeen++;
      cursor++;
    }
    field.setSelectionRange(cursor, cursor);
  });

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-number-step]');
    if (!button) return;
    const field = document.getElementById(button.dataset.numberTarget);
    if (!field || (field.value !== '' && !numberPattern.test(field.value))) return;
    const current = Number(field.value.replace(/,/g, '') || 0);
    const increment = Number(button.dataset.numberStep);
    const precision = (String(Math.abs(increment)).split('.')[1] ?? '').length;
    const next = Number(Math.max(0, current + increment).toFixed(precision));
    if (!Number.isFinite(next) || (field.matches('[data-number-format]') && !Number.isSafeInteger(next))) return;
    field.value = field.matches('[data-number-format]') ? formatGroupedNumber(String(next)) : String(next);
    if (field.matches('[data-number-format]')) updateNumberValidity(field);
    field.dispatchEvent(new Event('input', { bubbles: true }));
  });

  document.querySelectorAll('[data-year-auto-load]').forEach((form) => {
    let submitTimer;
    const scheduleSubmit = (input) => {
      clearTimeout(submitTimer);
      if (!input.value || !input.validity.valid) return;
      submitTimer = setTimeout(() => form.requestSubmit(), 400);
    };
    form.addEventListener('input', (event) => {
      const input = event.target.closest('[data-auto-load-year]');
      if (input) scheduleSubmit(input);
    });
    form.addEventListener('change', (event) => {
      const input = event.target.closest('[data-auto-load-year]');
      if (!input || !input.value || !input.validity.valid) return;
      clearTimeout(submitTimer);
      form.requestSubmit();
    });
  });

  document.querySelectorAll('[data-autocomplete-input]').forEach((input) => {
    const valueField = document.getElementById(input.dataset.autocompleteTarget);
    const options = document.getElementById(input.getAttribute('list'));
    if (!valueField || !options) return;
    const syncValue = () => {
      const entered = input.value.trim().toLocaleLowerCase();
      const option = [...options.options].find((item) => item.value.trim().toLocaleLowerCase() === entered);
      valueField.value = option?.dataset.value ?? '';
      const valid = !!option || (input.value === '' && input.dataset.autocompleteAllowEmpty === 'true');
      input.setCustomValidity(valid ? '' : 'Choose an option from the suggestions.');
      return valid;
    };
    input.addEventListener('input', syncValue);
    input.addEventListener('change', () => {
      if (syncValue()) valueField.dispatchEvent(new Event('change', { bubbles: true }));
    });
    syncValue();
  });

  const autoGrow = (field) => {
    field.style.height = 'auto';
    field.style.height = `${field.scrollHeight}px`;
  };
  const effortFields = document.querySelectorAll('[data-auto-grow]');
  effortFields.forEach(autoGrow);
  document.addEventListener('input', (event) => {
    const field = event.target.closest('[data-auto-grow]');
    if (field) autoGrow(field);
  });
})();
