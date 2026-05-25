// Interactivity: validation, auth state, orders storage, dynamic rendering

function qs(selector, root) { return (root || document).querySelector(selector); }
function qsa(selector, root) { return Array.from((root || document).querySelectorAll(selector)); }

// Form validation helpers (client-side only, demo)
const validators = {
  login: value => /^.{3,}$/.test(value),
  password: value => /^.{4,}$/.test(value),
  fio: value => /^[А-Яа-яЁё\s]{3,}$/.test(value),
  // Accept either 11 digits without symbols (e.g., 79991234567) or formatted +7(XXX)-XXX-XX-XX
  phone: value => /^(?:\d{11}|\+7\(\d{3}\)-\d{3}-\d{2}-\d{2})$/.test(value),
  email: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
  qty: value => Number(value) > 0 && Number.isInteger(Number(value))
};

function attachValidation(form) {
  if (!form) return;
  qsa('[data-validate]', form).forEach(input => {
    const type = input.dataset.validate;
    const errorEl = input.closest('.field')?.querySelector('.error');
    const hintEl = input.closest('.field')?.querySelector('.hint');
    const setState = (ok) => {
      input.style.borderColor = ok ? 'rgba(45,212,191,.45)' : 'rgba(239,68,68,.55)';
      if (errorEl) errorEl.style.display = ok ? 'none' : 'block';
      if (hintEl) hintEl.style.display = ok ? 'block' : 'none';
    };
    input.addEventListener('input', () => setState(validators[type]?.(input.value) ?? true));
    setState(validators[type]?.(input.value) ?? true);
  });
}

// Local storage helpers
const storage = {
  getAuth() {
    try { return JSON.parse(localStorage.getItem('avoska_auth') || 'null'); } catch { return null; }
  },
  setAuth(auth) {
    localStorage.setItem('avoska_auth', JSON.stringify(auth));
  },
  clearAuth() { localStorage.removeItem('avoska_auth'); },
  getOrders() {
    try { return JSON.parse(localStorage.getItem('avoska_orders') || '[]'); } catch { return []; }
  },
  setOrders(list) { localStorage.setItem('avoska_orders', JSON.stringify(list)); }
};

// Registration bookkeeping
storage.getRegistered = function() {
  try { return JSON.parse(localStorage.getItem('avoska_registered') || '[]'); } catch { return []; }
};
storage.addRegistered = function(login) {
  const list = storage.getRegistered();
  if (!list.includes(login)) {
    list.push(login);
    localStorage.setItem('avoska_registered', JSON.stringify(list));
  }
};
storage.isRegistered = function(login) {
  return storage.getRegistered().includes(login);
};

function updateAdminLinkVisibility() {
  const auth = storage.getAuth();
  const isAdmin = auth?.role === 'admin';
  qsa('[data-auth-admin]').forEach(a => { a.style.display = isAdmin ? 'inline' : 'none'; });
}

function wireLogout() {
  qsa('#logout-btn').forEach(btn => btn.addEventListener('click', () => {
    storage.clearAuth();
    updateAdminLinkVisibility();
    alert('Вы вышли из аккаунта.');
    window.location.href = './index.html';
  }));
}

function handleLogin(form) {
  if (!form) return;
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const login = form.login?.value?.trim();
    const password = form.password?.value ?? '';
    // Basic validation
    const okLogin = validators.login(login);
    const okPass = validators.password(password);
    if (!okLogin || !okPass) {
      alert('Неверные логин или пароль.');
      return;
    }
    const isAdmin = login === 'sklad' && password === '123qwe';
    storage.setAuth({ login, role: isAdmin ? 'admin' : 'user' });
    updateAdminLinkVisibility();
    alert(isAdmin ? 'Вы вошли как администратор.' : 'Вход выполнен.');
    // Redirect back to home
    window.location.href = './index.html';
  });
}

function handleRegister(form) {
  if (!form) return;
  form.addEventListener('submit', (e) => {
    // Validate client-side and simulate success
    let ok = true;
    qsa('[data-validate]', form).forEach(input => {
      const type = input.dataset.validate;
      if (!(validators[type]?.(input.value))) ok = false;
    });
    if (!ok) {
      e.preventDefault();
      alert('Пожалуйста, исправьте ошибки формы.');
      return;
    }
    storage.addRegistered(form.login?.value?.trim());
    alert('Регистрация выполнена. Теперь можете войти.');
  });
}

