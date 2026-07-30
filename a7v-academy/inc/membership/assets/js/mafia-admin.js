/* Mafia Academy — admin scripts (charts, tabs, dynamic rows, approvals) */
(function () {
	'use strict';

	/* ------------------------------ Charts ------------------------------ */
	function initCharts() {
		var el = document.getElementById('mam-chart-data');
		if (!el || typeof Chart === 'undefined') { return; }
		var data;
		try { data = JSON.parse(el.textContent); } catch (e) { return; }

		var red = '#FF0000', gold = '#c9a24b', grid = 'rgba(255,255,255,.06)', tick = '#9aa0ad';
		Chart.defaults.color = tick;
		Chart.defaults.font.family = 'Tahoma, sans-serif';

		var sales = document.getElementById('mamSalesChart');
		if (sales) {
			new Chart(sales, {
				type: 'line',
				data: { labels: data.labels, datasets: [{
					label: 'فروش (تومان)', data: data.revenue, borderColor: red,
					backgroundColor: 'rgba(255,0,0,.15)', fill: true, tension: .38,
					pointBackgroundColor: red, pointRadius: 3, borderWidth: 2
				}] },
				options: { plugins: { legend: { display: false } }, scales: {
					x: { grid: { color: grid } }, y: { grid: { color: grid }, beginAtZero: true } } }
			});
		}

		var subs = document.getElementById('mamSubsChart');
		if (subs) {
			new Chart(subs, {
				type: 'bar',
				data: { labels: data.labels, datasets: [{
					label: 'اعضای جدید', data: data.subs, backgroundColor: gold, borderRadius: 5
				}] },
				options: { plugins: { legend: { display: false } }, scales: {
					x: { grid: { color: grid } }, y: { grid: { color: grid }, beginAtZero: true, ticks: { precision: 0 } } } }
			});
		}

		var donut = document.getElementById('mamDonut');
		if (donut) {
			new Chart(donut, {
				type: 'doughnut',
				data: { labels: ['فعال', 'منقضی', 'در انتظار'], datasets: [{
					data: data.breakdown, backgroundColor: ['#25a05a', red, gold], borderColor: '#0d0d0f', borderWidth: 2
				}] },
				options: { cutout: '62%', plugins: { legend: { display: false } } }
			});
		}
	}

	/* ------------------------------- Tabs ------------------------------- */
	function initTabs() {
		var tabs = document.querySelectorAll('.mam-tab');
		if (!tabs.length) { return; }
		tabs.forEach(function (t) {
			t.addEventListener('click', function () {
				var name = t.getAttribute('data-tab');
				document.querySelectorAll('.mam-tab').forEach(function (x) { x.classList.toggle('on', x === t); });
				document.querySelectorAll('.mam-panel').forEach(function (p) {
					p.classList.toggle('on', p.getAttribute('data-panel') === name);
				});
			});
		});
	}

	/* --------------------------- Dynamic rows --------------------------- */
	function cloneTemplate(tplId, listId) {
		var tpl = document.getElementById(tplId);
		var list = document.getElementById(listId);
		if (!tpl || !list) { return; }
		list.appendChild(tpl.content.cloneNode(true));
	}

	function initRepeaters() {
		var addPlan = document.getElementById('mam-add-plan');
		if (addPlan) { addPlan.addEventListener('click', function () { cloneTemplate('mam-plan-template', 'mam-plans-list'); }); }

		var addField = document.getElementById('mam-add-field');
		if (addField) { addField.addEventListener('click', function () { cloneTemplate('mam-field-template', 'mam-fields-list'); }); }

		var addDisc = document.getElementById('mam-add-disc');
		if (addDisc) { addDisc.addEventListener('click', function () { cloneTemplate('mam-disc-template', 'mam-disc-list'); }); }

		document.addEventListener('click', function (e) {
			if (e.target.classList.contains('mam-remove-plan')) { e.target.closest('.mam-plan-row').remove(); }
			if (e.target.classList.contains('mam-remove-field')) { e.target.closest('.mam-field-row').remove(); }
			if (e.target.classList.contains('mam-remove-disc')) { e.target.closest('.mam-disc-row').remove(); }
		});
	}

	/* ------------------------ Copy plan buy-link ------------------------ */
	function initCopy() {
		document.addEventListener('click', function (e) {
			var btn = e.target.closest ? e.target.closest('.mam-copy-link') : null;
			if (!btn) { return; }
			e.preventDefault();
			var val = btn.getAttribute('data-link') || '';
			var done = function () {
				var old = btn.textContent;
				btn.textContent = 'کپی شد ✅';
				setTimeout(function () { btn.textContent = old; }, 1500);
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(val).then(done).catch(function () {
					window.prompt('کپی کنید:', val);
				});
			} else {
				window.prompt('کپی کنید:', val);
			}
		});
	}

	/* --------------------------- Drag reorder --------------------------- */
	function initDrag() {
		var list = document.getElementById('mam-fields-list');
		if (!list) { return; }
		var dragging = null;
		list.addEventListener('dragstart', function (e) {
			var row = e.target.closest('.mam-field-row');
			if (row) { dragging = row; row.classList.add('dragging'); }
		});
		list.addEventListener('dragend', function () {
			if (dragging) { dragging.classList.remove('dragging'); dragging = null; }
		});
		list.addEventListener('dragover', function (e) {
			e.preventDefault();
			if (!dragging) { return; }
			var after = getAfter(list, e.clientY);
			if (after == null) { list.appendChild(dragging); }
			else { list.insertBefore(dragging, after); }
		});
		function getAfter(container, y) {
			var els = Array.prototype.slice.call(container.querySelectorAll('.mam-field-row:not(.dragging)'));
			return els.reduce(function (closest, child) {
				var box = child.getBoundingClientRect();
				var offset = y - box.top - box.height / 2;
				if (offset < 0 && offset > closest.offset) { return { offset: offset, element: child }; }
				return closest;
			}, { offset: -Infinity }).element || null;
		}
	}

	/* ------------------------- Payment approvals ------------------------ */
	function initApprovals() {
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('.mam-act');
			if (!btn) { return; }
			var id = btn.getAttribute('data-id');
			var doAction = btn.getAttribute('data-do');
			if (doAction === 'reject' && !confirm('این پرداخت رد شود؟')) { return; }
			btn.disabled = true;

			var body = new URLSearchParams();
			body.append('action', 'mam_admin_action');
			body.append('nonce', (window.MAMAdmin || {}).nonce || '');
			body.append('do', doAction);
			body.append('id', id);

			fetch((window.MAMAdmin || {}).ajax, { method: 'POST', credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					btn.disabled = false;
					if (!res || !res.success) { alert(res && res.data ? res.data.msg : 'خطا'); return; }
					var row = document.getElementById('mam-row-' + id);
					if (row) {
						var cell = row.querySelector('.mam-status-cell');
						var cls = res.data.status === 'completed' ? 'ok' : 'bad';
						var lbl = res.data.status === 'completed' ? 'تکمیل شده' : 'ناموفق';
						if (cell) { cell.innerHTML = '<span class="mam-badge mam-badge-' + cls + '">' + lbl + '</span>'; }
						row.querySelectorAll('.mam-act').forEach(function (b) { b.remove(); });
					}
				})
				.catch(function () { btn.disabled = false; alert('خطا در ارتباط.'); });
		});
	}

	/* ------------------------ Media (logo) picker ---------------------- */
	function initMedia() {
		if (!window.wp || !wp.media) { return; }
		var frame = null;
		document.addEventListener('click', function (e) {
			var btn = e.target.closest ? e.target.closest('.mam-media-pick') : null;
			if (!btn) { return; }
			e.preventDefault();
			var targetName = btn.getAttribute('data-target');
			var input = document.querySelector('input[name="' + targetName + '"]');
			if (!input) { return; }

			frame = wp.media({ title: 'انتخاب تصویر لوگو', button: { text: 'انتخاب' }, multiple: false });
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				input.value = att.url;
				var fld = btn.closest('.mam-logo-fld');
				var prev = fld ? fld.querySelector('.mam-logo-preview') : null;
				if (prev) { prev.innerHTML = '<img src="' + att.url + '" alt="">'; }
			});
			frame.open();
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initCharts(); initTabs(); initRepeaters(); initDrag(); initApprovals(); initCopy(); initMedia();
	});
})();
