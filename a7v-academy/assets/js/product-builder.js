/* =========================================================
 * A7V Dossier — settings page app.
 * Define fields (icon + label) per content type, reorder,
 * add/remove, toggle enable, edit title, save via AJAX.
 * ========================================================= */
(function () {
	'use strict';
	if (typeof A7VDossier === 'undefined' || A7VDossier.mode !== 'settings') { return; }

	var CFG = A7VDossier;
	var settings = clone(CFG.settings);
	var icons = CFG.icons;               // {key:{label,svg}}
	var iconKeys = Object.keys(icons);
	var types = Object.keys(CFG.postTypes);
	var current = types[0];

	var $tabs = document.getElementById('a7vDsTabs');
	var $editor = document.getElementById('a7vDsEditor');
	var $status = document.getElementById('a7vDsStatus');

	function clone(o) { return JSON.parse(JSON.stringify(o || {})); }
	function el(t, c, h) { var e = document.createElement(t); if (c) e.className = c; if (h !== undefined) e.innerHTML = h; return e; }
	function uid() { return 'f' + Math.random().toString(36).slice(2, 8); }
	function conf(t) {
		if (!settings[t]) settings[t] = { enabled: true, title: 'اطلاعات پرونده', fields: [] };
		if (!Array.isArray(settings[t].fields)) settings[t].fields = [];
		return settings[t];
	}

	/* ---- tabs ---- */
	function renderTabs() {
		$tabs.innerHTML = '';
		types.forEach(function (t) {
			var b = el('button', 'a7v-ds-tab' + (t === current ? ' active' : ''), CFG.postTypes[t]);
			b.type = 'button';
			b.addEventListener('click', function () { current = t; renderTabs(); renderEditor(); });
			$tabs.appendChild(b);
		});
	}

	/* ---- editor ---- */
	function renderEditor() {
		var c = conf(current);
		$editor.innerHTML = '';

		var row = el('div', 'a7v-ds-row');
		// enable toggle
		var tog = el('label', 'a7v-ds-toggle');
		var cb = el('input'); cb.type = 'checkbox'; cb.checked = !!c.enabled;
		cb.addEventListener('change', function () { c.enabled = cb.checked; });
		tog.appendChild(cb); tog.appendChild(el('span', null, 'نمایش پرونده برای این نوع محتوا'));
		row.appendChild(tog);
		// title
		var title = el('input', 'a7v-ds-title-inp'); title.type = 'text'; title.value = c.title || ''; title.placeholder = 'عنوان کارت (مثلاً: اطلاعات پرونده آموزش)';
		title.addEventListener('input', function () { c.title = title.value; });
		row.appendChild(title);
		$editor.appendChild(row);

		$editor.appendChild(el('div', 'a7v-ds-fields-head', 'فیلدهای این پرونده (آیکون + عنوان). با کشیدن جابجا کن:'));

		var list = el('div', 'a7v-ds-fields'); list.id = 'a7vDsFieldList';
		c.fields.forEach(function (f, idx) { list.appendChild(fieldRow(c, f, idx)); });
		$editor.appendChild(list);

		var add = el('button', 'button a7v-ds-add', '+ افزودن فیلد'); add.type = 'button';
		add.addEventListener('click', function () { c.fields.push({ key: uid(), icon: 'star', label: '' }); renderEditor(); });
		$editor.appendChild(add);
	}

	function fieldRow(c, f, idx) {
		var row = el('div', 'a7v-ds-field');
		row.setAttribute('draggable', 'true');
		row.dataset.idx = idx;

		row.appendChild(el('span', 'drag', '⠿'));

		// icon preview
		var prev = el('span', 'a7v-ds-ic-preview', icons[f.icon] ? icons[f.icon].svg : '');
		row.appendChild(prev);

		// icon select
		var sel = el('select');
		iconKeys.forEach(function (k) {
			var o = el('option'); o.value = k; o.textContent = icons[k].label; if (k === f.icon) o.selected = true; sel.appendChild(o);
		});
		sel.addEventListener('change', function () { f.icon = sel.value; prev.innerHTML = icons[f.icon] ? icons[f.icon].svg : ''; });
		row.appendChild(sel);

		// label
		var lbl = el('input', 'a7v-ds-label-inp'); lbl.type = 'text'; lbl.value = f.label || ''; lbl.placeholder = 'عنوان فیلد (مثلاً: مدرس)';
		lbl.addEventListener('input', function () { f.label = lbl.value; });
		row.appendChild(lbl);

		// tools
		var up = el('button', 'tool', '▲'); up.type = 'button';
		up.addEventListener('click', function () { if (idx > 0) { swap(c.fields, idx, idx - 1); renderEditor(); } });
		var dn = el('button', 'tool', '▼'); dn.type = 'button';
		dn.addEventListener('click', function () { if (idx < c.fields.length - 1) { swap(c.fields, idx, idx + 1); renderEditor(); } });
		var del = el('button', 'tool del', '🗑'); del.type = 'button';
		del.addEventListener('click', function () { c.fields.splice(idx, 1); renderEditor(); });
		row.appendChild(up); row.appendChild(dn); row.appendChild(del);

		attachDrag(row, c);
		return row;
	}

	function swap(arr, i, j) { var t = arr[i]; arr[i] = arr[j]; arr[j] = t; }

	/* ---- drag reorder ---- */
	var dragSrc = null;
	function attachDrag(row, c) {
		row.addEventListener('dragstart', function () { dragSrc = row; row.classList.add('dragging'); });
		row.addEventListener('dragend', function () { row.classList.remove('dragging'); dragSrc = null; });
		row.addEventListener('dragover', function (e) {
			e.preventDefault();
			var list = document.getElementById('a7vDsFieldList');
			if (!dragSrc || dragSrc === row) return;
			var r = row.getBoundingClientRect();
			list.insertBefore(dragSrc, (e.clientY - r.top) > r.height / 2 ? row.nextSibling : row);
		});
		row.addEventListener('drop', function (e) {
			e.preventDefault();
			var list = document.getElementById('a7vDsFieldList');
			var order = Array.prototype.map.call(list.children, function (x) { return parseInt(x.dataset.idx, 10); });
			c.fields = order.map(function (i) { return c.fields[i]; });
			renderEditor();
		});
	}

	/* ---- save ---- */
	function save() {
		$status.textContent = CFG.i18n.saving;
		var body = new URLSearchParams();
		body.set('action', 'a7v_save_dossier');
		body.set('nonce', CFG.nonce);
		body.set('settings', JSON.stringify(settings));
		fetch(CFG.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				$status.textContent = (j && j.success) ? CFG.i18n.saved : 'خطا در ذخیره';
				setTimeout(function () { $status.textContent = ''; }, 2600);
			})
			.catch(function () { $status.textContent = 'خطا در ذخیره'; });
	}

	document.getElementById('a7vDsSave').addEventListener('click', save);
	renderTabs();
	renderEditor();
})();