function handleOrderSubmit(form) {
  if (!form) return;
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const auth = storage.getAuth();
    if (!(auth && storage.isRegistered(auth.login))) {
      alert('Для оформления заказа сначала зарегистрируйтесь и войдите.');
      window.location.href = './login.html';
      return;
    }
    const product = form.product?.value?.trim();
    const qty = Number(form.qty?.value || 0);
    const address = form.address?.value?.trim();
    if (!product || !validators.qty(qty) || !address) {
      alert('Пожалуйста, заполните все поля корректно.');
      return;
    }
    const orders = storage.getOrders();
    orders.push({ product, qty, address, status: 'новое' });
    storage.setOrders(orders);
    // Redirect to Orders page
    window.location.href = './orders.html';
  });
}

function guardOrderAccess() {
  const form = qs('form[data-form="order"]');
  if (!form) return;
  const auth = storage.getAuth();
  if (!(auth && storage.isRegistered(auth.login))) {
    alert('Доступ к оформлению заказа только после регистрации и входа.');
    window.location.href = './login.html';
  }
}

function statusPill(status) {
  const cls = status === 'подтверждено' ? 'status-confirmed' : status === 'отменено' ? 'status-canceled' : 'status-new';
  return `<span class="status-pill ${cls}">${status}</span>`;
}

function renderOrdersPage() {
  const body = qs('#orders-body');
  if (!body) return;
  const orders = storage.getOrders();
  const search = (qs('#orders-search')?.value || '').toLowerCase();
  const status = qs('#orders-status')?.value || '';
  const filtered = orders.filter(o => {
    const byText = o.product.toLowerCase().includes(search);
    const byStatus = !status || (o.status === status);
    return byText && byStatus;
  });
  if (!filtered.length) {
    body.innerHTML = `<tr><td colspan="4" class="hint">Нет заказов. Создайте новый на странице «Новый заказ».</td></tr>`;
    return;
  }
  body.innerHTML = filtered.map(o => `
    <tr>
      <td>${o.product}</td>
      <td>${o.qty}</td>
      <td>${statusPill(o.status || 'новое')}</td>
      <td>${o.address}</td>
    </tr>
  `).join('');
}

function renderAdminPage() {
  const body = qs('#admin-orders-body');
  if (!body) return;
  const auth = storage.getAuth();
  // Show a simple note if not admin
  if (!(auth && auth.role === 'admin')) {
    body.innerHTML = `<tr><td colspan="6" class="error">Доступ ограничен. Войдите как sklad / 123qwe.</td></tr>`;
    return;
  }
  const orders = storage.getOrders();
  const search = (qs('#admin-search')?.value || '').toLowerCase();
  const status = qs('#admin-status')?.value || '';
  const filtered = orders.filter(o => {
    const byText = o.product.toLowerCase().includes(search);
    const byStatus = !status || (o.status === status);
    return byText && byStatus;
  });
  if (!filtered.length) {
    body.innerHTML = `<tr><td colspan="6" class="hint">Пока нет заказов.</td></tr>`;
    return;
  }
  body.innerHTML = filtered.map((o, idx) => `
    <tr data-idx="${idx}">
      <td>-</td>
      <td>-</td>
      <td>${o.product}</td>
      <td>${o.qty}</td>
      <td>${statusPill(o.status || 'новое')}</td>
      <td style="display:flex; gap:8px">
        <button class="btn" data-action="confirm">Подтвердить</button>
        <button class="btn secondary" data-action="cancel">Отменить</button>
      </td>
    </tr>
  `).join('');
  body.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const tr = btn.closest('tr');
    const idx = Number(tr?.dataset.idx);
    const all = storage.getOrders();
    if (Number.isInteger(idx) && all[idx]) {
      if (all[idx].status === 'подтверждено' || all[idx].status === 'отменено') return;
      all[idx].status = btn.dataset.action === 'confirm' ? 'подтверждено' : 'отменено';
      storage.setOrders(all);
      renderAdminPage();
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  attachValidation(qs('form[data-form="register"]'));
  attachValidation(qs('form[data-form="login"]'));
  attachValidation(qs('form[data-form="order"]'));

  // Auth and page wiring
  updateAdminLinkVisibility();
  handleLogin(qs('form[data-form="login"]'));
  handleRegister(qs('form[data-form="register"]'));
  handleOrderSubmit(qs('form[data-form="order"]'));
  guardOrderAccess();

  renderOrdersPage();
  renderAdminPage();

  // Live filters and logout
  ['orders-search','orders-status','admin-search','admin-status'].forEach(id => {
    const el = qs('#' + id);
    if (el) el.addEventListener('input', () => { renderOrdersPage(); renderAdminPage(); });
    if (el) el.addEventListener('change', () => { renderOrdersPage(); renderAdminPage(); });
  });
  wireLogout();
});


