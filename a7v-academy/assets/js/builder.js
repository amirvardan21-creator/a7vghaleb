/* =========================================================
 * A7V Home Builder — front-end app (vanilla JS)
 * Schema-driven inspector, drag-reorder, live preview, AJAX save.
 * ========================================================= */
(function () {
        'use strict';

        if (typeof A7VBuilder === 'undefined') { return; }

        var CFG = A7VBuilder;
        var registry = CFG.registry;
        var layout = clone(CFG.layout);        // working copy
        var selectedId = null;
        var previewTimer = null;

        // DOM refs
        var $list = document.getElementById('a7vBlockList');
        var $insp = document.getElementById('a7vInspector');
        var $frame = document.getElementById('a7vPreview');
        var $status = document.getElementById('a7vStatus');
        var $addModal = document.getElementById('a7vAddModal');
        var $addGrid = document.getElementById('a7vAddGrid');

        /* ---------- helpers ---------- */
        function clone(o) { return JSON.parse(JSON.stringify(o)); }
        function uid(type) { return type + '-' + Math.random().toString(36).slice(2, 7); }
        function el(tag, cls, html) {
                var e = document.createElement(tag);
                if (cls) e.className = cls;
                if (html !== undefined) e.innerHTML = html;
                return e;
        }
        function findBlock(id) { return layout.find(function (b) { return b.id === id; }); }
        function label(type) { return (registry[type] && registry[type].label) || type; }
        function dashicon(type) { return (registry[type] && registry[type].dashicon) || 'dashicons-block-default'; }

        /* ---------- sidebar list ---------- */
        function renderList() {
                $list.innerHTML = '';
                layout.forEach(function (block) {
                        var li = el('li', 'a7v-b-item' + (block.id === selectedId ? ' active' : '') + (block.visible ? '' : ' hidden-block'));
                        li.setAttribute('draggable', 'true');
                        li.dataset.id = block.id;

                        li.appendChild(el('span', 'drag dashicons dashicons-menu'));
                        li.appendChild(el('span', 'b-ic', '<span class="dashicons ' + dashicon(block.type) + '"></span>'));
                        li.appendChild(el('span', 'b-name', label(block.type)));

                        var eye = el('button', 'b-tool eye', '<span class="dashicons dashicons-' + (block.visible ? 'visibility' : 'hidden') + '"></span>');
                        eye.title = block.visible ? 'مخفی کردن' : 'نمایش';
                        eye.addEventListener('click', function (ev) { ev.stopPropagation(); block.visible = !block.visible; renderList(); schedulePreview(); });
                        li.appendChild(eye);

                        var del = el('button', 'b-tool del', '<span class="dashicons dashicons-trash"></span>');
                        del.title = 'حذف';
                        del.addEventListener('click', function (ev) {
                                ev.stopPropagation();
                                if (!confirm(CFG.i18n.confirmDelete)) return;
                                layout = layout.filter(function (b) { return b.id !== block.id; });
                                if (selectedId === block.id) { selectedId = null; renderInspector(); }
                                renderList(); schedulePreview();
                        });
                        li.appendChild(del);

                        li.addEventListener('click', function () { selectBlock(block.id); });
                        attachDrag(li);
                        $list.appendChild(li);
                });
        }

        function selectBlock(id) {
                selectedId = id;
                renderList();
                renderInspector();
        }

        /* ---------- drag reorder ---------- */
        var dragSrc = null;
        function attachDrag(li) {
                li.addEventListener('dragstart', function () { dragSrc = li; li.classList.add('dragging'); });
                li.addEventListener('dragend', function () { li.classList.remove('dragging'); dragSrc = null; });
                li.addEventListener('dragover', function (e) {
                        e.preventDefault();
                        if (!dragSrc || dragSrc === li) return;
                        var rect = li.getBoundingClientRect();
                        var after = (e.clientY - rect.top) > rect.height / 2;
                        $list.insertBefore(dragSrc, after ? li.nextSibling : li);
                });
                li.addEventListener('drop', function (e) {
                        e.preventDefault();
                        // Rebuild layout order from DOM
                        var ids = Array.prototype.map.call($list.children, function (c) { return c.dataset.id; });
                        layout.sort(function (a, b) { return ids.indexOf(a.id) - ids.indexOf(b.id); });
                        renderList(); schedulePreview();
                });
        }

        /* ---------- inspector (schema-driven form) ---------- */
        function renderInspector() {
                $insp.innerHTML = '';
                var block = selectedId ? findBlock(selectedId) : null;
                if (!block) {
                        $insp.appendChild(el('div', 'a7v-b-insp-empty', 'یک بخش را از لیست انتخاب کن تا تنظیماتش اینجا نمایش داده شود.'));
                        return;
                }
                var head = el('div', 'a7v-b-insp-head', '<span class="dashicons ' + dashicon(block.type) + '"></span> ' + label(block.type));
                $insp.appendChild(head);

                var fields = (registry[block.type] && registry[block.type].fields) || [];
                if (!fields.length) {
                        $insp.appendChild(el('div', 'a7v-b-insp-empty', 'این بخش محتوای پویا دارد و از نوشته‌های سایت پر می‌شود؛ تنظیم متنی ندارد. می‌توانی آن را جابجا، مخفی یا حذف کنی.'));
                        return;
                }
                fields.forEach(function (f) {
                        $insp.appendChild(buildField(f, block.settings, function (val) {
                                block.settings[f.key] = val;
                                schedulePreview();
                        }));
                });
        }

        function buildField(f, store, onChange) {
                var wrap = el('div', 'a7v-field');
                if (f.type !== 'repeater') {
                        wrap.appendChild(el('label', null, f.label));
                }
                var val = store[f.key];

                switch (f.type) {
                        case 'textarea': {
                                var ta = el('textarea');
                                ta.value = val || '';
                                ta.addEventListener('input', function () { onChange(ta.value); });
                                wrap.appendChild(ta);
                                break;
                        }
                        case 'select': {
                                var sel = el('select');
                                Object.keys(f.options || {}).forEach(function (k) {
                                        var o = el('option'); o.value = k; o.textContent = f.options[k];
                                        if (String(val) === String(k)) o.selected = true;
                                        sel.appendChild(o);
                                });
                                sel.addEventListener('change', function () { onChange(sel.value); });
                                wrap.appendChild(sel);
                                break;
                        }
                        case 'image': {
                                wrap.appendChild(buildImageField(val, onChange));
                                break;
                        }
                        case 'repeater': {
                                wrap.appendChild(el('label', null, f.label));
                                wrap.appendChild(buildRepeater(f, Array.isArray(val) ? val : [], onChange));
                                break;
                        }
                        case 'color': {
                                var inp = el('input');
                                inp.type = 'color';
                                if (val) inp.value = val;
                                inp.addEventListener('input', function () { onChange(inp.value); });
                                wrap.appendChild(inp);
                                break;
                        }
                        case 'toggle': {
                                var inp = el('input');
                                inp.type = 'checkbox';
                                if (val) inp.checked = true;
                                inp.addEventListener('change', function () { onChange(inp.checked); });
                                wrap.appendChild(inp);
                                break;
                        }
                        default: { // text, url
                                var inp = el('input');
                                inp.type = (f.type === 'url') ? 'url' : 'text';
                                inp.value = val || '';
                                inp.addEventListener('input', function () { onChange(inp.value); });
                                wrap.appendChild(inp);
                        }
                }
                return wrap;
        }

        function buildImageField(val, onChange) {
                var box = el('div', 'a7v-img-field');
                var prev = el('div', 'a7v-img-prev');
                if (val) prev.style.backgroundImage = "url('" + val + "')";
                var btns = el('div', 'a7v-img-btns');
                var pick = el('button', 'button', 'انتخاب تصویر'); pick.type = 'button';
                var rem = el('button', 'button', 'حذف'); rem.type = 'button';
                pick.addEventListener('click', function () {
                        var frame = wp.media({ title: 'انتخاب تصویر', multiple: false, library: { type: 'image' } });
                        frame.on('select', function () {
                                var att = frame.state().get('selection').first().toJSON();
                                prev.style.backgroundImage = "url('" + att.url + "')";
                                onChange(att.url);
                        });
                        frame.open();
                });
                rem.addEventListener('click', function () { prev.style.backgroundImage = ''; onChange(''); });
                btns.appendChild(pick); btns.appendChild(rem);
                box.appendChild(prev); box.appendChild(btns);
                return box;
        }

        function buildRepeater(f, rows, onChange) {
                var rep = el('div', 'a7v-rep');
                var data = clone(rows);

                function commit() { onChange(clone(data)); }
                function draw() {
                        rep.innerHTML = '';
                        data.forEach(function (row, idx) {
                                var rr = el('div', 'a7v-rep-row');
                                var del = el('button', 'a7v-rep-del', '✕'); del.type = 'button';
                                del.addEventListener('click', function () { data.splice(idx, 1); draw(); commit(); });
                                rr.appendChild(del);
                                f.fields.forEach(function (sub) {
                                        rr.appendChild(buildField(sub, row, function (v) { row[sub.key] = v; commit(); }));
                                });
                                rep.appendChild(rr);
                        });
                        var add = el('button', 'button a7v-rep-add', '+ افزودن مورد'); add.type = 'button';
                        add.addEventListener('click', function () {
                                var blank = {};
                                f.fields.forEach(function (sub) { blank[sub.key] = ''; });
                                data.push(blank); draw(); commit();
                        });
                        rep.appendChild(add);
                }
                draw();
                return rep;
        }

        /* ---------- add-block modal ---------- */
        function buildAddGrid() {
                $addGrid.innerHTML = '';
                var sections = [], widgets = [];
                Object.keys(registry).forEach(function (type) {
                        (registry[type].section ? sections : widgets).push(type);
                });
                function card(type) {
                        var c = el('div', 'a7v-add-card', '<span class="dashicons ' + dashicon(type) + '"></span><span>' + label(type) + '</span>');
                        c.addEventListener('click', function () { addBlock(type); closeModal(); });
                        return c;
                }
                $addGrid.appendChild(el('div', 'a7v-add-sep', 'بخش‌های آماده قالب'));
                sections.forEach(function (t) { $addGrid.appendChild(card(t)); });
                $addGrid.appendChild(el('div', 'a7v-add-sep', 'بلوک‌های عمومی'));
                widgets.forEach(function (t) { $addGrid.appendChild(card(t)); });
        }

        function defaultSettings(type) {
                // Seed from defaultLayout if a matching block type exists, else empty by schema.
                var seed = (CFG.defaultLayout || []).find(function (b) { return b.type === type; });
                if (seed) return clone(seed.settings);
                var s = {};
                ((registry[type] && registry[type].fields) || []).forEach(function (f) {
                        s[f.key] = (f.type === 'repeater') ? [] : (f.type === 'toggle' ? false : '');
                });
                return s;
        }

        function addBlock(type) {
                var block = { id: uid(type), type: type, visible: true, settings: defaultSettings(type) };
                layout.push(block);
                renderList();
                selectBlock(block.id);
                schedulePreview();
        }

        function openModal() { buildAddGrid(); $addModal.hidden = false; }
        function closeModal() { $addModal.hidden = true; }

        /* ---------- preview ---------- */
        function schedulePreview() {
                clearTimeout(previewTimer);
                setStatus('در حال به‌روزرسانی پیش‌نمایش…', true);
                previewTimer = setTimeout(pushPreview, 450);
        }

        function pushPreview() {
                post('a7v_preview_layout', function () {
                        reloadFrame();
                        setStatus('', false);
                });
        }

        function reloadFrame() {
                // cache-bust so the iframe always re-renders
                $frame.src = CFG.previewUrl + '&_=' + Date.now();
        }

        /* ---------- save ---------- */
        function save() {
                setStatus(CFG.i18n.saving, true);
                post('a7v_save_layout', function () {
                        setStatus(CFG.i18n.saved, false);
                        setTimeout(function () { setStatus('', false); }, 2500);
                }, function () { setStatus('خطا در ذخیره', false); });
        }

        function post(action, ok, fail) {
                var body = new URLSearchParams();
                body.set('action', action);
                body.set('nonce', CFG.nonce);
                body.set('layout', JSON.stringify(layout));
                fetch(CFG.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (j) { if (j && j.success) { ok && ok(j.data); } else { fail && fail(); } })
                        .catch(function () { fail && fail(); });
        }

        function setStatus(msg, busy) {
                $status.textContent = msg || '';
                $status.style.opacity = busy ? '.7' : '1';
        }

        /* ---------- device toggle ---------- */
        function initDevices() {
                document.querySelectorAll('.a7v-dev').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                                document.querySelectorAll('.a7v-dev').forEach(function (b) { b.classList.remove('active'); });
                                btn.classList.add('active');
                                $frame.style.width = btn.dataset.w;
                        });
                });
        }

        /* ---------- reset ---------- */
        function reset() {
                if (!confirm(CFG.i18n.confirmReset)) return;
                layout = clone(CFG.defaultLayout);
                selectedId = null;
                renderList(); renderInspector(); schedulePreview();
        }

        /* ---------- boot ---------- */
        function init() {
                renderList();
                renderInspector();
                initDevices();
                reloadFrame();
                // push current working copy so preview reflects unsaved defaults too
                pushPreview();

                document.getElementById('a7vSave').addEventListener('click', save);
                document.getElementById('a7vReset').addEventListener('click', reset);
                document.getElementById('a7vAddBlock').addEventListener('click', openModal);
                document.getElementById('a7vReloadPreview').addEventListener('click', pushPreview);
                Array.prototype.forEach.call($addModal.querySelectorAll('[data-close]'), function (x) {
                        x.addEventListener('click', closeModal);
                });
        }

        if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
        } else {
                init();
        }
})();
