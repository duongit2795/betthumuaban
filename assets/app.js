/* Tele Manager – SPA (không cần build) */
(() => {
  'use strict';

  // ================= Icons =================
  const P = {
    home: '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
    bot: '<rect x="3" y="8" width="18" height="12" rx="3"/><path d="M12 8V4M8 4h8"/><circle cx="9" cy="14" r="1.3"/><circle cx="15" cy="14" r="1.3"/>',
    list: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    more: '<circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>',
    grid: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    trophy: '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>',
    star: '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>',
    msg: '<path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-6.4A8 8 0 1 1 21 12z"/>',
    shield: '<path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6z"/>',
    ban: '<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>',
    mute: '<path d="M11 5 6 9H3v6h3l5 4z"/><path d="m22 9-6 6M16 9l6 6"/>',
    volume: '<path d="M11 5 6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/>',
    alert: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
    kick: '<path d="M16 17l5-5-5-5M21 12H9M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    minus: '<path d="M5 12h14"/>',
    settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
    refresh: '<path d="M21 12a9 9 0 0 1-15.5 6.2L3 16M3 12a9 9 0 0 1 15.5-6.2L21 8"/><path d="M21 3v5h-5M3 21v-5h5"/>',
    download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
    send: '<path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
    chevron: '<path d="m9 18 6-6-6-6"/>',
    back: '<path d="m15 18-6-6 6-6"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    moon: '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    link: '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>',
    trash: '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
    copy: '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
    edit: '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
    note: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
    power: '<path d="M18.4 6.6a9 9 0 1 1-12.8 0M12 2v10"/>',
    git: '<circle cx="6" cy="6" r="2.5"/><circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="9" r="2.5"/><path d="M6 8.5v7M18 11.5c0 3-3 3.5-6 4.5-2 .7-3.6 1.3-4.4 2.2"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
    pin: '<path d="M12 17v5M5 17h14l-1.8-3.6a2 2 0 0 1-.2-.9V6a2 2 0 0 1 2-2H7a2 2 0 0 1 2 2v6.5a2 2 0 0 1-.2.9z"/>',
    lock: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    hand: '<path d="M18 11V6a2 2 0 0 0-4 0v5M14 10V4a2 2 0 0 0-4 0v6M10 10.5V6a2 2 0 0 0-4 0v8"/><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.9-6-2.4l-3.6-3.6a2 2 0 0 1 2.8-2.8L7 15"/>',
    zap: '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
    calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    external: '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>',
    key: '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
    database: '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
    reply: '<path d="M9 17 4 12l5-5"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>',
    terminal: '<path d="m4 17 6-6-6-6M12 19h8"/>',
    globe: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
  };
  const icon = (n, cls = '') => `<svg class="i ${cls}" viewBox="0 0 24 24" aria-hidden="true">${P[n] || ''}</svg>`;

  // ================= Utils =================
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
  const num = (n) => Number(n || 0).toLocaleString('vi-VN');
  const pad = (n) => String(n).padStart(2, '0');
  const fmtDate = (t, time = false) => {
    if (!t) return '—';
    const d = new Date(t * 1000);
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}` + (time ? ` ${pad(d.getHours())}:${pad(d.getMinutes())}` : '');
  };
  const ago = (t) => {
    if (!t) return 'chưa có';
    const s = Math.floor(Date.now() / 1000) - t;
    if (s < 60) return 'vừa xong';
    if (s < 3600) return Math.floor(s / 60) + ' phút trước';
    if (s < 86400) return Math.floor(s / 3600) + ' giờ trước';
    if (s < 86400 * 30) return Math.floor(s / 86400) + ' ngày trước';
    return fmtDate(t);
  };
  const COLORS = ['#3b82f6', '#8b5cf6', '#ec4899', '#f97316', '#10b981', '#06b6d4', '#6366f1', '#ef4444', '#14b8a6', '#a855f7'];
  const avatar = (name, seed, cls = '') => {
    const s = String(seed || name || '?');
    let h = 0;
    for (const c of s) h = (h * 31 + c.charCodeAt(0)) >>> 0;
    const ch = (String(name || '?').replace(/^@/, '').trim()[0] || '?');
    return `<div class="avatar ${cls}" style="background:${COLORS[h % COLORS.length]}">${esc(ch)}</div>`;
  };
  // Bộ đếm giờ của trang hiện tại (tự dừng khi chuyển trang)
  const timers = new Set();
  const every = (fn, ms) => { const id = setInterval(fn, ms); timers.add(id); return id; };
  const cleanups = [];
  const onLeave = (fn) => cleanups.push(fn);
  const stopTimers = () => { timers.forEach(clearInterval); timers.clear(); while (cleanups.length) { try { cleanups.pop()(); } catch {} } };
  const debounce = (fn, ms = 300) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

  const STATUS = {
    member: ['Thành viên', 'green'],
    muted: ['Bị cấm chat', 'amber'],
    banned: ['Bị cấm', 'red'],
    left: ['Đã rời', ''],
  };
  const statusBadge = (m) => {
    if (m.is_admin && m.status !== 'banned') return `<span class="badge blue">${icon('shield', 'sm')}Admin</span>`;
    const [t, c] = STATUS[m.status] || [m.status, ''];
    return `<span class="badge ${c}">${t}</span>`;
  };
  const ACTION = {
    ban: ['Cấm', 'ban', 'red'], unban: ['Gỡ cấm', 'check', 'green'], kick: ['Đuổi', 'kick', 'red'],
    mute: ['Cấm chat', 'mute', 'amber'], unmute: ['Bỏ cấm chat', 'volume', 'green'], warn: ['Cảnh cáo', 'alert', 'amber'],
    unwarn: ['Gỡ cảnh cáo', 'check', 'green'], resetwarn: ['Xoá cảnh cáo', 'check', 'green'], delete: ['Xoá tin vi phạm', 'trash', 'red'],
    points: ['Điểm', 'star', 'blue'], setpoints: ['Đặt điểm', 'star', 'blue'], reset_points: ['Reset điểm', 'star', 'amber'],
    join: ['Vào nhóm', 'plus', 'green'], leave: ['Rời nhóm', 'minus', ''], send: ['Gửi tin', 'send', 'blue'],
    settings: ['Cài đặt', 'settings', ''], bot_added: ['Thêm bot vào nhóm', 'bot', 'green'], bot_removed: ['Bot rời nhóm', 'bot', 'red'],
    bot_add: ['Thêm bot', 'bot', 'blue'], bot_delete: ['Xoá bot', 'bot', 'red'], bot_on: ['Bật bot', 'power', 'green'], bot_off: ['Tắt bot', 'power', ''],
    rule: ['Lệnh tự tạo', 'terminal', 'blue'], update: ['Cập nhật hệ thống', 'download', 'blue'], system: ['Hệ thống', 'settings', ''], pin: ['Ghim tin', 'pin', 'blue'], unpin: ['Bỏ ghim', 'pin', ''],
  };

  // ================= State & API =================
  const S = { user: null, csrf: '', site: window.SITE_NAME || 'Tele Manager', allowRegister: false, status: null };

  async function api(route, data, opts = {}) {
    const isPost = data !== undefined;
    let url = 'api.php?r=' + encodeURIComponent(route);
    if (!isPost && opts.query) url += '&' + new URLSearchParams(opts.query).toString();
    const res = await fetch(url, {
      method: isPost ? 'POST' : 'GET',
      headers: isPost ? { 'Content-Type': 'application/json', 'X-CSRF-Token': S.csrf } : {},
      body: isPost ? JSON.stringify(data) : undefined,
      credentials: 'same-origin',
    });
    let j;
    try { j = await res.json(); } catch { throw new Error('Máy chủ trả về dữ liệu không hợp lệ (HTTP ' + res.status + ')'); }
    if (!j.ok) {
      if (res.status === 401 && !opts.noRedirect) { S.user = null; go('/login'); }
      const e = new Error(j.error || 'Có lỗi xảy ra');
      e.status = res.status;
      throw e;
    }
    return j.data;
  }

  // ================= UI helpers =================
  function toast(msg, type = 'ok') {
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML = (type === 'err' ? icon('alert') : icon('check')) + `<span>${esc(msg)}</span>`;
    $('#toasts').appendChild(el);
    setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, type === 'err' ? 4500 : 2600);
  }

  function sheet(html, mount, onClose) {
    const ov = document.createElement('div');
    ov.className = 'overlay';
    ov.innerHTML = `<div class="sheet" role="dialog"><div class="grab"></div>${html}</div>`;
    let closed = false;
    const close = () => {
      if (closed) return;
      closed = true;
      ov.remove();
      if (!$('.overlay')) document.body.style.overflow = '';
      onClose?.();
    };
    ov.addEventListener('click', (e) => { if (e.target === ov || e.target.closest('[data-close]')) close(); });
    document.body.appendChild(ov);
    document.body.style.overflow = 'hidden';
    mount?.(ov.querySelector('.sheet'), close);
    return close;
  }

  function confirmBox(title, text, { ok = 'Xác nhận', danger = false } = {}) {
    return new Promise((resolve) => {
      let result = false;
      sheet(`
        <div class="sheet-head"><h2>${esc(title)}</h2></div>
        <p class="text-2" style="margin:0 0 18px">${text}</p>
        <div class="grid grid-2"><button class="btn" data-close>Huỷ</button><button class="btn ${danger ? 'danger' : 'primary'}" data-ok>${esc(ok)}</button></div>`,
      (el, c) => { el.querySelector('[data-ok]').onclick = () => { result = true; c(); }; },
      () => resolve(result));
    });
  }

  async function withBtn(btn, fn) {
    if (!btn) return fn();
    const old = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner"></div>';
    try { return await fn(); } finally { btn.disabled = false; btn.innerHTML = old; }
  }

  const formData = (form) => {
    const o = {};
    for (const el of form.elements) {
      if (!el.name) continue;
      if (el.type === 'checkbox') o[el.name] = el.checked;
      else if (el.type === 'radio') { if (el.checked) o[el.name] = el.value; }
      else o[el.name] = el.value;
    }
    return o;
  };

  const sw = (name, checked, title, desc = '') => `
    <label class="switch-row"><div class="txt"><b>${title}</b>${desc ? `<span>${desc}</span>` : ''}</div>
    <div class="switch"><input type="checkbox" name="${name}" ${checked ? 'checked' : ''}><span></span></div></label>`;

  const empty = (ic, text) => `<div class="list-empty">${icon(ic)}<div>${text}</div></div>`;
  const loading = () => `<div class="stack"><div class="skeleton" style="height:90px"></div><div class="skeleton" style="height:200px"></div></div>`;

  // Biểu đồ cột 1 chuỗi (tin nhắn theo ngày)
  function barChart(data, key = 'messages', label = 'tin nhắn') {
    const W = 600, H = 170, padL = 30, padB = 22, padT = 8;
    const max = Math.max(1, ...data.map((d) => d[key]));
    const nice = (() => { const p = Math.pow(10, Math.floor(Math.log10(max))); const n = Math.ceil(max / p); return (n <= 2 ? 2 : n <= 5 ? 5 : 10) * p; })();
    const iw = W - padL, ih = H - padB - padT;
    const bw = iw / data.length;
    const barW = Math.max(4, Math.min(28, bw - 6));
    let g = '';
    [0, 0.5, 1].forEach((f) => {
      const y = padT + ih - ih * f;
      g += `<line class="grid-line" x1="${padL}" x2="${W}" y1="${y}" y2="${y}"/><text x="${padL - 6}" y="${y + 3}" text-anchor="end">${num(Math.round(nice * f))}</text>`;
    });
    data.forEach((d, i) => {
      const v = d[key];
      const h = Math.max(v > 0 ? 2 : 0, (v / nice) * ih);
      const x = padL + i * bw + (bw - barW) / 2;
      const y = padT + ih - h;
      const r = Math.min(4, barW / 2, h);
      const path = h > 0 ? `M${x},${padT + ih} V${y + r} Q${x},${y} ${x + r},${y} H${x + barW - r} Q${x + barW},${y} ${x + barW},${y + r} V${padT + ih} Z` : '';
      const [yy, mm, dd] = d.day.split('-');
      g += `<g data-tip="${dd}/${mm}: ${num(v)} ${label}" data-x="${x + barW / 2}" data-y="${y}">
        <rect class="bar-hit" x="${padL + i * bw}" y="${padT}" width="${bw}" height="${ih + padB}"/>
        ${path ? `<path class="bar" d="${path}"/>` : ''}
        ${i % 2 === (data.length - 1) % 2 ? `<text x="${x + barW / 2}" y="${H - 6}" text-anchor="middle">${dd}/${mm}</text>` : ''}</g>`;
    });
    return `<div class="chart" data-chart><svg viewBox="0 0 ${W} ${H}" role="img" aria-label="Biểu đồ ${label} 14 ngày">${g}</svg></div>`;
  }
  function bindCharts(root) {
    $$('[data-chart]', root).forEach((c) => {
      const svg = c.querySelector('svg');
      let tip;
      const show = (g) => {
        if (!tip) { tip = document.createElement('div'); tip.className = 'tip'; c.appendChild(tip); }
        const sc = svg.clientWidth / svg.viewBox.baseVal.width;
        tip.textContent = g.dataset.tip;
        tip.style.left = Math.min(Math.max(g.dataset.x * sc, 50), svg.clientWidth - 50) + 'px';
        tip.style.top = g.dataset.y * sc + 'px';
        tip.style.display = 'block';
      };
      $$('g[data-tip]', svg).forEach((g) => {
        g.addEventListener('mouseenter', () => show(g));
        g.addEventListener('click', () => show(g));
      });
      svg.addEventListener('mouseleave', () => tip && (tip.style.display = 'none'));
    });
  }

  // ================= Router =================
  const routes = [];
  const route = (pattern, view, opts = {}) => routes.push({ re: new RegExp('^' + pattern.replace(/:(\w+)/g, '(?<$1>[^/]+)') + '$'), view, ...opts });
  const go = (path) => { if (location.hash !== '#' + path) location.hash = path; else render(); };
  const current = () => location.hash.slice(1) || '/';
  let renderSeq = 0;

  async function render() {
    $$('.overlay').forEach((o) => o.remove());
    document.body.style.overflow = '';
    const path = current();
    const seq = ++renderSeq;
    if (!S.status || !S.status.installed) {
      if (S.status && !S.status.installed) return renderInstall();
    }
    const publicPaths = ['/login', '/register'];
    if (!S.user && !publicPaths.includes(path)) return go('/login');
    if (S.user && publicPaths.includes(path)) return go('/');
    if (publicPaths.includes(path)) return path === '/login' ? renderLogin() : renderRegister();

    let r, params = {};
    for (const x of routes) { const m = path.match(x.re); if (m) { r = x; params = m.groups || {}; break; } }
    if (!r) return go('/');
    if (r.admin && S.user.role !== 'admin') return go('/');

    shell(r.nav || '', r.title || '', r.back);
    // Thay #main bằng phần tử mới để gỡ toàn bộ sự kiện của trang trước
    // (tránh một lần bấm chạy nhiều lần, mở nhiều cửa sổ chồng nhau)
    const old = $('#main');
    const main = old.cloneNode(false);
    old.replaceWith(main);
    stopTimers();
    main.innerHTML = loading();
    try {
      const out = await r.view(params);
      if (seq !== renderSeq) return;
      if (out.title !== undefined) $('#page-title').textContent = out.title;
      if (out.actions) $('#page-actions').innerHTML = out.actions;
      main.innerHTML = `<div class="fade-in">${out.html}</div>`;
      bindCharts(main);
      out.mount?.(main);
      window.scrollTo(0, 0);
    } catch (e) {
      if (seq !== renderSeq) return;
      main.innerHTML = `<div class="alert err" style="margin-top:12px">${icon('alert')}<div>${esc(e.message)}</div></div>`;
    }
  }

  function navItems() {
    if (S.user.role === 'admin') {
      return {
        tab: [['/', 'home', 'Tổng quan'], ['/groups', 'users', 'Nhóm'], ['/bots', 'bot', 'Bot'], ['/logs', 'list', 'Nhật ký'], ['/more', 'grid', 'Thêm']],
        side: [['/', 'home', 'Tổng quan'], ['/groups', 'users', 'Nhóm'], ['/bots', 'bot', 'Bot'], ['/rules', 'terminal', 'Lệnh tự tạo'], ['/logs', 'list', 'Nhật ký'], ['/users', 'key', 'Tài khoản web'], ['/system', 'settings', 'Hệ thống & cập nhật'], ['/me', 'star', 'Điểm của tôi'], ['/account', 'user', 'Tài khoản']],
      };
    }
    const m = [['/', 'star', 'Của tôi'], ['/rank', 'trophy', 'Xếp hạng'], ['/account', 'user', 'Tài khoản']];
    return { tab: m, side: m };
  }

  function shell(nav, title, back) {
    if (!$('#main')) {
      $('#app').innerHTML = `
      <div class="layout">
        <aside class="sidebar" id="sidebar"></aside>
        <div>
          <header class="topbar" id="topbar">
            <div id="page-back"></div>
            <div class="title ellipsis" id="page-title"></div>
            <div class="row" id="page-actions"></div>
          </header>
          <main class="main" id="main"></main>
        </div>
      </div>
      <nav class="tabbar" id="tabbar"></nav>`;
      window.addEventListener('scroll', () => $('#topbar')?.classList.toggle('scrolled', window.scrollY > 4), { passive: true });
    }
    const items = navItems();
    const path = current();
    const isActive = (p) => (p === '/' ? path === '/' : path === p || path.startsWith(p + '/') || (p === '/groups' && path.startsWith('/group/')) || (p === '/more' && ['/users', '/system', '/account', '/me', '/rules'].some((x) => path.startsWith(x))));
    $('#tabbar').innerHTML = items.tab.map(([p, ic, t]) => `<a href="#${p}" class="${isActive(p) ? 'active' : ''}">${icon(ic)}<span>${t}</span></a>`).join('');
    $('#sidebar').innerHTML = `<div class="brand"><img src="assets/icon.svg" alt="">${esc(S.site)}</div>`
      + items.side.map(([p, ic, t]) => `<a href="#${p}" class="nav ${isActive(p) && !(p === '/more') ? 'active' : ''}">${icon(ic)}${t}</a>`).join('')
      + `<div class="foot stack"><a class="nav" href="javascript:void 0" data-theme-toggle>${icon('moon')}Giao diện sáng/tối</a><a class="nav" href="javascript:void 0" data-logout>${icon('logout')}Đăng xuất</a>
         <div class="row" style="padding:8px 12px">${avatar(S.user.display_name, S.user.id)}<div class="grow"><div class="ellipsis" style="font-weight:600">${esc(S.user.display_name)}</div><div class="xs muted">${S.user.role === 'admin' ? 'Quản trị viên' : 'Thành viên'}</div></div></div></div>`;
    $('#page-title').textContent = title;
    $('#page-actions').innerHTML = '';
    $('#page-back').innerHTML = back ? `<a class="btn icon ghost" href="#${back}" aria-label="Quay lại">${icon('back')}</a>` : `<div class="brand only-mobile"><img src="assets/icon.svg" alt=""></div>`;
  }

  // Global handlers
  document.addEventListener('click', async (e) => {
    if (e.target.closest('[data-theme-toggle]')) { toggleTheme(); return; }
    if (e.target.closest('[data-logout]')) {
      const d = await api('auth/logout', {});
      S.csrf = d.csrf; S.user = null; $('#app').innerHTML = ''; go('/login');
    }
    const cp = e.target.closest('[data-copy]');
    if (cp) { navigator.clipboard?.writeText(cp.dataset.copy).then(() => toast('Đã sao chép')); }
  });
  // Esc đóng cửa sổ trên cùng
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const ovs = $$('.overlay');
    const top = ovs[ovs.length - 1];
    if (!top) return;
    const btn = top.querySelector('[data-close]');
    if (btn) btn.click(); else top.click();
  });
  function toggleTheme() {
    const cur = document.documentElement.dataset.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    try { localStorage.setItem('theme', next); } catch {}
  }

  // ================= Auth views =================
  function authWrap(inner) {
    $('#app').innerHTML = `<div class="auth"><div class="auth-card fade-in">
      <img class="auth-logo" src="assets/icon.svg" alt="">
      ${inner}</div></div>`;
  }

  function renderLogin() {
    authWrap(`
      <h1 class="center">${esc(S.site)}</h1>
      <p class="center muted" style="margin:6px 0 22px">Đăng nhập để quản lý nhóm Telegram</p>
      <form class="card card-pad stack" id="f">
        <label class="field"><span class="lbl">Tên đăng nhập</span><div class="input-icon">${icon('user')}<input class="input" name="username" autocomplete="username" required></div></label>
        <label class="field"><span class="lbl">Mật khẩu</span><div class="input-icon">${icon('lock')}<input class="input" type="password" name="password" autocomplete="current-password" required></div></label>
        <button class="btn primary block" style="height:46px">Đăng nhập</button>
      </form>
      ${S.allowRegister ? `<p class="center small" style="margin-top:16px">Chưa có tài khoản? <a href="#/register">Đăng ký thành viên</a></p>` : ''}`);
    $('#f').onsubmit = async (e) => {
      e.preventDefault();
      const btn = e.target.querySelector('button');
      await withBtn(btn, async () => {
        try {
          const d = await api('auth/login', formData(e.target), { noRedirect: true });
          S.user = d.user; $('#app').innerHTML = ''; go('/');
        } catch (err) { toast(err.message, 'err'); }
      });
    };
  }

  function renderRegister() {
    authWrap(`
      <h1 class="center">Đăng ký thành viên</h1>
      <p class="center muted" style="margin:6px 0 22px">Sau khi đăng ký, liên kết Telegram để xem điểm tương tác</p>
      <form class="card card-pad stack" id="f">
        <label class="field"><span class="lbl">Tên hiển thị</span><input class="input" name="display_name" maxlength="60"></label>
        <label class="field"><span class="lbl">Tên đăng nhập</span><input class="input" name="username" autocomplete="username" required pattern="[a-zA-Z0-9_.]{3,32}"><div class="hint">3-32 ký tự: chữ, số, dấu _ hoặc .</div></label>
        <label class="field"><span class="lbl">Mật khẩu</span><input class="input" type="password" name="password" autocomplete="new-password" required minlength="6"></label>
        <button class="btn primary block" style="height:46px">Tạo tài khoản</button>
      </form>
      <p class="center small" style="margin-top:16px">Đã có tài khoản? <a href="#/login">Đăng nhập</a></p>`);
    $('#f').onsubmit = async (e) => {
      e.preventDefault();
      await withBtn(e.target.querySelector('button'), async () => {
        try { const d = await api('auth/register', formData(e.target), { noRedirect: true }); S.user = d.user; $('#app').innerHTML = ''; go('/'); }
        catch (err) { toast(err.message, 'err'); }
      });
    };
  }

  function renderInstall() {
    const c = S.status.checks || {};
    const ck = (ok, t) => `<div>${ok ? `<span style="color:var(--success)">${icon('check', 'sm')}</span>` : `<span style="color:var(--danger)">${icon('x', 'sm')}</span>`}${t}</div>`;
    authWrap(`
      <h1 class="center">Cài đặt Tele Manager</h1>
      <p class="center muted" style="margin:6px 0 18px">Thiết lập lần đầu – chỉ mất 1 phút</p>
      <div class="card card-pad" style="margin-bottom:12px"><div class="check-list">
        ${ck(true, 'PHP ' + esc(S.status.php))}${ck(c.curl, 'cURL')}${ck(c.mbstring, 'mbstring')}${ck(c.pdo_sqlite || c.pdo_mysql, 'PDO SQLite / MySQL')}${ck(c.zip, 'Zip (để cập nhật từ GitHub)')}${ck(c.writable, 'Quyền ghi thư mục data/')}
      </div></div>
      <form class="card card-pad stack" id="f">
        <label class="field"><span class="lbl">Tên website</span><input class="input" name="site_name" value="Tele Manager"></label>
        <div class="field"><span class="lbl">Cơ sở dữ liệu</span>
          <div class="segmented" id="drv"><button type="button" data-v="sqlite" class="active">SQLite (đơn giản)</button><button type="button" data-v="mysql">MySQL</button></div>
          <input type="hidden" name="driver" value="sqlite">
        </div>
        <div id="mysql" class="stack hidden">
          <div class="alert info">${icon('info')}<div>Tạo database trong aaPanel → <b>Databases</b> → <b>Add database</b>, rồi nhập thông tin dưới đây.</div></div>
          <div class="grid grid-2"><label class="field"><span class="lbl">Host</span><input class="input" name="db_host" value="localhost"></label><label class="field"><span class="lbl">Port</span><input class="input" name="db_port" value="3306" inputmode="numeric"></label></div>
          <label class="field"><span class="lbl">Tên database</span><input class="input" name="db_name"></label>
          <label class="field"><span class="lbl">User</span><input class="input" name="db_user"></label>
          <label class="field"><span class="lbl">Mật khẩu</span><input class="input" type="password" name="db_pass"></label>
        </div>
        <h3 style="margin-top:18px">Tài khoản quản trị</h3>
        <label class="field"><span class="lbl">Tên đăng nhập</span><input class="input" name="username" required pattern="[a-zA-Z0-9_.]{3,32}" value="admin"></label>
        <label class="field"><span class="lbl">Mật khẩu</span><input class="input" type="password" name="password" required minlength="6"></label>
        <button class="btn primary block" style="height:46px">Cài đặt</button>
      </form>`);
    $$('#drv button').forEach((b) => b.onclick = () => {
      $$('#drv button').forEach((x) => x.classList.toggle('active', x === b));
      $('[name=driver]').value = b.dataset.v;
      $('#mysql').classList.toggle('hidden', b.dataset.v !== 'mysql');
    });
    $('#f').onsubmit = async (e) => {
      e.preventDefault();
      await withBtn(e.target.querySelector('button[type=submit],button:not([type])'), async () => {
        try {
          const d = await api('install', formData(e.target));
          S.user = d.user;
          await boot();
          toast('Cài đặt thành công!');
          go('/bots');
        } catch (err) { toast(err.message, 'err'); }
      });
    };
  }

  // ================= Admin: Dashboard =================
  route('/', async () => (S.user.role === 'admin' ? viewDashboard() : viewMe()), { title: '' });

  async function viewDashboard() {
    const d = await api('dashboard');
    const st = d.stats;
    const total14 = d.chart.reduce((a, b) => a + b.messages, 0);
    const stat = (ic, cls, label, v, sub = '') => `<div class="card stat"><div class="label"><span class="ico ${cls}">${icon(ic)}</span>${label}</div><div class="value">${num(v)}</div>${sub ? `<div class="sub">${sub}</div>` : ''}</div>`;
    return {
      title: 'Tổng quan',
      actions: `<button class="btn icon ghost only-mobile" data-theme-toggle aria-label="Đổi giao diện">${icon('moon')}</button>`,
      html: `
      ${!d.has_bot ? `<a href="#/bots" class="alert warn" style="margin:4px 0 14px;color:inherit">${icon('bot')}<div><b>Chưa có bot nào.</b> Nhấn để thêm bot Telegram (lấy token từ @BotFather).</div></a>` : ''}
      <div class="hero" style="margin-top:4px">
        <div class="muted small">Xin chào, ${esc(S.user.display_name)} 👋</div>
        <div class="row" style="align-items:flex-end;margin-top:6px"><div class="big">${num(st.messages_today)}</div><div class="muted" style="padding-bottom:7px">tin nhắn hôm nay</div></div>
        <div class="row wrap small" style="margin-top:8px;gap:14px"><span>${icon('users', 'sm')} ${num(st.groups)} nhóm</span><span>${icon('bot', 'sm')} ${num(st.bots)} bot</span><span>${icon('plus', 'sm')} ${num(st.joins_today)} người mới</span></div>
      </div>
      <div class="grid grid-4" style="margin-top:12px">
        ${stat('users', '', 'Thành viên', st.members, 'trong tất cả nhóm')}
        ${stat('shield', 'amber', 'Xử lý 24h', st.actions_24h, 'cảnh cáo, mute, ban…')}
        ${stat('mute', 'amber', 'Đang cấm chat', st.muted)}
        ${stat('ban', 'red', 'Đã bị cấm', st.banned)}
      </div>
      <div class="card" style="margin-top:12px">
        <div class="card-head"><div><h3>Tin nhắn 14 ngày</h3><div class="xs muted">Tổng ${num(total14)} tin nhắn</div></div></div>
        <div class="card-body">${barChart(d.chart)}</div>
      </div>
      <div class="grid md-2" style="margin-top:0">
        <div>
          <div class="section-title"><h2>Tương tác cao nhất</h2></div>
          <div class="list">${d.top_members.length ? d.top_members.map((m, i) => `
            <a class="list-item" href="#/member/${m.id}"><div class="rank r${i + 1}">${i + 1}</div>${avatar(m.name, m.tg_user_id)}
            <div class="grow"><div class="ellipsis" style="font-weight:600">${esc(m.name)}</div><div class="xs muted ellipsis">${esc(m.group)}</div></div>
            <div class="points">${num(m.points)}</div></a>`).join('') : empty('trophy', 'Chưa có dữ liệu')}</div>
        </div>
        <div>
          <div class="section-title"><h2>Hoạt động gần đây</h2><a href="#/logs" class="small">Xem tất cả</a></div>
          <div class="list">${d.recent_logs.length ? d.recent_logs.map(logItem).join('') : empty('list', 'Chưa có hoạt động')}</div>
        </div>
      </div>`,
    };
  }

  function logItem(l) {
    const [t, ic, c] = ACTION[l.action] || [l.action, 'info', ''];
    return `<div class="log-item"><div class="log-ico ${c}">${icon(ic)}</div><div class="grow">
      <div class="small"><b>${esc(t)}</b>${l.target_name ? ` · ${esc(l.target_name)}` : ''}</div>
      ${l.detail ? `<div class="xs text-2" style="overflow-wrap:anywhere">${esc(l.detail)}</div>` : ''}
      <div class="xs muted">${esc(l.actor || '')}${l.group_title ? ' · ' + esc(l.group_title) : ''} · ${ago(l.created_at)}</div></div></div>`;
  }

  // ================= Admin: Groups =================
  route('/groups', async () => {
    const rows = await api('groups');
    const card = (g) => `
      <a class="list-item" href="#/group/${g.id}">
        ${avatar(g.title, g.chat_id, 'sq')}
        <div class="grow"><div class="row" style="gap:6px"><span class="ellipsis" style="font-weight:600">${esc(g.title)}</span>${g.active ? '' : '<span class="badge red">Đã rời</span>'}${g.active && !g.bot_is_admin ? '<span class="badge amber">Bot chưa là admin</span>' : ''}</div>
        <div class="xs muted">${num(g.member_count || g.tracked)} thành viên · ${num(g.messages_today)} tin hôm nay${g.bot_username ? ' · @' + esc(g.bot_username) : ''}</div></div>
        <span class="chev">${icon('chevron')}</span></a>`;
    return {
      title: 'Nhóm',
      html: rows.length ? `<div class="list">${rows.map(card).join('')}</div>
        <p class="xs muted center" style="margin-top:14px">Thêm bot vào nhóm Telegram, nhóm sẽ tự xuất hiện ở đây.</p>`
        : `<div class="card">${empty('users', 'Chưa có nhóm nào.<br>Hãy thêm bot vào nhóm Telegram và cấp quyền quản trị.')}</div>`,
    };
  }, { admin: true, title: 'Nhóm' });

  route('/group/:id', (p) => viewGroup(p.id, 'overview'), { admin: true, back: '/groups' });
  route('/group/:id/:tab', (p) => viewGroup(p.id, p.tab), { admin: true, back: '/groups' });

  async function viewGroup(id, tab) {
    const d = await api('group', undefined, { query: { id } });
    const g = d.group;
    const tabs = [['overview', 'Tổng quan'], ['chat', '💬 Chat'], ['members', 'Thành viên'], ['settings', 'Cài đặt'], ['rules', 'Lệnh'], ['send', 'Gửi tin'], ['logs', 'Nhật ký']];
    const tabsHtml = `<div class="tabs">${tabs.map(([k, t]) => `<button data-tab="${k}" class="${k === tab ? 'active' : ''}">${t}</button>`).join('')}</div>`;
    let body = '';
    let mount = () => {};
    if (tab === 'members') ({ body, mount } = groupMembers(d));
    else if (tab === 'settings') ({ body, mount } = groupSettings(d));
    else if (tab === 'send') ({ body, mount } = groupSend(d));
    else if (tab === 'logs') ({ body, mount } = groupLogs(d));
    else if (tab === 'rules') ({ body, mount } = rulesBlock(g.id));
    else if (tab === 'chat') ({ body, mount } = groupChat(d));
    else ({ body, mount } = groupOverview(d));
    return {
      title: g.title,
      html: tabsHtml + `<div id="tabbody">${body}</div>`,
      mount: (root) => {
        $$('[data-tab]', root).forEach((b) => b.onclick = () => go(`/group/${id}/${b.dataset.tab}`));
        $('[data-tab].active', root)?.scrollIntoView({ block: 'nearest', inline: 'center' });
        mount(root);
      },
    };
  }

  function groupOverview(d) {
    const g = d.group, c = d.counts;
    const body = `
      ${!g.active ? `<div class="alert err" style="margin-bottom:12px">${icon('alert')}<div>Bot không còn trong nhóm này.</div></div>` : !g.bot_is_admin ? `<div class="alert warn" style="margin-bottom:12px">${icon('alert')}<div>Bot <b>chưa là quản trị viên</b>. Hãy cấp quyền <b>Xoá tin nhắn</b> và <b>Cấm thành viên</b> để bot hoạt động đầy đủ, rồi nhấn <b>Đồng bộ</b>.</div></div>` : ''}
      <div class="card card-pad">
        <div class="row">${avatar(g.title, g.chat_id, 'lg sq')}<div class="grow"><h2 class="ellipsis">${esc(g.title)}</h2>
        <div class="xs muted mono">${esc(g.chat_id)}</div>
        <div class="row wrap" style="gap:6px;margin-top:6px"><span class="badge">${g.type === 'supergroup' ? 'Siêu nhóm' : 'Nhóm'}</span>${g.username ? `<a class="badge blue" target="_blank" rel="noopener" href="https://t.me/${esc(g.username)}">@${esc(g.username)}</a>` : ''}${g.bot_username ? `<span class="badge">${icon('bot', 'sm')}@${esc(g.bot_username)}</span>` : ''}</div></div></div>
        <div class="grid grid-2" style="margin-top:14px"><button class="btn soft sm" data-sync>${icon('refresh', 'sm')}Đồng bộ</button><a class="btn sm" href="#/group/${g.id}/send">${icon('send', 'sm')}Gửi tin</a></div>
      </div>
      <div class="grid grid-3" style="margin-top:12px">
        <div class="card stat"><div class="label">Thành viên</div><div class="value">${num(g.member_count)}</div><div class="sub">${num(g.tracked)} đã ghi nhận</div></div>
        <div class="card stat"><div class="label">Tổng điểm</div><div class="value">${num(c.points)}</div></div>
        <div class="card stat"><div class="label">Quản trị viên</div><div class="value">${num(c.admins)}</div></div>
        <div class="card stat"><div class="label">Bị cảnh cáo</div><div class="value">${num(c.warned)}</div></div>
        <div class="card stat"><div class="label">Cấm chat</div><div class="value">${num(c.muted)}</div></div>
        <div class="card stat"><div class="label">Bị cấm</div><div class="value">${num(c.banned)}</div></div>
      </div>
      <div class="card" style="margin-top:12px"><div class="card-head"><h3>Tin nhắn 14 ngày</h3></div><div class="card-body">${barChart(d.chart)}</div></div>
      <div class="section-title"><h2>Vùng nguy hiểm</h2></div>
      <div class="list">
        <button class="list-item" data-resetpts>${icon('star')}<div class="grow">Đặt lại điểm toàn nhóm</div></button>
        ${g.active ? `<button class="list-item" data-leave style="color:var(--danger)">${icon('kick')}<div class="grow">Cho bot rời nhóm</div></button>` : ''}
        <button class="list-item" data-del style="color:var(--danger)">${icon('trash')}<div class="grow">Xoá nhóm khỏi web (xoá dữ liệu)</div></button>
      </div>`;
    const mount = (root) => {
      $('[data-sync]', root).onclick = (e) => withBtn(e.currentTarget, async () => {
        try { const r = await api('group/sync', { id: g.id }); toast(`Đã đồng bộ: ${r.member_count} thành viên, ${r.admins} admin`); render(); } catch (err) { toast(err.message, 'err'); }
      });
      $('[data-resetpts]', root).onclick = async () => {
        if (!(await confirmBox('Đặt lại điểm?', 'Toàn bộ điểm tương tác của thành viên trong nhóm sẽ về 0.', { danger: true, ok: 'Đặt lại' }))) return;
        await api('group/reset-points', { id: g.id }); toast('Đã đặt lại điểm'); render();
      };
      const lv = $('[data-leave]', root);
      if (lv) lv.onclick = async () => {
        if (!(await confirmBox('Cho bot rời nhóm?', 'Bot sẽ rời khỏi nhóm Telegram này.', { danger: true, ok: 'Rời nhóm' }))) return;
        await api('group/leave', { id: g.id }); toast('Bot đã rời nhóm'); render();
      };
      $('[data-del]', root).onclick = async () => {
        if (!(await confirmBox('Xoá nhóm?', 'Toàn bộ dữ liệu thành viên, điểm và nhật ký của nhóm sẽ bị xoá vĩnh viễn.', { danger: true, ok: 'Xoá' }))) return;
        await api('group/delete', { id: g.id }); toast('Đã xoá nhóm'); go('/groups');
      };
    };
    return { body, mount };
  }

  function memberRowHtml(m) {
    const extra = [];
    if (m.warns) extra.push(`${icon('alert', 'sm')}${m.warns}`);
    return `<button class="list-item" data-member="${m.id}">${avatar(m.name, m.tg_user_id)}
      <div class="grow"><div class="row" style="gap:6px"><span class="ellipsis" style="font-weight:600">${esc(m.name)}</span>${statusBadge(m)}</div>
      <div class="xs muted ellipsis">${m.username ? '@' + esc(m.username) + ' · ' : ''}${num(m.messages)} tin · ${ago(m.last_active)}</div></div>
      <div style="text-align:right"><div class="points">${num(m.points)}</div><div class="xs muted">${m.warns ? `<span style="color:var(--warning)">⚠ ${m.warns}</span>` : 'điểm'}</div></div></button>`;
  }

  function groupMembers(d) {
    const g = d.group;
    const st = { q: '', status: '', sort: 'points', page: 1 };
    const filters = [['', 'Tất cả'], ['active', 'Trong nhóm'], ['admin', 'Admin'], ['warned', 'Bị cảnh cáo'], ['muted', 'Cấm chat'], ['banned', 'Bị cấm'], ['left', 'Đã rời']];
    const body = `
      <div class="row" style="margin-bottom:10px"><div class="input-icon grow">${icon('search')}<input class="input" id="q" placeholder="Tìm tên, @username, ID…"></div>
        <select class="input" id="sort" style="width:auto"><option value="points">Điểm</option><option value="messages">Tin nhắn</option><option value="recent">Hoạt động</option><option value="joined">Mới vào</option><option value="warns">Cảnh cáo</option></select></div>
      <div class="chips" style="margin-bottom:12px">${filters.map(([k, t]) => `<button class="chip ${k === '' ? 'active' : ''}" data-f="${k}">${t}</button>`).join('')}</div>
      <div class="row between" style="margin:0 2px 8px"><span class="small muted" id="total"></span><button class="btn sm ghost" data-addid>${icon('plus', 'sm')}Thêm theo ID</button></div>
      <div class="list" id="mlist">${loading()}</div>
      <div class="pager" id="pager"></div>`;
    const mount = (root) => {
      const load = async () => {
        const r = await api('members', undefined, { query: { group_id: g.id, ...st } });
        $('#total', root).textContent = `${num(r.total)} thành viên`;
        $('#mlist', root).innerHTML = r.rows.length ? r.rows.map(memberRowHtml).join('') : empty('users', 'Không có thành viên phù hợp');
        $('#pager', root).innerHTML = r.pages > 1 ? `<button class="btn sm" ${r.page <= 1 ? 'disabled' : ''} data-pg="-1">${icon('back', 'sm')}</button><span class="small">${r.page}/${r.pages}</span><button class="btn sm" ${r.page >= r.pages ? 'disabled' : ''} data-pg="1">${icon('chevron', 'sm')}</button>` : '';
      };
      $('#q', root).oninput = debounce((e) => { st.q = e.target.value; st.page = 1; load(); });
      $('#sort', root).onchange = (e) => { st.sort = e.target.value; st.page = 1; load(); };
      root.addEventListener('click', async (e) => {
        const f = e.target.closest('[data-f]');
        if (f) { $$('[data-f]', root).forEach((x) => x.classList.toggle('active', x === f)); st.status = f.dataset.f; st.page = 1; load(); }
        const pg = e.target.closest('[data-pg]');
        if (pg) { st.page += Number(pg.dataset.pg); load(); }
        const mb = e.target.closest('[data-member]');
        if (mb) openMember(mb.dataset.member, load);
        if (e.target.closest('[data-addid]')) {
          sheet(`<div class="sheet-head"><h2>Thêm thành viên theo ID</h2></div>
            <form class="stack" id="fa"><label class="field"><span class="lbl">ID Telegram</span><input class="input" name="tg_user_id" inputmode="numeric" required><div class="hint">Dùng để cấm trước một người chưa có trong danh sách. Xem ID bằng lệnh /id.</div></label>
            <button class="btn primary block">Thêm</button></form>`, (el, close) => {
            $('#fa', el).onsubmit = async (ev) => {
              ev.preventDefault();
              try { const r = await api('member/add', { id: g.id, ...formData(ev.target) }); close(); load(); openMember(r.member.id, load); } catch (err) { toast(err.message, 'err'); }
            };
          });
        }
      });
      load();
    };
    return { body, mount };
  }

  // Chi tiết thành viên + hành động
  async function openMember(id, onChange) {
    let d;
    try { d = await api('member', undefined, { query: { id } }); } catch (e) { return toast(e.message, 'err'); }
    const m = d.member;
    const act = (a, ic, t, cls = '') => `<button class="act ${cls}" data-a="${a}"><span class="ic">${icon(ic)}</span>${t}</button>`;
    const html = `
      <div class="row" style="margin-bottom:14px">${avatar(m.name, m.tg_user_id, 'lg')}
        <div class="grow"><h2 class="ellipsis">${esc(m.name)}</h2>
        <div class="small muted">${m.username ? `<a href="https://t.me/${esc(m.username)}" target="_blank" rel="noopener">@${esc(m.username)}</a> · ` : ''}<span class="mono" data-copy="${m.tg_user_id}" style="cursor:pointer">${m.tg_user_id}</span></div>
        <div class="row wrap" style="gap:6px;margin-top:4px">${statusBadge(m)}${m.is_bot ? '<span class="badge">Bot</span>' : ''}${d.web_user ? `<span class="badge blue">${icon('link', 'sm')}${esc(d.web_user.username)}</span>` : ''}</div></div>
        <button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <div class="grid grid-3x" style="gap:8px;margin-bottom:14px">
        <div class="card stat" style="padding:10px"><div class="label xs">Điểm</div><div class="value" style="font-size:20px;margin-top:2px">${num(m.points)}</div><div class="sub">Hạng #${d.rank}</div></div>
        <div class="card stat" style="padding:10px"><div class="label xs">Tin nhắn</div><div class="value" style="font-size:20px;margin-top:2px">${num(m.messages)}</div></div>
        <div class="card stat" style="padding:10px"><div class="label xs">Cảnh cáo</div><div class="value" style="font-size:20px;margin-top:2px;${m.warns ? 'color:var(--warning)' : ''}">${m.warns}/${d.warn_limit}</div></div>
      </div>
      <div class="actions" style="margin-bottom:14px">
        ${act('warn', 'alert', 'Cảnh cáo', 'amber')}
        ${m.warns ? act('unwarn', 'check', 'Gỡ CC', 'green') : act('points', 'star', 'Điểm')}
        ${m.status === 'muted' ? act('unmute', 'volume', 'Bỏ mute', 'green') : act('mute', 'mute', 'Cấm chat', 'amber')}
        ${act('kick', 'kick', 'Đuổi', 'red')}
        ${m.status === 'banned' ? act('unban', 'check', 'Gỡ cấm', 'green') : act('ban', 'ban', 'Cấm', 'red')}
        ${m.warns ? act('points', 'star', 'Điểm') : act('resetwarn', 'refresh', 'Xoá CC')}
        ${act('setpoints', 'edit', 'Đặt điểm')}
        ${act('note', 'note', 'Ghi chú')}
      </div>
      ${m.status === 'muted' ? `<div class="alert warn" style="margin-bottom:12px">${icon('mute')}<div>Cấm chat ${m.muted_until ? 'đến ' + fmtDate(m.muted_until, true) : 'vĩnh viễn'}</div></div>` : ''}
      ${m.note ? `<div class="alert info" style="margin-bottom:12px">${icon('note')}<div style="white-space:pre-wrap">${esc(m.note)}</div></div>` : ''}
      <dl class="kv card card-pad" style="margin:0 0 14px">
        <dt>Nhóm</dt><dd>${esc(d.group.title)}</dd>
        <dt>Tham gia</dt><dd>${fmtDate(m.joined_at)}</dd>
        <dt>Hoạt động</dt><dd>${ago(m.last_active)}</dd>
        ${d.other_groups.length ? `<dt>Nhóm khác</dt><dd>${d.other_groups.map((o) => `${esc(o.title)} (${num(o.points)}đ)`).join(', ')}</dd>` : ''}
      </dl>
      <h3 style="margin-bottom:8px">Lịch sử xử lý</h3>
      <div class="list">${d.logs.length ? d.logs.map(logItem).join('') : empty('list', 'Chưa có lịch sử')}</div>`;
    sheet(html, (el, close) => {
      el.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-a]');
        if (!b) return;
        const a = b.dataset.a;
        const refresh = () => { close(); onChange?.(); openMember(id, onChange); };
        if (a === 'note') return noteForm(m, refresh);
        actionForm(a, m, d, refresh);
      });
    });
  }

  function noteForm(m, done) {
    sheet(`<div class="sheet-head"><h2>Ghi chú nội bộ</h2></div><form class="stack" id="fn">
      <textarea class="input" name="note" placeholder="Chỉ quản trị viên thấy…">${esc(m.note || '')}</textarea><button class="btn primary block">Lưu</button></form>`, (el, close) => {
      $('#fn', el).onsubmit = async (e) => { e.preventDefault(); await api('member/note', { id: m.id, ...formData(e.target) }); close(); toast('Đã lưu ghi chú'); done(); };
    });
  }

  function actionForm(a, m, d, done) {
    const titles = { warn: 'Cảnh cáo', unwarn: 'Gỡ 1 cảnh cáo', resetwarn: 'Xoá toàn bộ cảnh cáo', mute: 'Cấm chat', unmute: 'Bỏ cấm chat', kick: 'Đuổi khỏi nhóm', ban: 'Cấm vĩnh viễn', unban: 'Gỡ cấm', points: 'Cộng / trừ điểm', setpoints: 'Đặt điểm' };
    const danger = ['ban', 'kick'].includes(a);
    let fields = '';
    if (a === 'mute') {
      const opts = [[10, '10 phút'], [60, '1 giờ'], [360, '6 giờ'], [1440, '1 ngày'], [10080, '7 ngày'], [0, 'Vĩnh viễn']];
      fields += `<div class="field"><span class="lbl">Thời gian</span><div class="chips" style="flex-wrap:wrap">${opts.map(([v, t], i) => `<button type="button" class="chip ${i === 1 ? 'active' : ''}" data-min="${v}">${t}</button>`).join('')}</div>
        <input type="hidden" name="minutes" value="60"><div class="row" style="margin-top:8px"><input class="input" type="number" min="0" id="custom" placeholder="Hoặc nhập số phút"></div></div>`;
    }
    if (a === 'points') fields += `<div class="field"><span class="lbl">Số điểm</span><div class="row"><button type="button" class="btn icon" data-sign>${icon('minus')}</button><input class="input" type="number" name="amount" value="10" required style="text-align:center;font-weight:700;font-size:18px"><button type="button" class="btn icon" data-plus>${icon('plus')}</button></div><div class="hint">Số âm để trừ điểm. Hiện có ${num(m.points)} điểm.</div></div>`;
    if (a === 'setpoints') fields += `<label class="field"><span class="lbl">Điểm mới</span><input class="input" type="number" min="0" name="amount" value="${m.points}" required></label>`;
    if (['warn', 'mute', 'kick', 'ban', 'points'].includes(a)) fields += `<label class="field"><span class="lbl">Lý do (tuỳ chọn)</span><input class="input" name="reason" maxlength="200" placeholder="VD: Spam quảng cáo"></label>`;
    if (a !== 'setpoints') fields += `<label class="switch-row" style="padding:6px 0;border:0"><div class="txt"><b>Thông báo trong nhóm</b><span>Bot gửi tin nhắn thông báo vào nhóm</span></div><div class="switch"><input type="checkbox" name="notify" checked><span></span></div></label>`;
    const warnNote = a === 'warn' && m.warns + 1 >= d.warn_limit ? `<div class="alert warn">${icon('alert')}<div>Lần cảnh cáo này sẽ đạt giới hạn ${d.warn_limit} và tự động xử lý theo cài đặt nhóm.</div></div>` : '';
    sheet(`<div class="sheet-head"><h2>${titles[a]}</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <div class="row" style="margin-bottom:14px">${avatar(m.name, m.tg_user_id)}<div><b>${esc(m.name)}</b><div class="xs muted">${m.tg_user_id}</div></div></div>
      <form class="stack" id="fa">${warnNote}${fields}<button class="btn ${danger ? 'danger' : 'primary'} block" style="height:46px">${titles[a]}</button></form>`, (el, close) => {
      $$('[data-min]', el).forEach((c) => c.onclick = () => { $$('[data-min]', el).forEach((x) => x.classList.toggle('active', x === c)); $('[name=minutes]', el).value = c.dataset.min; $('#custom', el).value = ''; });
      const cu = $('#custom', el);
      if (cu) cu.oninput = () => { $$('[data-min]', el).forEach((x) => x.classList.remove('active')); $('[name=minutes]', el).value = cu.value; };
      const amt = $('[name=amount]', el);
      $('[data-sign]', el)?.addEventListener('click', () => { amt.value = -Math.abs(Number(amt.value) || 10); });
      $('[data-plus]', el)?.addEventListener('click', () => { amt.value = Math.abs(Number(amt.value) || 10); });
      $('#fa', el).onsubmit = async (e) => {
        e.preventDefault();
        await withBtn(e.target.querySelector('button:last-child'), async () => {
          try {
            await api('member/action', { id: m.id, action: a, ...formData(e.target) });
            close(); toast(titles[a] + ' thành công'); done();
          } catch (err) { toast(err.message, 'err'); }
        });
      };
    });
  }

  function groupSettings(d) {
    const s = d.settings, g = d.group;
    const num_ = (name, label, hint = '', min = 0) => `<label class="field"><span class="lbl">${label}</span><input class="input" type="number" min="${min}" name="${name}" value="${esc(s[name])}">${hint ? `<div class="hint">${hint}</div>` : ''}</label>`;
    const body = `
      <form id="fs" class="stack-lg">
        <div><div class="section-title" style="margin-top:0"><h2>👋 Chào mừng & tạm biệt</h2></div>
        <div class="card">
          ${sw('welcome_enabled', s.welcome_enabled, 'Gửi lời chào thành viên mới')}
          <div class="form-group"><label class="field"><span class="lbl">Nội dung chào mừng</span><textarea class="input" name="welcome_text">${esc(s.welcome_text)}</textarea>
          <div class="hint">Biến: <code>{mention}</code> <code>{name}</code> <code>{username}</code> <code>{group}</code> <code>{id}</code> · hỗ trợ HTML Telegram (&lt;b&gt;, &lt;i&gt;, &lt;a&gt;)</div></label></div>
          ${sw('goodbye_enabled', s.goodbye_enabled, 'Gửi lời tạm biệt khi rời nhóm')}
          <div class="form-group"><label class="field"><span class="lbl">Nội dung tạm biệt</span><input class="input" name="goodbye_text" value="${esc(s.goodbye_text)}"></label></div>
          ${sw('delete_service_messages', s.delete_service_messages, 'Xoá tin hệ thống', '"X đã tham gia", "X đã rời nhóm"…')}
        </div></div>

        <div><div class="section-title"><h2>📜 Nội quy</h2></div>
        <div class="card"><div class="form-group"><textarea class="input" name="rules_text" style="min-height:120px">${esc(s.rules_text)}</textarea><div class="hint">Hiển thị khi thành viên gõ /rules</div></div></div></div>

        <div><div class="section-title"><h2>🛡 Bảo vệ nhóm</h2></div>
        <div class="card">
          ${sw('anti_link', s.anti_link, 'Chặn link', 'Xoá tin nhắn chứa đường link')}
          <div class="form-group"><label class="field"><span class="lbl">Tên miền được phép</span><textarea class="input" name="link_whitelist" style="min-height:60px" placeholder="youtube.com&#10;t.me/nhomcuatoi">${esc((s.link_whitelist || []).join('\n'))}</textarea><div class="hint">Mỗi dòng một tên miền</div></label></div>
          ${sw('anti_forward', s.anti_forward, 'Chặn tin chuyển tiếp', 'Xoá tin forward từ kênh/người khác')}
          ${sw('anti_flood', s.anti_flood, 'Chống spam (flood)', 'Tự cấm chat khi gửi quá nhanh')}
          <div class="form-group grid grid-2">${num_('flood_limit', 'Số tin tối đa', '', 2)}${num_('flood_seconds', 'Trong (giây)', '', 1)}</div>
          <div class="form-group"><label class="field"><span class="lbl">Từ cấm</span><textarea class="input" name="banned_words" placeholder="Mỗi dòng một từ">${esc((s.banned_words || []).join('\n'))}</textarea><div class="hint">Tin nhắn chứa từ cấm sẽ bị xoá</div></label></div>
          ${sw('violation_warn', s.violation_warn, 'Cảnh cáo khi vi phạm', 'Mỗi vi phạm tính 1 lần cảnh cáo')}
          ${sw('admins_exempt', s.admins_exempt, 'Bỏ qua quản trị viên', 'Admin không bị áp dụng bộ lọc')}
        </div></div>

        <div><div class="section-title"><h2>🔒 Khoá nội dung</h2></div>
        <div class="card">
          ${sw('lock_photo', s.lock_photo, 'Ảnh')}${sw('lock_video', s.lock_video, 'Video')}${sw('lock_sticker', s.lock_sticker, 'Sticker')}${sw('lock_gif', s.lock_gif, 'GIF')}
          ${sw('lock_document', s.lock_document, 'Tệp tin')}${sw('lock_voice', s.lock_voice, 'Ghi âm / âm thanh')}${sw('lock_poll', s.lock_poll, 'Bình chọn')}
        </div></div>

        <div><div class="section-title"><h2>⚠️ Cảnh cáo</h2></div>
        <div class="card"><div class="form-group grid grid-2">
          ${num_('warn_limit', 'Giới hạn cảnh cáo', '0 = không giới hạn')}
          <label class="field"><span class="lbl">Khi đạt giới hạn</span><select class="input" name="warn_action">
            <option value="mute" ${s.warn_action === 'mute' ? 'selected' : ''}>Cấm chat</option><option value="kick" ${s.warn_action === 'kick' ? 'selected' : ''}>Đuổi</option><option value="ban" ${s.warn_action === 'ban' ? 'selected' : ''}>Cấm vĩnh viễn</option></select></label>
          </div><div class="form-group">${num_('mute_minutes', 'Thời gian cấm chat mặc định (phút)', '0 = vĩnh viễn')}</div></div></div>

        <div><div class="section-title"><h2>⭐ Điểm tương tác</h2></div>
        <div class="card">
          ${sw('points_enabled', s.points_enabled, 'Tính điểm tương tác', 'Cộng điểm khi thành viên nhắn tin')}
          <div class="form-group grid grid-2">${num_('points_per_message', 'Điểm mỗi tin')}${num_('points_cooldown', 'Giãn cách (giây)', 'Chống spam lấy điểm')}</div>
          <div class="form-group grid grid-2">${num_('points_min_length', 'Độ dài tối thiểu', 'ký tự')}${num_('points_per_join', 'Điểm mời thành viên', 'Thưởng người mời')}</div>
        </div></div>

        <div><div class="section-title"><h2>🤖 Lệnh bot</h2></div>
        <div class="card">
          ${sw('commands_enabled', s.commands_enabled, 'Bật lệnh trong nhóm')}
          ${sw('member_commands', s.member_commands, 'Cho thành viên dùng lệnh', '/me, /top, /rules, /info')}
          ${d.bots.length > 1 ? `<div class="form-group"><label class="field"><span class="lbl">Bot quản lý nhóm</span><select class="input" name="__bot">${d.bots.map((b) => `<option value="${b.id}" ${b.id === g.bot_id ? 'selected' : ''}>@${esc(b.username)}</option>`).join('')}</select></label></div>` : ''}
        </div></div>

        <div style="position:sticky;bottom:calc(var(--nav-h) + var(--safe-b) + 10px);z-index:5"><button class="btn primary block" style="height:48px">${icon('check')}Lưu cài đặt</button></div>
      </form>`;
    const mount = (root) => {
      $('#fs', root).onsubmit = async (e) => {
        e.preventDefault();
        const data = formData(e.target);
        const bot_id = data.__bot; delete data.__bot;
        await withBtn(e.target.querySelector('button.primary'), async () => {
          try { await api('group/settings', { id: g.id, settings: data, bot_id }); toast('Đã lưu cài đặt'); } catch (err) { toast(err.message, 'err'); }
        });
      };
    };
    return { body, mount };
  }


  // ================= Chat nhóm =================
  const linkify = (t) => esc(t).replace(/(https?:\/\/[^\s<]+|t\.me\/[^\s<]+)/g, (u) => `<a href="${u.startsWith('http') ? u : 'https://' + u}" target="_blank" rel="noopener noreferrer">${u}</a>`);
  const hhmm = (t) => { const d = new Date(t * 1000); return pad(d.getHours()) + ':' + pad(d.getMinutes()); };
  const dayLabel = (t) => {
    const d = new Date(t * 1000), n = new Date();
    const key = (x) => x.getFullYear() + '-' + x.getMonth() + '-' + x.getDate();
    if (key(d) === key(n)) return 'Hôm nay';
    const y = new Date(n); y.setDate(n.getDate() - 1);
    if (key(d) === key(y)) return 'Hôm qua';
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
  };
  const nameColor = (seed) => { let h = 0; for (const c of String(seed)) h = (h * 31 + c.charCodeAt(0)) >>> 0; return COLORS[h % COLORS.length]; };

  function groupChat(d) {
    const g = d.group;
    const body = `<div class="chat-wrap">
      <div class="chat-top" id="ctop"></div>
      <div class="chat" id="chat"><div class="stack"><div class="skeleton" style="height:56px;width:70%"></div><div class="skeleton" style="height:42px;width:55%"></div><div class="skeleton" style="height:64px;width:75%"></div></div></div>
      <form class="composer" id="composer">
        <div id="replybar"></div>
        <div class="row"><textarea rows="1" name="text" placeholder="Nhắn vào nhóm với tư cách bot…" aria-label="Nội dung"></textarea>
        <button class="btn primary icon" aria-label="Gửi">${icon('send')}</button></div>
      </form>
    </div>`;

    const mount = (root) => {
      const S2 = { msgs: [], byId: new Map(), hasMore: false, lastId: 0, since: 0, warnLimit: 3, replyTo: null, loadingMore: false, unseen: 0 };
      const chatEl = $('#chat', root);
      const nearBottom = () => window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 160;
      const toBottom = (smooth) => window.scrollTo({ top: document.documentElement.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });

      const tools = (m) => {
        if (m.kind === 'service') return '';
        const mb = m.member;
        const b = (a, ic, title, cls = '') => `<button type="button" data-ca="${a}" data-id="${m.id}" class="${cls}" title="${title}" aria-label="${title}">${icon(ic)}</button>`;
        let h = '';
        if (!m.deleted) h += b('del', 'trash', 'Xoá tin', 'red');
        if (mb && !m.is_bot && !mb.is_admin) {
          h += b('warn', 'alert', 'Cảnh cáo', 'amber');
          h += mb.status === 'muted' ? b('unmute', 'volume', 'Bỏ cấm chat') : b('mute', 'mute', 'Cấm chat', 'amber');
          h += mb.status === 'banned' ? b('unban', 'check', 'Gỡ cấm') : b('ban', 'ban', 'Cấm khỏi nhóm', 'red');
        }
        h += b('more', 'more', 'Thêm');
        return `<div class="tools">${h}</div>`;
      };

      const msgHtml = (m, prev) => {
        if (m.kind === 'service') return `<div class="svc" data-mid="${m.id}">${esc(m.text)} · ${hhmm(m.created_at)}</div>`;
        const first = !prev || prev.kind === 'service' || prev.tg_user_id !== m.tg_user_id || m.created_at - prev.created_at > 300 || dayLabel(prev.created_at) !== dayLabel(m.created_at);
        const color = nameColor(m.tg_user_id);
        const mb = m.member;
        const badges = [
          m.is_bot ? '<span class="badge blue" style="font-size:10px;padding:0 6px">BOT</span>' : '',
          mb?.is_admin ? '<span class="badge blue" style="font-size:10px;padding:0 6px">Admin</span>' : '',
          mb?.status === 'muted' ? '<span class="badge amber" style="font-size:10px;padding:0 6px">Cấm chat</span>' : '',
          mb?.status === 'banned' ? '<span class="badge red" style="font-size:10px;padding:0 6px">Bị cấm</span>' : '',
          mb?.warns ? `<span class="badge amber" style="font-size:10px;padding:0 6px">⚠ ${mb.warns}/${S2.warnLimit}</span>` : '',
        ].join('');
        const quote = m.reply_to ? `<div class="quote" data-jump="${m.reply_to}"><b>${esc(m.reply_name || '')}</b><span>${esc(m.reply_text || '…')}</span></div>` : '';
        const media = m.media && m.kind !== 'text' ? `<div class="media">${esc(m.media)}</div>` : '';
        const showText = m.text && !(m.kind !== 'text' && m.text === m.media);
        return `<div class="msg ${first ? 'first' : ''} ${m.is_bot ? 'bot' : ''} ${m.deleted ? 'deleted' : ''}" data-mid="${m.id}" data-tgmid="${m.message_id}">
          <div class="av">${first ? (m.is_bot ? `<div class="avatar" style="background:linear-gradient(135deg,var(--primary),var(--primary-2))">${icon('bot', 'sm')}</div>` : avatar(m.name, m.tg_user_id)) : ''}</div>
          <div class="body">
            <div class="bubble">
              ${first ? `<div class="who"><span class="n" style="color:${color}">${esc(m.name || 'Ẩn danh')}</span>${badges}</div>` : ''}
              ${quote}${media}${showText ? `<div class="txt">${linkify(m.text)}</div>` : ''}
              <div class="meta">${m.pinned ? '<span>📌</span>' : ''}${m.edited ? '<span>đã sửa</span>' : ''}<span>${hhmm(m.created_at)}</span></div>
            </div>
            ${m.deleted ? `<div class="del-note">🗑 Đã xoá${m.note ? ' · ' + esc(m.note) : ''}</div>` : ''}
            ${tools(m)}
          </div>
        </div>`;
      };

      const render = (mode) => {
        // Giữ vị trí cuộn: neo theo đáy trang (không giật khi thêm tin cũ/tin đổi)
        const fromBottom = document.documentElement.scrollHeight - window.scrollY;
        let html = '', prev = null, lastDay = '';
        for (const m of S2.msgs) {
          const day = dayLabel(m.created_at);
          if (day !== lastDay) { html += `<div class="day-sep">${day}</div>`; lastDay = day; prev = null; }
          html += msgHtml(m, prev);
          prev = m;
        }
        chatEl.innerHTML = S2.msgs.length ? html : `<div class="chat-empty">${icon('msg')}<div style="margin-top:6px">Chưa có tin nhắn nào được ghi nhận.<br><span class="xs">Tin nhắn mới trong nhóm sẽ hiện ở đây.</span></div></div>`;
        $('#ctop', root).innerHTML = S2.hasMore ? `<button class="btn sm soft" data-more>⬆ Xem thêm 30 tin cũ hơn</button>` : (S2.msgs.length ? '<span class="xs muted">— Đầu lịch sử chat (lưu 30 ngày) —</span>' : '');
        if (mode === 'bottom') toBottom(false);
        else window.scrollTo(0, document.documentElement.scrollHeight - fromBottom);
      };

      const add = (list) => { for (const m of list) { if (!S2.byId.has(m.id)) S2.msgs.push(m); S2.byId.set(m.id, m); } };
      const replaceIn = (list) => { for (const m of list) { const i = S2.msgs.findIndex((x) => x.id === m.id); if (i >= 0) S2.msgs[i] = m; S2.byId.set(m.id, m); } };

      const pill = () => {
        let p = $('#newpill');
        if (!S2.unseen) { p?.remove(); return; }
        if (!p) { p = document.createElement('button'); p.id = 'newpill'; p.className = 'btn primary sm new-pill'; p.onclick = () => { S2.unseen = 0; pill(); toBottom(true); }; root.appendChild(p); }
        p.innerHTML = `↓ ${S2.unseen} tin mới`;
      };
      const onScroll = () => { if (S2.unseen && nearBottom()) { S2.unseen = 0; pill(); } };
      window.addEventListener('scroll', onScroll, { passive: true });
      onLeave(() => { window.removeEventListener('scroll', onScroll); $('#newpill')?.remove(); });

      const load = async () => {
        try {
          const r = await api('chat', undefined, { query: { group_id: g.id } });
          S2.warnLimit = r.warn_limit; S2.hasMore = r.has_more; S2.since = r.server_time;
          add(r.messages);
          S2.lastId = S2.msgs.length ? S2.msgs[S2.msgs.length - 1].id : 0;
          render('bottom');
        } catch (e) { chatEl.innerHTML = `<div class="alert err">${icon('alert')}<div>${esc(e.message)}</div></div>`; }
      };

      const loadMore = async (btn) => {
        if (S2.loadingMore || !S2.msgs.length) return;
        S2.loadingMore = true;
        await withBtn(btn, async () => {
          try {
            const r = await api('chat', undefined, { query: { group_id: g.id, before_id: S2.msgs[0].id } });
            S2.hasMore = r.has_more;
            for (const m of r.messages) S2.byId.set(m.id, m);
            S2.msgs = r.messages.filter((m) => !S2.msgs.some((x) => x.id === m.id)).concat(S2.msgs);
            render('keep');
          } catch (e) { toast(e.message, 'err'); }
        });
        S2.loadingMore = false;
      };

      let polling = false;
      const poll = async () => {
        if (polling || document.hidden) return;
        polling = true;
        try {
          const r = await api('chat/poll', undefined, { query: { group_id: g.id, after_id: S2.lastId, since: S2.since } });
          const stick = nearBottom();
          S2.since = r.server_time;
          if (r.changed.length) replaceIn(r.changed);
          if (r.messages.length) {
            add(r.messages);
            S2.lastId = S2.msgs[S2.msgs.length - 1].id;
            if (!stick) S2.unseen += r.messages.filter((m) => m.kind !== 'service').length;
          }
          if (r.changed.length || r.messages.length) { render(stick && r.messages.length ? 'bottom' : 'keep'); pill(); }
        } catch {}
        polling = false;
      };
      every(poll, 4000);

      // Cập nhật trạng thái thành viên sau khi xử lý (badge, nút)
      const refreshMember = async (memberId) => {
        try {
          const r = await api('member', undefined, { query: { id: memberId } });
          const mm = r.member;
          for (const m of S2.msgs) if (m.member && m.member.id === mm.id) m.member = { id: mm.id, status: mm.status, is_admin: mm.is_admin, warns: mm.warns, points: mm.points };
          render('keep');
        } catch {}
      };

      const memberFor = (m) => ({ id: m.member.id, name: m.name, tg_user_id: m.tg_user_id, points: m.member.points, warns: m.member.warns, note: null });

      const setReply = (m) => {
        S2.replyTo = m;
        $('#replybar', root).innerHTML = m ? `<div class="reply-bar">${icon('reply', 'sm')}<div class="q"><b>Trả lời ${esc(m.name)}</b><span>${esc(m.text || m.media || '')}</span></div><button type="button" class="btn icon sm ghost" data-unreply>${icon('x', 'sm')}</button></div>` : '';
        if (m) $('#composer textarea', root).focus({ preventScroll: true });
      };

      const moreSheet = (m) => {
        const mb = m.member;
        const it = (a, ic, t, danger) => `<button class="list-item" data-sa="${a}" ${danger ? 'style="color:var(--danger)"' : ''}>${icon(ic)}<div class="grow">${t}</div></button>`;
        sheet(`<div class="sheet-head"><h2 class="ellipsis">${esc(m.name || 'Tin nhắn')}</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
          <div class="card card-pad small text-2" style="margin-bottom:12px;max-height:120px;overflow:auto;white-space:pre-wrap;overflow-wrap:anywhere">${esc(m.text || m.media || '')}</div>
          <div class="list">
            ${it('reply', 'reply', 'Trả lời tin này')}
            ${it(m.pinned ? 'unpin' : 'pin', 'pin', m.pinned ? 'Bỏ ghim' : 'Ghim tin')}
            ${it('copy', 'copy', 'Sao chép nội dung')}
            ${mb ? it('profile', 'user', 'Xem hồ sơ thành viên') : ''}
            ${mb && !m.is_bot ? it('points', 'star', 'Cộng / trừ điểm') : ''}
            ${mb && !m.is_bot && !mb.is_admin ? it('kick', 'kick', 'Đuổi khỏi nhóm', true) : ''}
            ${!m.deleted ? it('del', 'trash', 'Xoá tin nhắn', true) : ''}
          </div>`, (el, close) => el.addEventListener('click', async (e) => {
          const b = e.target.closest('[data-sa]');
          if (!b) return;
          close();
          doAction(b.dataset.sa, m);
        }));
      };

      const doAction = async (a, m) => {
        if (a === 'del') {
          try { await api('chat/delete', { id: m.id }); m.deleted = 1; render('keep'); toast('Đã xoá tin nhắn'); } catch (e) { toast(e.message, 'err'); }
        } else if (a === 'pin' || a === 'unpin') {
          try { await api('chat/pin', { id: m.id, unpin: a === 'unpin' }); m.pinned = a === 'pin' ? 1 : 0; render('keep'); toast(a === 'pin' ? 'Đã ghim tin' : 'Đã bỏ ghim'); } catch (e) { toast(e.message, 'err'); }
        } else if (a === 'reply') setReply(m);
        else if (a === 'copy') { navigator.clipboard?.writeText(m.text || ''); toast('Đã sao chép'); }
        else if (a === 'profile') openMember(m.member.id, () => refreshMember(m.member.id));
        else if (a === 'more') moreSheet(m);
        else if (m.member) actionForm(a, memberFor(m), { warn_limit: S2.warnLimit }, () => refreshMember(m.member.id));
      };

      root.addEventListener('click', (e) => {
        const ca = e.target.closest('[data-ca]');
        if (ca) { const m = S2.byId.get(Number(ca.dataset.id)); if (m) doAction(ca.dataset.ca, m); return; }
        const more = e.target.closest('[data-more]');
        if (more) { loadMore(more); return; }
        if (e.target.closest('[data-unreply]')) { setReply(null); return; }
        const j = e.target.closest('[data-jump]');
        if (j) {
          const el = $(`[data-tgmid="${j.dataset.jump}"]`, root);
          if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.classList.add('hl'); setTimeout(() => el.classList.remove('hl'), 1400); }
          else toast('Tin gốc nằm ngoài phần đang hiển thị');
        }
      });

      // Ô soạn tin: tự giãn, Enter để gửi (Shift+Enter xuống dòng trên máy tính)
      const form = $('#composer', root);
      const ta = form.querySelector('textarea');
      const grow = () => { ta.style.height = 'auto'; ta.style.height = Math.min(140, ta.scrollHeight) + 'px'; };
      ta.addEventListener('input', grow);
      ta.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey && matchMedia('(hover: hover)').matches) { e.preventDefault(); form.requestSubmit(); } });
      form.onsubmit = async (e) => {
        e.preventDefault();
        const text = ta.value.trim();
        if (!text) return;
        const btn = form.querySelector('button.primary');
        await withBtn(btn, async () => {
          try {
            await api('chat/send', { group_id: g.id, text, reply_to: S2.replyTo ? Number(S2.replyTo.message_id) : 0 });
            ta.value = ''; grow(); setReply(null);
            await poll();
            toBottom(true);
          } catch (err) { toast(err.message, 'err'); }
        });
      };

      load();
    };
    return { body, mount };
  }

  function groupSend(d) {
    const g = d.group;
    const body = `
      <form class="card card-pad stack" id="fm">
        <label class="field"><span class="lbl">Nội dung gửi vào nhóm</span><textarea class="input" name="text" style="min-height:160px" required placeholder="Nhập thông báo…"></textarea></label>
        <div class="card" style="box-shadow:none">
          ${sw('html', true, 'Định dạng HTML', '&lt;b&gt;đậm&lt;/b&gt;, &lt;i&gt;nghiêng&lt;/i&gt;, &lt;a href=".."&gt;link&lt;/a&gt;')}
          ${sw('pin', false, 'Ghim tin nhắn')}
          ${sw('silent', false, 'Gửi im lặng', 'Không phát âm báo')}
          ${sw('preview', false, 'Xem trước link')}
        </div>
        <button class="btn primary block" style="height:46px">${icon('send')}Gửi tới ${esc(g.title)}</button>
      </form>`;
    const mount = (root) => {
      $('#fm', root).onsubmit = async (e) => {
        e.preventDefault();
        await withBtn(e.target.querySelector('button.primary'), async () => {
          try { await api('group/send', { id: g.id, ...formData(e.target) }); toast('Đã gửi tin nhắn'); e.target.text.value = ''; } catch (err) { toast(err.message, 'err'); }
        });
      };
    };
    return { body, mount };
  }

  function logsBlock(groupId) {
    const st = { action: '', page: 1, q: '' };
    if (groupId) st.group_id = groupId;
    const f = [['', 'Tất cả'], ['moderation', 'Xử lý vi phạm'], ['points', 'Điểm'], ['members', 'Ra / vào'], ['system', 'Hệ thống']];
    const body = `<div class="input-icon" style="margin-bottom:10px">${icon('search')}<input class="input" id="lq" placeholder="Tìm theo tên, người xử lý, ID…"></div>
      <div class="chips" style="margin-bottom:12px">${f.map(([k, t]) => `<button class="chip ${k === '' ? 'active' : ''}" data-lf="${k}">${t}</button>`).join('')}</div>
      <div class="list" id="llist">${loading()}</div><div class="pager" id="lpager"></div>`;
    const mount = (root) => {
      const load = async () => {
        const r = await api('logs', undefined, { query: st });
        $('#llist', root).innerHTML = r.rows.length ? r.rows.map(logItem).join('') : empty('list', 'Chưa có nhật ký');
        $('#lpager', root).innerHTML = r.pages > 1 ? `<button class="btn sm" ${r.page <= 1 ? 'disabled' : ''} data-lp="-1">${icon('back', 'sm')}</button><span class="small">${r.page}/${r.pages}</span><button class="btn sm" ${r.page >= r.pages ? 'disabled' : ''} data-lp="1">${icon('chevron', 'sm')}</button>` : '';
      };
      $('#lq', root).oninput = debounce((e) => { st.q = e.target.value; st.page = 1; load(); });
      root.addEventListener('click', (e) => {
        const c = e.target.closest('[data-lf]');
        if (c) { $$('[data-lf]', root).forEach((x) => x.classList.toggle('active', x === c)); st.action = c.dataset.lf; st.page = 1; load(); }
        const p = e.target.closest('[data-lp]');
        if (p) { st.page += Number(p.dataset.lp); load(); window.scrollTo(0, 0); }
      });
      load();
    };
    return { body, mount };
  }
  const groupLogs = (d) => logsBlock(d.group.id);

  route('/logs', async () => { const { body, mount } = logsBlock(null); return { title: 'Nhật ký', html: body, mount }; }, { admin: true, title: 'Nhật ký' });

  // ================= Admin: Bots =================
  route('/bots', async () => {
    const bots = await api('bots');
    const item = (b) => `
      <div class="card card-pad" style="margin-bottom:12px">
        <div class="row">${avatar(b.name || b.username, b.tg_bot_id, 'sq')}
          <div class="grow"><div class="row" style="gap:6px"><b class="ellipsis">${esc(b.name || '')}</b>
          <span class="dot ${b.status === 'running' ? 'green' : b.status === 'error' ? 'red' : ''}"></span></div>
          <div class="small muted"><a href="https://t.me/${esc(b.username)}" target="_blank" rel="noopener">@${esc(b.username)}</a> · ${b.groups} nhóm</div></div>
          <label class="switch"><input type="checkbox" data-toggle="${b.id}" ${b.enabled ? 'checked' : ''}><span></span></label></div>
        ${b.last_error ? `<div class="alert err" style="margin-top:12px">${icon('alert')}<div class="small" style="overflow-wrap:anywhere">${esc(b.last_error)}</div></div>` : ''}
        <div class="xs muted mono" style="margin-top:10px;overflow-wrap:anywhere">${esc(b.token_hint)}</div>
        <div class="row wrap" style="margin-top:12px;gap:8px">
          <a class="btn sm soft" href="https://t.me/${esc(b.username)}?startgroup=true&admin=delete_messages+restrict_members+pin_messages+invite_users" target="_blank" rel="noopener">${icon('plus', 'sm')}Thêm vào nhóm</a>
          <button class="btn sm" data-check="${b.id}">${icon('refresh', 'sm')}Kiểm tra</button>
          <button class="btn sm soft-danger" data-delbot="${b.id}">${icon('trash', 'sm')}Xoá</button>
        </div>
      </div>`;
    return {
      title: 'Bot',
      actions: `<button class="btn primary sm" data-addbot>${icon('plus', 'sm')}Thêm bot</button>`,
      html: `${bots.length ? bots.map(item).join('') : `<div class="card">${empty('bot', 'Chưa có bot nào')}</div>`}
        <div class="card card-pad stack" style="margin-top:6px">
          <h3>Hướng dẫn</h3>
          <div class="small text-2">1. Mở <a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a> → <code>/newbot</code> → copy token.<br>
          2. Nhấn <b>Thêm bot</b> và dán token. Web sẽ tự đặt webhook (cần HTTPS).<br>
          3. Trong BotFather: <code>/setprivacy</code> → chọn bot → <b>Disable</b> để bot đọc được tin nhắn tính điểm.<br>
          4. Nhấn <b>Thêm vào nhóm</b>, cấp quyền quản trị cho bot.</div>
        </div>`,
      mount: (root) => {
        const addBot = () => sheet(`<div class="sheet-head"><h2>Thêm bot</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
          <form class="stack" id="fb"><label class="field"><span class="lbl">Bot token</span><input class="input mono" name="token" required placeholder="123456789:AA…" autocomplete="off"><div class="hint">Lấy từ @BotFather</div></label>
          <button class="btn primary block" style="height:46px">Thêm bot</button></form>`, (el, close) => {
          $('#fb', el).onsubmit = async (e) => {
            e.preventDefault();
            await withBtn(e.target.querySelector('button'), async () => {
              try {
                const r = await api('bots/add', formData(e.target));
                close();
                if (r.warning) toast(r.warning, 'err'); else toast('Đã thêm @' + r.username);
                if (r.privacy) setTimeout(() => toast('Lưu ý: hãy tắt Privacy Mode trong @BotFather (/setprivacy → Disable) để tính điểm.', 'err'), 800);
                render();
              } catch (err) { toast(err.message, 'err'); }
            });
          };
        });
        $('[data-addbot]')?.addEventListener('click', addBot);
        root.addEventListener('change', async (e) => {
          const t = e.target.closest('[data-toggle]');
          if (!t) return;
          try { await api('bots/toggle', { id: t.dataset.toggle, enabled: t.checked }); toast(t.checked ? 'Đã bật bot' : 'Đã tắt bot'); } catch (err) { toast(err.message, 'err'); }
          render();
        });
        root.addEventListener('click', async (e) => {
          const c = e.target.closest('[data-check]');
          if (c) await withBtn(c, async () => {
            try {
              const r = await api('bots/refresh', { id: c.dataset.check });
              if (r.last_error && r.last_error_date > Date.now() / 1000 - 600) toast('Webhook lỗi: ' + r.last_error, 'err');
              else toast(`Bot hoạt động tốt · ${r.pending} update đang chờ`);
              if (r.privacy) setTimeout(() => toast('Privacy Mode đang bật: bot chỉ nhận lệnh, không tính điểm tin nhắn thường.', 'err'), 600);
              render();
            } catch (err) { toast(err.message, 'err'); }
          });
          const d = e.target.closest('[data-delbot]');
          if (d && (await confirmBox('Xoá bot?', 'Webhook sẽ bị gỡ. Dữ liệu nhóm vẫn được giữ lại.', { danger: true, ok: 'Xoá' }))) {
            await api('bots/delete', { id: d.dataset.delbot }); toast('Đã xoá bot'); render();
          }
        });
      },
    };
  }, { admin: true, title: 'Bot' });

  // ================= Admin: Lệnh tự tạo =================
  const TRIGGER = {
    command: ['Lệnh /', 'Người dùng gõ lệnh, VD: /diemdanh'],
    contains: ['Chứa từ', 'Tin nhắn có chứa từ khoá (nhiều từ cách nhau bằng |)'],
    exact: ['Đúng câu', 'Tin nhắn đúng nguyên câu (không phân biệt hoa thường)'],
    starts: ['Bắt đầu bằng', 'Tin nhắn bắt đầu bằng cụm từ, phần sau là {args}'],
    regex: ['Regex', 'Biểu thức chính quy nâng cao, nhóm (…) đầu tiên là {args}'],
  };
  const RACT = {
    reply: ['💬 Trả lời tin nhắn', true],
    send: ['📢 Gửi tin vào nhóm', false],
    points: ['⭐ Cộng / trừ điểm', true],
    delete: ['🗑 Xoá tin nhắn', true],
    pin: ['📌 Ghim tin nhắn', true],
    warn: ['⚠️ Cảnh cáo', true],
    mute: ['🔇 Cấm chat', true],
    unmute: ['🔊 Bỏ cấm chat', true],
    kick: ['👢 Đuổi khỏi nhóm', true],
    ban: ['⛔ Cấm', true],
    unban: ['✅ Gỡ cấm', true],
  };
  const AD_PRESETS = [[0, 'Ngay lập tức'], [5, 'Sau 5 giây'], [10, 'Sau 10 giây'], [20, 'Sau 20 giây'], [30, 'Sau 30 giây'], [60, 'Sau 1 phút'], [120, 'Sau 2 phút'], [300, 'Sau 5 phút'], [600, 'Sau 10 phút'], [1800, 'Sau 30 phút'], [3600, 'Sau 1 giờ'], [21600, 'Sau 6 giờ'], [86400, 'Sau 1 ngày'], [172800, 'Sau 2 ngày']];
  // Danh mục biến: [mã, giải thích, ví dụ]
  const VAR_GROUPS = [
    { title: '🙋 Người gửi lệnh', vars: [
      ['{mention}', 'Tag tên người gửi (bấm vào mở trang cá nhân)', '@Lan'],
      ['{name}', 'Họ tên đầy đủ', 'Nguyễn Lan'],
      ['{first_name}', 'Tên', 'Lan'],
      ['{last_name}', 'Họ', 'Nguyễn'],
      ['{username}', '@username (không có thì dùng tên)', '@lan99'],
      ['{id}', 'ID Telegram', '123456789'],
      ['{points}', 'Điểm hiện tại', '152'],
      ['{rank}', 'Hạng trong nhóm', '3'],
      ['{messages}', 'Số tin nhắn đã gửi', '420'],
      ['{warns}', 'Số lần bị cảnh cáo', '1'],
      ['{warn_limit}', 'Giới hạn cảnh cáo của nhóm', '3'],
      ['{warns_left}', 'Còn bao nhiêu lần cảnh cáo nữa thì bị xử lý', '2'],
      ['{role}', 'Vai trò: Quản trị viên / Thành viên', 'Thành viên'],
      ['{joined}', 'Ngày vào nhóm', '01/10/2026'],
      ['{days}', 'Số ngày đã ở trong nhóm', '44'],
    ] },
    { title: '↩️ Người được reply', note: 'Chỉ có giá trị khi lệnh được dùng bằng cách reply tin của người khác', vars: [
      ['{target_mention}', 'Tag tên người được reply', '@Huy'],
      ['{target_name}', 'Họ tên người được reply', 'Quốc Huy'],
      ['{target_first_name}', 'Tên người được reply', 'Huy'],
      ['{target_username}', '@username người được reply', '@huy_dev'],
      ['{target_id}', 'ID Telegram người được reply', '987654321'],
      ['{target_points}', 'Điểm của người được reply', '88'],
      ['{target_rank}', 'Hạng của người được reply', '7'],
      ['{target_messages}', 'Số tin nhắn của người được reply', '230'],
      ['{target_warns}', 'Số cảnh cáo của người được reply', '0'],
      ['{target_role}', 'Vai trò người được reply', 'Thành viên'],
      ['{target_days}', 'Số ngày người được reply đã ở trong nhóm', '12'],
    ] },
    { title: '👥 Nhóm', vars: [
      ['{group}', 'Tên nhóm', 'Hội Yêu Cafe'],
      ['{member_count}', 'Số thành viên', '1.250'],
      ['{messages_today}', 'Số tin nhắn hôm nay', '312'],
      ['{rules}', 'Nội quy nhóm (đặt trong Cài đặt nhóm)', '1. Tôn trọng mọi người…'],
      ['{top1}', 'Người có điểm cao nhất', 'Tuấn Kiệt'],
      ['{top1_points}', 'Điểm của người đứng đầu', '846'],
      ['{top3}', 'Danh sách top 3 (nhiều dòng)', '🥇 Kiệt — 846 điểm…'],
      ['{group_link}', 'Link nhóm công khai (nếu có)', 'https://t.me/hoicafe'],
      ['{group_id}', 'ID nhóm', '-1001234567890'],
      ['{bot}', '@username của bot', '@quanly_bot'],
    ] },
    { title: '⌨️ Chữ gõ sau lệnh', note: 'VD người dùng gõ: /boi mai có mưa không', vars: [
      ['{args}', 'Toàn bộ phần chữ sau lệnh', 'mai có mưa không'],
      ['{arg1}', 'Từ thứ 1 sau lệnh', 'mai'],
      ['{arg2}', 'Từ thứ 2 sau lệnh', 'có'],
      ['{arg3}', 'Từ thứ 3 sau lệnh', 'mưa'],
    ] },
    { title: '🕒 Thời gian', vars: [
      ['{time}', 'Giờ hiện tại', '20:45'],
      ['{date}', 'Ngày hôm nay', '14/11/2026'],
      ['{datetime}', 'Giờ và ngày', '20:45 14/11/2026'],
      ['{weekday}', 'Thứ trong tuần', 'Thứ bảy'],
      ['{day}', 'Ngày (số)', '14'],
      ['{month}', 'Tháng', '11'],
      ['{year}', 'Năm', '2026'],
    ] },
    { title: '🎲 Ngẫu nhiên & khác', vars: [
      ['{random}', 'Số ngẫu nhiên từ 1 đến 100', '73'],
      ['{random:1-6}', 'Số ngẫu nhiên trong khoảng (đổi 1-6 tuỳ ý)', '4'],
      ['{choice:Có|Không|Có thể}', 'Chọn ngẫu nhiên 1 trong các lựa chọn (cách nhau bằng |)', 'Có thể'],
      ['{count}', 'Lệnh đã được dùng bao nhiêu lần', '58'],
      ['{command}', 'Tên lệnh', '/diemdanh'],
    ] },
  ];
  const VAR_LIST = VAR_GROUPS.flatMap((g) => g.vars);
  const VAR_QUICK = ['{mention}', '{name}', '{points}', '{rank}', '{target_mention}', '{args}', '{group}', '{time}'];

  // Xem trước nội dung với giá trị mẫu (chỉ giữ thẻ HTML Telegram cho phép)
  function previewText(t) {
    let out = String(t || '');
    out = out.replace(/\{random:(-?\d+)-(-?\d+)\}/g, (_, a) => a).replace(/\{choice:([^{}]*)\}/g, (_, o) => (o.split('|')[0] || '').trim());
    for (const [code, , ex] of VAR_LIST) if (!code.includes(':')) out = out.split(code).join(code === '{mention}' || code === '{target_mention}' ? `\u0001${ex}\u0002` : ex);
    out = esc(out)
      .replace(/\u0001(.*?)\u0002/g, '<a class="pv-mention">$1</a>')
      .replace(/&lt;(\/?)(b|i|u|s|code|pre|tg-spoiler)&gt;/g, '<$1$2>')
      .replace(/&lt;a href=&quot;(https?:\/\/[^&]*?)&quot;&gt;/g, '<a href="$1" target="_blank" rel="noopener">')
      .replace(/&lt;\/a&gt;/g, '</a>');
    return out;
  }
  // Mẫu lệnh có sẵn (cat: nhóm hiển thị)
  const RULE_TEMPLATES = [
    // ---- Điểm & giải trí ----
    { cat: '⭐ Điểm & giải trí', icon: '📅', title: 'Điểm danh hằng ngày', desc: '/diemdanh · +5 điểm, 1 lần/ngày',
      rule: { name: 'Điểm danh hằng ngày', trigger_type: 'command', trigger_value: 'diemdanh', show_in_menu: 1, conditions: { who: 'all', cooldown: 86400, autodel: true, autodel_after: 5, autodel_trigger: true, autodel_reply: false, autodel_target: false },
        actions: [{ type: 'points', who: 'sender', amount: 5 }, { type: 'reply', who: 'sender', text: '✅ {mention} điểm danh thành công! <b>+5 điểm</b>\nTổng: {points} điểm · Hạng #{rank}' }] } },
    { cat: '⭐ Điểm & giải trí', icon: '💖', title: 'Cảm ơn tặng điểm', desc: 'Reply + /camon · người kia +1 điểm',
      rule: { name: 'Cảm ơn thành viên', trigger_type: 'command', trigger_value: 'camon', show_in_menu: 1, conditions: { who: 'all', require_reply: true, cooldown: 60 },
        actions: [{ type: 'points', who: 'target', amount: 1 }, { type: 'reply', who: 'sender', text: '💖 {name} đã cảm ơn {target_mention}! <b>+1 điểm</b> (tổng {target_points})' }] } },
    { cat: '⭐ Điểm & giải trí', icon: '💰', title: 'Xem điểm nhanh', desc: '/diem · tự xoá sau 30 giây',
      rule: { name: 'Xem điểm của tôi', trigger_type: 'command', trigger_value: 'diem', show_in_menu: 1, conditions: { who: 'all', autodel: true, autodel_after: 30, autodel_trigger: true, autodel_reply: true, autodel_target: false },
        actions: [{ type: 'reply', who: 'sender', text: '⭐ {mention}: <b>{points}</b> điểm · hạng #{rank} · {messages} tin nhắn' }] } },
    { cat: '⭐ Điểm & giải trí', icon: '🎰', title: 'Quay số may mắn', desc: '/quay · 30% trúng +10 điểm, 1 giờ/lần',
      rule: { name: 'Quay số may mắn', trigger_type: 'command', trigger_value: 'quay', show_in_menu: 1, conditions: { who: 'all', cooldown: 3600, chance: 30, autodel: true, autodel_after: 60, autodel_trigger: true, autodel_reply: true, autodel_target: false },
        actions: [{ type: 'points', who: 'sender', amount: 10 }, { type: 'reply', who: 'sender', text: '🎉 Chúc mừng {mention} quay trúng <b>+10 điểm</b>! Tổng: {points}' }] } },
    { cat: '⭐ Điểm & giải trí', icon: '🎲', title: 'Tung xúc xắc', desc: '/xucxac · ra số 1–6',
      rule: { name: 'Tung xúc xắc', trigger_type: 'command', trigger_value: 'xucxac', show_in_menu: 1, conditions: { who: 'all', cooldown: 10 },
        actions: [{ type: 'reply', who: 'sender', text: '🎲 {name} tung được <b>{random:1-6}</b>!' }] } },
    { cat: '⭐ Điểm & giải trí', icon: '🔮', title: 'Bói vui', desc: '/boi câu hỏi · trả lời ngẫu nhiên %',
      rule: { name: 'Bói vui', trigger_type: 'command', trigger_value: 'boi', show_in_menu: 1, conditions: { who: 'all', cooldown: 30 },
        actions: [{ type: 'reply', who: 'sender', text: '🔮 "{args}"\nKhả năng xảy ra: <b>{random}%</b>' }] } },
    { cat: '⭐ Điểm & giải trí', icon: '🎁', title: 'Admin thưởng điểm', desc: 'Reply + /thuong · người kia +10 điểm',
      rule: { name: 'Thưởng điểm', trigger_type: 'command', trigger_value: 'thuong', show_in_menu: 1, conditions: { who: 'admin', require_reply: true, autodel: true, autodel_after: 0, autodel_trigger: true, autodel_reply: false, autodel_target: false },
        actions: [{ type: 'points', who: 'target', amount: 10 }, { type: 'send', who: 'sender', text: '🎁 {target_mention} được admin thưởng <b>+10 điểm</b>! Tổng: {target_points}' }] } },

    // ---- Thông tin & tự trả lời ----
    { cat: '💬 Thông tin & tự trả lời', icon: '📜', title: 'Xem nội quy', desc: '/noiquy · tự xoá sau 2 phút',
      rule: { name: 'Nội quy nhóm', trigger_type: 'command', trigger_value: 'noiquy', show_in_menu: 1, conditions: { who: 'all', autodel: true, autodel_after: 120, autodel_trigger: true, autodel_reply: true, autodel_target: false },
        actions: [{ type: 'reply', who: 'sender', text: '📜 <b>Nội quy {group}</b>\n\n{rules}' }] } },
    { cat: '💬 Thông tin & tự trả lời', icon: '🆔', title: 'Xem ID', desc: '/myid · tự xoá sau 30 giây',
      rule: { name: 'Xem ID của tôi', trigger_type: 'command', trigger_value: 'myid', show_in_menu: 1, conditions: { who: 'all', autodel: true, autodel_after: 30, autodel_trigger: true, autodel_reply: true, autodel_target: false },
        actions: [{ type: 'reply', who: 'sender', text: '🆔 {name}: <code>{id}</code>' }] } },
    { cat: '💬 Thông tin & tự trả lời', icon: '👋', title: 'Chào lại', desc: 'Ai nhắn "hi / xin chào" → bot chào lại',
      rule: { name: 'Chào lại', trigger_type: 'exact', trigger_value: 'hi|hello|chào|xin chào|chào cả nhà', conditions: { who: 'all', cooldown: 300 },
        actions: [{ type: 'reply', who: 'sender', text: 'Chào {name} 👋 Chúc bạn một ngày vui vẻ!' }] } },
    { cat: '💬 Thông tin & tự trả lời', icon: '💬', title: 'Trả lời hỏi giá', desc: 'Ai hỏi "giá bao nhiêu" → trả lời sẵn',
      rule: { name: 'Trả lời hỏi giá', trigger_type: 'contains', trigger_value: 'giá bao nhiêu|bảng giá|báo giá', conditions: { who: 'all', cooldown: 30 },
        actions: [{ type: 'reply', who: 'sender', text: '💬 Chào {name}! Bảng giá xem tại tin nhắn ghim của nhóm nhé.' }] } },
    { cat: '💬 Thông tin & tự trả lời', icon: '📞', title: 'Thông tin liên hệ', desc: 'Hỏi "liên hệ / admin đâu" → gửi liên hệ',
      rule: { name: 'Thông tin liên hệ', trigger_type: 'contains', trigger_value: 'liên hệ|admin đâu|hotline|số điện thoại', conditions: { who: 'all', cooldown: 60, autodel: true, autodel_after: 300, autodel_trigger: false, autodel_reply: true, autodel_target: false },
        actions: [{ type: 'reply', who: 'sender', text: '📞 Liên hệ admin: @ten_admin\n⏰ Hỗ trợ 8h–22h hằng ngày' }] } },
    { cat: '💬 Thông tin & tự trả lời', icon: '🌙', title: 'Chúc ngủ ngon', desc: '"ngủ ngon" → bot chúc lại',
      rule: { name: 'Chúc ngủ ngon', trigger_type: 'contains', trigger_value: 'ngủ ngon|good night', conditions: { who: 'all', cooldown: 600 },
        actions: [{ type: 'reply', who: 'sender', text: '🌙 Chúc {name} ngủ ngon, mơ đẹp nhé!' }] } },
    { cat: '💬 Thông tin & tự trả lời', icon: '🎉', title: 'Admin chào mừng', desc: 'Reply + /chaomung · chào thành viên mới',
      rule: { name: 'Chào mừng thành viên', trigger_type: 'command', trigger_value: 'chaomung', show_in_menu: 1, conditions: { who: 'admin', require_reply: true, autodel: true, autodel_after: 0, autodel_trigger: true, autodel_reply: false, autodel_target: false },
        actions: [{ type: 'send', who: 'sender', text: '🎉 Chào mừng {target_mention} đến với <b>{group}</b>! Nhóm hiện có {member_count} thành viên. Gõ /noiquy để xem nội quy nhé.' }] } },

    // ---- Quản trị ----
    { cat: '🛡 Quản trị', icon: '🚫', title: 'Chặn từ khoá + cảnh cáo', desc: 'Xoá tin và cảnh cáo người gửi',
      rule: { name: 'Chặn lừa đảo', trigger_type: 'contains', trigger_value: 'lừa đảo|nhận tiền miễn phí', conditions: { who: 'member' },
        actions: [{ type: 'delete', who: 'sender' }, { type: 'warn', who: 'sender', reason: 'Nội dung cấm' }] } },
    { cat: '🛡 Quản trị', icon: '🔗', title: 'Chặn link mời nhóm khác', desc: 'Xoá link t.me/+… và cảnh cáo',
      rule: { name: 'Chặn link mời nhóm', trigger_type: 'regex', trigger_value: 't\\.me/(\\+|joinchat)', conditions: { who: 'member' },
        actions: [{ type: 'delete', who: 'sender' }, { type: 'warn', who: 'sender', reason: 'Gửi link mời nhóm khác' }] } },
    { cat: '🛡 Quản trị', icon: '🛡', title: 'Xử lý spam nhanh', desc: 'Reply + /spam · xoá tin + cấm chat 1 ngày',
      rule: { name: 'Xử lý spam nhanh', trigger_type: 'command', trigger_value: 'spam', show_in_menu: 1, conditions: { who: 'admin', require_reply: true, autodel: true, autodel_after: 10, autodel_trigger: true, autodel_reply: true, autodel_target: false },
        actions: [{ type: 'delete', who: 'target' }, { type: 'mute', who: 'target', minutes: 1440, reason: 'Spam' }] } },
    { cat: '🛡 Quản trị', icon: '🤐', title: 'Cấm chat 1 giờ', desc: 'Reply + /im · cấm chat 60 phút',
      rule: { name: 'Cấm chat 1 giờ', trigger_type: 'command', trigger_value: 'im', show_in_menu: 1, conditions: { who: 'admin', require_reply: true, autodel: true, autodel_after: 10, autodel_trigger: true, autodel_reply: true, autodel_target: false },
        actions: [{ type: 'mute', who: 'target', minutes: 60, reason: '' }] } },
    { cat: '🛡 Quản trị', icon: '⚠️', title: 'Cảnh cáo nhanh', desc: 'Reply + /cc lý do · cảnh cáo, tự dọn lệnh',
      rule: { name: 'Cảnh cáo nhanh', trigger_type: 'command', trigger_value: 'cc', show_in_menu: 1, conditions: { who: 'admin', require_reply: true, autodel: true, autodel_after: 0, autodel_trigger: true, autodel_reply: false, autodel_target: false },
        actions: [{ type: 'warn', who: 'target', reason: '' }] } },
    { cat: '🛡 Quản trị', icon: '📌', title: 'Ghim tin nhanh', desc: 'Reply + /ghim · ghim và xoá lệnh ngay',
      rule: { name: 'Ghim tin nhắn', trigger_type: 'command', trigger_value: 'ghim', show_in_menu: 1, conditions: { who: 'admin', require_reply: true, autodel: true, autodel_after: 0, autodel_trigger: true, autodel_reply: false, autodel_target: false },
        actions: [{ type: 'pin', who: 'target' }] } },
    { cat: '🛡 Quản trị', icon: '🧹', title: 'Dọn tin nhắn', desc: 'Reply + /xoa · xoá tin đó và lệnh',
      rule: { name: 'Xoá tin nhắn', trigger_type: 'command', trigger_value: 'xoa', show_in_menu: 1, conditions: { who: 'admin', require_reply: true, autodel: true, autodel_after: 0, autodel_trigger: true, autodel_reply: true, autodel_target: true },
        actions: [{ type: 'delete', who: 'target' }] } },
  ];
  // Mẫu: người gửi xoá ngay, bot/tin được reply xoá theo thời gian của mẫu
  RULE_TEMPLATES.forEach(({ rule: { conditions: c } }) => {
    if (!c.autodel) return;
    c.autodel_trigger_after = c.autodel_reply ? 0 : c.autodel_after;
    c.autodel_reply_after = c.autodel_after;
    c.autodel_target_after = c.autodel_after;
    delete c.autodel_after;
  });

  const triggerChip = (r) => r.trigger_type === 'command'
    ? `<span class="badge blue mono">/${esc(r.trigger_value)}</span>`
    : `<span class="badge">${TRIGGER[r.trigger_type][0]}: ${esc(r.trigger_value.length > 28 ? r.trigger_value.slice(0, 28) + '…' : r.trigger_value)}</span>`;

  function ruleSummary(r) {
    const c = r.conditions || {};
    const ifs = [];
    if (c.who === 'admin') ifs.push('chỉ admin');
    if (c.who === 'member') ifs.push('chỉ thành viên');
    if (c.min_points) ifs.push(`≥ ${c.min_points} điểm`);
    if (c.require_reply) ifs.push('phải reply');
    if (c.cooldown) ifs.push('chờ ' + humanSec(c.cooldown));
    if (c.chance && c.chance < 100) ifs.push(c.chance + '% may mắn');
    let tail = '';
    if (c.autodel) {
      const when = (k) => { const v = c['autodel_' + k + '_after'] ?? c.autodel_after ?? 0; return v ? 'sau ' + humanSec(v) : 'ngay'; };
      const parts = [['trigger', 'người gửi'], ['reply', 'bot'], ['target', 'tin được reply']].filter(([k]) => c['autodel_' + k]).map(([k, l]) => `${l} ${when(k)}`);
      tail = ` · 🧹 xoá ${parts.join(', ')}`;
    }
    const thens = (r.actions || []).map((a) => {
      const t = (RACT[a.type] || [a.type])[0].replace(/^\S+\s/, '');
      const who = a.who === 'target' ? ' (người được reply)' : '';
      if (a.type === 'points') return `${a.amount > 0 ? '+' : ''}${a.amount} điểm${who}`;
      if (a.type === 'mute') return `cấm chat ${a.minutes ? humanSec(a.minutes * 60) : 'vĩnh viễn'}${who}`;
      return t.toLowerCase() + (['reply', 'send'].includes(a.type) ? '' : who);
    });
    return `<b>Nếu</b> ${ifs.length ? ifs.join(', ') : 'bất kỳ ai'} → <b>Thì</b> ${thens.join(', ')}${tail}`;
  }
  function humanSec(s) {
    if (s % 86400 === 0) return s / 86400 + ' ngày';
    if (s % 3600 === 0) return s / 3600 + ' giờ';
    if (s % 60 === 0) return s / 60 + ' phút';
    return s + ' giây';
  }

  function rulesBlock(groupId) {
    const body = `
      <div class="row between" style="margin-bottom:12px"><div class="small muted grow">${groupId ? 'Lệnh riêng của nhóm và lệnh dùng chung mọi nhóm' : 'Tự tạo lệnh theo kiểu <b>Khi… Nếu… Thì…</b>'}</div>
        <button class="btn primary sm" data-newrule>${icon('plus', 'sm')}Tạo lệnh</button></div>
      <div id="rlist">${loading()}</div>`;
    const mount = (root) => {
      let groups = [];
      const load = async () => {
        const d = await api('rules', undefined, { query: groupId ? { group_id: groupId } : {} });
        groups = d.groups;
        const cmds = d.rules.filter((r) => r.trigger_type === 'command');
        const allOn = d.rules.length && d.rules.every((r) => r.enabled);
        const allMenu = cmds.length && cmds.every((r) => r.show_in_menu);
        const master = `<div class="card" style="margin-bottom:12px">
            <label class="switch-row"><div class="txt"><b>🔒 Chỉ admin thấy menu lệnh</b><span>${d.menu_admin_only ? 'Thành viên gõ "/" sẽ không thấy danh sách lệnh' : 'Đang tắt: mọi thành viên đều thấy menu khi gõ "/"'}</span></div>
              <div class="switch"><input type="checkbox" data-menumode ${d.menu_admin_only ? 'checked' : ''}><span></span></div></label>
            ${d.rules.length ? `<label class="switch-row"><div class="txt"><b>Bật tất cả lệnh</b><span>${d.rules.filter((r) => r.enabled).length}/${d.rules.length} lệnh đang bật</span></div>
              <div class="switch"><input type="checkbox" data-bulk="enabled" ${allOn ? 'checked' : ''}><span></span></div></label>
            ${cmds.length ? `<label class="switch-row"><div class="txt"><b>Hiện tất cả trong menu lệnh Telegram</b><span>Hiện khi gõ "/" trong nhóm · ${cmds.filter((r) => r.show_in_menu).length}/${cmds.length} lệnh</span></div>
              <div class="switch"><input type="checkbox" data-bulk="show_in_menu" ${allMenu ? 'checked' : ''}><span></span></div></label>` : ''}` : ''}
          </div>`;
        ids = d.rules.map((r) => r.id);
        $('#rlist', root).innerHTML = master + (d.rules.length ? `<div class="list">${d.rules.map((r) => `
          <div class="list-item" style="align-items:flex-start">
            <button class="grow" data-rule='${esc(JSON.stringify(r))}' style="all:unset;cursor:pointer;flex:1;min-width:0">
              <div class="row wrap" style="gap:6px">${triggerChip(r)}<b class="ellipsis">${esc(r.name)}</b></div>
              <div class="xs text-2" style="margin-top:4px">${ruleSummary(r)}</div>
              <div class="xs muted" style="margin-top:3px">${r.group_id ? esc(r.group_title || 'Nhóm') : 'Tất cả nhóm'} · ${num(r.uses)} lượt dùng${r.last_used ? ' · ' + ago(r.last_used) : ''}${r.trigger_type === 'command' && r.show_in_menu ? ' · <span style="color:var(--primary)">hiện trong menu</span>' : ''}</div>
            </button>
            <label class="switch" style="margin-top:2px"><input type="checkbox" data-rtoggle="${r.id}" ${r.enabled ? 'checked' : ''}><span></span></label>
          </div>`).join('')}</div>`
          : `<div class="card">${empty('terminal', 'Chưa có lệnh nào. Nhấn <b>Tạo lệnh</b> để bắt đầu từ mẫu có sẵn.')}</div>`);
      };
      let ids = [];
      const pickTemplate = () => sheet(`<div class="sheet-head"><h2>Tạo lệnh mới</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
        <button class="list-item card" data-tpl="-1" style="border-radius:14px;margin-bottom:10px;border:1px solid var(--border)"><span style="font-size:22px">✏️</span><div class="grow"><b>Tạo từ đầu</b><div class="xs muted">Tự thiết lập mọi thứ</div></div>${icon('chevron')}</button>
        <div class="small muted" style="margin:14px 2px 0">Hoặc chọn mẫu có sẵn (${RULE_TEMPLATES.length} mẫu)</div>
        ${[...new Set(RULE_TEMPLATES.map((t) => t.cat))].map((cat) => `<div class="small" style="margin:14px 2px 8px;font-weight:600">${cat}</div>
          <div class="list">${RULE_TEMPLATES.map((t, i) => t.cat !== cat ? '' : `<button class="list-item" data-tpl="${i}"><span style="font-size:22px">${t.icon}</span><div class="grow"><b>${t.title}</b><div class="xs muted">${esc(t.desc)}</div></div>${icon('chevron')}</button>`).join('')}</div>`).join('')}`,
      (el, close) => el.addEventListener('click', (e) => {
        const b = e.target.closest('[data-tpl]');
        if (!b) return;
        close();
        const i = Number(b.dataset.tpl);
        const base = i < 0 ? { trigger_type: 'command', trigger_value: '', name: '', conditions: { who: 'all' }, actions: [{ type: 'reply', who: 'sender', text: '' }], show_in_menu: 1 } : JSON.parse(JSON.stringify(RULE_TEMPLATES[i].rule));
        base.enabled = 1;
        base.group_id = groupId || null;
        ruleEditor(base, groups, load);
      }));
      root.addEventListener('click', (e) => {
        if (e.target.closest('[data-newrule]')) pickTemplate();
        const r = e.target.closest('[data-rule]');
        if (r) ruleEditor(JSON.parse(r.dataset.rule), groups, load);
      });
      root.addEventListener('change', async (e) => {
        const mm = e.target.closest('[data-menumode]');
        if (mm) {
          mm.disabled = true;
          try {
            const r = await api('rules/menu-mode', { admin_only: mm.checked });
            toast(r.admin_only ? 'Từ giờ chỉ admin thấy menu lệnh' : 'Mọi thành viên đều thấy menu lệnh');
            (r.menu_errors || []).length && toast('Không cập nhật được menu ở: ' + r.menu_errors.join(', '), 'err');
          } catch (err) { toast(err.message, 'err'); }
          return load();
        }
        const bk = e.target.closest('[data-bulk]');
        if (bk) {
          bk.disabled = true;
          try {
            const r = await api('rules/bulk', { ids, field: bk.dataset.bulk, value: bk.checked });
            toast(bk.dataset.bulk === 'enabled' ? (bk.checked ? `Đã bật ${r.updated} lệnh` : `Đã tắt ${r.updated} lệnh`) : (bk.checked ? `Đã hiện ${r.updated} lệnh trong menu` : `Đã ẩn ${r.updated} lệnh khỏi menu`));
            (r.menu_errors || []).length && toast('Không cập nhật được menu ở: ' + r.menu_errors.join(', '), 'err');
          } catch (err) { toast(err.message, 'err'); }
          return load();
        }
        const t = e.target.closest('[data-rtoggle]');
        if (!t) return;
        try { await api('rules/toggle', { id: t.dataset.rtoggle, enabled: t.checked }); toast(t.checked ? 'Đã bật lệnh' : 'Đã tắt lệnh'); } catch (err) { toast(err.message, 'err'); }
        load();
      });
      load();
    };
    return { body, mount };
  }

  // Bảng tra cứu biến: tìm kiếm, giải thích, ví dụ; chạm để chèn
  function varPicker(onPick) {
    const rows = (q) => VAR_GROUPS.map((g) => {
      const list = g.vars.filter(([c, d, ex]) => !q || (c + ' ' + d + ' ' + ex).toLowerCase().includes(q));
      if (!list.length) return '';
      return `<div class="small" style="font-weight:700;margin:14px 2px 4px">${g.title}</div>${g.note ? `<div class="xs muted" style="margin:0 2px 8px">${g.note}</div>` : ''}
        <div class="list">${list.map(([c, d, ex]) => `<button type="button" class="list-item var-row" data-pick="${esc(c)}">
          <code class="var-code">${esc(c)}</code><div class="grow"><div class="small">${esc(d)}</div><div class="xs muted">VD: ${esc(ex)}</div></div>${icon('plus', 'sm')}</button>`).join('')}</div>`;
    }).join('') || '<div class="list-empty">Không tìm thấy biến phù hợp</div>';
    sheet(`<div class="sheet-head"><h2>Biến nội dung</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <div class="alert info" style="margin-bottom:10px">${icon('info')}<div class="xs">Biến là chữ trong ngoặc nhọn, bot sẽ <b>tự thay bằng giá trị thật</b> khi gửi. VD: <code>Chào {name}, bạn có {points} điểm</code> → <i>Chào Lan, bạn có 152 điểm</i>.</div></div>
      <div class="input-icon">${icon('search')}<input class="input" id="vq" placeholder="Tìm biến: điểm, tên, giờ…"></div>
      <div id="vlist">${rows('')}</div>`, (el, close) => {
      $('#vq', el).addEventListener('input', (e) => { $('#vlist', el).innerHTML = rows(e.target.value.trim().toLowerCase()); });
      el.addEventListener('click', (e) => {
        const b = e.target.closest('[data-pick]');
        if (!b) return;
        close();
        onPick(b.dataset.pick);
        toast('Đã chèn ' + b.dataset.pick);
      });
    });
  }

  function ruleEditor(rule, groups, onSaved) {
    const r = JSON.parse(JSON.stringify(rule));
    r.conditions = { who: 'all', min_points: 0, require_reply: false, cooldown: 0, chance: 100, autodel: false, autodel_trigger: true, autodel_reply: true, autodel_target: false, ...(r.conditions || {}) };
    // Thời gian xoá riêng từng loại (rule cũ chỉ có autodel_after chung)
    ['trigger', 'reply', 'target'].forEach((k) => {
      const key = 'autodel_' + k + '_after';
      if (r.conditions[key] === undefined) r.conditions[key] = k === 'trigger' ? 0 : (r.conditions.autodel_after ?? 20);
    });
    const adCustom = {};
    ['trigger', 'reply', 'target'].forEach((k) => { adCustom[k] = !AD_PRESETS.some(([v]) => v === r.conditions['autodel_' + k + '_after']); });
    const cd = r.conditions.cooldown || 0;
    const unit = cd && cd % 86400 === 0 ? 86400 : cd && cd % 3600 === 0 ? 3600 : cd && cd % 60 === 0 ? 60 : 1;
    let lastText = null;

    const actionHtml = (a, i) => {
      const [label, hasWho] = RACT[a.type] || ['?', false];
      let f = '';
      if (hasWho) f += `<div class="segmented" style="margin-top:10px"><button type="button" data-aw="${i}" data-v="sender" class="${a.who !== 'target' ? 'active' : ''}">${a.type === 'reply' ? 'Reply người gửi' : 'Người gửi lệnh'}</button><button type="button" data-aw="${i}" data-v="target" class="${a.who === 'target' ? 'active' : ''}">Người được reply</button></div>`;
      if (['reply', 'send'].includes(a.type)) f += `<textarea class="input" data-af="${i}" data-k="text" style="margin-top:10px;min-height:80px" placeholder="Nội dung… dùng biến như {mention}, {points}">${esc(a.text || '')}</textarea>
        <div class="pv"><div class="pv-label">👁 Xem trước (giá trị mẫu)</div><div class="pv-body" data-pv="${i}">${a.text ? previewText(a.text) : '<span class="muted">Nội dung sẽ hiện ở đây…</span>'}</div></div>`;
      if (a.type === 'points') f += `<div class="row" style="margin-top:10px"><input class="input" type="number" data-af="${i}" data-k="amount" value="${a.amount ?? 1}" style="text-align:center;font-weight:700"><span class="small muted nowrap">điểm (âm = trừ)</span></div>`;
      if (a.type === 'mute') f += `<div class="row" style="margin-top:10px"><input class="input" type="number" min="0" data-af="${i}" data-k="minutes" value="${a.minutes ?? 60}"><span class="small muted nowrap">phút (0 = vĩnh viễn)</span></div>`;
      if (['warn', 'mute', 'kick', 'ban'].includes(a.type)) f += `<input class="input" data-af="${i}" data-k="reason" value="${esc(a.reason || '')}" placeholder="Lý do (tuỳ chọn)" style="margin-top:10px">`;
      return `<div class="card card-pad" style="box-shadow:none;margin-bottom:10px;background:var(--surface-2)">
        <div class="row"><span class="badge blue">${i + 1}</span><select class="input" data-at="${i}" style="height:38px;flex:1">${Object.entries(RACT).map(([k, v]) => `<option value="${k}" ${k === a.type ? 'selected' : ''}>${v[0]}</option>`).join('')}</select>
        <button type="button" class="btn icon sm ghost" data-adel="${i}" aria-label="Xoá">${icon('trash', 'sm')}</button></div>${f}</div>`;
    };

    const html = () => {
      const c = r.conditions;
      const isCmd = r.trigger_type === 'command';
      const usesTarget = r.actions.some((a) => a.who === 'target');
      return `
      <div class="sheet-head"><h2>${r.id ? 'Sửa lệnh' : 'Tạo lệnh'}</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <form id="fr" class="stack-lg">
        <div class="stack">
          <div class="row" style="gap:8px"><span class="badge blue">KHI</span><b>Kích hoạt khi</b></div>
          <div class="chips" style="flex-wrap:wrap">${Object.entries(TRIGGER).map(([k, v]) => `<button type="button" class="chip ${k === r.trigger_type ? 'active' : ''}" data-tt="${k}">${v[0]}</button>`).join('')}</div>
          <label class="field">${isCmd ? `<div class="input-icon"><span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-weight:700;color:var(--muted)">/</span><input class="input mono" name="trigger_value" value="${esc(r.trigger_value)}" placeholder="diemdanh" style="padding-left:28px" autocapitalize="off" required></div>`
            : `<input class="input" name="trigger_value" value="${esc(r.trigger_value)}" placeholder="${r.trigger_type === 'regex' ? '^gia (\\w+)$' : 'từ khoá 1|từ khoá 2'}" required>`}
            <div class="hint">${TRIGGER[r.trigger_type][1]}${isCmd ? '. Chỉ chữ thường không dấu, số, dấu _' : ''}</div></label>
          <label class="field"><span class="lbl">Tên / mô tả lệnh</span><input class="input" name="name" value="${esc(r.name || '')}" placeholder="VD: Điểm danh hằng ngày" maxlength="120"></label>
          <label class="field"><span class="lbl">Áp dụng cho</span><select class="input" name="group_id"><option value="">Tất cả nhóm</option>${groups.map((g) => `<option value="${g.id}" ${g.id === r.group_id ? 'selected' : ''}>${esc(g.title)}</option>`).join('')}</select></label>
        </div>

        <div class="stack">
          <div class="row" style="gap:8px"><span class="badge amber">NẾU</span><b>Điều kiện</b></div>
          <div class="field"><span class="lbl">Ai được dùng</span><div class="segmented" id="who">${[['all', 'Mọi người'], ['admin', 'Chỉ admin'], ['member', 'Không phải admin']].map(([k, t]) => `<button type="button" data-who="${k}" class="${c.who === k ? 'active' : ''}">${t}</button>`).join('')}</div></div>
          <div class="grid grid-2">
            <label class="field"><span class="lbl">Điểm tối thiểu</span><input class="input" type="number" min="0" name="min_points" value="${c.min_points || 0}"></label>
            <label class="field"><span class="lbl">Tỉ lệ thành công (%)</span><input class="input" type="number" min="1" max="100" name="chance" value="${c.chance || 100}"></label>
          </div>
          <div class="field"><span class="lbl">Thời gian chờ mỗi người</span><div class="row"><input class="input" type="number" min="0" name="cd_val" value="${cd / unit}"><select class="input" name="cd_unit" style="width:auto">${[[1, 'giây'], [60, 'phút'], [3600, 'giờ'], [86400, 'ngày']].map(([v, t]) => `<option value="${v}" ${v === unit ? 'selected' : ''}>${t}</option>`).join('')}</select></div><div class="hint">0 = không giới hạn. VD điểm danh: 1 ngày</div></div>
          <div class="card" style="box-shadow:none">${sw('require_reply', c.require_reply, 'Bắt buộc reply tin nhắn', usesTarget ? 'Nên bật vì có hành động lên "người được reply"' : 'Phải reply tin của ai đó mới chạy')}</div>
        </div>

        <div class="stack">
          <div class="row" style="gap:8px"><span class="badge green">THÌ</span><b>Hành động (chạy lần lượt)</b></div>
          <div id="acts">${r.actions.map(actionHtml).join('')}</div>
          <button type="button" class="btn soft block" data-addact>${icon('plus', 'sm')}Thêm hành động</button>
          <div class="field"><span class="lbl">Biến hay dùng (chạm để chèn vào nội dung)</span>
            <div class="chips" style="flex-wrap:wrap">${VAR_QUICK.map((v) => { const d = VAR_LIST.find((x) => x[0] === v); return `<button type="button" class="chip var-chip" data-var="${v}" title="${esc(d[1])}"><code>${v}</code><small>${esc(d[1].split('(')[0].trim())}</small></button>`; }).join('')}</div>
            <button type="button" class="btn sm soft block" data-allvars style="margin-top:10px">📖 Xem tất cả ${VAR_LIST.length} biến kèm giải thích</button>
            <div class="hint">Định dạng: &lt;b&gt;đậm&lt;/b&gt; · &lt;i&gt;nghiêng&lt;/i&gt; · &lt;u&gt;gạch chân&lt;/u&gt; · &lt;code&gt;mã&lt;/code&gt; · &lt;a href="https://…"&gt;link&lt;/a&gt;</div></div>
        </div>

        <div class="stack">
          <div class="row" style="gap:8px"><span class="badge">🧹</span><b>Tự động xoá</b></div>
          <div class="card" style="box-shadow:none">
            ${sw('autodel', c.autodel, 'Tự động xoá tin nhắn', c.autodel ? 'Giữ nhóm gọn gàng sau khi dùng lệnh' : 'Đang tắt: tin nhắn được giữ lại')}
            ${c.autodel ? (() => {
              const kinds = [['trigger', '🙋 Tin người gửi', 'Tin gõ lệnh / từ khoá'], ['reply', '🤖 Tin bot trả lời', 'Gồm cả tin báo lỗi, VD "Bạn cần đợi…"'], ['target', '↩️ Tin được reply', 'Tin của người nhận']];
              const sel = kinds.filter(([k]) => c['autodel_' + k]).length;
              const row = ([k, t, d]) => {
                const on = !!c['autodel_' + k];
                const after = Number(c['autodel_' + k + '_after']) || 0;
                const preset = AD_PRESETS.some(([v]) => v === after) && !adCustom[k];
                const u = after && after % 86400 === 0 ? 86400 : after && after % 3600 === 0 ? 3600 : after && after % 60 === 0 ? 60 : 1;
                return `<div class="form-group">
                  <label class="row" style="cursor:pointer"><div class="grow"><b style="font-size:14.5px">${t}</b><div class="xs muted">${d}</div></div>
                    <div class="switch"><input type="checkbox" name="autodel_${k}" ${on ? 'checked' : ''}><span></span></div></label>
                  ${on ? `<div class="row" style="margin-top:10px"><span class="small muted nowrap">Xoá</span>
                    <select class="input" name="ad_${k}_sel" style="height:40px">${AD_PRESETS.map(([v, l]) => `<option value="${v}" ${preset && v === after ? 'selected' : ''}>${l}</option>`).join('')}<option value="custom" ${preset ? '' : 'selected'}>Tuỳ chỉnh…</option></select></div>
                    ${preset ? '' : `<div class="row" style="margin-top:8px"><span class="small muted nowrap">Sau</span><input class="input" type="number" min="0" name="ad_${k}_val" value="${after / u}" style="height:40px"><select class="input" name="ad_${k}_unit" style="width:auto;height:40px">${[[1, 'giây'], [60, 'phút'], [3600, 'giờ'], [86400, 'ngày']].map(([v, l]) => `<option value="${v}" ${v === u ? 'selected' : ''}>${l}</option>`).join('')}</select></div>`}` : ''}
                </div>`;
              };
              const combos = [
                ['Người gửi ngay · bot 20 giây', { trigger: 0, reply: 20 }],
                ['Người gửi ngay · bot 1 phút', { trigger: 0, reply: 60 }],
                ['Cả 2 sau 30 giây', { trigger: 30, reply: 30 }],
                ['Chỉ xoá lệnh ngay', { trigger: 0 }],
                ['Xoá tất cả ngay', { trigger: 0, reply: 0, target: 0 }],
              ];
              return `<div class="form-group"><div class="xs muted" style="margin-bottom:6px">Chọn nhanh</div>
                  <div class="chips" style="flex-wrap:wrap">${combos.map(([l, v], i) => {
                    const match = kinds.every(([k]) => (k in v) === !!c['autodel_' + k] && (!(k in v) || v[k] === Number(c['autodel_' + k + '_after'])));
                    return `<button type="button" class="chip ${match ? 'active' : ''}" data-adc="${i}" style="height:32px;font-size:12.5px">${l}</button>`;
                  }).join('')}</div></div>
                ${kinds.map(row).join('')}
                ${sel ? '' : `<div class="form-group"><div class="hint" style="color:var(--danger);margin:0">Bật ít nhất 1 loại tin cần xoá</div></div>`}
                <div class="form-group"><div class="alert info" style="margin:0">${icon('info')}<div class="xs">Bot cần quyền <b>Xoá tin nhắn</b>. Tối đa 2 ngày (giới hạn Telegram). Hẹn trên 20 giây nên đặt Cron mỗi 1 phút (trang Hệ thống) để xoá đúng giờ.</div></div></div>`;
            })() : ''}
          </div>
        </div>

        <div class="card" style="box-shadow:none">
          ${sw('enabled', r.enabled !== 0, 'Bật lệnh')}
          ${isCmd ? sw('show_in_menu', !!r.show_in_menu, 'Hiện trong menu lệnh Telegram', 'Hiện khi gõ "/" trong nhóm') : ''}
        </div>
        ${r.actions.some((a) => ['kick', 'ban', 'mute', 'warn'].includes(a.type) && a.who === 'target') && c.who !== 'admin' ? `<div class="alert warn">${icon('alert')}<div class="small">Lệnh có hành động xử phạt người khác nhưng <b>ai cũng dùng được</b>. Nên chọn "Chỉ admin".</div></div>` : ''}
        <div class="grid ${r.id ? 'grid-2' : ''}">${r.id ? `<button type="button" class="btn soft-danger" data-rdel>${icon('trash', 'sm')}Xoá</button>` : ''}<button class="btn primary" style="height:46px">${icon('check', 'sm')}Lưu lệnh</button></div>
      </form>`;
    };

    // Đồng bộ giá trị form vào r trước khi vẽ lại
    const collect = (el) => {
      const f = $('#fr', el);
      if (!f) return;
      r.trigger_value = f.trigger_value.value;
      r.name = f.name.value;
      r.group_id = f.group_id.value ? Number(f.group_id.value) : null;
      r.conditions.min_points = Number(f.min_points.value) || 0;
      r.conditions.chance = Number(f.chance.value) || 100;
      r.conditions.cooldown = (Number(f.cd_val.value) || 0) * Number(f.cd_unit.value);
      r.conditions.require_reply = f.require_reply.checked;
      r.conditions.autodel = f.autodel.checked;
      ['trigger', 'reply', 'target'].forEach((k) => {
        if (f['autodel_' + k]) r.conditions['autodel_' + k] = f['autodel_' + k].checked;
        const sel = f['ad_' + k + '_sel'];
        if (!sel) return;
        if (sel.value === 'custom') {
          adCustom[k] = true;
          if (f['ad_' + k + '_val']) r.conditions['autodel_' + k + '_after'] = Math.min(172800, (Number(f['ad_' + k + '_val'].value) || 0) * Number(f['ad_' + k + '_unit'].value));
        } else {
          adCustom[k] = false;
          r.conditions['autodel_' + k + '_after'] = Number(sel.value);
        }
      });
      r.enabled = f.enabled.checked ? 1 : 0;
      if (f.show_in_menu) r.show_in_menu = f.show_in_menu.checked ? 1 : 0;
      $$('[data-af]', el).forEach((x) => { const a = r.actions[x.dataset.af]; a[x.dataset.k] = x.type === 'number' ? Number(x.value) : x.value; });
    };

    sheet('<div id="redit"></div>', (el, close) => {
      const box = $('#redit', el);
      const draw = () => { box.innerHTML = html(); };
      const redraw = () => { collect(el); draw(); };
      draw();
      el.addEventListener('focusin', (e) => { if (e.target.matches('textarea[data-af]')) lastText = e.target; });
      const updatePreview = (ta) => {
        const pv = $(`[data-pv="${ta.dataset.af}"]`, el);
        if (pv) pv.innerHTML = ta.value.trim() ? previewText(ta.value) : '<span class="muted">Nội dung sẽ hiện ở đây…</span>';
      };
      el.addEventListener('input', (e) => { if (e.target.matches('textarea[data-af]')) updatePreview(e.target); });
      const insertVar = (code) => {
        const ta = lastText && document.contains(lastText) ? lastText : $('textarea[data-af]', el);
        if (!ta) return toast('Thêm hành động "Trả lời" hoặc "Gửi tin" trước', 'err');
        const p = ta.selectionStart ?? ta.value.length;
        ta.value = ta.value.slice(0, p) + code + ta.value.slice(ta.selectionEnd ?? p);
        ta.focus({ preventScroll: true });
        ta.selectionStart = ta.selectionEnd = p + code.length;
        lastText = ta;
        updatePreview(ta);
      };
      el.addEventListener('change', (e) => {
        const t = e.target.closest('[data-at]');
        if (t) { collect(el); const a = r.actions[t.dataset.at]; a.type = t.value; if (!RACT[a.type][1]) a.who = 'sender'; draw(); }
        if (/^(autodel(_trigger|_reply|_target)?|ad_\w+_sel)$/.test(e.target.name || '')) { collect(el); draw(); }
      });
      el.addEventListener('click', async (e) => {
        const b = e.target.closest('button');
        if (!b) return;
        if (b.dataset.tt) { collect(el); r.trigger_type = b.dataset.tt; if (r.trigger_type === 'command') r.trigger_value = r.trigger_value.replace(/[^a-z0-9_]/gi, '').toLowerCase(); draw(); }
        else if (b.dataset.adc !== undefined) {
          collect(el);
          const v = [{ trigger: 0, reply: 20 }, { trigger: 0, reply: 60 }, { trigger: 30, reply: 30 }, { trigger: 0 }, { trigger: 0, reply: 0, target: 0 }][b.dataset.adc];
          ['trigger', 'reply', 'target'].forEach((k) => {
            r.conditions['autodel_' + k] = k in v;
            if (k in v) r.conditions['autodel_' + k + '_after'] = v[k];
            adCustom[k] = false;
          });
          draw();
        }
        else if (b.dataset.who) { collect(el); r.conditions.who = b.dataset.who; draw(); }
        else if (b.dataset.aw !== undefined) { collect(el); r.actions[b.dataset.aw].who = b.dataset.v; draw(); }
        else if (b.dataset.adel !== undefined) { collect(el); r.actions.splice(Number(b.dataset.adel), 1); draw(); }
        else if (b.hasAttribute('data-addact')) { collect(el); r.actions.push({ type: 'reply', who: 'sender', text: '' }); draw(); }
        else if (b.dataset.var) insertVar(b.dataset.var);
        else if (b.hasAttribute('data-allvars')) varPicker(insertVar); else if (b.hasAttribute('data-rdel')) {
          if (!(await confirmBox('Xoá lệnh?', `Xoá lệnh <b>${esc(r.name)}</b>?`, { danger: true, ok: 'Xoá' }))) return;
          await api('rules/delete', { id: r.id }); close(); toast('Đã xoá lệnh'); onSaved();
        }
      });
      el.addEventListener('submit', async (e) => {
        e.preventDefault();
        collect(el);
        await withBtn(e.target.querySelector('button.primary'), async () => {
          try {
            const res = await api('rules/save', r);
            close();
            toast('Đã lưu lệnh' + (r.trigger_type === 'command' ? ' /' + res.rule.trigger_value : ''));
            (res.menu_errors || []).length && toast('Không cập nhật được menu ở: ' + res.menu_errors.join(', '), 'err');
            onSaved();
          } catch (err) { toast(err.message, 'err'); }
        });
      });
      void redraw;
    });
  }

  route('/rules', async () => { const { body, mount } = rulesBlock(null); return { title: 'Lệnh tự tạo', html: body, mount }; }, { admin: true, title: 'Lệnh tự tạo' });

  // ================= Admin: More / Users / System =================
  route('/more', async () => ({
    title: 'Thêm',
    html: `
      <div class="card card-pad row" style="margin-bottom:14px">${avatar(S.user.display_name, S.user.id, 'lg')}<div class="grow"><h2>${esc(S.user.display_name)}</h2><div class="small muted">@${esc(S.user.username)} · Quản trị viên</div></div></div>
      <div class="list">
        <a class="list-item" href="#/rules">${icon('terminal')}<div class="grow">Lệnh tự tạo</div><span class="chev">${icon('chevron')}</span></a>
        <a class="list-item" href="#/users">${icon('key')}<div class="grow">Tài khoản web</div><span class="chev">${icon('chevron')}</span></a>
        <a class="list-item" href="#/system">${icon('download')}<div class="grow">Hệ thống & cập nhật</div><span class="chev">${icon('chevron')}</span></a>
        <a class="list-item" href="#/me">${icon('star')}<div class="grow">Điểm của tôi</div><span class="chev">${icon('chevron')}</span></a>
        <a class="list-item" href="#/account">${icon('user')}<div class="grow">Tài khoản & liên kết Telegram</div><span class="chev">${icon('chevron')}</span></a>
        <button class="list-item" data-theme-toggle>${icon('moon')}<div class="grow">Giao diện sáng / tối</div></button>
        <button class="list-item" data-logout style="color:var(--danger)">${icon('logout')}<div class="grow">Đăng xuất</div></button>
      </div>`,
  }), { admin: true, title: 'Thêm' });

  route('/users', async () => {
    const users = await api('users');
    const item = (u) => `<button class="list-item" data-user='${esc(JSON.stringify(u))}'>${avatar(u.display_name, u.id)}
      <div class="grow"><div class="row" style="gap:6px"><b class="ellipsis">${esc(u.display_name)}</b>${u.role === 'admin' ? '<span class="badge blue">Admin</span>' : '<span class="badge">Thành viên</span>'}${u.disabled ? '<span class="badge red">Khoá</span>' : ''}</div>
      <div class="xs muted">@${esc(u.username)}${u.tg_user_id ? ' · TG ' + u.tg_user_id : ' · chưa liên kết'} · ${ago(u.last_login)}</div></div><span class="chev">${icon('chevron')}</span></button>`;
    const form = (u) => sheet(`<div class="sheet-head"><h2>${u ? 'Sửa tài khoản' : 'Tạo tài khoản'}</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <form class="stack" id="fu">
        ${u ? `<div class="small muted">@${esc(u.username)}</div>` : `<label class="field"><span class="lbl">Tên đăng nhập</span><input class="input" name="username" required></label>`}
        <label class="field"><span class="lbl">Tên hiển thị</span><input class="input" name="display_name" value="${esc(u?.display_name || '')}"></label>
        <label class="field"><span class="lbl">${u ? 'Mật khẩu mới (bỏ trống nếu giữ)' : 'Mật khẩu'}</span><input class="input" type="password" name="password" ${u ? '' : 'required'} minlength="6" autocomplete="new-password"></label>
        <label class="field"><span class="lbl">ID Telegram</span><input class="input" name="tg_user_id" inputmode="numeric" value="${esc(u?.tg_user_id || '')}"><div class="hint">Admin có ID Telegram sẽ dùng được lệnh quản trị của bot</div></label>
        <label class="field"><span class="lbl">Vai trò</span><select class="input" name="role"><option value="member">Thành viên</option><option value="admin" ${u?.role === 'admin' ? 'selected' : ''}>Quản trị viên</option></select></label>
        ${u ? `<div class="card" style="box-shadow:none">${sw('disabled', u.disabled, 'Khoá tài khoản')}</div>` : ''}
        <div class="grid ${u ? 'grid-2' : ''}">${u ? `<button type="button" class="btn soft-danger" data-deluser>${icon('trash', 'sm')}Xoá</button>` : ''}<button class="btn primary">Lưu</button></div>
      </form>`, (el, close) => {
      $('#fu', el).onsubmit = async (e) => {
        e.preventDefault();
        try { await api('users/save', { id: u?.id, ...formData(e.target) }); close(); toast('Đã lưu'); render(); } catch (err) { toast(err.message, 'err'); }
      };
      $('[data-deluser]', el)?.addEventListener('click', async () => {
        close();
        if (await confirmBox('Xoá tài khoản?', `Xoá tài khoản <b>${esc(u.username)}</b>?`, { danger: true, ok: 'Xoá' })) {
          try { await api('users/delete', { id: u.id }); toast('Đã xoá'); render(); } catch (err) { toast(err.message, 'err'); }
        }
      });
    });
    return {
      title: 'Tài khoản web',
      actions: `<button class="btn primary sm" data-newuser>${icon('plus', 'sm')}Tạo</button>`,
      html: `<div class="list">${users.map(item).join('')}</div>`,
      mount: (root) => {
        $('[data-newuser]').onclick = () => form(null);
        root.addEventListener('click', (e) => { const b = e.target.closest('[data-user]'); if (b) form(JSON.parse(b.dataset.user)); });
      },
    };
  }, { admin: true, title: 'Tài khoản web', back: '/more' });

  route('/system', async () => {
    const d = await api('system');
    const s = d.settings, v = d.version, env = d.env;
    return {
      title: 'Hệ thống',
      html: `
      <div class="card card-pad">
        <div class="row"><div class="stat" style="padding:0"><span class="ico" style="width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:var(--primary-soft);color:var(--primary)">${icon('git')}</span></div>
          <div class="grow"><h2>Cập nhật từ GitHub</h2><div class="small muted">Phiên bản ${esc(v.version)}${v.sha ? ' · <span class="mono">' + esc(v.sha.slice(0, 7)) + '</span>' : ''}${v.updated_at ? ' · ' + fmtDate(v.updated_at, true) : ''}</div></div></div>
        <div id="upd" style="margin-top:14px"></div>
        <div class="grid grid-2" style="margin-top:12px"><button class="btn" data-checkupd>${icon('refresh', 'sm')}Kiểm tra</button><button class="btn primary" data-doupd>${icon('download', 'sm')}Cập nhật</button></div>
        ${!env.writable ? `<div class="alert warn" style="margin-top:12px">${icon('alert')}<div class="small">Thư mục web không có quyền ghi. Trong aaPanel → Files, đặt owner <b>www</b> cho thư mục website.</div></div>` : ''}
        ${!env.zip ? `<div class="alert warn" style="margin-top:12px">${icon('alert')}<div class="small">PHP thiếu extension <b>zip</b>. aaPanel → App Store → PHP → Settings → Install extensions → zip.</div></div>` : ''}
      </div>

      <form id="fsys">
        <div class="section-title"><h2>Cài đặt chung</h2></div>
        <div class="card">
          <div class="form-group"><label class="field"><span class="lbl">Tên website</span><input class="input" name="site_name" value="${esc(s.site_name)}"></label></div>
          <div class="form-group"><label class="field"><span class="lbl">Địa chỉ web (dùng cho webhook)</span><input class="input" name="site_url" value="${esc(s.site_url)}" placeholder="${esc(d.detected_url)}"><div class="hint">Phải là <b>https://</b>. Tự nhận: ${esc(d.detected_url)}</div></label></div>
          ${sw('allow_register', s.allow_register === '1', 'Cho phép đăng ký thành viên', 'Thành viên tự tạo tài khoản để xem điểm')}
        </div>
        <div class="section-title"><h2>Nguồn cập nhật</h2></div>
        <div class="card">
          <div class="form-group"><label class="field"><span class="lbl">Repo GitHub</span><input class="input" name="update_repo" value="${esc(s.update_repo)}" placeholder="owner/repo"></label></div>
          <div class="form-group"><label class="field"><span class="lbl">Nhánh</span><input class="input" name="update_branch" value="${esc(s.update_branch)}" placeholder="main"></label></div>
          <div class="form-group"><label class="field"><span class="lbl">GitHub token (repo riêng tư)</span><input class="input" type="password" name="update_token" value="${esc(s.update_token)}" autocomplete="off" placeholder="ghp_… (bỏ trống nếu repo công khai)"></label></div>
        </div>
        <button class="btn primary block" style="height:46px;margin-top:14px">${icon('check')}Lưu cài đặt</button>
      </form>

      <div class="section-title"><h2>Tác vụ định kỳ (Cron)</h2></div>
      <div class="card card-pad stack"><div class="small text-2">aaPanel → <b>Cron</b> → Add → Type: <b>Access URL</b>, <b>mỗi 1 phút</b> (cần cho tự động xoá tin hẹn giờ):</div>
        <div class="row"><input class="input mono" readonly value="${esc(d.cron_url)}"><button class="btn icon" data-copy="${esc(d.cron_url)}">${icon('copy')}</button></div></div>

      <div class="section-title"><h2>Môi trường</h2></div>
      <dl class="kv card card-pad">
        <dt>PHP</dt><dd>${esc(env.php)}</dd><dt>Database</dt><dd>${env.db === 'mysql' ? 'MySQL' : 'SQLite'}</dd>
        <dt>Máy chủ</dt><dd>${esc(env.server || '—')}</dd><dt>cURL</dt><dd>${env.curl ? '✅' : '❌'}</dd><dt>Zip</dt><dd>${env.zip ? '✅' : '❌'}</dd><dt>Quyền ghi</dt><dd>${env.writable ? '✅' : '❌'}</dd>
      </dl>`,
      mount: (root) => {
        const box = $('#upd', root);
        const check = async (btn) => withBtn(btn, async () => {
          try {
            const r = await api('system/check-update');
            box.innerHTML = r.has_update
              ? `<div class="alert info">${icon('download')}<div><b>Có bản mới</b> <span class="mono">${esc(r.latest.short)}</span> · ${fmtDate(r.latest.date, true)}<div class="small text-2" style="white-space:pre-wrap;margin-top:4px">${esc(r.commits.length ? r.commits.map((c) => '• ' + c.message).join('\n') : r.latest.message)}</div></div></div>`
              : `<div class="alert ok">${icon('check')}<div>Bạn đang dùng bản mới nhất (<span class="mono">${esc(r.latest.short)}</span>) từ <b>${esc(r.repo)}</b>@${esc(r.branch)}</div></div>`;
          } catch (err) { box.innerHTML = `<div class="alert err">${icon('alert')}<div class="small">${esc(err.message)}</div></div>`; }
        });
        $('[data-checkupd]', root).onclick = (e) => check(e.currentTarget);
        $('[data-doupd]', root).onclick = async (e) => {
          if (!(await confirmBox('Cập nhật hệ thống?', 'Tải mã nguồn mới nhất từ GitHub và ghi đè lên web. Dữ liệu (thư mục data/) được giữ nguyên.', { ok: 'Cập nhật' }))) return;
          const btn = e.target.closest('button');
          await withBtn(btn, async () => {
            try {
              const r = await api('system/update', {});
              box.innerHTML = `<div class="alert ok">${icon('check')}<div class="small">${r.log.map(esc).join('<br>')}</div></div>`;
              try { await api('system/migrate', {}); } catch {}
              toast('Cập nhật thành công! Đang tải lại…');
              setTimeout(() => location.reload(), 1500);
            } catch (err) { box.innerHTML = `<div class="alert err">${icon('alert')}<div class="small">${esc(err.message)}</div></div>`; }
          });
        };
        $('#fsys', root).onsubmit = async (e) => {
          e.preventDefault();
          const data = formData(e.target);
          await withBtn(e.target.querySelector('button.primary'), async () => {
            try {
              const r = await api('system/settings', { settings: data });
              S.site = r.settings.site_name;
              toast('Đã lưu cài đặt');
              (r.webhook_errors || []).forEach((m) => toast(m, 'err'));
            } catch (err) { toast(err.message, 'err'); }
          });
        };
        check($('[data-checkupd]', root));
      },
    };
  }, { admin: true, title: 'Hệ thống', back: '/more' });

  // ================= Member views =================
  async function viewMe() {
    const d = await api('me');
    const u = d.user;
    if (!u.tg_user_id) {
      return {
        title: S.user.role === 'admin' ? 'Điểm của tôi' : 'Của tôi',
        html: `<div class="hero"><div class="muted small">Xin chào 👋</div><h1 style="margin-top:4px">${esc(u.display_name)}</h1><div class="muted small" style="margin-top:6px">Liên kết tài khoản Telegram để xem điểm tương tác, thứ hạng và trạng thái của bạn trong các nhóm.</div></div>
          <div style="margin-top:14px">${linkCard(d)}</div>`,
        mount: mountLink,
      };
    }
    const ms = d.memberships;
    const total = ms.reduce((a, b) => a + b.points, 0);
    const msgs = ms.reduce((a, b) => a + b.messages, 0);
    const best = ms.reduce((a, b) => (!a || b.rank < a.rank ? b : a), null);
    return {
      title: S.user.role === 'admin' ? 'Điểm của tôi' : 'Của tôi',
      actions: `<button class="btn icon ghost only-mobile" data-theme-toggle aria-label="Đổi giao diện">${icon('moon')}</button>`,
      html: `
      <div class="hero">
        <div class="muted small">Tổng điểm tương tác</div>
        <div class="big">${num(total)}</div>
        <div class="row wrap small" style="gap:14px;margin-top:6px"><span>${icon('msg', 'sm')} ${num(msgs)} tin nhắn</span><span>${icon('users', 'sm')} ${ms.length} nhóm</span>${best ? `<span>${icon('trophy', 'sm')} Hạng cao nhất #${best.rank}</span>` : ''}</div>
      </div>
      <div class="section-title"><h2>Nhóm của bạn</h2></div>
      ${ms.length ? ms.map((m) => `
        <a class="card card-pad" href="#/rank/${m.group_id}" style="display:block;color:inherit;margin-bottom:12px">
          <div class="row">${avatar(m.title, m.group_id, 'sq')}<div class="grow"><div class="ellipsis" style="font-weight:600">${esc(m.title)}</div>
          <div class="row wrap" style="gap:6px;margin-top:2px">${statusBadge(m)}${m.warns ? `<span class="badge amber">⚠ ${m.warns}/${m.warn_limit} cảnh cáo</span>` : ''}</div></div>
          <div style="text-align:right"><div class="points" style="font-size:20px">${num(m.points)}</div><div class="xs muted">điểm</div></div></div>
          <div class="row between small" style="margin-top:12px"><span class="muted">Hạng <b style="color:var(--text)">#${m.rank}</b> / ${num(m.total)}</span><span class="muted">${num(m.messages)} tin nhắn</span></div>
          <div class="progress" style="margin-top:6px"><div style="width:${Math.max(4, 100 - ((m.rank - 1) / Math.max(1, m.total)) * 100)}%"></div></div>
          ${m.status === 'muted' ? `<div class="alert warn" style="margin-top:10px">${icon('mute')}<div class="small">Bạn đang bị cấm chat ${m.muted_until ? 'đến ' + fmtDate(m.muted_until, true) : ''}</div></div>` : ''}
        </a>`).join('') : `<div class="card">${empty('users', 'Bot chưa ghi nhận bạn trong nhóm nào.<br>Hãy nhắn tin trong nhóm có bot.')}</div>`}`,
    };
  }

  function linkCard(d) {
    const bot = d.bots[0];
    return `<div class="card card-pad stack" id="linkcard">
      <div class="row"><span style="color:var(--primary)">${icon('link')}</span><h3 class="grow">Liên kết Telegram</h3></div>
      ${d.link_code ? `
        <div class="code-box"><span>${esc(d.link_code)}</span><button class="btn icon sm ghost" data-copy="/link ${esc(d.link_code)}">${icon('copy', 'sm')}</button></div>
        ${bot ? `<a class="btn primary block" href="https://t.me/${esc(bot)}?start=link_${esc(d.link_code)}" target="_blank" rel="noopener">${icon('send', 'sm')}Mở @${esc(bot)} để liên kết</a>` : ''}
        <div class="small muted">Hoặc gửi <code>/link ${esc(d.link_code)}</code> cho bot trong chat riêng. Sau đó nhấn <b>Tôi đã liên kết</b>.</div>
        <button class="btn block" data-relink>${icon('refresh', 'sm')}Tôi đã liên kết</button>`
        : `<div class="small text-2">Tạo mã rồi gửi cho bot qua chat riêng để xác minh tài khoản Telegram của bạn.</div><button class="btn primary block" data-gencode>Tạo mã liên kết</button>`}
    </div>`;
  }
  function mountLink(root) {
    root.addEventListener('click', async (e) => {
      if (e.target.closest('[data-gencode]')) { await api('me/link-code', {}); render(); }
      if (e.target.closest('[data-relink]')) {
        const st = await api('status');
        S.user = st.user;
        if (st.user?.tg_user_id) { toast('Liên kết thành công!'); } else toast('Chưa nhận được liên kết, hãy gửi mã cho bot', 'err');
        render();
      }
    });
  }

  route('/me', viewMe, { title: 'Điểm của tôi', back: '/more' });

  route('/rank', async () => {
    const d = await api('me');
    if (!d.memberships.length) return { title: 'Xếp hạng', html: `<div class="card">${empty('trophy', d.user.tg_user_id ? 'Bạn chưa tham gia nhóm nào có bot.' : 'Hãy liên kết Telegram ở trang "Của tôi" để xem bảng xếp hạng.')}</div>` };
    if (d.memberships.length === 1) return viewRank(d.memberships[0].group_id, false);
    return {
      title: 'Xếp hạng',
      html: `<div class="list">${d.memberships.map((m) => `<a class="list-item" href="#/rank/${m.group_id}">${avatar(m.title, m.group_id, 'sq')}<div class="grow"><div class="ellipsis" style="font-weight:600">${esc(m.title)}</div><div class="xs muted">Hạng #${m.rank} · ${num(m.points)} điểm</div></div><span class="chev">${icon('chevron')}</span></a>`).join('')}</div>`,
    };
  }, { title: 'Xếp hạng' });

  route('/rank/:gid', (p) => viewRank(p.gid, true), { title: 'Xếp hạng', back: '/rank' });

  async function viewRank(gid) {
    const d = await api('leaderboard', undefined, { query: { group_id: gid } });
    const rows = d.rows;
    const podium = rows.slice(0, 3);
    const order = [1, 0, 2].filter((i) => podium[i]);
    const h = [92, 120, 76];
    return {
      title: d.group.title,
      html: `
      ${podium.length ? `<div class="card card-pad" style="display:flex;align-items:flex-end;justify-content:center;gap:10px;padding-top:22px">
        ${order.map((i) => { const r = podium[i]; return `<div style="flex:1;max-width:120px;text-align:center">
          <div style="display:flex;justify-content:center">${avatar(r.name, r.tg_user_id, i === 0 ? 'lg' : '')}</div>
          <div class="small ellipsis" style="font-weight:600;margin-top:6px">${esc(r.name)}</div><div class="xs muted">${num(r.points)} điểm</div>
          <div style="height:${h[i]}px;margin-top:8px;border-radius:12px 12px 4px 4px;background:${['linear-gradient(180deg,#f5c542,#e0a100)', 'linear-gradient(180deg,#c7d0dc,#8a96a8)', 'linear-gradient(180deg,#e8995a,#c26d2b)'][i]};display:grid;place-items:center;color:#fff;font-size:22px;font-weight:700">${i + 1}</div></div>`; }).join('')}
      </div>` : ''}
      <div class="list" style="margin-top:12px">${rows.length ? rows.map((r, i) => `
        <div class="list-item" style="${r.tg_user_id === d.me ? 'background:var(--primary-soft)' : ''}"><div class="rank r${i + 1}">${i + 1}</div>${avatar(r.name, r.tg_user_id)}
          <div class="grow"><div class="ellipsis" style="font-weight:600">${esc(r.name)}${r.tg_user_id === d.me ? ' <span class="badge blue">Bạn</span>' : ''}</div><div class="xs muted">${num(r.messages)} tin nhắn</div></div>
          <div class="points">${num(r.points)}</div></div>`).join('') : empty('trophy', 'Chưa có dữ liệu')}</div>`,
    };
  }

  route('/account', async () => {
    const d = await api('me');
    const u = d.user;
    return {
      title: 'Tài khoản',
      html: `
      <div class="card card-pad row" style="margin-bottom:14px">${avatar(u.display_name, u.id, 'lg')}<div class="grow"><h2>${esc(u.display_name)}</h2><div class="small muted">@${esc(u.username)} · ${u.role === 'admin' ? 'Quản trị viên' : 'Thành viên'}</div>
        <div class="xs muted">Tham gia ${fmtDate(u.created_at)}</div></div></div>
      ${u.tg_user_id ? `<div class="card card-pad row" style="margin-bottom:14px"><span style="color:var(--success)">${icon('check')}</span><div class="grow"><b>Đã liên kết Telegram</b><div class="xs muted mono">${esc(u.tg_user_id)}</div></div><button class="btn sm soft-danger" data-unlink>Huỷ</button></div>` : `<div style="margin-bottom:14px">${linkCard(d)}</div>`}
      <form class="card card-pad stack" id="fp">
        <h3>Thông tin</h3>
        <label class="field"><span class="lbl">Tên hiển thị</span><input class="input" name="display_name" value="${esc(u.display_name)}"></label>
        <h3 style="margin-top:10px">Đổi mật khẩu</h3>
        <label class="field"><span class="lbl">Mật khẩu hiện tại</span><input class="input" type="password" name="current_password" autocomplete="current-password"></label>
        <label class="field"><span class="lbl">Mật khẩu mới</span><input class="input" type="password" name="new_password" minlength="6" autocomplete="new-password"></label>
        <button class="btn primary block">Lưu</button>
      </form>
      <div class="list" style="margin-top:14px">
        <button class="list-item" data-theme-toggle>${icon('moon')}<div class="grow">Giao diện sáng / tối</div></button>
        <button class="list-item" data-logout style="color:var(--danger)">${icon('logout')}<div class="grow">Đăng xuất</div></button>
      </div>`,
      mount: (root) => {
        mountLink(root);
        $('[data-unlink]', root)?.addEventListener('click', async () => {
          if (!(await confirmBox('Huỷ liên kết?', 'Bạn sẽ không xem được điểm cho tới khi liên kết lại.', { danger: true, ok: 'Huỷ liên kết' }))) return;
          await api('me/unlink', {}); S.user.tg_user_id = null; render();
        });
        $('#fp', root).onsubmit = async (e) => {
          e.preventDefault();
          try { const r = await api('me/update', formData(e.target)); S.user = r.user; toast('Đã lưu'); e.target.current_password.value = ''; e.target.new_password.value = ''; shell('', 'Tài khoản', S.user.role === 'admin' ? '/more' : null); $('#page-title').textContent = 'Tài khoản'; }
          catch (err) { toast(err.message, 'err'); }
        };
      },
    };
  }, { title: 'Tài khoản' });

  route('/member/:id', async (p) => { setTimeout(() => openMember(p.id), 50); return viewDashboard(); }, { admin: true });

  // ================= Boot =================
  async function boot() {
    try {
      const st = await api('status', undefined, { noRedirect: true });
      S.status = st; S.csrf = st.csrf; S.user = st.user; S.site = st.site_name; S.allowRegister = st.allow_register;
    } catch (e) {
      $('#app').innerHTML = `<div class="auth"><div class="alert err">${icon('alert')}<div>${esc(e.message)}</div></div></div>`;
      throw e;
    }
  }
  window.addEventListener('hashchange', render);
  boot().then(render).catch(() => {});
})();
