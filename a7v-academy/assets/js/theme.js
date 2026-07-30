/* A7V Academy — interactions only (content rendered server-side by PHP) */
document.addEventListener('DOMContentLoaded', function () {
  // Sticky header
  var header = document.getElementById('siteHeader');
  if (header) {
    window.addEventListener('scroll', function () {
      header.classList.toggle('scrolled', window.scrollY > 20);
    });
  }
  // Mobile drawer
  var ham = document.getElementById('hamburger'),
      drawer = document.getElementById('mobileDrawer'),
      ov = document.getElementById('overlay');
  function closeDrawer() { if (drawer) drawer.classList.remove('open'); if (ov) ov.classList.remove('show'); }
  if (ham) ham.addEventListener('click', function () { drawer.classList.add('open'); ov.classList.add('show'); });
  if (ov) ov.addEventListener('click', closeDrawer);
  if (drawer) drawer.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', closeDrawer); });
  // Notice bar
  var nc = document.getElementById('noticeClose');
  if (nc) nc.addEventListener('click', function () {
    var bar = document.getElementById('noticeBar'); if (bar) bar.classList.add('hide');
  });
  // Countdown
  var cd = document.getElementById('countdown');
  if (cd) {
    var total = 48 * 3600;
    setInterval(function () {
      total = total > 0 ? total - 1 : 48 * 3600;
      var h = String(Math.floor(total / 3600)).padStart(2, '0');
      var m = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
      var s = String(total % 60).padStart(2, '0');
      cd.textContent = h + ':' + m + ':' + s;
    }, 1000);
  }
  // FAQ accordion
  document.querySelectorAll('.faq-q').forEach(function (q) {
    q.addEventListener('click', function () { q.parentElement.classList.toggle('open'); });
  });
  // Row sliders (RTL aware)
  document.querySelectorAll('.row-nav').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var track = document.getElementById('track-' + btn.dataset.row);
      if (!track) return;
      var dir = btn.classList.contains('next') ? 1 : -1;
      track.scrollBy({ left: dir * -300, behavior: 'smooth' });
    });
  });

  // Auth tabs (register / login)
  document.querySelectorAll('[data-auth-tab]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.dataset.authTab;
      document.querySelectorAll('[data-auth-tab]').forEach(function (x) { x.classList.toggle('active', x === b); });
      document.querySelectorAll('.auth-form').forEach(function (f) { f.classList.toggle('active', f.dataset.form === t); });
    });
  });

  // Dashboard tabs
  document.querySelectorAll('.dash-menu [data-tab]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.dataset.tab;
      document.querySelectorAll('.dash-menu [data-tab]').forEach(function (x) { x.classList.toggle('active', x === b); });
      document.querySelectorAll('.dash-panel').forEach(function (p) { p.classList.toggle('active', p.dataset.panel === t); });
      var main = document.querySelector('.dash-main');
      if (main) main.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
});


/* Paywall popup (download / locked content) */
document.addEventListener('DOMContentLoaded', function () {
  var modal = document.getElementById('a7vPaywall');
  function openModal() { if (modal) { modal.classList.add('open'); document.body.style.overflow = 'hidden'; } }
  function closeModal() { if (modal) { modal.classList.remove('open'); document.body.style.overflow = ''; } }
  document.querySelectorAll('.js-locked').forEach(function (el) {
    el.addEventListener('click', function (e) { e.preventDefault(); openModal(); });
  });
  if (modal) {
    modal.querySelectorAll('[data-close]').forEach(function (c) { c.addEventListener('click', closeModal); });
  }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
});
