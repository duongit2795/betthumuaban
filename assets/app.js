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
    megaphone: '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1z"/><path d="M15 9a3 3 0 0 1 0 6M18 6a7 7 0 0 1 0 12"/>',
    image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>',
    up: '<path d="m18 15-6-6-6 6"/>',
    down: '<path d="m6 9 6 6 6-6"/>',
    terminal: '<path d="m4 17 6-6-6-6M12 19h8"/>',
    wallet: '<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5"/><path d="M17 14h.01"/>',
    heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
    bank: '<path d="M3 21h18M4 10h16M5 10v8M9.5 10v8M14.5 10v8M19 10v8M12 3l9 5H3z"/>',
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
    rule: ['Lệnh tự tạo', 'terminal', 'blue'], ad_post: ['Quảng cáo', 'megaphone', 'blue'], username: ['Nhắc đặt username', 'user', 'amber'], login: ['Đăng nhập', 'key', 'green'], update: ['Cập nhật hệ thống', 'download', 'blue'], system: ['Hệ thống', 'settings', ''], pin: ['Ghim tin', 'pin', 'blue'], unpin: ['Bỏ ghim', 'pin', ''],
    payments_settings: ['Cài đặt nạp tiền', 'bank', ''], wallet_assign: ['Cộng tiền tay', 'wallet', 'green'], wallet_adjust: ['Điều chỉnh số dư', 'wallet', 'blue'],
  };

  // ================= State & API =================
  const S = { user: null, csrf: '', site: window.SITE_NAME || 'Tele Manager', allowRegister: false, status: null, access: null, tgLogin: null };
  // Phân quyền: admin web (toàn quyền) / quản trị viên nhóm (nhóm của mình) / thành viên
  const isWebAdmin = () => !!S.access?.web_admin;
  const isManager = () => !!S.access?.manager;
  const can = (perm) => isWebAdmin() || !!S.access?.perms?.[perm];
  const roleLabel = () => (isWebAdmin() ? 'Admin web' : isManager() ? 'Quản trị viên nhóm' : 'Thành viên');

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
    enhanceRich(ov);
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
    if (r.webAdmin && !isWebAdmin()) { toast('Chỉ admin web mới vào được mục này', 'err'); return go('/'); }
    if (r.manage && !isManager()) return go('/');
    if (r.perm && !can(r.perm)) { toast('Bạn chưa được cấp quyền vào mục này', 'err'); return go('/'); }

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
      enhanceRich(main);
      bindCharts(main);
      out.mount?.(main);
      window.scrollTo(0, 0);
    } catch (e) {
      if (seq !== renderSeq) return;
      main.innerHTML = `<div class="alert err" style="margin-top:12px">${icon('alert')}<div>${esc(e.message)}</div></div>`;
    }
  }

  const payNav = () => [...(S.pay?.wallet ? [['/wallet', 'wallet', 'Ví']] : []), ...(S.pay?.amnesty ? [['/amnesty', 'heart', 'Ân xá']] : [])];
  function navItems() {
    if (!isWebAdmin() && isManager()) {
      return {
        tab: [['/', 'home', 'Tổng quan'], ['/groups', 'users', 'Nhóm'], ['/rules', 'terminal', 'Lệnh'], ...(can('logs') ? [['/logs', 'list', 'Nhật ký']] : []), ['/more', 'grid', 'Thêm']],
        side: [['/', 'home', 'Tổng quan'], ['/groups', 'users', 'Nhóm của tôi'], ['/rules', 'terminal', 'Lệnh'], ...(can('logs') ? [['/logs', 'list', 'Nhật ký']] : []), ['/me', 'star', 'Điểm của tôi'], ['/rank', 'trophy', 'Xếp hạng'], ...payNav(), ['/account', 'user', 'Tài khoản']],
      };
    }
    if (isWebAdmin()) {
      return {
        tab: [['/', 'home', 'Tổng quan'], ['/groups', 'users', 'Nhóm'], ['/bots', 'bot', 'Bot'], ['/logs', 'list', 'Nhật ký'], ['/more', 'grid', 'Thêm']],
        side: [['/', 'home', 'Tổng quan'], ['/groups', 'users', 'Nhóm'], ['/bots', 'bot', 'Bot'], ['/rules', 'terminal', 'Lệnh'], ['/ads', 'megaphone', 'Quảng cáo'], ['/logs', 'list', 'Nhật ký'], ['/users', 'key', 'Tài khoản web'], ['/payments', 'bank', 'Nạp tiền & ân xá'], ['/system', 'settings', 'Hệ thống & cập nhật'], ['/me', 'star', 'Điểm của tôi'], ...payNav(), ['/account', 'user', 'Tài khoản']],
      };
    }
    const m = [['/', 'star', 'Của tôi'], ['/rank', 'trophy', 'Xếp hạng'], ...payNav(), ['/account', 'user', 'Tài khoản']];
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
    const direct = (p) => (p === '/' ? path === '/' : path === p || path.startsWith(p + '/') || (p === '/groups' && path.startsWith('/group/')) || (p === '/rules' && path === '/commands'));
    const inTabs = items.tab.some(([p]) => p !== '/more' && direct(p));
    const isActive = (p) => direct(p) || (p === '/more' && !inTabs && ['/users', '/system', '/account', '/me', '/rules', '/commands', '/rank', '/ads', '/wallet', '/amnesty', '/payments'].some((x) => path.startsWith(x)));
    $('#tabbar').innerHTML = items.tab.map(([p, ic, t]) => `<a href="#${p}" class="${isActive(p) ? 'active' : ''}">${icon(ic)}<span>${t}</span></a>`).join('');
    $('#sidebar').innerHTML = `<div class="brand"><img src="assets/icon.svg" alt="">${esc(S.site)}</div>`
      + items.side.map(([p, ic, t]) => `<a href="#${p}" class="nav ${isActive(p) && !(p === '/more') ? 'active' : ''}">${icon(ic)}${t}</a>`).join('')
      + `<div class="foot stack"><a class="nav" href="javascript:void 0" data-theme-toggle>${icon('moon')}Giao diện sáng/tối</a><a class="nav" href="javascript:void 0" data-logout>${icon('logout')}Đăng xuất</a>
         <div class="row" style="padding:8px 12px">${avatar(S.user.display_name, S.user.id)}<div class="grow"><div class="ellipsis" style="font-weight:600">${esc(S.user.display_name)}</div><div class="xs muted">${roleLabel()}</div></div></div></div>`;
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

  // ===== Đăng nhập bằng Telegram: lấy mã → nhắn cho bot → xác nhận =====
  function tgLoginBox(box, onBack) {
    stopTimers();
    let code = null, expiresAt = 0, checking = false;
    const bots = S.tgLogin?.bots || [];
    const fmt = (sec) => `${Math.floor(sec / 60)}:${pad(sec % 60)}`;
    const draw = (state = 'pending', name = '') => {
      const left = Math.max(0, expiresAt - Math.floor(Date.now() / 1000));
      const expired = state === 'expired' || (code && left <= 0);
      box.innerHTML = `<div class="card card-pad stack fade-in">
        <div class="row"><div class="tg-ico">${icon('send')}</div><div class="grow"><h3>Đăng nhập bằng Telegram</h3><div class="xs muted">Không cần mật khẩu</div></div></div>
        ${!code ? `<div class="center" style="padding:16px"><div class="spinner" style="margin:0 auto"></div></div>` : expired ? `
          <div class="alert warn">${icon('alert')}<div class="small">Mã đã hết hạn${state === 'denied' ? ' hoặc đã bị huỷ' : ''}.</div></div>
          <button type="button" class="btn primary block" data-newcode>${icon('refresh', 'sm')}Lấy mã mới</button>` : `
          <ol class="tg-steps">
            <li><b>Mở bot</b> và bấm <b>Bắt đầu / Start</b> (mã được gửi tự động)</li>
            <li>Bot hỏi xác nhận → bấm <b>✅ Xác nhận đăng nhập</b></li>
          </ol>
          <div class="code-box tg-code"><span>${esc(code)}</span><button type="button" class="btn icon sm ghost" data-copy="/${esc(S.tgLogin?.cmd || 'login')} ${esc(code)}" aria-label="Sao chép">${icon('copy', 'sm')}</button></div>
          ${bots.map((b) => `<a class="btn primary block" style="height:48px" href="https://t.me/${esc(b)}?start=login_${esc(code)}" target="_blank" rel="noopener">${icon('send', 'sm')}Mở @${esc(b)}</a>`).join('')}
          <div class="xs muted center">Hoặc tự nhắn <code>/${esc(S.tgLogin?.cmd || 'login')} ${esc(code)}</code> cho bot trong chat riêng</div>
          <div class="tg-status ${state === 'waiting' ? 'ok' : ''}">${state === 'waiting'
            ? `${icon('check', 'sm')}<span>Bot đã nhận mã từ <b>${esc(name)}</b>. Bấm <b>Xác nhận</b> trong Telegram…</span>`
            : `<div class="spinner"></div><span>Đang chờ bạn gửi mã cho bot · còn <b data-left>${fmt(left)}</b></span>`}</div>`}
        <button type="button" class="btn ghost block sm" data-back>${icon('back', 'sm')}${onBack ? 'Quay lại' : 'Đăng nhập bằng mật khẩu'}</button>
      </div>`;
    };
    const newCode = async () => {
      code = null; draw();
      try {
        const r = await api('auth/tg-code', {}, { noRedirect: true });
        code = r.code; expiresAt = Math.floor(Date.now() / 1000) + r.expires_in;
        if (r.bots?.length) S.tgLogin = { bots: r.bots };
        draw();
      } catch (e) { box.innerHTML = `<div class="alert err">${icon('alert')}<div>${esc(e.message)}</div></div>`; }
    };
    let lastState = 'pending';
    every(async () => {
      if (!code || checking) return;
      const left = expiresAt - Math.floor(Date.now() / 1000);
      const lb = $('[data-left]', box);
      if (lb) lb.textContent = fmt(Math.max(0, left));
      if (left <= -60) return;
      checking = true;
      try {
        const r = await api('auth/tg-check', { code }, { noRedirect: true });
        if (r.status === 'done') {
          stopTimers();
          toast(r.created ? 'Đã tạo tài khoản từ Telegram và đăng nhập!' : 'Đăng nhập thành công!');
          S.user = r.user; await boot(); $('#app').innerHTML = ''; go('/');
        } else if (r.status !== lastState) {
          lastState = r.status;
          if (['expired', 'denied', 'invalid', 'used'].includes(r.status)) { expiresAt = 0; draw('expired'); }
          else draw(r.status, r.name || '');
        }
      } catch (e) { stopTimers(); toast(e.message, 'err'); draw('expired'); }
      checking = false;
    }, 2000);
    box.addEventListener('click', (e) => {
      if (e.target.closest('[data-newcode]')) { lastState = 'pending'; newCode(); }
      if (e.target.closest('[data-back]')) { stopTimers(); (onBack || renderLogin)(); }
    });
    newCode();
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
      ${S.tgLogin ? `<div class="divider"><span>hoặc</span></div>
        <button type="button" class="btn block tg-btn" id="tglogin">${icon('send')}Đăng nhập bằng Telegram</button>` : ''}
      <div id="tgbox"></div>
      ${S.allowRegister ? `<p class="center small" style="margin-top:16px">Chưa có tài khoản? <a href="#/register">Đăng ký thành viên</a></p>` : ''}`);
    stopTimers();
    $('#tglogin')?.addEventListener('click', () => {
      $('#f').classList.add('hidden'); $('#tglogin').classList.add('hidden'); $('.divider')?.classList.add('hidden');
      tgLoginBox($('#tgbox'), () => renderLogin());
    });
    $('#f').onsubmit = async (e) => {
      e.preventDefault();
      const btn = e.target.querySelector('button');
      await withBtn(btn, async () => {
        try {
          const d = await api('auth/login', formData(e.target), { noRedirect: true });
          S.user = d.user; await boot(); $('#app').innerHTML = ''; go('/');
        } catch (err) { toast(err.message, 'err'); }
      });
    };
  }

  function renderRegister() {
    stopTimers();
    authWrap(`
      <h1 class="center">Đăng ký thành viên</h1>
      <p class="center muted" style="margin:6px 0 22px">Sau khi đăng ký, liên kết Telegram để xem điểm tương tác</p>
      <form class="card card-pad stack" id="f">
        <label class="field"><span class="lbl">Tên hiển thị</span><input class="input" name="display_name" maxlength="60"></label>
        <label class="field"><span class="lbl">Tên đăng nhập</span><input class="input" name="username" autocomplete="username" required pattern="[a-zA-Z0-9_.]{3,32}"><div class="hint">3-32 ký tự: chữ, số, dấu _ hoặc .</div></label>
        <label class="field"><span class="lbl">Mật khẩu</span><input class="input" type="password" name="password" autocomplete="new-password" required minlength="6"></label>
        <button class="btn primary block" style="height:46px">Tạo tài khoản</button>
        ${S.tgLogin ? `<button type="button" class="btn block tg-btn" id="tgreg">${icon('send')}Đăng ký nhanh bằng Telegram</button>` : ''}
      </form>
      <p class="center small" style="margin-top:16px">Đã có tài khoản? <a href="#/login">Đăng nhập</a></p>`);
    $('#f').onsubmit = async (e) => {
      e.preventDefault();
      await withBtn(e.target.querySelector('button'), async () => {
        try { const d = await api('auth/register', formData(e.target), { noRedirect: true }); S.user = d.user; await boot(); $('#app').innerHTML = ''; go('/'); }
        catch (err) { toast(err.message, 'err'); }
      });
    };
    $('#tgreg')?.addEventListener('click', () => {
      const f = $('#f'); const box = document.createElement('div'); f.replaceWith(box);
      tgLoginBox(box, () => renderRegister());
    });
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
  route('/', async () => (isManager() ? viewDashboard() : viewMe()), { title: '' });

  async function viewDashboard() {
    const d = await api('dashboard');
    const st = d.stats;
    const total14 = d.chart.reduce((a, b) => a + b.messages, 0);
    const stat = (ic, cls, label, v, sub = '') => `<div class="card stat"><div class="label"><span class="ico ${cls}">${icon(ic)}</span>${label}</div><div class="value">${num(v)}</div>${sub ? `<div class="sub">${sub}</div>` : ''}</div>`;
    return {
      title: 'Tổng quan',
      actions: `<button class="btn icon ghost only-mobile" data-theme-toggle aria-label="Đổi giao diện">${icon('moon')}</button>`,
      html: `
      ${!isWebAdmin() ? `<div class="alert info" style="margin:4px 0 14px">${icon('shield')}<div class="small">Bạn đang quản lý <b>${S.access.groups.length} nhóm</b> với quyền <b>quản trị viên nhóm</b>. Một số mục chỉ admin web mới vào được.</div></div>` : ''}
      ${!d.has_bot && isWebAdmin() ? `<a href="#/bots" class="alert warn" style="margin:4px 0 14px;color:inherit">${icon('bot')}<div><b>Chưa có bot nào.</b> Nhấn để thêm bot Telegram (lấy token từ @BotFather).</div></a>` : ''}
      <div class="hero" style="margin-top:4px">
        <div class="muted small">Xin chào, ${esc(S.user.display_name)} 👋</div>
        <div class="row" style="align-items:flex-end;margin-top:6px"><div class="big">${num(st.messages_today)}</div><div class="muted" style="padding-bottom:7px">tin nhắn hôm nay</div></div>
        <div class="row wrap small" style="margin-top:8px;gap:14px"><span>${icon('users', 'sm')} ${num(st.groups)} nhóm</span>${st.bots !== null ? `<span>${icon('bot', 'sm')} ${num(st.bots)} bot</span>` : ''}<span>${icon('plus', 'sm')} ${num(st.joins_today)} người mới</span></div>
      </div>
      <div class="grid grid-4" style="margin-top:12px">
        ${stat('users', '', 'Thành viên', st.members, isWebAdmin() ? 'trong tất cả nhóm' : 'trong nhóm của bạn')}
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
          <div class="section-title"><h2>Hoạt động gần đây</h2>${can('logs') ? '<a href="#/logs" class="small">Xem tất cả</a>' : ''}</div>
          <div class="list">${d.recent_logs.length ? d.recent_logs.map(logItem).join('') : empty('list', 'Chưa có hoạt động')}</div>
        </div>
      </div>`,
    };
  }

  // ===== Nhật ký: dòng tóm tắt + bảng chi tiết đầy đủ =====
  const LOGS = new Map();
  const LOG_SRC = { telegram: ['qua Telegram', 'blue'], web: ['qua Web', 'green'], bot: ['Bot tự động', 'amber'], rule: ['Lệnh tự tạo', 'blue'], auto: ['Tự động', 'amber'] };
  const actorName = (a) => String(a || '').replace(/\s*\((Telegram|Web|tự động|lệnh tự tạo)\)$/u, '');
  const fmtFull = (t) => { const d = new Date(t * 1000); return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())} ${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`; };

  function logItem(l) {
    LOGS.set(l.id, l);
    const [t, ic, c] = ACTION[l.action] || [l.action, 'info', ''];
    const mt = l.meta || {};
    const src = LOG_SRC[mt.source];
    const ev = l.evidence;
    const evText = ev ? ((ev.media && ev.kind !== 'text' ? ev.media + ' ' : '') + (ev.text && ev.text !== ev.media ? ev.text : '')).trim() : '';
    return `<button type="button" class="log-item log-btn" data-log="${l.id}"><div class="log-ico ${c}">${icon(ic)}</div><div class="grow">
      <div class="small"><b>${esc(t)}</b>${l.target_name ? ` · ${esc(l.target_name)}` : ''}${mt.escalated_from ? ' <span class="badge amber" style="font-size:10px">tự động</span>' : ''}</div>
      ${l.detail ? `<div class="xs text-2" style="overflow-wrap:anywhere">${esc(l.detail)}</div>` : ''}
      ${evText ? `<div class="log-ev">${esc(evText.length > 140 ? evText.slice(0, 140) + '…' : evText)}</div>` : ''}
      <div class="xs muted log-meta">${l.actor ? `<span>👮 ${esc(actorName(l.actor))}</span>` : ''}${src ? `<span class="badge ${src[1]}" style="font-size:10px;padding:0 6px">${src[0]}</span>` : ''}${l.group_title ? `<span>${esc(l.group_title)}</span>` : ''}<span>${ago(l.created_at)}</span></div>
      </div><span class="chev">${icon('chevron', 'sm')}</span></button>`;
  }

  function logDetail(id) {
    const l = LOGS.get(Number(id));
    if (!l) return;
    const [t, ic, c] = ACTION[l.action] || [l.action, 'info', ''];
    const mt = l.meta || {};
    const ev = l.evidence;
    const src = LOG_SRC[mt.source];
    const rows = [];
    const row = (k, v) => v !== undefined && v !== null && v !== '' && rows.push(`<dt>${k}</dt><dd>${v}</dd>`);
    row('Thành viên', l.target_name ? `${esc(l.target_name)}${l.target_tg_id ? `<div class="xs muted mono">${esc(l.target_tg_id)}</div>` : ''}` : '');
    row('Nhóm', esc(l.group_title || ''));
    if (mt.minutes !== undefined) row('Thời hạn', mt.minutes ? `${humanSec(mt.minutes * 60)}<div class="xs muted">đến ${fmtDate(mt.until, true)}</div>` : 'Vĩnh viễn');
    if (mt.previous_until) row('Hạn cấm cũ', fmtDate(mt.previous_until, true));
    if (mt.warns !== undefined) row('Cảnh cáo', `${mt.warns}/${mt.limit || '∞'}`);
    if (mt.escalate_to) row('Kết quả', `Đủ cảnh cáo → ${esc((ACTION[mt.escalate_to] || [mt.escalate_to])[0].toLowerCase())}`);
    if (mt.escalated_from) row('Kích hoạt', 'Tự động sau khi đủ số lần cảnh cáo');
    if (mt.previous_warns !== undefined) row('Cảnh cáo', `${mt.previous_warns} → 0`);
    if (mt.amount !== undefined) row('Điểm', `<b>${mt.amount > 0 ? '+' : ''}${mt.amount}</b> (${mt.before} → ${mt.after})`);
    else if (mt.before !== undefined) row('Điểm', `${mt.before} → ${mt.after}`);
    row('Lý do', esc(mt.reason || (['delete'].includes(l.action) ? l.detail : '') || ''));
    row('Xử lý bởi', `${esc(actorName(l.actor) || '—')}${src ? ` <span class="badge ${src[1]}" style="font-size:10px">${src[0]}</span>` : ''}${mt.actor_tg_id ? `<div class="xs muted mono">TG ${esc(String(mt.actor_tg_id))}</div>` : ''}`);
    row('Thời gian', fmtFull(l.created_at));
    if (!rows.length || (!mt.reason && l.detail && !mt.minutes && mt.warns === undefined && mt.amount === undefined)) row('Chi tiết', esc(l.detail || ''));
    sheet(`<div class="sheet-head"><div class="log-ico ${c}" style="width:40px;height:40px">${icon(ic)}</div><h2>${esc(t)}</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <dl class="kv card card-pad" style="margin:0 0 14px">${rows.join('')}</dl>
      ${ev ? `<h3 style="margin-bottom:8px">💬 ${['warn', 'mute', 'kick', 'ban', 'delete'].includes(l.action) ? 'Nội dung vi phạm' : 'Tin nhắn liên quan'}</h3>
        <div class="card card-pad stack" style="margin-bottom:12px">
          <div class="row xs muted">${ev.from ? `<b style="color:var(--text)">${esc(ev.from)}</b>` : ''}<span>${ev.date ? fmtFull(ev.date) : ''}</span>${ev.message_id ? `<span class="mono">#${ev.message_id}</span>` : ''}</div>
          ${ev.media && ev.kind !== 'text' ? `<div><span class="badge">${esc(ev.media)}</span></div>` : ''}
          ${ev.text && ev.text !== ev.media ? `<div class="ev-text">${esc(ev.text)}</div>` : ''}
          <div class="row wrap" style="gap:8px">
            ${ev.text ? `<button class="btn sm" data-copy="${esc(ev.text)}">${icon('copy', 'sm')}Sao chép</button>` : ''}
            ${mt.link ? `<a class="btn sm soft" href="${esc(mt.link)}" target="_blank" rel="noopener">${icon('external', 'sm')}Mở trong Telegram</a>` : ''}
          </div>
        </div>` : mt.link ? `<a class="btn soft block" href="${esc(mt.link)}" target="_blank" rel="noopener">${icon('external', 'sm')}Mở tin trong Telegram</a>` : ''}`);
  }
  document.addEventListener('click', (e) => { const b = e.target.closest('[data-log]'); if (b) logDetail(b.dataset.log); });

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
  }, { manage: true, title: 'Nhóm' });

  route('/group/:id', (p) => viewGroup(p.id, 'overview'), { manage: true, back: '/groups' });
  route('/group/:id/:tab', (p) => viewGroup(p.id, p.tab), { manage: true, back: '/groups' });

  async function viewGroup(id, tab) {
    const d = await api('group', undefined, { query: { id } });
    const g = d.group;
    const gp = d.access || {};
    const tabs = [['overview', 'Tổng quan'], ['chat', '💬 Chat', 'chat'], ['members', 'Thành viên'], ['settings', 'Cài đặt', 'settings'], ['rules', 'Lệnh'], ['send', 'Gửi tin', 'send'], ['logs', 'Nhật ký', 'logs']]
      .filter(([, , perm]) => !perm || gp[perm]);
    if (!tabs.some(([k]) => k === tab)) tab = 'overview';
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
        <div class="grid grid-2" style="margin-top:14px"><button class="btn soft sm" data-sync>${icon('refresh', 'sm')}Đồng bộ</button>${(d.access || {}).send ? `<a class="btn sm" href="#/group/${g.id}/send">${icon('send', 'sm')}Gửi tin</a>` : `<a class="btn sm" href="#/group/${g.id}/members">${icon('users', 'sm')}Thành viên</a>`}</div>
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
      ${!(d.access || {}).danger ? '' : `<div class="section-title"><h2>Vùng nguy hiểm</h2></div>
      <div class="list">
        <button class="list-item" data-resetpts>${icon('star')}<div class="grow">Đặt lại điểm toàn nhóm</div></button>
        ${g.active ? `<button class="list-item" data-leave style="color:var(--danger)">${icon('kick')}<div class="grow">Cho bot rời nhóm</div></button>` : ''}
        <button class="list-item" data-del style="color:var(--danger)">${icon('trash')}<div class="grow">Xoá nhóm khỏi web (xoá dữ liệu)</div></button>
      </div>`}`;
    const mount = (root) => {
      $('[data-sync]', root).onclick = (e) => withBtn(e.currentTarget, async () => {
        try { const r = await api('group/sync', { id: g.id }); toast(`Đã đồng bộ: ${r.member_count} thành viên, ${r.admins} admin`); render(); } catch (err) { toast(err.message, 'err'); }
      });
      if ($('[data-resetpts]', root)) $('[data-resetpts]', root).onclick = async () => {
        if (!(await confirmBox('Đặt lại điểm?', 'Toàn bộ điểm tương tác của thành viên trong nhóm sẽ về 0.', { danger: true, ok: 'Đặt lại' }))) return;
        await api('group/reset-points', { id: g.id }); toast('Đã đặt lại điểm'); render();
      };
      const lv = $('[data-leave]', root);
      if (lv) lv.onclick = async () => {
        if (!(await confirmBox('Cho bot rời nhóm?', 'Bot sẽ rời khỏi nhóm Telegram này.', { danger: true, ok: 'Rời nhóm' }))) return;
        await api('group/leave', { id: g.id }); toast('Bot đã rời nhóm'); render();
      };
      if ($('[data-del]', root)) $('[data-del]', root).onclick = async () => {
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
      ${(() => {
        // Quản trị viên nhóm không xử phạt được quản trị viên khác
        const mod = can('moderate'), pts = can('points'), protect = m.is_admin && !isWebAdmin();
        const list = [
          mod && !protect ? act('warn', 'alert', 'Cảnh cáo', 'amber') : '',
          mod && m.warns ? act('unwarn', 'check', 'Gỡ CC', 'green') : '',
          mod && !protect ? (m.status === 'muted' ? act('unmute', 'volume', 'Bỏ mute', 'green') : act('mute', 'mute', 'Cấm chat', 'amber')) : '',
          mod && !protect ? act('kick', 'kick', 'Đuổi', 'red') : '',
          mod && !protect ? (m.status === 'banned' ? act('unban', 'check', 'Gỡ cấm', 'green') : act('ban', 'ban', 'Cấm', 'red')) : '',
          mod && m.warns ? act('resetwarn', 'refresh', 'Xoá CC') : '',
          pts ? act('points', 'star', 'Điểm') : '',
          pts ? act('setpoints', 'edit', 'Đặt điểm') : '',
          mod ? act('note', 'note', 'Ghi chú') : '',
        ].filter(Boolean);
        return list.length ? `<div class="actions" style="margin-bottom:14px">${list.join('')}</div>`
          : `<div class="alert info" style="margin-bottom:14px">${icon('lock')}<div class="small">${protect ? 'Không thể xử lý quản trị viên của nhóm.' : 'Bạn chỉ có quyền xem.'}</div></div>`;
      })()}
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

  function actionForm(a, m, d, done, extra = {}) {
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
    const evNote = d.evidence && (d.evidence.text || d.evidence.media) ? `<div class="field"><span class="lbl">💬 Tin nhắn vi phạm (sẽ ghi vào nhật ký & thông báo)</span><div class="log-ev" style="margin:0">${esc(((d.evidence.media && d.evidence.kind !== 'text' ? d.evidence.media + ' ' : '') + (d.evidence.text || '')).slice(0, 300))}</div></div>` : '';
    const warnNote = a === 'warn' && m.warns + 1 >= d.warn_limit ? `<div class="alert warn">${icon('alert')}<div>Lần cảnh cáo này sẽ đạt giới hạn ${d.warn_limit} và tự động xử lý theo cài đặt nhóm.</div></div>` : '';
    sheet(`<div class="sheet-head"><h2>${titles[a]}</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <div class="row" style="margin-bottom:14px">${avatar(m.name, m.tg_user_id)}<div><b>${esc(m.name)}</b><div class="xs muted">${m.tg_user_id}</div></div></div>
      <form class="stack" id="fa">${evNote}${warnNote}${fields}<button class="btn ${danger ? 'danger' : 'primary'} block" style="height:46px">${titles[a]}</button></form>`, (el, close) => {
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
            await api('member/action', { id: m.id, action: a, ...formData(e.target), ...extra });
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
          <div class="form-group"><label class="field"><span class="lbl">Nội dung chào mừng</span><textarea class="input" data-rich name="welcome_text">${esc(s.welcome_text)}</textarea>
          <div class="hint">Biến: <code>{mention}</code> <code>{name}</code> <code>{username}</code> <code>{group}</code> <code>{id}</code> · hỗ trợ HTML Telegram (&lt;b&gt;, &lt;i&gt;, &lt;a&gt;)</div></label></div>
          ${sw('goodbye_enabled', s.goodbye_enabled, 'Gửi lời tạm biệt khi rời nhóm')}
          <div class="form-group"><label class="field"><span class="lbl">Nội dung tạm biệt</span><input class="input" name="goodbye_text" value="${esc(s.goodbye_text)}"></label></div>
          ${sw('delete_service_messages', s.delete_service_messages, 'Xoá tin hệ thống', '"X đã tham gia", "X đã rời nhóm"…')}
        </div></div>

        <div><div class="section-title"><h2>📜 Nội quy</h2></div>
        <div class="card"><div class="form-group"><textarea class="input" data-rich name="rules_text" style="min-height:120px">${esc(s.rules_text)}</textarea><div class="hint">Hiển thị khi thành viên gõ /rules</div></div></div></div>

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

        <div><div class="section-title"><h2>📣 Thông báo xử lý</h2></div>
        <div class="card">
          ${sw('mod_notify', s.mod_notify, 'Bot thông báo khi xử lý', 'Gửi tin vào nhóm khi cảnh cáo, cấm chat, cấm, đuổi, gỡ cấm…')}
          ${sw('mod_notify_detail', s.mod_notify_detail, 'Thông báo chi tiết', 'Thành viên, thời hạn (đến mấy giờ), số cảnh cáo, lý do, thời gian. Tắt = 1–2 dòng ngắn gọn')}
          ${sw('mod_show_admin', s.mod_show_admin, 'Hiện người xử lý', 'Ghi rõ admin nào xử lý, qua Telegram / Web / Bot tự động')}
          ${sw('mod_show_evidence', s.mod_show_evidence, 'Trích nội dung vi phạm', 'Khi xử lý bằng cách reply tin nhắn, trích lại nội dung tin đó')}
          ${sw('mod_delete_evidence', s.mod_delete_evidence, 'Tự xoá tin vi phạm', 'Khi cấm / cấm chat / đuổi bằng cách reply, xoá luôn tin bị reply (nội dung vẫn lưu ở Nhật ký web)')}
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
      <form class="composer ${can('send') ? '' : 'hidden'}" id="composer">
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
        if (mb && !m.is_bot && can('moderate') && !(mb.is_admin && !isWebAdmin())) {
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
            ${can('send') ? it('reply', 'reply', 'Trả lời tin này') : ''}
            ${it(m.pinned ? 'unpin' : 'pin', 'pin', m.pinned ? 'Bỏ ghim' : 'Ghim tin')}
            ${it('copy', 'copy', 'Sao chép nội dung')}
            ${mb ? it('profile', 'user', 'Xem hồ sơ thành viên') : ''}
            ${mb && !m.is_bot && can('points') ? it('points', 'star', 'Cộng / trừ điểm') : ''}
            ${mb && !m.is_bot && !mb.is_admin && can('moderate') ? it('kick', 'kick', 'Đuổi khỏi nhóm', true) : ''}
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
        else if (m.member) actionForm(a, memberFor(m), { warn_limit: S2.warnLimit, evidence: m }, () => refreshMember(m.member.id), { chat_msg_id: m.id });
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
        <label class="field"><span class="lbl">Nội dung gửi vào nhóm</span><textarea class="input" data-rich name="text" style="min-height:160px" placeholder="Nhập thông báo…"></textarea></label>
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

  route('/logs', async () => { const { body, mount } = logsBlock(null); return { title: 'Nhật ký', html: body, mount }; }, { manage: true, perm: 'logs', title: 'Nhật ký' });

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
  }, { webAdmin: true, title: 'Bot' });

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

  const stripTags = (h) => String(h || '').replace(/<[^>]+>/g, '').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
  // ================= Trình soạn thảo trực quan (xuất HTML Telegram) =================
  // Dùng: <textarea data-rich> → enhanceRich(root). Textarea vẫn giữ giá trị HTML (ẩn đi), mọi code cũ đọc .value như trước.
  const RICH_TOOLS = [
    ['bold', '<b>B</b>', 'Đậm (Ctrl+B)'],
    ['italic', '<i>I</i>', 'Nghiêng (Ctrl+I)'],
    ['underline', '<u>U</u>', 'Gạch chân (Ctrl+U)'],
    ['strike', '<s>S</s>', 'Gạch ngang (Ctrl+Shift+X)'],
    ['code', '<span class="mono">&lt;/&gt;</span>', 'Mã / chữ đơn cách (Ctrl+Shift+M)'],
    ['spoiler', '▒', 'Ẩn nội dung – spoiler (Ctrl+Shift+P)'],
    ['link', '🔗', 'Chèn link (Ctrl+K)'],
    ['clear', '🧹', 'Xoá định dạng'],
  ];

  // HTML Telegram → HTML hiển thị trong ô soạn thảo (chỉ giữ thẻ Telegram cho phép)
  function tgToRich(html) {
    const tpl = document.createElement('template');
    tpl.innerHTML = String(html || '').replace(/<tg-spoiler>/g, '<span class="tg-spoiler">').replace(/<\/tg-spoiler>/g, '</span>');
    const walk = (node) => {
      let out = '';
      node.childNodes.forEach((n) => {
        if (n.nodeType === 3) { out += esc(n.textContent).replace(/\n/g, '<br>'); return; }
        if (n.nodeType !== 1) return;
        const tag = n.tagName.toLowerCase();
        const inner = walk(n);
        if (['b', 'strong'].includes(tag)) out += `<b>${inner}</b>`;
        else if (['i', 'em'].includes(tag)) out += `<i>${inner}</i>`;
        else if (['u', 'ins'].includes(tag)) out += `<u>${inner}</u>`;
        else if (['s', 'strike', 'del'].includes(tag)) out += `<s>${inner}</s>`;
        else if (['code', 'pre'].includes(tag)) out += `<code>${inner}</code>`;
        else if (tag === 'a' && n.getAttribute('href')) out += `<a href="${esc(n.getAttribute('href'))}">${inner}</a>`;
        else if (tag === 'span' && n.classList.contains('tg-spoiler')) out += `<span class="tg-spoiler">${inner}</span>`;
        else if (tag === 'br') out += '<br>';
        else out += inner;
      });
      return out;
    };
    return walk(tpl.content);
  }

  // HTML trong ô soạn thảo → HTML Telegram (b, i, u, s, code, a, tg-spoiler, xuống dòng = \n)
  function richToTg(root) {
    const block = /^(div|p|li|h[1-6]|blockquote)$/;
    let out = '';
    const nl = () => { if (out && !out.endsWith('\n')) out += '\n'; };
    const walk = (node) => {
      node.childNodes.forEach((n) => {
        if (n.nodeType === 3) { out += n.textContent.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[c]).replace(/ /g, ' '); return; }
        if (n.nodeType !== 1) return;
        const tag = n.tagName.toLowerCase();
        if (tag === 'br') { out += '\n'; return; }
        const st = n.style || {};
        const wrap = [];
        if (['b', 'strong'].includes(tag) || Number(st.fontWeight) >= 600 || st.fontWeight === 'bold') wrap.push('b');
        if (['i', 'em'].includes(tag) || st.fontStyle === 'italic') wrap.push('i');
        if (['u', 'ins'].includes(tag) || (st.textDecoration || '').includes('underline')) wrap.push('u');
        if (['s', 'strike', 'del'].includes(tag) || (st.textDecoration || '').includes('line-through')) wrap.push('s');
        if (['code', 'pre'].includes(tag)) wrap.push('code');
        if (tag === 'span' && n.classList.contains('tg-spoiler')) wrap.push('tg-spoiler');
        if (block.test(tag)) nl();
        if (tag === 'a' && n.getAttribute('href')) out += `<a href="${n.getAttribute('href').replace(/"/g, '&quot;')}">`;
        wrap.forEach((w) => { out += `<${w}>`; });
        walk(n);
        wrap.slice().reverse().forEach((w) => { out += `</${w}>`; });
        if (tag === 'a' && n.getAttribute('href')) out += '</a>';
        if (block.test(tag)) nl();
      });
    };
    walk(root);
    // Dọn thẻ rỗng và xuống dòng thừa ở cuối
    let prev;
    do { prev = out; out = out.replace(/<(b|i|u|s|code|tg-spoiler)><\/\1>/g, ''); } while (out !== prev);
    return out.replace(/\n+$/, '');
  }

  function richEditor(ta) {
    if (ta._rich) return ta._rich;
    // Không để ô soạn thảo nằm trong <label>: bấm vào label làm trình duyệt lấy kiểu chữ đậm của tiêu đề
    const lab = ta.closest('label');
    if (lab) {
      const d = document.createElement('div');
      d.className = lab.className;
      while (lab.firstChild) d.appendChild(lab.firstChild);
      lab.replaceWith(d);
    }
    const wrap = document.createElement('div');
    wrap.className = 'rich';
    wrap.innerHTML = `<div class="rich-bar">${RICH_TOOLS.map(([k, ic, t]) => `<button type="button" class="rich-btn" data-rc="${k}" title="${t}" aria-label="${t}">${ic}</button>`).join('')}
      <span class="grow"></span><button type="button" class="rich-btn rich-mode" data-rc="html" title="Sửa bằng mã HTML">HTML</button></div>
      <div class="rich-ed input" contenteditable="true" spellcheck="false" data-ph="${esc(ta.placeholder || '')}"></div>`;
    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(ta);
    ta.classList.add('rich-src');
    const ed = wrap.querySelector('.rich-ed');
    ed.style.minHeight = ta.style.minHeight || '100px';
    if (ta.disabled) { ed.contentEditable = 'false'; wrap.classList.add('disabled'); }
    ed.innerHTML = tgToRich(ta.value);
    let htmlMode = false;
    const sync = () => {
      ta.value = richToTg(ed);
      wrap.classList.toggle('empty', !ed.textContent.trim());
      ta.dispatchEvent(new Event('input', { bubbles: true }));
    };
    const state = () => {
      wrap.querySelectorAll('[data-rc]').forEach((b) => {
        const k = b.dataset.rc;
        let on = false;
        try { on = ['bold', 'italic', 'underline'].includes(k) ? document.queryCommandState(k) : k === 'strike' ? document.queryCommandState('strikeThrough') : false; } catch {}
        if (k === 'html') on = htmlMode;
        b.classList.toggle('on', on);
      });
    };
    const wrapSel = (tag, cls) => {
      const sel = window.getSelection();
      if (!sel.rangeCount || sel.isCollapsed) return toast('Bôi đen chữ cần định dạng trước', 'err');
      const r = sel.getRangeAt(0);
      if (!ed.contains(r.commonAncestorContainer)) return;
      const el = document.createElement(tag);
      if (cls) el.className = cls;
      el.appendChild(r.extractContents());
      r.insertNode(el);
      sel.removeAllRanges();
      const nr = document.createRange(); nr.selectNodeContents(el); sel.addRange(nr);
    };
    const cmd = (k) => {
      if (htmlMode && k !== 'html') return;
      ed.focus();
      if (k === 'bold' || k === 'italic' || k === 'underline') document.execCommand(k);
      else if (k === 'strike') document.execCommand('strikeThrough');
      else if (k === 'code') wrapSel('code');
      else if (k === 'spoiler') wrapSel('span', 'tg-spoiler');
      else if (k === 'clear') { document.execCommand('removeFormat'); document.execCommand('unlink'); }
      else if (k === 'link') {
        const sel = window.getSelection();
        if (!sel.rangeCount || sel.isCollapsed) return toast('Bôi đen chữ cần gắn link trước', 'err');
        const range = sel.getRangeAt(0).cloneRange();
        let url = prompt('Nhập link (https://… hoặc @username):', 'https://');
        if (!url || url === 'https://') return;
        url = url.trim();
        if (url.startsWith('@')) url = 'https://t.me/' + url.slice(1);
        if (!/^(https?:\/\/|tg:\/\/)/i.test(url)) url = 'https://' + url;
        sel.removeAllRanges(); sel.addRange(range);
        document.execCommand('createLink', false, url);
      } else if (k === 'html') {
        htmlMode = !htmlMode;
        wrap.classList.toggle('html-mode', htmlMode);
        if (htmlMode) { sync(); ta.focus(); } else { ed.innerHTML = tgToRich(ta.value); sync(); ed.focus(); }
      }
      if (k !== 'html') sync();
      state();
    };
    wrap.querySelector('.rich-bar').addEventListener('mousedown', (e) => { if (e.target.closest('[data-rc]')) e.preventDefault(); }); // giữ vùng chọn
    wrap.querySelector('.rich-bar').addEventListener('click', (e) => { const b = e.target.closest('[data-rc]'); if (b) cmd(b.dataset.rc); });
    ed.addEventListener('input', sync);
    ed.addEventListener('keyup', state);
    ed.addEventListener('mouseup', state);
    ed.addEventListener('keydown', (e) => {
      if (!(e.ctrlKey || e.metaKey)) return;
      const k = e.key.toLowerCase();
      const map = e.shiftKey ? { x: 'strike', m: 'code', p: 'spoiler' } : { k: 'link', b: 'bold', i: 'italic', u: 'underline' };
      if (map[k]) { e.preventDefault(); cmd(map[k]); }
    });
    // Dán: chỉ lấy chữ thuần (tránh dán định dạng lạ từ web khác)
    ed.addEventListener('paste', (e) => {
      e.preventDefault();
      const t = (e.clipboardData || window.clipboardData).getData('text/plain');
      document.execCommand('insertText', false, t);
    });
    // Chế độ HTML: sửa trực tiếp textarea
    ta.addEventListener('input', (e) => { if (htmlMode && e.isTrusted) wrap.classList.toggle('empty', !ta.value.trim()); });
    wrap.classList.toggle('empty', !ed.textContent.trim());
    const api = {
      el: ed,
      // Chèn chữ (VD biến {name}) tại vị trí con trỏ
      insert(text) {
        if (htmlMode) {
          const p = ta.selectionStart ?? ta.value.length;
          ta.value = ta.value.slice(0, p) + text + ta.value.slice(ta.selectionEnd ?? p);
          ta.dispatchEvent(new Event('input', { bubbles: true }));
          return;
        }
        ed.focus({ preventScroll: true });
        const sel = window.getSelection();
        if (!sel.rangeCount || !ed.contains(sel.anchorNode)) { const r = document.createRange(); r.selectNodeContents(ed); r.collapse(false); sel.removeAllRanges(); sel.addRange(r); }
        document.execCommand('insertText', false, text);
        sync();
      },
      setValue(v) { ta.value = v; ed.innerHTML = tgToRich(v); sync(); },
      textLength: () => (htmlMode ? stripTags(ta.value).length : ed.textContent.length),
    };
    ta._rich = api;
    ed._ta = ta;
    return api;
  }
  const enhanceRich = (root) => $$('textarea[data-rich]', root).forEach((t) => richEditor(t));

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
        const cmds = d.rules.filter((r) => r.trigger_type === 'command' && r.can_edit);
        const allOn = d.rules.filter((r) => r.can_edit).length && d.rules.filter((r) => r.can_edit).every((r) => r.enabled);
        const allMenu = cmds.length && cmds.every((r) => r.show_in_menu);
        webAdminRules = d.web_admin;
        const editable = d.rules.filter((r) => r.can_edit);
        const master = `<div class="card" style="margin-bottom:12px">
            ${!d.web_admin ? '' : `<label class="switch-row"><div class="txt"><b>🔒 Chỉ admin thấy menu lệnh</b><span>${d.menu_admin_only ? 'Thành viên gõ "/" sẽ không thấy danh sách lệnh' : 'Đang tắt: mọi thành viên đều thấy menu khi gõ "/"'}</span></div>
              <div class="switch"><input type="checkbox" data-menumode ${d.menu_admin_only ? 'checked' : ''}><span></span></div></label>`}
            ${editable.length ? `<label class="switch-row"><div class="txt"><b>Bật tất cả lệnh${d.web_admin ? '' : ' của nhóm tôi'}</b><span>${editable.filter((r) => r.enabled).length}/${editable.length} lệnh đang bật</span></div>
              <div class="switch"><input type="checkbox" data-bulk="enabled" ${allOn ? 'checked' : ''}><span></span></div></label>
            ${cmds.length ? `<label class="switch-row"><div class="txt"><b>Hiện tất cả trong menu lệnh Telegram</b><span>Hiện khi gõ "/" trong nhóm · ${cmds.filter((r) => r.show_in_menu).length}/${cmds.length} lệnh</span></div>
              <div class="switch"><input type="checkbox" data-bulk="show_in_menu" ${allMenu ? 'checked' : ''}><span></span></div></label>` : ''}` : ''}
          </div>`;
        ids = editable.map((r) => r.id);
        const masterHtml = /switch-row/.test(master) ? master : '';
        $('#rlist', root).innerHTML = masterHtml + (!d.web_admin && d.rules.some((r) => !r.can_edit) ? `<div class="xs muted" style="margin:0 2px 10px">${icon('lock', 'sm')} Lệnh có khoá là lệnh dùng chung cho mọi nhóm, chỉ admin web sửa được.</div>` : '') + (d.rules.length ? `<div class="list">${d.rules.map((r) => `
          <div class="list-item" style="align-items:flex-start">
            <button class="grow" data-rule='${esc(JSON.stringify(r))}' style="all:unset;cursor:pointer;flex:1;min-width:0">
              <div class="row wrap" style="gap:6px">${triggerChip(r)}<b class="ellipsis">${esc(r.name)}</b></div>
              <div class="xs text-2" style="margin-top:4px">${ruleSummary(r)}</div>
              <div class="xs muted" style="margin-top:3px">${r.group_id ? esc(r.group_title || 'Nhóm') : 'Tất cả nhóm'} · ${num(r.uses)} lượt dùng${r.last_used ? ' · ' + ago(r.last_used) : ''}${r.trigger_type === 'command' && r.show_in_menu ? ' · <span style="color:var(--primary)">hiện trong menu</span>' : ''}</div>
            </button>
            ${r.can_edit ? `<label class="switch" style="margin-top:2px"><input type="checkbox" data-rtoggle="${r.id}" ${r.enabled ? 'checked' : ''}><span></span></label>`
              : `<span class="badge" title="Lệnh dùng chung – chỉ admin web sửa được">${icon('lock', 'sm')}${r.enabled ? 'Bật' : 'Tắt'}</span>`}
          </div>`).join('')}</div>`
          : `<div class="card">${empty('terminal', 'Chưa có lệnh nào. Nhấn <b>Tạo lệnh</b> để bắt đầu từ mẫu có sẵn.')}</div>`);
      };
      let ids = [];
      let webAdminRules = false;
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
        base.group_id = groupId || (webAdminRules ? null : groups[0]?.id ?? null);
        ruleEditor(base, groups, load, webAdminRules);
      }));
      root.addEventListener('click', (e) => {
        if (e.target.closest('[data-newrule]')) pickTemplate();
        const r = e.target.closest('[data-rule]');
        if (r) {
          const rule = JSON.parse(r.dataset.rule);
          if (!rule.can_edit) return toast('Đây là lệnh dùng chung cho mọi nhóm, chỉ admin web sửa được', 'err');
          ruleEditor(rule, groups, load, webAdminRules);
        }
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

  function ruleEditor(rule, groups, onSaved, webAdmin = isWebAdmin()) {
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
      if (['reply', 'send'].includes(a.type)) f += `<textarea class="input" data-rich data-af="${i}" data-k="text" style="margin-top:10px;min-height:80px" placeholder="Nội dung… dùng biến như {mention}, {points}">${esc(a.text || '')}</textarea>
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
          <label class="field"><span class="lbl">Áp dụng cho</span><select class="input" name="group_id">${webAdmin ? '<option value="">Tất cả nhóm</option>' : ''}${groups.map((g) => `<option value="${g.id}" ${g.id === r.group_id ? 'selected' : ''}>${esc(g.title)}</option>`).join('')}</select></label>
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
      const draw = () => { box.innerHTML = html(); enhanceRich(box); };
      const redraw = () => { collect(el); draw(); };
      draw();
      el.addEventListener('focusin', (e) => {
        const ta = e.target.matches('textarea[data-af]') ? e.target : e.target.closest('.rich')?.querySelector('textarea[data-af]');
        if (ta) lastText = ta;
      });
      const updatePreview = (ta) => {
        const pv = $(`[data-pv="${ta.dataset.af}"]`, el);
        if (pv) pv.innerHTML = ta.value.trim() ? previewText(ta.value) : '<span class="muted">Nội dung sẽ hiện ở đây…</span>';
      };
      el.addEventListener('input', (e) => { if (e.target.matches('textarea[data-af]')) updatePreview(e.target); });
      const insertVar = (code) => {
        const ta = lastText && document.contains(lastText) ? lastText : $('textarea[data-af]', el);
        if (!ta) return toast('Thêm hành động "Trả lời" hoặc "Gửi tin" trước', 'err');
        if (ta._rich) { ta._rich.insert(code); lastText = ta; updatePreview(ta); return; }
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

  const cmdTabs = (active) => `<div class="segmented" style="margin:4px 0 14px"><button type="button" class="${active === 'rules' ? 'active' : ''}" onclick="location.hash='/rules'">✨ Lệnh tự tạo</button><button type="button" class="${active === 'commands' ? 'active' : ''}" onclick="location.hash='/commands'">📚 Lệnh mặc định</button></div>`;
  route('/rules', async () => { const { body, mount } = rulesBlock(null); return { title: 'Lệnh', html: cmdTabs('rules') + body, mount }; }, { manage: true, title: 'Lệnh' });

  // ================= Lệnh mặc định: đổi tên, bật/tắt, xem chi tiết =================
  route('/commands', async () => {
    const d = await api('commands');
    const groups = [['member', '👥 Lệnh thành viên (trong nhóm)'], ['admin', '🛡 Lệnh quản trị (trong nhóm)'], ['private', '💬 Lệnh chat riêng với bot']];
    const row = (c) => `<button type="button" class="list-item cmd-row" data-cmd="${c.key}">
      <div class="grow">
        <div class="row wrap" style="gap:6px">${c.names.length ? c.names.map((n, i) => `<span class="badge ${i === 0 ? 'blue' : ''} mono">/${esc(n)}</span>`).join('') : `<span class="badge mono" style="text-decoration:line-through">/${esc(c.original)}</span>`}
          ${!c.enabled ? '<span class="badge red">Đã tắt</span>' : ''}${c.customized && c.enabled ? '<span class="badge green">Đã sửa</span>' : ''}${c.overridden.length ? '<span class="badge amber">Bị lệnh tự tạo thay</span>' : ''}</div>
        <div class="small" style="margin-top:4px">${esc(c.description)}</div>
        ${c.names[0] && c.names[0] !== c.original ? `<div class="xs muted">Tên gốc: /${esc(c.original)}${c.keep_original ? ' (vẫn dùng được)' : ' (đã tắt)'}</div>` : ''}
      </div><span class="chev">${icon('chevron')}</span></button>`;
    return {
      title: 'Lệnh',
      html: cmdTabs('commands') + `
        <div class="alert info" style="margin-bottom:12px">${icon('info')}<div class="small">Bấm vào lệnh để xem <b>chi tiết, cú pháp, ví dụ</b>${d.can_edit ? ' và <b>đổi tên</b> (VD <code>/me</code> → <code>/canhan</code>), bật/tắt, sửa mô tả trong menu Telegram' : ''}.</div></div>
        ${(() => {
          const ru = d.features.require_username;
          const scope = ru.groups.length ? `${ru.groups.length} nhóm` : 'tất cả nhóm';
          return `<div class="section-title"><h2>⚙️ Tính năng mặc định</h2></div>
          <div class="list"><button type="button" class="list-item cmd-row" data-feature="require_username">
            <div class="feat-ico">🔖</div>
            <div class="grow"><div class="row wrap" style="gap:6px"><b>Yêu cầu đặt Username</b>${ru.enabled ? '<span class="badge green">Đang bật</span>' : '<span class="badge">Tắt</span>'}</div>
            <div class="xs text-2" style="margin-top:3px">${ru.enabled ? `${esc(ru.actions[ru.action])} · ${scope}${ru.on_join ? ' · kiểm tra khi vào nhóm' : ''}` : 'Bắt thành viên phải có @username Telegram mới được nhắn tin'}</div></div>
            <span class="chev">${icon('chevron')}</span></button></div>`;
        })()}
        ${groups.map(([k, t]) => `<div class="section-title"><h2>${t}</h2></div><div class="list">${d.commands.filter((c) => c.scope === k).map(row).join('')}</div>`).join('')}
        ${d.can_edit ? `<button class="btn soft-danger block" data-resetall style="margin-top:16px">${icon('refresh', 'sm')}Đặt lại tất cả lệnh về mặc định</button>` : ''}`,
      mount: (root) => {
        root.addEventListener('click', async (e) => {
          if (e.target.closest('[data-feature="require_username"]')) return usernameSheet(d, () => render());
          const b = e.target.closest('[data-cmd]');
          if (b) commandSheet(d.commands.find((c) => c.key === b.dataset.cmd), d.can_edit, () => render());
          if (e.target.closest('[data-resetall]')) {
            if (!(await confirmBox('Đặt lại tất cả?', 'Mọi tên thay thế, mô tả và trạng thái bật/tắt của lệnh mặc định sẽ về như ban đầu.', { danger: true, ok: 'Đặt lại' }))) return;
            try { await api('commands/reset', {}); toast('Đã đặt lại lệnh mặc định'); render(); } catch (err) { toast(err.message, 'err'); }
          }
        });
      },
    };
  }, { manage: true, title: 'Lệnh' });

  // Tính năng mặc định: yêu cầu đặt Username Telegram
  function usernameSheet(d, onSaved) {
    const ru = d.features.require_username;
    const can = d.can_edit;
    const dis = can ? '' : 'disabled';
    const autodelOpts = [[0, 'Không xoá'], [30, '30 giây'], [60, '1 phút'], [120, '2 phút'], [300, '5 phút'], [600, '10 phút']];
    const cdOpts = [[0, 'Lần nào cũng nhắc'], [60, '1 phút'], [300, '5 phút'], [1800, '30 phút'], [3600, '1 giờ'], [86400, '1 ngày']];
    const hints = { remind: 'Bot nhắc, tin nhắn vẫn giữ', delete: 'Xoá tin vừa gửi rồi nhắc', mute: 'Xoá tin, cấm chat; đặt username xong bấm nút để được mở', kick: 'Xoá tin và mời ra khỏi nhóm' };
    sheet(`<div class="sheet-head"><div class="feat-ico">🔖</div><h2>Yêu cầu đặt Username</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <div class="alert info" style="margin-bottom:12px">${icon('info')}<div class="small">Thành viên <b>chưa có @username</b> Telegram khi nhắn tin (hoặc vừa vào nhóm) sẽ bị xử lý theo cài đặt dưới đây. Tin nhắc có nút <b>✅ Tôi đã đặt username</b>: bấm vào bot kiểm tra lại và tự bỏ cấm chat.</div></div>
      <form id="fu" class="stack">
        <div class="card" style="box-shadow:none">
          ${sw('enabled', ru.enabled, 'Bật yêu cầu đặt Username')}
          ${sw('on_join', ru.on_join, 'Kiểm tra ngay khi vào nhóm', 'Không cần đợi họ nhắn tin')}
          ${sw('exempt_admins', ru.exempt_admins, 'Bỏ qua quản trị viên')}
        </div>
        <div class="field"><span class="lbl">Khi thành viên chưa có username</span>
          <div class="stack" style="gap:6px">${Object.entries(ru.actions).map(([k, t]) => `<label class="radio-row"><input type="radio" name="action" value="${k}" ${ru.action === k ? 'checked' : ''} ${dis}><span><b>${t}</b><small>${hints[k]}</small></span></label>`).join('')}</div></div>
        <div class="field"><span class="lbl">Áp dụng cho</span>
          <div class="chips" style="flex-wrap:wrap"><button type="button" class="chip ${ru.groups.length ? '' : 'active'}" data-allgroups ${dis}>Tất cả nhóm</button>
          ${d.groups.map((g) => `<button type="button" class="chip ${ru.groups.includes(g.id) ? 'active' : ''}" data-gsel="${g.id}" ${dis}>${esc(g.title)}</button>`).join('')}</div></div>
        <div class="grid grid-2">
          <label class="field"><span class="lbl">Tự xoá tin nhắc</span><select class="input" name="autodel" ${dis}>${autodelOpts.map(([v, t]) => `<option value="${v}" ${v === ru.autodel ? 'selected' : ''}>${t}</option>`).join('')}</select></label>
          <label class="field"><span class="lbl">Nhắc lại cùng người sau</span><select class="input" name="cooldown" ${dis}>${cdOpts.map(([v, t]) => `<option value="${v}" ${v === ru.cooldown ? 'selected' : ''}>${t}</option>`).join('')}</select></label>
        </div>
        <label class="field"><span class="lbl">Nội dung tin nhắc</span><textarea class="input" data-rich name="text" style="min-height:120px" ${dis}>${esc(ru.text)}</textarea>
          <div class="hint">Biến: <code>{mention}</code> tag tên · <code>{name}</code> tên · <code>{id}</code> · <code>{group}</code> tên nhóm. Hỗ trợ &lt;b&gt; &lt;i&gt;.</div></label>
        <div class="pv"><div class="pv-label">👁 Xem trước</div><div class="pv-body" id="upv"></div></div>
        ${can ? `<div class="grid grid-2"><button type="button" class="btn" data-deftext>${icon('refresh', 'sm')}Nội dung mặc định</button><button class="btn primary">${icon('check', 'sm')}Lưu</button></div>`
          : `<div class="alert info">${icon('lock')}<div class="xs">Chỉ admin web được thay đổi.</div></div>`}
      </form>`, (el, close) => {
      const f = $('#fu', el);
      let groupsSel = [...ru.groups];
      const preview = () => {
        const act = f.querySelector('[name=action]:checked')?.value;
        const extra = { mute: '\n\n🔇 Bạn đang bị <b>tạm cấm chat</b> cho tới khi đặt username.', kick: '\n\n👢 Bạn đã bị mời ra khỏi nhóm. Đặt username rồi vào lại nhé.' }[act] || '';
        const t = (f.text.value + extra).replace(/\{mention\}/g, '\u0001Minh Anh\u0002').replace(/\{name\}/g, 'Minh Anh').replace(/\{id\}/g, '123456789').replace(/\{group\}/g, d.groups[0]?.title || 'Nhóm');
        $('#upv', el).innerHTML = previewText(t) + (act !== 'kick' ? '<div style="margin-top:8px"><span class="badge blue">✅ Tôi đã đặt username</span></div>' : '');
      };
      preview();
      el.addEventListener('input', preview);
      el.addEventListener('change', preview);
      el.addEventListener('click', (e) => {
        const all = e.target.closest('[data-allgroups]');
        const g = e.target.closest('[data-gsel]');
        if (all) groupsSel = [];
        if (g) { const id = Number(g.dataset.gsel); groupsSel = groupsSel.includes(id) ? groupsSel.filter((x) => x !== id) : [...groupsSel, id]; }
        if (all || g) {
          $$('[data-gsel]', el).forEach((b) => b.classList.toggle('active', groupsSel.includes(Number(b.dataset.gsel))));
          $('[data-allgroups]', el).classList.toggle('active', !groupsSel.length);
        }
        if (e.target.closest('[data-deftext]')) { f.text._rich ? f.text._rich.setValue(ru.default_text) : (f.text.value = ru.default_text); preview(); }
      });
      f.addEventListener('submit', async (e) => {
        e.preventDefault();
        const v = formData(f);
        await withBtn(f.querySelector('button.primary'), async () => {
          try {
            await api('commands/feature', { feature: 'require_username', config: { enabled: v.enabled, on_join: v.on_join, exempt_admins: v.exempt_admins, action: v.action, groups: groupsSel, autodel: Number(v.autodel), cooldown: Number(v.cooldown), text: v.text } });
            close(); toast(v.enabled ? 'Đã bật yêu cầu đặt Username' : 'Đã lưu (đang tắt)'); onSaved();
          } catch (err) { toast(err.message, 'err'); }
        });
      });
    });
  }

  function commandSheet(c, canEdit, onSaved) {
    const st = { aliases: [...c.aliases], keep: c.keep_original, enabled: c.enabled, desc: c.description };
    const draw = () => `
      <div class="sheet-head"><h2 class="mono">/${esc(c.names[0] || c.original)}</h2><span class="badge ${c.scope === 'admin' ? 'amber' : c.scope === 'private' ? '' : 'blue'}">${c.scope_label}</span><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      ${c.overridden.length ? `<div class="alert warn" style="margin-bottom:12px">${icon('alert')}<div class="small">Có <b>lệnh tự tạo</b> cùng tên ${c.overridden.map((o) => `<code>/${esc(o.name)}</code> (${esc(o.where.join(', '))})`).join(', ')}, lệnh tự tạo sẽ được dùng thay lệnh này.</div></div>` : ''}
      <div class="card card-pad stack" style="margin-bottom:14px">
        <div>${esc(c.detail)}</div>
        <div><div class="xs muted" style="margin-bottom:4px">Cú pháp</div><code class="cmd-code">${esc(c.usage)}</code></div>
        <div><div class="xs muted" style="margin-bottom:4px">Ví dụ</div>${c.examples.map((x) => `<div class="cmd-ex">${esc(x)}</div>`).join('')}</div>
        ${c.note ? `<div class="alert info" style="margin:0">${icon('info')}<div class="xs">${esc(c.note)}</div></div>` : ''}
        <div class="xs muted">Ai dùng được: ${c.scope === 'admin' ? 'admin nhóm trên Telegram và admin web đã liên kết Telegram' : c.scope === 'private' ? 'mọi người, trong chat riêng với bot' : 'mọi thành viên (có thể giới hạn ở Cài đặt nhóm → Lệnh bot)'}</div>
      </div>
      ${!canEdit ? `<div class="alert info">${icon('lock')}<div class="xs">Chỉ admin web được đổi tên hoặc tắt lệnh mặc định.</div></div>` : c.fixed ? `<div class="alert info">${icon('lock')}<div class="xs">Lệnh này không đổi tên được.</div></div>` : `
      <form id="fc" class="stack">
        <div class="card" style="box-shadow:none">${sw('enabled', st.enabled, 'Bật lệnh', 'Tắt thì bot bỏ qua lệnh này và ẩn khỏi menu')}</div>
        <div class="field"><span class="lbl">Tên thay thế (tối đa 5)</span>
          <div class="row wrap" style="gap:6px;margin-bottom:8px">${st.aliases.length ? st.aliases.map((a, i) => `<span class="badge blue mono" style="font-size:13px;padding:4px 6px 4px 10px">/${esc(a)}<button type="button" class="btn icon sm ghost" data-rmalias="${i}" style="width:22px;height:22px" aria-label="Xoá">${icon('x', 'sm')}</button></span>`).join('') : '<span class="xs muted">Chưa có, đang dùng tên gốc /' + esc(c.original) + '</span>'}</div>
          <div class="row"><div class="input-icon grow"><span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-weight:700;color:var(--muted)">/</span><input class="input mono" id="newalias" placeholder="VD: canhan" style="padding-left:28px" autocapitalize="off" maxlength="32"></div><button type="button" class="btn soft" data-addalias>${icon('plus', 'sm')}Thêm</button></div>
          <div class="hint">Chữ thường không dấu, số, dấu _. Tên thay thế đầu tiên sẽ là <b>tên chính</b> (hiện trong menu, /help).</div></div>
        ${st.aliases.length ? `<div class="card" style="box-shadow:none">${sw('keep_original', st.keep, 'Vẫn dùng được tên gốc /' + esc(c.original), 'Tắt nếu chỉ muốn dùng tên mới')}</div>` : ''}
        <label class="field"><span class="lbl">Mô tả trong menu Telegram</span><input class="input" name="description" value="${esc(st.desc)}" maxlength="250"><div class="hint">Mặc định: ${esc(c.default_description)}</div></label>
        <div class="grid grid-2"><button type="button" class="btn" data-resetone>${icon('refresh', 'sm')}Mặc định</button><button class="btn primary">${icon('check', 'sm')}Lưu</button></div>
      </form>`}`;
    sheet('<div id="cmdbox"></div>', (el, close) => {
      const box = $('#cmdbox', el);
      const collect = () => {
        const f = $('#fc', el);
        if (!f) return;
        st.enabled = f.enabled.checked;
        if (f.keep_original) st.keep = f.keep_original.checked;
        st.desc = f.description.value;
      };
      const redraw = () => { collect(); box.innerHTML = draw(); };
      box.innerHTML = draw();
      const addAlias = () => {
        const inp = $('#newalias', el);
        const v = inp.value.trim().replace(/^\/+/, '').toLowerCase();
        if (!v) return;
        if (!/^[a-z0-9_]{1,32}$/.test(v)) return toast('Tên chỉ gồm chữ thường không dấu, số, dấu _', 'err');
        if (st.aliases.includes(v) || v === c.original) return toast('Tên này đã có', 'err');
        if (st.aliases.length >= 5) return toast('Tối đa 5 tên thay thế', 'err');
        st.aliases.push(v);
        if (st.aliases.length === 1) st.keep = true;
        redraw();
        $('#newalias', el)?.focus();
      };
      el.addEventListener('keydown', (e) => { if (e.target.id === 'newalias' && e.key === 'Enter') { e.preventDefault(); addAlias(); } });
      el.addEventListener('click', async (e) => {
        if (e.target.closest('[data-addalias]')) addAlias();
        const rm = e.target.closest('[data-rmalias]');
        if (rm) { st.aliases.splice(Number(rm.dataset.rmalias), 1); redraw(); }
        if (e.target.closest('[data-resetone]')) {
          try { await api('commands/reset', { key: c.key }); close(); toast('Đã đặt lại /' + c.original); onSaved(); } catch (err) { toast(err.message, 'err'); }
        }
      });
      el.addEventListener('submit', async (e) => {
        e.preventDefault();
        collect();
        const extra = $('#newalias', el)?.value.trim();
        if (extra) addAlias();
        await withBtn(e.target.querySelector('button.primary'), async () => {
          try {
            const r = await api('commands/save', { key: c.key, aliases: st.aliases, keep_original: st.keep, enabled: st.enabled, description: st.desc });
            close();
            toast(r.command.enabled ? `Đã lưu: ${r.command.names.map((n) => '/' + n).join(', ')}` : `Đã tắt /${c.original}`);
            onSaved();
          } catch (err) { toast(err.message, 'err'); }
        });
      });
    });
  }

  // ================= Quảng cáo xoay vòng =================
  const ivLabel = (sec) => (sec < 60 ? sec + ' giây' : humanSec(sec));
  const adImg = (a) => `api.php?r=ads/image&id=${a.id}&v=${a.updated_at}`;

  // Bản xem trước giống bài đăng Telegram (ảnh + chú thích + nút)
  function tgPost(img, caption, buttons) {
    const rows = [];
    (buttons || []).forEach((b) => { (rows[b.row] = rows[b.row] || []).push(b); });
    return `<div class="tgpost">${img ? `<img src="${esc(img)}" alt="">` : `<div class="tgpost-noimg">${icon('image')}<span>Chưa có ảnh</span></div>`}
      <div class="tgpost-cap">${caption ? previewText(caption) : '<span class="muted">Nội dung bài quảng cáo…</span>'}</div></div>
      ${rows.filter(Boolean).map((r) => `<div class="tgpost-btns">${r.map((b) => `<span class="tgpost-btn ${b.style ? 'st-' + b.style : ''}">${b.emoji ? '✨ ' : ''}${esc(b.text || 'Nút')}<i>↗</i></span>`).join('')}</div>`).join('')}`;
  }

  route('/ads', async () => {
    const d = await api('ads');
    const now = Math.floor(Date.now() / 1000);
    const cronOk = d.last_tick && now - d.last_tick < 150;
    const gname = (id) => d.groups.find((g) => g.id === id)?.title || '#' + id;
    const card = (c) => `
      <div class="card ad-camp" style="margin-bottom:14px">
        <div class="card-pad">
          <div class="row" style="align-items:flex-start"><div class="feat-ico">📢</div>
            <div class="grow"><div class="row wrap" style="gap:6px"><b style="font-size:16px">${esc(c.name)}</b>${c.enabled ? '<span class="badge green">Đang chạy</span>' : '<span class="badge">Tạm dừng</span>'}</div>
            <div class="xs text-2" style="margin-top:4px">🔄 Đổi bài mỗi <b>${ivLabel(c.interval_sec)}</b> · ${c.rotate_order === 'random' ? 'ngẫu nhiên' : 'lần lượt'}${c.daily_new ? ` · 🆕 bài mới hằng ngày lúc <b>${esc(c.daily_time)}</b>` : ''}</div>
            <div class="xs text-2">🤖 ${c.bot_mode === 'custom' ? `Bot quảng cáo riêng <b>@${esc(c.bot_username || '')}</b>` : 'Bot mặc định của nhóm'} · 👥 ${c.group_ids.length ? c.group_ids.map((id) => esc(gname(id))).join(', ') : '<span style="color:var(--danger)">chưa chọn nhóm</span>'}</div></div>
            <button class="btn icon sm ghost" data-editcamp="${c.id}" aria-label="Sửa">${icon('settings', 'sm')}</button></div>
          ${c.last_error ? `<div class="alert err" style="margin-top:10px">${icon('alert')}<div class="xs" style="overflow-wrap:anywhere">${esc(c.last_error)}</div></div>` : ''}
          ${c.posts.map((p) => `<div class="alert ok" style="margin-top:10px">${icon('pin')}<div class="xs">Đang ghim ở <b>${esc(p.group)}</b> · hiện bài <b>${esc(c.ads.find((a) => a.id === p.ad_id)?.title || '#' + p.ad_id)}</b> · đăng ${ago(p.created_at)}${p.link ? ` · <a href="${esc(p.link)}" target="_blank" rel="noopener">mở tin</a>` : ''}</div></div>`).join('')}
          <div class="grid grid-2" style="margin-top:12px">
            <button class="btn primary sm" data-runcamp="${c.id}">${icon('send', 'sm')}Đăng & ghim ngay</button>
            ${c.enabled ? `<button class="btn sm soft-danger" data-stopcamp="${c.id}">${icon('power', 'sm')}Dừng</button>` : `<button class="btn sm soft" data-oncamp="${c.id}">${icon('power', 'sm')}Bật lại</button>`}
          </div>
        </div>
        <div class="list" style="border-radius:0 0 var(--radius) var(--radius);border-left:0;border-right:0;border-bottom:0;box-shadow:none">
          ${c.ads.map((a, i) => `<div class="list-item ad-row ${a.enabled ? '' : 'off'}">
            <button type="button" class="ad-thumb" data-editad="${a.id}" data-camp="${c.id}">${a.has_image ? `<img src="${adImg(a)}" alt="" loading="lazy">` : icon('image')}</button>
            <button type="button" class="grow ad-info" data-editad="${a.id}" data-camp="${c.id}">
              <div class="row wrap" style="gap:6px"><b class="ellipsis">${esc(a.title || 'Bài ' + (i + 1))}</b>${a.enabled ? '' : '<span class="badge">Tắt</span>'}${c.posts.some((p) => p.ad_id === a.id) ? '<span class="badge blue">Đang hiện</span>' : ''}</div>
              <div class="xs text-2 ad-cap">${esc(stripTags(a.caption).slice(0, 90))}</div>
              <div class="xs muted">${a.buttons.length} nút${a.buttons.some((b) => b.style) ? ' ' + a.buttons.filter((b) => b.style).map((b) => ({ primary: '🔵', success: '🟢', danger: '🔴' })[b.style]).join('') : ''} · hiện ${num(a.shows)} lần</div>
            </button>
            <div class="ad-move"><button class="btn icon sm ghost" data-move="${a.id}" data-dir="-1" ${i === 0 ? 'disabled' : ''} aria-label="Lên">${icon('up', 'sm')}</button><button class="btn icon sm ghost" data-move="${a.id}" data-dir="1" ${i === c.ads.length - 1 ? 'disabled' : ''} aria-label="Xuống">${icon('down', 'sm')}</button></div>
          </div>`).join('')}
          <button type="button" class="list-item" data-newad="${c.id}" style="color:var(--primary);justify-content:center">${icon('plus', 'sm')}<b>Thêm bài quảng cáo</b> <span class="xs muted">(${c.ads.length} bài)</span></button>
        </div>
      </div>`;
    return {
      title: 'Quảng cáo',
      actions: `<button class="btn primary sm" data-newcamp>${icon('plus', 'sm')}Chiến dịch</button>`,
      html: `
        ${cronOk ? `<div class="alert ok" style="margin-bottom:12px">${icon('check')}<div class="small">Bộ xoay vòng đang chạy · lần cuối ${ago(d.last_tick)}</div></div>`
          : `<div class="alert warn" style="margin-bottom:12px">${icon('alert')}<div class="small"><b>Cần đặt Cron mỗi 1 phút</b> để bot đổi bài & đăng bài mới tự động${d.last_tick ? ` (lần chạy cuối ${ago(d.last_tick)})` : ''}.<br>
            aaPanel → <b>Cron</b> → Add → Type <b>Shell Script</b> → Period: <b>N Minutes = 1</b> → Script:<div class="row" style="margin:6px 0"><code class="cmd-code grow">${esc(d.cron_cmd)}</code><button class="btn icon sm" data-copy="${esc(d.cron_cmd)}">${icon('copy', 'sm')}</button></div>
            <span class="xs">⚠️ Dùng đúng đường dẫn PHP ở trên (cùng phiên bản với web). Lệnh <code>php</code> trơn có thể gọi bản PHP khác thiếu extension.</span><br>
            <span class="xs">Hoặc Type <b>Access URL</b>: <code style="overflow-wrap:anywhere">${esc(d.cron_url)}</code></span></div></div>`}
        ${d.campaigns.length ? d.campaigns.map(card).join('') : `<div class="card">${empty('megaphone', 'Chưa có chiến dịch quảng cáo.<br>Bấm <b>+ Chiến dịch</b> để bắt đầu.')}</div>`}
        <div class="card card-pad xs text-2" style="margin-top:4px">💡 Bot đăng 1 bài (ảnh + nội dung + nút) và <b>ghim</b> lên nhóm, sau đó <b>tự sửa tin đã ghim</b> sang bài kế tiếp theo chu kỳ. Mỗi ngày đúng giờ cài đặt sẽ đăng & ghim bài mới. Bot đăng cần quyền <b>gửi ảnh</b> và <b>ghim tin</b> trong nhóm.</div>`,
      mount: (root) => {
        const reload = () => render();
        $('[data-newcamp]')?.addEventListener('click', () => campaignSheet(null, d, reload));
        root.addEventListener('click', async (e) => {
          const t = (sel) => e.target.closest(sel);
          let b;
          if ((b = t('[data-editcamp]'))) return campaignSheet(d.campaigns.find((c) => c.id === Number(b.dataset.editcamp)), d, reload);
          if ((b = t('[data-newad]'))) return adSheet(null, Number(b.dataset.newad), reload);
          if ((b = t('[data-editad]'))) { const c = d.campaigns.find((x) => x.id === Number(b.dataset.camp)); return adSheet(c.ads.find((a) => a.id === Number(b.dataset.editad)), c.id, reload); }
          if ((b = t('[data-move]'))) { await api('ads/ad/move', { id: Number(b.dataset.move), dir: Number(b.dataset.dir) }); return reload(); }
          if ((b = t('[data-runcamp]'))) {
            return withBtn(b, async () => {
              try {
                const r = await api('ads/campaign/run', { id: Number(b.dataset.runcamp) });
                if (r.done.length) toast('Đã đăng & ghim ở: ' + r.done.join(', '));
                r.errors.forEach((x) => toast(x, 'err'));
                reload();
              } catch (err) { toast(err.message, 'err'); }
            });
          }
          if ((b = t('[data-stopcamp]'))) {
            if (!(await confirmBox('Dừng quảng cáo?', 'Bot sẽ bỏ ghim bài quảng cáo và ngừng đổi bài.', { danger: true, ok: 'Dừng' }))) return;
            await api('ads/campaign/stop', { id: Number(b.dataset.stopcamp) }); toast('Đã dừng'); return reload();
          }
          if ((b = t('[data-oncamp]'))) {
            const c = d.campaigns.find((x) => x.id === Number(b.dataset.oncamp));
            await api('ads/campaign/save', { ...c, enabled: true }); toast('Đã bật lại · bài sẽ được đăng ở lượt cron kế tiếp'); return reload();
          }
        });
      },
    };
  }, { webAdmin: true, title: 'Quảng cáo' });

  function campaignSheet(c, d, onSaved) {
    const v = c || { name: '', enabled: 1, group_ids: [], bot_mode: 'default', interval_sec: 10, rotate_order: 'sequence', daily_new: 1, daily_time: '00:00', pin: 1, pin_silent: 1, delete_old: 0 };
    let sel = [...v.group_ids];
    let mode = v.bot_mode;
    const draw = () => `
      <div class="sheet-head"><h2>${c ? 'Sửa chiến dịch' : 'Chiến dịch quảng cáo mới'}</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <form id="fcamp" class="stack">
        <label class="field"><span class="lbl">Tên chiến dịch</span><input class="input" name="name" value="${esc(v.name)}" placeholder="VD: Quảng cáo tháng 11" required maxlength="120"></label>
        <div class="field"><span class="lbl">Đăng vào nhóm</span><div class="chips" style="flex-wrap:wrap">${d.groups.map((g) => `<button type="button" class="chip ${sel.includes(g.id) ? 'active' : ''}" data-g="${g.id}">${esc(g.title)}</button>`).join('') || '<span class="xs muted">Chưa có nhóm nào</span>'}</div></div>
        <div class="field"><span class="lbl">Bot đăng quảng cáo</span>
          <div class="segmented"><button type="button" data-mode="default" class="${mode === 'default' ? 'active' : ''}">🤖 Bot mặc định</button><button type="button" data-mode="custom" class="${mode === 'custom' ? 'active' : ''}">➕ Bot Telegram khác</button></div>
          ${mode === 'custom' ? `<div class="stack" style="margin-top:10px">
            <input class="input mono" name="bot_token" placeholder="${c && c.has_token ? 'Đang dùng @' + esc(c.bot_username || '') + ' · nhập token mới để đổi' : 'Token bot quảng cáo (từ @BotFather)'}" autocomplete="off">
            <div class="hint">Thêm bot này vào nhóm và cấp quyền <b>quản trị</b> (gửi tin, ghim tin). Bot quảng cáo chỉ dùng để đăng, không cần cài webhook.</div></div>`
            : '<div class="hint">Dùng bot đang quản lý từng nhóm.</div>'}
        </div>
        <div class="grid grid-2">
          <label class="field"><span class="lbl">Đổi bài mỗi</span><select class="input" name="interval_sec">${d.intervals.map((x) => `<option value="${x}" ${x === v.interval_sec ? 'selected' : ''}>${ivLabel(x)}</option>`).join('')}</select></label>
          <label class="field"><span class="lbl">Thứ tự</span><select class="input" name="rotate_order"><option value="sequence">Lần lượt</option><option value="random" ${v.rotate_order === 'random' ? 'selected' : ''}>Ngẫu nhiên</option></select></label>
        </div>
        <div class="card" style="box-shadow:none">
          ${sw('daily_new', v.daily_new, 'Đăng bài mới mỗi ngày', 'Đúng giờ bên dưới, bot đăng & ghim 1 bài mới')}
          <div class="form-group"><label class="field"><span class="lbl">Giờ đăng bài mới</span><input class="input" type="time" name="daily_time" value="${esc(v.daily_time)}"></label></div>
          ${sw('pin', v.pin, 'Ghim bài quảng cáo')}
          ${sw('pin_silent', v.pin_silent, 'Ghim im lặng', 'Không gửi thông báo ghim cho thành viên')}
          ${sw('delete_old', v.delete_old, 'Xoá bài của hôm trước', 'Tắt = chỉ bỏ ghim bài cũ')}
          ${sw('enabled', v.enabled, 'Bật chiến dịch')}
        </div>
        <div class="grid ${c ? 'grid-2' : ''}">${c ? `<button type="button" class="btn soft-danger" data-delcamp>${icon('trash', 'sm')}Xoá</button>` : ''}<button class="btn primary">${icon('check', 'sm')}Lưu</button></div>
      </form>`;
    sheet('<div id="campbox"></div>', (el, close) => {
      const box = $('#campbox', el);
      const keep = () => { const f = $('#fcamp', el); if (f) Object.assign(v, formData(f)); };
      box.innerHTML = draw();
      el.addEventListener('click', async (e) => {
        const g = e.target.closest('[data-g]');
        if (g) { const id = Number(g.dataset.g); sel = sel.includes(id) ? sel.filter((x) => x !== id) : [...sel, id]; g.classList.toggle('active'); }
        const m = e.target.closest('[data-mode]');
        if (m) { keep(); mode = m.dataset.mode; box.innerHTML = draw(); }
        if (e.target.closest('[data-delcamp]')) {
          if (!(await confirmBox('Xoá chiến dịch?', 'Bài quảng cáo sẽ bị bỏ ghim và xoá toàn bộ bài trong chiến dịch.', { danger: true, ok: 'Xoá' }))) return;
          await api('ads/campaign/delete', { id: c.id }); close(); toast('Đã xoá chiến dịch'); onSaved();
        }
      });
      el.addEventListener('submit', async (e) => {
        e.preventDefault();
        const f = formData(e.target);
        await withBtn(e.target.querySelector('button.primary'), async () => {
          try {
            await api('ads/campaign/save', { id: c?.id, ...f, interval_sec: Number(f.interval_sec), group_ids: sel, bot_mode: mode });
            close(); toast('Đã lưu chiến dịch'); onSaved();
          } catch (err) { toast(err.message, 'err'); }
        });
      });
    });
  }

  // Nén ảnh trước khi tải lên (tối đa 1600px, JPEG)
  function resizeImage(file) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => {
        const k = Math.min(1, 1600 / Math.max(img.width, img.height));
        const cv = document.createElement('canvas');
        cv.width = Math.round(img.width * k); cv.height = Math.round(img.height * k);
        cv.getContext('2d').drawImage(img, 0, 0, cv.width, cv.height);
        resolve(cv.toDataURL('image/jpeg', 0.88));
        URL.revokeObjectURL(img.src);
      };
      img.onerror = () => reject(new Error('Không đọc được ảnh'));
      img.src = URL.createObjectURL(file);
    });
  }

  function adSheet(a, campaignId, onSaved) {
    const st = {
      title: a?.title || '', caption: a?.caption || '', enabled: a ? a.enabled : 1, image: null,
      imgUrl: a?.has_image ? adImg(a) : null,
      buttons: (a?.buttons || []).map((b, i, arr) => ({ ...b, same: i > 0 && b.row === arr[i - 1].row })),
    };
    const rowsOf = () => { let r = -1; return st.buttons.map((b, i) => ({ text: b.text, url: b.url, style: b.style || '', emoji: b.emoji || '', row: (r = i > 0 && b.same ? r : r + 1) })); };
    const btnsHtml = () => st.buttons.map((b, i) => `<div class="ad-btn-edit">
        <div class="row" style="gap:6px"><input class="input" data-bt="${i}" data-k="text" value="${esc(b.text)}" placeholder="Chữ trên nút, VD: Mua Ngay" maxlength="64">
        <button type="button" class="btn icon sm ghost" data-rmbtn="${i}" aria-label="Xoá nút">${icon('trash', 'sm')}</button></div>
        <input class="input mono" data-bt="${i}" data-k="url" value="${esc(b.url)}" placeholder="https://… hoặc @username" style="margin-top:6px">
        <div class="btn-colors">${[['', 'Mặc định'], ['primary', 'Xanh dương'], ['success', 'Xanh lá'], ['danger', 'Đỏ']].map(([v, t]) => `<button type="button" class="btn-color ${v ? 'st-' + v : 'st-default'} ${(b.style || '') === v ? 'active' : ''}" data-bstyle="${i}" data-v="${v}" title="${t}">${(b.style || '') === v ? '✓ ' : ''}${t}</button>`).join('')}</div>
        <details class="xs" style="margin-top:6px"${b.emoji ? ' open' : ''}><summary class="muted" style="cursor:pointer">✨ Emoji động trước chữ (tuỳ chọn, cần Telegram Premium)</summary>
          <input class="input mono" data-bt="${i}" data-k="emoji" value="${esc(b.emoji || '')}" placeholder="ID custom emoji, VD 5368324170671202286" inputmode="numeric" style="margin-top:6px">
          <div class="muted" style="margin-top:4px">Chỉ hoạt động khi chủ bot có Telegram Premium. Lấy ID bằng cách gửi emoji cho bot như @getidsbot.</div></details>
        ${i > 0 ? `<label class="row xs" style="margin-top:6px;gap:6px;cursor:pointer"><input type="checkbox" data-bt="${i}" data-k="same" ${b.same ? 'checked' : ''}> Cùng hàng với nút phía trên</label>` : ''}
      </div>`).join('');
    const html = `
      <div class="sheet-head"><h2>${a ? 'Sửa bài quảng cáo' : 'Bài quảng cáo mới'}</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <div class="ad-editor">
        <form id="fad" class="stack">
          <div class="field"><span class="lbl">Ảnh</span>
            <label class="ad-pick"><input type="file" accept="image/*" id="adfile" hidden><span id="adpickimg">${st.imgUrl ? `<img src="${st.imgUrl}" alt="">` : `${icon('image')}<b>Chọn ảnh</b><small>JPG / PNG, nên ngang 16:9</small>`}</span></label></div>
          <label class="field"><span class="lbl">Tên bài (chỉ hiện trên web)</span><input class="input" name="title" value="${esc(st.title)}" placeholder="VD: Hidemium 230k" maxlength="120"></label>
          <label class="field"><span class="lbl">Nội dung <span class="xs muted" id="capn"></span></span><textarea class="input" data-rich name="caption" style="min-height:120px" placeholder="Hidemium 30 ngày giá 230k, rẻ nhất thị trường - Mua liên hệ @domino_real">${esc(st.caption)}</textarea>
            <div class="hint">Bôi đen chữ rồi bấm nút hoặc <b>Ctrl+B</b> đậm · <b>Ctrl+I</b> nghiêng · <b>Ctrl+U</b> gạch chân · <b>Ctrl+K</b> link. @username tự thành link trên Telegram.</div></label>
          <div class="field"><span class="lbl">Nút bấm</span><div id="adbtns" class="stack" style="gap:8px">${btnsHtml()}</div>
            <button type="button" class="btn soft block sm" data-addbtn style="margin-top:8px">${icon('plus', 'sm')}Thêm nút</button></div>
          <div class="card" style="box-shadow:none">${sw('enabled', st.enabled, 'Bật bài này', 'Tắt thì bỏ qua khi xoay vòng')}</div>
          <div class="grid ${a ? 'grid-2' : ''}">${a ? `<button type="button" class="btn soft-danger" data-delad>${icon('trash', 'sm')}Xoá</button>` : ''}<button class="btn primary">${icon('check', 'sm')}Lưu bài</button></div>
        </form>
        <div class="ad-preview"><div class="pv-label">👁 Xem trước trên Telegram</div><div id="adpv"></div></div>
      </div>`;
    sheet(html, (el, close) => {
      el.classList.add('sheet-wide');
      const f = $('#fad', el);
      enhanceRich(el);
      const pv = () => {
        st.caption = f.caption.value;
        const len = f.caption._rich ? f.caption._rich.textLength() : st.caption.length;
        $('#capn', el).textContent = `${len}/1024`;
        $('#capn', el).style.color = len > 1024 ? 'var(--danger)' : '';
        $('#adpv', el).innerHTML = tgPost(st.image || st.imgUrl, st.caption, rowsOf());
      };
      pv();
      $('#adfile', el).addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        try { st.image = await resizeImage(file); $('#adpickimg', el).innerHTML = `<img src="${st.image}" alt="">`; pv(); }
        catch (err) { toast(err.message, 'err'); }
      });
      el.addEventListener('input', (e) => {
        const b = e.target.closest('[data-bt]');
        if (b) { const x = st.buttons[b.dataset.bt]; x[b.dataset.k] = b.type === 'checkbox' ? b.checked : b.value; }
        pv();
      });
      el.addEventListener('change', (e) => { const b = e.target.closest('[data-bt][type=checkbox]'); if (b) { st.buttons[b.dataset.bt].same = b.checked; pv(); } });
      el.addEventListener('click', async (e) => {
        const bs = e.target.closest('[data-bstyle]');
        if (bs) { st.buttons[bs.dataset.bstyle].style = bs.dataset.v; $('#adbtns', el).innerHTML = btnsHtml(); pv(); return; }
        if (e.target.closest('[data-addbtn]')) { st.buttons.push({ text: '', url: '', style: '', emoji: '', same: false }); $('#adbtns', el).innerHTML = btnsHtml(); pv(); $$('[data-k=text]', el).pop()?.focus(); }
        const rm = e.target.closest('[data-rmbtn]');
        if (rm) { st.buttons.splice(Number(rm.dataset.rmbtn), 1); if (st.buttons[0]) st.buttons[0].same = false; $('#adbtns', el).innerHTML = btnsHtml(); pv(); }
        if (e.target.closest('[data-delad]')) {
          if (!(await confirmBox('Xoá bài quảng cáo?', 'Bài này sẽ bị xoá khỏi vòng quảng cáo.', { danger: true, ok: 'Xoá' }))) return;
          await api('ads/ad/delete', { id: a.id }); close(); toast('Đã xoá bài'); onSaved();
        }
      });
      f.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!st.image && !st.imgUrl) return toast('Chọn ảnh cho bài quảng cáo', 'err');
        await withBtn(f.querySelector('button.primary'), async () => {
          try {
            await api('ads/ad/save', { id: a?.id, campaign_id: campaignId, title: f.title.value, caption: f.caption.value, enabled: f.enabled.checked, buttons: rowsOf(), image: st.image || '' });
            close(); toast('Đã lưu bài quảng cáo'); onSaved();
          } catch (err) { toast(err.message, 'err'); }
        });
      });
    });
  }

  // ================= Admin: More / Users / System =================
  route('/more', async () => ({
    title: 'Thêm',
    html: `
      <div class="card card-pad row" style="margin-bottom:14px">${avatar(S.user.display_name, S.user.id, 'lg')}<div class="grow"><h2>${esc(S.user.display_name)}</h2><div class="small muted">@${esc(S.user.username)} · ${roleLabel()}</div></div></div>
      <div class="list">
        <a class="list-item" href="#/rules">${icon('terminal')}<div class="grow">Lệnh tự tạo & lệnh mặc định</div><span class="chev">${icon('chevron')}</span></a>
        ${isWebAdmin() ? `<a class="list-item" href="#/ads">${icon('megaphone')}<div class="grow">Quảng cáo xoay vòng</div><span class="chev">${icon('chevron')}</span></a>` : ''}
        ${isWebAdmin() ? `<a class="list-item" href="#/users">${icon('key')}<div class="grow">Tài khoản web & phân quyền</div><span class="chev">${icon('chevron')}</span></a>
        <a class="list-item" href="#/payments">${icon('bank')}<div class="grow">Nạp tiền & ân xá</div><span class="chev">${icon('chevron')}</span></a>
        <a class="list-item" href="#/system">${icon('download')}<div class="grow">Hệ thống & cập nhật</div><span class="chev">${icon('chevron')}</span></a>` : ''}
        ${S.pay?.wallet || isWebAdmin() ? `<a class="list-item" href="#/wallet">${icon('wallet')}<div class="grow">Ví của tôi</div><span class="small muted">${money(S.user.balance || 0)}</span><span class="chev">${icon('chevron')}</span></a>` : ''}
        ${S.pay?.amnesty || isWebAdmin() ? `<a class="list-item" href="#/amnesty">${icon('heart')}<div class="grow">Ân xá thành viên</div><span class="chev">${icon('chevron')}</span></a>` : ''}
        <a class="list-item" href="#/me">${icon('star')}<div class="grow">Điểm của tôi</div><span class="chev">${icon('chevron')}</span></a>
        ${!isWebAdmin() ? `<a class="list-item" href="#/rank">${icon('trophy')}<div class="grow">Xếp hạng</div><span class="chev">${icon('chevron')}</span></a>` : ''}
        <a class="list-item" href="#/account">${icon('user')}<div class="grow">Tài khoản & liên kết Telegram</div><span class="chev">${icon('chevron')}</span></a>
        <button class="list-item" data-theme-toggle>${icon('moon')}<div class="grow">Giao diện sáng / tối</div></button>
        <button class="list-item" data-logout style="color:var(--danger)">${icon('logout')}<div class="grow">Đăng xuất</div></button>
      </div>
      ${!isWebAdmin() ? `<div class="alert info" style="margin-top:14px">${icon('lock')}<div class="xs">Bot, tài khoản web, hệ thống & cập nhật, lệnh dùng chung cho mọi nhóm chỉ <b>admin web</b> mới vào được.</div></div>` : ''}`,
  }), { manage: true, title: 'Thêm' });

  route('/users', async () => {
    const users = await api('users');
    const item = (u) => `<button class="list-item" data-user='${esc(JSON.stringify(u))}'>${avatar(u.display_name, u.id)}
      <div class="grow"><div class="row" style="gap:6px"><b class="ellipsis">${esc(u.display_name)}</b>${u.role === 'admin' ? '<span class="badge blue">Admin web</span>' : u.managed_groups ? `<span class="badge green">${icon('shield', 'sm')}Admin ${u.managed_groups} nhóm</span>` : '<span class="badge">Thành viên</span>'}${u.disabled ? '<span class="badge red">Khoá</span>' : ''}</div>
      <div class="xs muted">@${esc(u.username)}${u.tg_user_id ? ' · TG ' + u.tg_user_id : ' · chưa liên kết'} · ${ago(u.last_login)}</div></div>${u.balance ? `<span class="small nowrap" style="font-weight:600">${money(u.balance)}</span>` : ''}<span class="chev">${icon('chevron')}</span></button>`;
    const form = (u) => sheet(`<div class="sheet-head"><h2>${u ? 'Sửa tài khoản' : 'Tạo tài khoản'}</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
      <form class="stack" id="fu">
        ${u ? `<div class="small muted">@${esc(u.username)}</div>` : `<label class="field"><span class="lbl">Tên đăng nhập</span><input class="input" name="username" required></label>`}
        <label class="field"><span class="lbl">Tên hiển thị</span><input class="input" name="display_name" value="${esc(u?.display_name || '')}"></label>
        <label class="field"><span class="lbl">${u ? 'Mật khẩu mới (bỏ trống nếu giữ)' : 'Mật khẩu'}</span><input class="input" type="password" name="password" ${u ? '' : 'required'} minlength="6" autocomplete="new-password"></label>
        <label class="field"><span class="lbl">ID Telegram</span><input class="input" name="tg_user_id" inputmode="numeric" value="${esc(u?.tg_user_id || '')}"><div class="hint">Admin có ID Telegram sẽ dùng được lệnh quản trị của bot</div></label>
        <label class="field"><span class="lbl">Vai trò</span><select class="input" name="role"><option value="member">Thành viên</option><option value="admin" ${u?.role === 'admin' ? 'selected' : ''}>Admin web (toàn quyền)</option></select>
          <div class="hint">Thành viên đã liên kết Telegram và là admin nhóm trên Telegram sẽ tự có quyền <b>Quản trị viên nhóm</b> (chỉnh ở Hệ thống → Phân quyền).</div></label>
        ${u ? `<div class="card" style="box-shadow:none">${sw('disabled', u.disabled, 'Khoá tài khoản')}</div>
        <div class="card card-pad stack" style="box-shadow:none;background:var(--surface-2)">
          <div class="row between"><b>${icon('wallet', 'sm')} Số dư ví</b><b data-ubal>${money(u.balance)}</b></div>
          <div class="grid grid-2"><input class="input" data-badj inputmode="numeric" placeholder="+50000 hoặc -20000"><input class="input" data-bnote placeholder="Ghi chú"></div>
          <div class="grid grid-2"><button type="button" class="btn sm soft" data-badjust>Cộng / trừ</button><button type="button" class="btn sm" data-btx>Lịch sử ví</button></div>
          <div data-btxlist></div>
        </div>` : ''}
        <div class="grid ${u ? 'grid-2' : ''}">${u ? `<button type="button" class="btn soft-danger" data-deluser>${icon('trash', 'sm')}Xoá</button>` : ''}<button class="btn primary">Lưu</button></div>
      </form>`, (el, close) => {
      $('#fu', el).onsubmit = async (e) => {
        e.preventDefault();
        try { await api('users/save', { id: u?.id, ...formData(e.target) }); close(); toast('Đã lưu'); render(); } catch (err) { toast(err.message, 'err'); }
      };
      $('[data-badjust]', el)?.addEventListener('click', (e) => withBtn(e.currentTarget, async () => {
        const amount = parseInt(($('[data-badj]', el).value || '').replace(/[^\d-]/g, ''), 10) || 0;
        if (!amount) return toast('Nhập số tiền, số âm để trừ', 'err');
        if (!(await confirmBox('Điều chỉnh số dư?', `${amount > 0 ? 'Cộng' : 'Trừ'} <b>${money(Math.abs(amount))}</b> ${amount > 0 ? 'cho' : 'của'} <b>${esc(u.username)}</b>`))) return;
        try { const r = await api('users/balance', { user_id: u.id, amount, note: $('[data-bnote]', el).value }); u.balance = r.balance; $('[data-ubal]', el).textContent = money(r.balance); $('[data-badj]', el).value = ''; toast('Đã cập nhật số dư'); } catch (err) { toast(err.message, 'err'); }
      }));
      $('[data-btx]', el)?.addEventListener('click', (e) => withBtn(e.currentTarget, async () => {
        const r = await api('users/tx', undefined, { query: { user_id: u.id } });
        $('[data-btxlist]', el).innerHTML = `<div class="list">${r.rows.length ? r.rows.map(txItem).join('') : empty('wallet', 'Chưa có giao dịch')}</div>`;
      }));
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
  }, { webAdmin: true, title: 'Tài khoản web', back: '/more' });

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

      ${(() => {
        const un = d.update_notice;
        const linked = un.admins.filter((a) => a.linked);
        return `<div class="section-title"><h2>🔔 Báo cập nhật qua Telegram</h2></div>
        <div class="card" id="updnotice">
          ${sw('update_notify', un.enabled, 'Nhắn admin khi có bản mới', 'Bot nhắn riêng cho admin web kèm danh sách thay đổi và nút ✅ Cập nhật ngay / ⏭ Bỏ qua')}
          <div class="form-group"><label class="field"><span class="lbl">Kiểm tra GitHub mỗi</span>
            <select class="input" name="update_check_hours">${[[1, '1 giờ'], [3, '3 giờ'], [6, '6 giờ'], [12, '12 giờ'], [24, '1 ngày'], [72, '3 ngày'], [168, '1 tuần']].map(([v, t]) => `<option value="${v}" ${v === un.hours ? 'selected' : ''}>${t}</option>`).join('')}</select>
            <div class="hint">Lần kiểm tra gần nhất: ${un.last_check ? ago(un.last_check) : 'chưa có'}${un.last_error ? ` · <span style="color:var(--danger)">${esc(un.last_error)}</span>` : ''}. Chạy khi cron hoặc bot nhận tin nhắn.</div></label></div>
          <div class="form-group">
            <div class="small" style="font-weight:600;margin-bottom:6px">Người nhận (admin web)</div>
            ${un.admins.map((a) => `<div class="row small" style="padding:3px 0">${a.linked ? `<span style="color:var(--success)">${icon('check', 'sm')}</span>` : `<span style="color:var(--warning)">${icon('alert', 'sm')}</span>`}<div class="grow">${esc(a.name)}</div><span class="xs ${a.linked ? 'muted' : ''}" style="${a.linked ? '' : 'color:var(--warning)'}">${a.linked ? 'đã liên kết Telegram' : 'chưa liên kết – không nhận được'}</span></div>`).join('')}
            <div class="xs muted" style="margin-top:6px">Admin cần <b>liên kết Telegram</b> (Tài khoản) và đã nhắn <code>/start</code> cho bot thì bot mới nhắn được.</div>
          </div>
          <div class="form-group"><button type="button" class="btn soft block" data-testnotice ${linked.length ? '' : 'disabled'}>${icon('send', 'sm')}Gửi thử thông báo</button><div id="testres"></div></div>
        </div>`;
      })()}

      <form id="fperm">
        <div class="section-title"><h2>🛡 Phân quyền quản trị viên nhóm</h2></div>
        <div class="card">
          ${sw('group_admin_access', d.access.enabled, 'Cho quản trị viên nhóm vào web', 'Admin của nhóm trên Telegram, đã liên kết tài khoản web, sẽ quản lý được nhóm của họ')}
          <div class="form-group"><div class="xs muted" style="margin-bottom:6px">Quyền được cấp (chỉ trong nhóm họ làm admin)</div></div>
          ${Object.entries(d.access.catalog).map(([k, c]) => sw('perm_' + k, d.access.perms[k], (k === 'danger' ? '⚠️ ' : '') + c.name, c.desc)).join('')}
          <div class="form-group"><div class="alert info" style="margin:0">${icon('lock')}<div class="xs">Luôn chỉ <b>admin web</b>: Bot, Tài khoản web, Hệ thống & cập nhật, lệnh dùng chung cho mọi nhóm, chế độ menu lệnh, đổi bot quản lý nhóm.</div></div></div>
          <div class="form-group">
            <div class="small" style="font-weight:600;margin-bottom:6px">Đang là quản trị viên nhóm (${d.access.managers.length})</div>
            ${d.access.managers.length ? d.access.managers.map((x) => `<div class="row small" style="padding:4px 0">${avatar(x.name, x.id)}<div class="grow">${esc(x.name)}</div><span class="badge green">${x.groups} nhóm</span></div>`).join('')
              : '<div class="xs muted">Chưa có ai. Thành viên cần đăng ký tài khoản web, liên kết Telegram và là admin của nhóm có bot.</div>'}
          </div>
        </div>
        <button class="btn primary block" style="height:46px;margin-top:12px">${icon('check')}Lưu phân quyền</button>
      </form>

      <form id="fsys">
        <div class="section-title"><h2>Cài đặt chung</h2></div>
        <div class="card">
          <div class="form-group"><label class="field"><span class="lbl">Tên website</span><input class="input" name="site_name" value="${esc(s.site_name)}"></label></div>
          <div class="form-group"><label class="field"><span class="lbl">Địa chỉ web (dùng cho webhook)</span><input class="input" name="site_url" value="${esc(s.site_url)}" placeholder="${esc(d.detected_url)}"><div class="hint">Phải là <b>https://</b>. Tự nhận: ${esc(d.detected_url)}</div></label></div>
          ${sw('allow_register', s.allow_register === '1', 'Cho phép đăng ký thành viên', 'Thành viên tự tạo tài khoản (kể cả tự tạo khi đăng nhập bằng Telegram lần đầu)')}
          ${sw('tg_login', s.tg_login !== '0', 'Đăng nhập bằng Telegram', 'Lấy mã trên web → nhắn cho bot → bấm xác nhận')}
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
      <div class="card card-pad stack">
        <div class="small">${d.cron_last && Date.now() / 1000 - d.cron_last < 180 ? `<span style="color:var(--success)">${icon('check', 'sm')}</span> Cron đang chạy · lần cuối ${ago(d.cron_last)}` : `<span style="color:var(--warning)">${icon('alert', 'sm')}</span> ${d.cron_last ? 'Cron chạy lần cuối ' + ago(d.cron_last) : 'Cron chưa chạy lần nào'}`}</div>
        <div class="small text-2">aaPanel → <b>Cron</b> → Add → Type <b>Shell Script</b> → Period <b>N Minutes = 1</b> → Script (khuyên dùng, cần cho quảng cáo xoay vòng):</div>
        <div class="row"><input class="input mono" readonly value="${esc(d.cron_cmd)}"><button class="btn icon" data-copy="${esc(d.cron_cmd)}">${icon('copy')}</button></div>
        <div class="xs muted">Dùng đúng đường dẫn PHP ở trên. Lệnh <code>php</code> trơn có thể gọi bản PHP khác thiếu extension (lỗi <i>undefined function mb_…</i>).</div>
        <div class="small text-2">Hoặc Type <b>Access URL</b>:</div>
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
        // Báo cập nhật qua Telegram: lưu ngay khi đổi
        const un = $('#updnotice', root);
        un.addEventListener('change', async (e) => {
          const t = e.target;
          if (!['update_notify', 'update_check_hours'].includes(t.name)) return;
          try { await api('system/settings', { settings: { [t.name]: t.type === 'checkbox' ? t.checked : Number(t.value) } }); toast('Đã lưu'); }
          catch (err) { toast(err.message, 'err'); }
        });
        $('[data-testnotice]', un)?.addEventListener('click', (e) => withBtn(e.currentTarget, async () => {
          try {
            const r = await api('system/test-update-notice', {});
            $('#testres', un).innerHTML = `<div class="stack" style="margin-top:10px">${r.results.map((x) => `<div class="alert ${x.ok ? 'ok' : 'err'}">${icon(x.ok ? 'check' : 'alert')}<div class="small"><b>${esc(x.name)}</b>: ${x.ok ? 'đã gửi, mở Telegram để xem' : esc(x.error)}</div></div>`).join('')}</div>`;
          } catch (err) { toast(err.message, 'err'); }
        }));
        $('#fperm', root).onsubmit = async (e) => {
          e.preventDefault();
          const f = formData(e.target);
          const perms = {};
          Object.keys(d.access.catalog).forEach((k) => { perms[k] = !!f['perm_' + k]; });
          await withBtn(e.target.querySelector('button.primary'), async () => {
            try { await api('system/settings', { settings: { group_admin_access: f.group_admin_access, group_admin_perms: perms } }); toast('Đã lưu phân quyền'); }
            catch (err) { toast(err.message, 'err'); }
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
  }, { webAdmin: true, title: 'Hệ thống', back: '/more' });

  // ================= Member views =================
  async function viewMe() {
    const d = await api('me');
    const u = d.user;
    if (!u.tg_user_id) {
      return {
        title: isManager() ? 'Điểm của tôi' : 'Của tôi',
        html: `<div class="hero"><div class="muted small">Xin chào 👋</div><h1 style="margin-top:4px">${esc(u.display_name)}</h1><div class="muted small" style="margin-top:6px">Liên kết tài khoản Telegram để xem điểm tương tác, thứ hạng và trạng thái của bạn trong các nhóm.</div></div>
          <div class="alert info" style="margin-top:12px">${icon('shield')}<div class="small">Nếu bạn là <b>quản trị viên nhóm</b> trên Telegram, sau khi liên kết bạn sẽ quản lý được nhóm của mình ngay trên web.</div></div>
          <div style="margin-top:14px">${linkCard(d)}</div>`,
        mount: mountLink,
      };
    }
    const ms = d.memberships;
    const total = ms.reduce((a, b) => a + b.points, 0);
    const msgs = ms.reduce((a, b) => a + b.messages, 0);
    const best = ms.reduce((a, b) => (!a || b.rank < a.rank ? b : a), null);
    return {
      title: isManager() ? 'Điểm của tôi' : 'Của tôi',
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
        <div class="code-box"><span>${esc(d.link_code)}</span><button class="btn icon sm ghost" data-copy="/${esc(d.link_cmd || 'link')} ${esc(d.link_code)}">${icon('copy', 'sm')}</button></div>
        ${bot ? `<a class="btn primary block" href="https://t.me/${esc(bot)}?start=link_${esc(d.link_code)}" target="_blank" rel="noopener">${icon('send', 'sm')}Mở @${esc(bot)} để liên kết</a>` : ''}
        <div class="small muted">Hoặc gửi <code>/${esc(d.link_cmd || 'link')} ${esc(d.link_code)}</code> cho bot trong chat riêng. Sau đó nhấn <b>Tôi đã liên kết</b>.</div>
        <button class="btn block" data-relink>${icon('refresh', 'sm')}Tôi đã liên kết</button>`
        : `<div class="small text-2">Tạo mã rồi gửi cho bot qua chat riêng để xác minh tài khoản Telegram của bạn.</div><button class="btn primary block" data-gencode>Tạo mã liên kết</button>`}
    </div>`;
  }
  function mountLink(root) {
    root.addEventListener('click', async (e) => {
      if (e.target.closest('[data-gencode]')) { await api('me/link-code', {}); render(); }
      if (e.target.closest('[data-relink]')) {
        const st = await api('status');
        const wasManager = isManager();
        S.user = st.user;
        S.access = st.access;
        if (st.user?.tg_user_id) {
          toast(!wasManager && isManager() ? `Liên kết thành công! Bạn quản lý ${S.access.groups.length} nhóm trên web.` : 'Liên kết thành công!');
        } else toast('Chưa nhận được liên kết, hãy gửi mã cho bot', 'err');
        if (!wasManager && isManager()) { $('#app').innerHTML = ''; go('/'); } else render();
      }
    });
  }

  // ================= Ví & nạp tiền =================
  const money = (v) => num(v) + 'đ';
  const qrSrc = (w, amount) => w.account_no ? `https://img.vietqr.io/image/${encodeURIComponent(w.bank)}-${encodeURIComponent(w.account_no)}-compact2.png?` + new URLSearchParams({ ...(amount > 0 ? { amount } : {}), addInfo: w.code, ...(w.account_name ? { accountName: w.account_name } : {}) }) : '';
  const TX_ICON = { deposit: ['plus', 'green'], amnesty: ['heart', 'amber'], amnesty_fail: ['x', ''], refund: ['refresh', 'blue'], admin: ['key', ''] };
  const txItem = (t) => { const [ic, c] = TX_ICON[t.type] || ['info', '']; return `<div class="list-item"><span class="log-ico ${c}">${icon(ic, 'sm')}</span>
    <div class="grow" style="min-width:0"><div class="ellipsis" style="font-weight:600">${esc(t.note || t.type_label)}</div><div class="xs muted">${esc(t.type_label)} · ${fmtDate(t.created_at, true)}</div></div>
    <div style="text-align:right" class="nowrap"><div class="tx-amt ${t.amount > 0 ? 'in' : 'out'}">${t.amount > 0 ? '+' : '−'}${money(Math.abs(t.amount))}</div><div class="xs muted">Dư ${money(t.balance_after)}</div></div></div>`; };

  route('/wallet', async () => {
    const w = await api('wallet');
    const amounts = [20000, 50000, 100000, 200000, 500000];
    return {
      title: 'Ví của tôi',
      html: `
      <div class="hero wallet-hero">
        <div class="muted small">${icon('wallet', 'sm')} Số dư ví</div>
        <div class="big" data-bal>${money(w.balance)}</div>
        <div class="row wrap small" style="gap:14px;margin-top:6px"><span>Đã nạp ${money(w.stats.deposit)}</span><span>Đã dùng ${money(w.stats.spent)}</span></div>
        ${S.pay?.amnesty ? `<a class="btn sm" href="#/amnesty" style="margin-top:12px;background:rgba(255,255,255,.18);color:#fff;border-color:transparent">${icon('heart', 'sm')}Ân xá thành viên</a>` : ''}
      </div>
      ${!w.enabled ? `<div class="alert info" style="margin-top:12px">${icon('info')}<div class="small">Admin chưa bật nạp tiền. Liên hệ admin để được cộng tiền vào ví.</div></div>` : `
      <div class="section-title"><h2>Nạp tiền</h2>${w.auto ? `<span class="badge green">${icon('zap', 'sm')}Tự động</span>` : '<span class="badge">Admin duyệt tay</span>'}</div>
      <div class="card card-pad stack">
        <div class="field"><span class="lbl">Số tiền muốn nạp</span>
          <div class="chips" style="flex-wrap:wrap">${amounts.map((a) => `<button type="button" class="chip" data-amt="${a}">${num(a / 1000)}k</button>`).join('')}</div>
          <input class="input" data-amount inputmode="numeric" placeholder="Hoặc nhập số tiền (tối thiểu ${money(w.min)})" style="margin-top:8px"></div>
        <div class="pay-box">
          <div class="qr-wrap"><img data-qr alt="Mã QR chuyển khoản" src="${esc(qrSrc(w, 0))}" loading="lazy"><div class="xs muted center">Quét bằng app ngân hàng</div></div>
          <dl class="pay-kv">
            <dt>Ngân hàng</dt><dd>${esc(w.bank_name)}</dd>
            <dt>Số tài khoản</dt><dd><span class="mono">${esc(w.account_no)}</span><button class="btn icon sm ghost" data-copy="${esc(w.account_no)}" aria-label="Sao chép">${icon('copy', 'sm')}</button></dd>
            ${w.account_name ? `<dt>Chủ tài khoản</dt><dd>${esc(w.account_name)}</dd>` : ''}
            <dt>Số tiền</dt><dd data-amtshow>Tuỳ bạn (≥ ${money(w.min)})</dd>
            <dt>Nội dung</dt><dd><span class="pay-code">${esc(w.code)}</span><button class="btn icon sm ghost" data-copy="${esc(w.code)}" aria-label="Sao chép">${icon('copy', 'sm')}</button></dd>
          </dl>
        </div>
        <div class="alert warn">${icon('alert')}<div class="xs">Ghi <b>đúng nội dung ${esc(w.code)}</b> để hệ thống nhận ra tài khoản của bạn. Sai nội dung hoặc dưới ${money(w.min)} sẽ cần admin cộng tay.</div></div>
        <button class="btn primary block" data-check>${icon('refresh', 'sm')}Tôi đã chuyển khoản — kiểm tra ngay</button>
        <div class="xs muted center" data-checkmsg>${w.auto ? 'Tiền thường được cộng sau 1–2 phút.' + (w.last_sync ? ' Lần đồng bộ gần nhất: ' + ago(w.last_sync) + '.' : '') : 'Sau khi chuyển, admin sẽ kiểm tra và cộng tiền.'}</div>
      </div>`}
      <div class="section-title"><h2>Lịch sử giao dịch</h2></div>
      <div class="list" data-hist>${w.history.length ? w.history.map(txItem).join('') : empty('wallet', 'Chưa có giao dịch')}</div>
      ${w.has_more ? `<button class="btn block" data-more style="margin-top:10px">Xem thêm</button>` : ''}`,
      mount: (root) => {
        let amount = 0;
        const setAmt = (a) => {
          amount = a;
          $$('[data-amt]', root).forEach((c) => c.classList.toggle('active', +c.dataset.amt === a));
          const img = $('[data-qr]', root); if (img) img.src = qrSrc(w, a);
          const sh = $('[data-amtshow]', root); if (sh) sh.innerHTML = a > 0 ? `<b>${money(a)}</b>` : `Tuỳ bạn (≥ ${money(w.min)})`;
        };
        root.addEventListener('click', async (e) => {
          const c = e.target.closest('[data-amt]');
          if (c) { $('[data-amount]', root).value = ''; setAmt(+c.dataset.amt); }
          if (e.target.closest('[data-more]')) {
            const b = e.target.closest('[data-more]');
            const last = w.history[w.history.length - 1];
            await withBtn(b, async () => {
              const r = await api('wallet', undefined, { query: { before: last.id } });
              w.history.push(...r.history);
              $('[data-hist]', root).insertAdjacentHTML('beforeend', r.history.map(txItem).join(''));
              if (!r.has_more) b.remove();
            });
          }
        });
        $('[data-amount]', root)?.addEventListener('input', debounce((e) => setAmt(Math.max(0, parseInt(e.target.value.replace(/\D/g, ''), 10) || 0)), 400));
        const btn = $('[data-check]', root);
        if (!btn) return;
        let polling = null;
        const check = async () => {
          const r = await api('wallet/check', {});
          if (r.added > 0) {
            toast('Đã cộng ' + money(r.added) + ' vào ví 🎉');
            if (polling) clearInterval(polling);
            render();
            return true;
          }
          return false;
        };
        btn.onclick = () => withBtn(btn, async () => {
          try {
            if (await check()) return;
            const msg = $('[data-checkmsg]', root);
            msg.innerHTML = '<span class="dot amber"></span> Chưa thấy tiền về. Đang tự kiểm tra lại mỗi 15 giây (tối đa 10 phút)…';
            if (polling) clearInterval(polling);
            let n = 0;
            polling = every(async () => { if (++n > 40) { clearInterval(polling); msg.textContent = 'Chưa nhận được tiền. Nếu đã chuyển đúng nội dung, hãy liên hệ admin.'; return; } try { await check(); } catch {} }, 15000);
          } catch (err) { toast(err.message, 'err'); }
        });
      },
    };
  }, { title: 'Ví của tôi' });

  // ================= Ân xá =================
  const punishCard = (m, showGroup, byId = false) => `<div class="card card-pad amnesty-card">
    <div class="row">${avatar(m.name, m.tg_user_id)}<div class="grow" style="min-width:0"><div class="ellipsis" style="font-weight:600">${esc(m.name)}${m.username ? ` <span class="xs muted">@${esc(m.username)}</span>` : ''}</div>
      <div class="xs muted ellipsis">${showGroup ? esc(m.group_title || '') + ' · ' : ''}<span class="mono">${esc(m.tg_user_id)}</span></div></div>${statusBadge(m)}</div>
    <div class="stack" style="gap:8px;margin-top:12px">${m.punishments.map((p) => `<div class="punish-row"><span class="p-ico">${p.icon}</span><div class="grow" style="min-width:0"><div class="small" style="font-weight:600">${esc(p.label)}</div><div class="xs muted">${esc(p.desc)}</div></div>
      <button class="btn sm primary nowrap" data-amnesty='${esc(JSON.stringify({ id: m.id, kind: p.kind, price: p.price, label: p.label, icon: p.icon, desc: p.desc, name: m.name, group: m.group_title, by_id: byId }))}'>${icon('heart', 'sm')}${money(p.price)}</button></div>`).join('')}</div></div>`;

  route('/amnesty', async () => {
    const d = await api('amnesty');
    let balance = d.balance;
    const priceList = Object.entries(d.kinds).filter(([k]) => d.prices[k] > 0);
    return {
      title: 'Ân xá',
      html: `
      <div class="hero amnesty-hero">
        <div class="row between" style="align-items:flex-start"><div><div class="muted small">🕊 Ân xá thành viên</div><div class="big" style="font-size:28px" data-bal>${money(balance)}</div><div class="muted xs">Số dư ví</div></div>
        <a class="btn sm" href="#/wallet" style="background:rgba(255,255,255,.18);color:#fff;border-color:transparent">${icon('plus', 'sm')}Nạp tiền</a></div>
        <div class="row wrap" style="gap:8px;margin-top:12px">${priceList.map(([k, v]) => `<span class="price-pill">${v.icon} ${esc(v.label)} · <b>${money(d.prices[k])}</b></span>`).join('')}</div>
      </div>
      ${!d.enabled ? `<div class="alert info" style="margin-top:12px">${icon('info')}<div class="small">Tính năng ân xá đang tắt. ${isWebAdmin() ? 'Bật ở <a href="#/payments">Nạp tiền & ân xá</a>.' : ''}</div></div>` : ''}
      ${d.note ? `<div class="alert info" style="margin-top:12px">${icon('info')}<div class="small">${esc(d.note)}</div></div>` : ''}
      <div class="section-title"><h2>Hình phạt của bạn</h2></div>
      ${!d.linked ? `<div class="card">${empty('link', 'Liên kết Telegram ở trang <a href="#/account">Tài khoản</a> để xem và ân xá hình phạt của bạn.')}</div>`
        : d.mine.length ? `<div class="stack">${d.mine.map((m) => punishCard(m, true)).join('')}</div>` : `<div class="card">${empty('check', 'Bạn không có hình phạt nào 🎉')}</div>`}
      ${d.enabled && d.all_groups.length ? `
      <div class="section-title"><h2>Ân xá theo ID Telegram</h2></div>
      <form class="card card-pad stack" id="flookup">
        <div class="xs muted">Người bị cấm <b>trước khi có web</b> không có trong danh sách: nhập ID Telegram, bot sẽ kiểm tra trực tiếp trên Telegram xem người đó đang bị cấm hay cấm chat.${d.allow_others ? '' : ' <b>Admin chỉ cho tự ân xá cho chính mình.</b>'}</div>
        <label class="field"><span class="lbl">Nhóm</span><select class="input" name="group_id">${d.all_groups.map((g) => `<option value="${g.id}">${esc(g.title)}</option>`).join('')}</select></label>
        <div class="row" style="gap:8px"><input class="input grow" name="who" required placeholder="ID Telegram, VD 123456789" inputmode="text" autocomplete="off"><button class="btn primary nowrap">${icon('search', 'sm')}Tra cứu</button></div>
        <div class="xs muted">Chưa biết ID? Người bị cấm nhắn <b>/start</b> cho bot (hoặc @userinfobot) để xem ID của mình.</div>
        <div data-lookup></div>
      </form>` : ''}
      ${d.allow_others && d.groups.length ? `
      <div class="section-title"><h2>Ân xá người khác</h2></div>
      <div class="chips" data-groups>${d.groups.map((g, i) => `<button class="chip ${i === 0 ? 'active' : ''}" data-g="${g.id}">${esc(g.title)}${g.punished ? ` · ${g.punished}` : ''}</button>`).join('')}</div>
      <div class="input-icon" style="margin:10px 0">${icon('search')}<input class="input" data-q placeholder="Tìm tên, @username hoặc ID"></div>
      <div data-others class="stack"></div>` : ''}
      ${d.recent.length ? `<div class="section-title"><h2>Ân xá gần đây</h2></div>
      <div class="list">${d.recent.map((r) => `<div class="list-item"><span class="log-ico amber">${icon('heart', 'sm')}</span><div class="grow" style="min-width:0"><div class="small ellipsis"><b>${esc(r.payer)}</b> · ${esc(r.note)}</div><div class="xs muted">${ago(r.created_at)}</div></div><div class="small nowrap" style="font-weight:600">${money(r.amount)}</div></div>`).join('')}</div>` : ''}`,
      mount: (root) => {
        let gid = d.groups[0]?.id;
        const box = $('[data-others]', root);
        const load = async () => {
          if (!box || !gid) return;
          box.innerHTML = '<div class="skeleton" style="height:110px"></div>';
          try {
            const r = await api('amnesty/search', undefined, { query: { group_id: gid, q: $('[data-q]', root).value } });
            box.innerHTML = r.rows.length ? r.rows.map((m) => punishCard(m, false)).join('') : `<div class="card">${empty('check', 'Không có thành viên nào đang bị xử lý')}</div>`;
          } catch (e) { box.innerHTML = `<div class="alert err">${icon('alert')}<div>${esc(e.message)}</div></div>`; }
        };
        load();
        $('[data-q]', root)?.addEventListener('input', debounce(load, 350));
        $('#flookup', root)?.addEventListener('submit', (e) => {
          e.preventDefault();
          const out = $('[data-lookup]', root);
          withBtn(e.target.querySelector('button'), async () => {
            try {
              const r = await api('amnesty/lookup', formData(e.target));
              out.innerHTML = punishCard(r, true, true);
            } catch (err) { out.innerHTML = `<div class="alert ${err.status === 404 ? 'info' : 'err'}">${icon(err.status === 404 ? 'info' : 'alert')}<div class="small">${esc(err.message)}</div></div>`; }
          });
        });
        root.addEventListener('click', (e) => {
          const g = e.target.closest('[data-g]');
          if (g) { gid = +g.dataset.g; $$('[data-g]', root).forEach((x) => x.classList.toggle('active', x === g)); load(); return; }
          const b = e.target.closest('[data-amnesty]');
          if (!b) return;
          if (!d.enabled) return toast('Tính năng ân xá đang tắt', 'err');
          const a = JSON.parse(b.dataset.amnesty);
          const after = balance - a.price;
          sheet(`<div class="sheet-head"><h2>🕊 Xác nhận ân xá</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
            <div class="card card-pad" style="box-shadow:none;background:var(--surface-2)">
              <div class="row">${avatar(a.name, a.id)}<div class="grow"><b>${esc(a.name)}</b><div class="xs muted">${esc(a.group || '')}</div></div></div>
              <div class="punish-row" style="margin-top:10px"><span class="p-ico">${a.icon}</span><div class="grow"><b class="small">${esc(a.label)}</b><div class="xs muted">${esc(a.desc)}</div></div></div>
            </div>
            <dl class="kv" style="margin:16px 0">
              <dt>Phí ân xá</dt><dd><b>${money(a.price)}</b></dd>
              <dt>Số dư hiện tại</dt><dd>${money(balance)}</dd>
              <dt>Số dư sau</dt><dd style="color:${after < 0 ? 'var(--danger)' : 'var(--success)'}"><b>${after < 0 ? 'Không đủ' : money(after)}</b></dd>
            </dl>
            ${after < 0 ? `<div class="alert err" style="margin-bottom:14px">${icon('alert')}<div class="small">Bạn cần nạp thêm <b>${money(-after)}</b>.</div></div>
              <a class="btn primary block" href="#/wallet">${icon('plus', 'sm')}Nạp tiền</a>`
              : `<div class="xs muted" style="margin-bottom:14px">Bot sẽ thực hiện ngay trên Telegram và thông báo trong nhóm. Nếu Telegram lỗi, tiền được hoàn tự động.</div>
              <div class="grid grid-2"><button class="btn" data-close>Huỷ</button><button class="btn primary" data-go>${icon('heart', 'sm')}Ân xá</button></div>`}`,
          (el, close) => {
            $('[data-go]', el)?.addEventListener('click', (ev) => withBtn(ev.currentTarget, async () => {
              try {
                const r = await api('amnesty/do', { member_id: a.id, kind: a.kind, by_id: !!a.by_id });
                balance = r.balance;
                close();
                sheet(`<div class="center" style="padding:10px 0 4px"><div style="font-size:54px">🕊</div><h2>Ân xá thành công</h2>
                  <p class="text-2 small">${esc(a.name)} đã được <b>${esc(r.label.toLowerCase())}</b>.<br>Đã trừ ${money(r.price)} · Số dư còn <b>${money(r.balance)}</b></p></div>
                  ${r.invite_link ? `<div class="field"><span class="lbl">Link vào lại nhóm (dùng 1 lần, hạn 3 ngày)</span><div class="code-box" style="font-size:13px;letter-spacing:0"><span class="ellipsis">${esc(r.invite_link)}</span><button class="btn icon sm ghost" data-copy="${esc(r.invite_link)}">${icon('copy', 'sm')}</button></div><div class="hint">Bot đã gửi link này cho người được ân xá (nếu họ đã mở bot).</div></div>` : ''}
                  <button class="btn primary block" data-close style="margin-top:14px">Xong</button>`, null, () => render());
              } catch (err) { toast(err.message, 'err'); }
            }));
          });
        });
      },
    };
  }, { title: 'Ân xá' });

  // ================= Admin: nạp tiền & ân xá =================
  const BANK_ST = { credited: ['Đã cộng', 'green'], unmatched: ['Không khớp', 'red'], below_min: ['Dưới mức tối thiểu', 'amber'], ignored: ['Bỏ qua', ''] };
  const AMNESTY_VARS = [['{member}', 'Tag người được ân xá'], ['{member_name}', 'Tên người được ân xá'], ['{member_id}', 'ID Telegram'], ['{kind}', 'Loại ân xá'], ['{icon}', 'Biểu tượng loại'], ['{before}', 'Hình phạt trước đó'], ['{payer}', 'Người trả tiền'], ['{price}', 'Phí'], ['{group}', 'Tên nhóm'], ['{time}', 'Thời gian']];

  route('/payments', async () => {
    const d = await api('payments');
    const w = d.wallet, am = d.amnesty, st = d.stats;
    const tab = sessionStorage.getItem('payTab') || 'deposit';
    const bankItem = (t) => { const [lb, c] = BANK_ST[t.status] || [t.status, '']; return `<div class="list-item" style="align-items:flex-start">
      <span class="log-ico ${c}">${icon('bank', 'sm')}</span><div class="grow" style="min-width:0">
      <div class="row between"><b>+${money(t.amount)}</b><span class="badge ${c}">${lb}</span></div>
      <div class="xs" style="overflow-wrap:anywhere;margin-top:2px">${esc(t.description || '(không có nội dung)')}</div>
      <div class="xs muted">${esc(t.tx_date || fmtDate(t.created_at, true))}${t.user ? ' · ' + esc(t.user.name) : ''}</div>
      ${t.status !== 'credited' && t.status !== 'ignored' ? `<div class="row" style="gap:6px;margin-top:8px"><button class="btn sm soft" data-assign="${t.id}" data-amt="${t.amount}">${icon('user', 'sm')}Cộng cho tài khoản…</button><button class="btn sm ghost" data-ignore="${t.id}">Bỏ qua</button></div>` : ''}</div></div>`; };
    return {
      title: 'Nạp tiền & ân xá',
      html: `
      <div class="grid grid-2" style="margin-bottom:14px">
        <div class="card stat"><div class="label"><span class="ico green">${icon('plus', 'sm')}</span>Tổng nạp</div><div class="value">${money(st.deposit)}</div></div>
        <div class="card stat"><div class="label"><span class="ico amber">${icon('heart', 'sm')}</span>Thu ân xá</div><div class="value">${money(st.amnesty)}</div><div class="sub">${num(st.amnesty_count)} lần</div></div>
        <div class="card stat"><div class="label"><span class="ico">${icon('wallet', 'sm')}</span>Số dư người dùng</div><div class="value">${money(st.balances)}</div></div>
        <div class="card stat"><div class="label"><span class="ico red">${icon('alert', 'sm')}</span>Chưa khớp</div><div class="value">${num(st.unmatched)}</div><div class="sub">giao dịch cần xử lý</div></div>
      </div>
      <div class="tabs" data-tabs><button data-t="deposit">Nạp tiền</button><button data-t="amnesty">Ân xá</button><button data-t="bank">Giao dịch NH${st.unmatched ? ` (${st.unmatched})` : ''}</button></div>

      <form class="stack" data-pane="deposit" id="fw">
        <div class="card">${sw('enabled', w.enabled, 'Bật nạp tiền', 'Hiện trang Ví và thông tin chuyển khoản cho người dùng')}</div>
        <div class="card card-pad stack">
          <h3>Tài khoản nhận tiền</h3>
          <label class="field"><span class="lbl">Ngân hàng</span><select class="input" name="bank">${Object.entries(d.banks).map(([k, v]) => `<option value="${k}" ${w.bank === k ? 'selected' : ''}>${esc(v)}</option>`).join('')}</select></label>
          <label class="field"><span class="lbl">Số tài khoản</span><input class="input" name="account_no" inputmode="numeric" value="${esc(w.account_no)}"></label>
          <label class="field"><span class="lbl">Tên chủ tài khoản</span><input class="input" name="account_name" value="${esc(w.account_name)}" placeholder="NGUYEN VAN A"></label>
          <div class="grid grid-2">
            <label class="field"><span class="lbl">Tiền tố nội dung</span><input class="input" name="prefix" value="${esc(w.prefix)}" maxlength="12"><div class="hint">Nội dung = tiền tố + ID, VD <b>${esc(w.prefix)}12</b></div></label>
            <label class="field"><span class="lbl">Nạp tối thiểu (đ)</span><input class="input" name="min" inputmode="numeric" value="${w.min}"></label>
          </div>
        </div>
        <div class="card card-pad stack">
          <h3>${icon('zap', 'sm')} Nạp tự động (API lịch sử giao dịch)</h3>
          <div class="alert info">${icon('lock')}<div class="xs">Link API chứa thông tin đăng nhập ngân hàng. Link chỉ lưu trong database, <b>không bao giờ hiện lại</b> trên web và không nằm trong mã nguồn. Hỗ trợ API dạng web2m (<span class="mono">api.web2m.com/historyapiacb/…</span>) và các API trả JSON có danh sách giao dịch.</div></div>
          <label class="field"><span class="lbl">Link API ${w.has_api ? '<span class="badge green">Đã lưu</span>' : '<span class="badge">Chưa có</span>'}</span>
            <input class="input" type="password" name="api_url" autocomplete="off" placeholder="${w.has_api ? '•••••• (để trống nếu giữ nguyên)' : 'https://api.web2m.com/historyapiacb/…'}"></label>
          ${w.has_api ? `<label class="row small" style="gap:8px"><input type="checkbox" name="clear_api"> Xoá link API đã lưu</label>` : ''}
          <div class="xs muted">${w.last_sync ? 'Đồng bộ gần nhất: ' + ago(w.last_sync) : 'Chưa đồng bộ lần nào'} · Cron chạy mỗi phút sẽ tự đồng bộ.</div>
          ${w.last_error ? `<div class="alert err">${icon('alert')}<div class="xs">Lỗi gần nhất: ${esc(w.last_error)}</div></div>` : ''}
          <div class="grid grid-2"><button type="button" class="btn" data-test>${icon('search', 'sm')}Kiểm tra API</button><button type="button" class="btn" data-sync>${icon('refresh', 'sm')}Đồng bộ ngay</button></div>
          <div data-testout></div>
        </div>
        <button class="btn primary block">Lưu cài đặt nạp tiền</button>
      </form>

      <form class="stack hidden" data-pane="amnesty" id="fa">
        <div class="card">${sw('enabled', am.enabled, 'Bật ân xá thành viên', 'Thành viên dùng số dư ví để gỡ hình phạt')}
          ${sw('allow_others', am.allow_others, 'Cho ân xá người khác', 'Thành viên trong nhóm trả tiền ân xá giúp bạn bè')}
          ${sw('notify', am.notify, 'Thông báo trong nhóm', 'Bot gửi thông báo ân xá vào nhóm')}
          ${sw('invite', am.invite, 'Gửi link mời khi gỡ cấm', 'Tạo link vào lại nhóm dùng 1 lần (hạn 3 ngày) cho người được gỡ cấm')}</div>
        <div class="card card-pad stack">
          <h3>Bảng giá (đ) <span class="xs muted" style="font-weight:400">— đặt 0 để tắt loại đó</span></h3>
          ${Object.entries({ unmute: '🔊 Gỡ cấm chat', unban: '✅ Gỡ cấm khỏi nhóm', unwarn: '🧹 Xoá toàn bộ cảnh cáo' }).map(([k, l]) => `<label class="field"><span class="lbl">${l}</span><input class="input" name="price_${k}" inputmode="numeric" value="${am.prices[k]}"></label>`).join('')}
        </div>
        <div class="card card-pad stack">
          <h3>Nội dung thông báo trong nhóm</h3>
          <textarea class="input" name="message" rows="7" data-rich>${esc(am.message)}</textarea>
          <div class="chips" style="flex-wrap:wrap">${AMNESTY_VARS.map(([v, t]) => `<button type="button" class="chip var-chip" data-avar="${v}" title="${esc(t)}"><code>${v}</code><small>${esc(t)}</small></button>`).join('')}</div>
          <button type="button" class="btn sm ghost" data-resetmsg>${icon('refresh', 'sm')}Dùng nội dung mặc định</button>
          <label class="field"><span class="lbl">Ghi chú hiện ở trang Ân xá & lệnh /anxa</span><input class="input" name="note" value="${esc(am.note)}" placeholder="VD: Ân xá không áp dụng cho lỗi lừa đảo"></label>
        </div>
        <button class="btn primary block">Lưu cài đặt ân xá</button>
      </form>

      <div class="hidden" data-pane="bank">
        <div class="chips" style="margin-bottom:10px">${[['', 'Tất cả'], ['unmatched', 'Không khớp'], ['below_min', 'Dưới tối thiểu'], ['credited', 'Đã cộng'], ['ignored', 'Bỏ qua']].map(([k, l]) => `<button class="chip ${k === '' ? 'active' : ''}" data-bst="${k}">${l}</button>`).join('')}</div>
        <div class="list" data-banklist>${d.bank_tx.length ? d.bank_tx.map(bankItem).join('') : empty('bank', 'Chưa có giao dịch nào')}</div>
        <div class="xs muted" style="margin-top:10px">Chỉ hiện giao dịch tiền vào. Giao dịch không khớp nội dung có thể cộng tay cho đúng tài khoản. Cộng / trừ số dư thủ công ở trang <a href="#/users">Tài khoản web</a>.</div>
      </div>`,
      mount: (root) => {
        const setTab = (t) => { sessionStorage.setItem('payTab', t); $$('[data-t]', root).forEach((b) => b.classList.toggle('active', b.dataset.t === t)); $$('[data-pane]', root).forEach((x) => x.classList.toggle('hidden', x.dataset.pane !== t)); };
        setTab(tab);
        $('[data-tabs]', root).onclick = (e) => { const b = e.target.closest('[data-t]'); if (b) setTab(b.dataset.t); };
        $('#fw', root).onsubmit = async (e) => {
          e.preventDefault();
          const f = formData(e.target);
          try { await api('payments/save', { wallet: f }); toast('Đã lưu'); await boot(); render(); } catch (err) { toast(err.message, 'err'); }
        };
        $('#fa', root).onsubmit = async (e) => {
          e.preventDefault();
          const f = formData(e.target);
          try {
            await api('payments/save', { amnesty: { enabled: f.enabled, allow_others: f.allow_others, notify: f.notify, invite: f.invite, message: f.message, note: f.note, prices: { unmute: +f.price_unmute || 0, unban: +f.price_unban || 0, unwarn: +f.price_unwarn || 0 } } });
            toast('Đã lưu'); await boot(); render();
          } catch (err) { toast(err.message, 'err'); }
        };
        const ta = $('#fa textarea[name=message]', root);
        $('#fa', root).addEventListener('click', (e) => {
          const v = e.target.closest('[data-avar]');
          if (v) { if (ta._rich) ta._rich.insert(v.dataset.avar); else ta.value += v.dataset.avar; }
          if (e.target.closest('[data-resetmsg]')) { if (ta._rich) ta._rich.setValue(am.default_message); else ta.value = am.default_message; toast('Đã đặt lại, bấm Lưu để áp dụng'); }
        });
        $('[data-test]', root).onclick = (e) => withBtn(e.currentTarget, async () => {
          const out = $('[data-testout]', root);
          try {
            const r = await api('payments/test', {});
            out.innerHTML = !r.ok ? `<div class="alert err">${icon('alert')}<div class="xs">${esc(r.error)}</div></div>`
              : `<div class="alert ok">${icon('check')}<div class="xs">Kết nối được. Đọc được <b>${r.total}</b> giao dịch (<b>${r.incoming}</b> tiền vào). Kiểm tra không cộng tiền.</div></div>
                ${r.sample.length ? `<div class="list" style="margin-top:8px">${r.sample.map((t) => `<div class="list-item"><div class="grow" style="min-width:0"><b>+${money(t.amount)}</b> <span class="xs muted">${esc(t.date)}</span><div class="xs" style="overflow-wrap:anywhere">${esc(t.description)}</div></div>${t.match ? `<span class="badge green">${esc(t.match)}</span>` : '<span class="badge">Không khớp</span>'}</div>`).join('')}</div>` : ''}`;
          } catch (err) { out.innerHTML = `<div class="alert err">${icon('alert')}<div class="xs">${esc(err.message)}</div></div>`; }
        });
        $('[data-sync]', root).onclick = (e) => withBtn(e.currentTarget, async () => {
          try {
            const r = await api('payments/sync', {});
            if (r.error) toast(r.error, 'err'); else toast(`Đồng bộ xong: ${r.new} giao dịch mới, cộng ${r.credited}, không khớp ${r.unmatched}`);
            if (r.new) render();
          } catch (err) { toast(err.message, 'err'); }
        });
        $('[data-pane=bank]', root).addEventListener('click', async (e) => {
          const f = e.target.closest('[data-bst]');
          if (f) {
            $$('[data-bst]', root).forEach((x) => x.classList.toggle('active', x === f));
            const r = await api('payments', undefined, { query: { status: f.dataset.bst } });
            $('[data-banklist]', root).innerHTML = r.bank_tx.length ? r.bank_tx.map(bankItem).join('') : empty('bank', 'Không có giao dịch');
            return;
          }
          const ig = e.target.closest('[data-ignore]');
          if (ig) { await api('payments/ignore', { id: +ig.dataset.ignore }); toast('Đã bỏ qua'); render(); return; }
          const as = e.target.closest('[data-assign]');
          if (as) sheet(`<div class="sheet-head"><h2>Cộng ${money(+as.dataset.amt)} cho…</h2><button class="btn icon ghost" data-close>${icon('x')}</button></div>
            <form class="stack" id="fas"><label class="field"><span class="lbl">Tên đăng nhập hoặc ID tài khoản web</span><input class="input" name="who" required placeholder="VD: lan99 hoặc 12"></label>
            <button class="btn primary block">Cộng tiền</button></form>`, (el, close) => {
            $('#fas', el).onsubmit = async (ev) => {
              ev.preventDefault();
              const who = ev.target.who.value.trim();
              try { await api('payments/assign', { id: +as.dataset.assign, ...(/^\d+$/.test(who) ? { user_id: +who } : { username: who }) }); close(); toast('Đã cộng tiền'); render(); } catch (err) { toast(err.message, 'err'); }
            };
          });
        });
      },
    };
  }, { webAdmin: true, title: 'Nạp tiền & ân xá', back: '/more' });

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
      <div class="card card-pad row" style="margin-bottom:14px">${avatar(u.display_name, u.id, 'lg')}<div class="grow"><h2>${esc(u.display_name)}</h2><div class="small muted">@${esc(u.username)} · ${roleLabel()}</div>
        <div class="xs muted">Tham gia ${fmtDate(u.created_at)}</div></div></div>
      ${u.tg_user_id ? `<div class="card card-pad row" style="margin-bottom:14px"><span style="color:var(--success)">${icon('check')}</span><div class="grow"><b>Đã liên kết Telegram</b><div class="xs muted mono">${esc(u.tg_user_id)}</div></div><button class="btn sm soft-danger" data-unlink>Huỷ</button></div>` : `<div style="margin-bottom:14px">${linkCard(d)}</div>`}
      <form class="card card-pad stack" id="fp">
        <h3>Thông tin</h3>
        <label class="field"><span class="lbl">Tên hiển thị</span><input class="input" name="display_name" value="${esc(u.display_name)}"></label>
        <h3 style="margin-top:10px">${u.has_password ? 'Đổi mật khẩu' : 'Đặt mật khẩu'}</h3>
        ${u.has_password ? `<label class="field"><span class="lbl">Mật khẩu hiện tại</span><input class="input" type="password" name="current_password" autocomplete="current-password"></label>`
          : `<div class="alert info">${icon('info')}<div class="xs">Tài khoản tạo từ Telegram, chưa có mật khẩu. Bạn vẫn đăng nhập được bằng Telegram; đặt mật khẩu nếu muốn đăng nhập bằng tên <b>${esc(u.username)}</b>.</div></div>`}
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
          try { const r = await api('me/update', formData(e.target)); const hadPw = S.user.has_password; S.user = r.user; toast('Đã lưu'); if (!hadPw && r.user.has_password) render(); if (e.target.current_password) e.target.current_password.value = ''; e.target.new_password.value = ''; shell('', 'Tài khoản', isManager() ? '/more' : null); $('#page-title').textContent = 'Tài khoản'; }
          catch (err) { toast(err.message, 'err'); }
        };
      },
    };
  }, { title: 'Tài khoản' });

  route('/member/:id', async (p) => { setTimeout(() => openMember(p.id), 50); return viewDashboard(); }, { manage: true });

  // ================= Boot =================
  async function boot() {
    try {
      const st = await api('status', undefined, { noRedirect: true });
      S.status = st; S.csrf = st.csrf; S.user = st.user; S.site = st.site_name; S.allowRegister = st.allow_register; S.access = st.access; S.tgLogin = st.tg_login; S.pay = st.pay;
    } catch (e) {
      $('#app').innerHTML = `<div class="auth"><div class="alert err">${icon('alert')}<div>${esc(e.message)}</div></div></div>`;
      throw e;
    }
  }
  window.addEventListener('hashchange', render);
  boot().then(render).catch(() => {});
})();
