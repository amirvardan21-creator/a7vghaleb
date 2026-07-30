/* Mafia Academy — front-end interactions */
(function () {
	'use strict';

	/* -------- Card-to-card box toggle -------- */
	function syncCardBox() {
		var box = document.querySelector('[data-card-box]');
		var checked = document.querySelector('input[name="mam_method"]:checked');
		var isCard = checked && checked.value === 'card';
		if (box) {
			box.hidden = !isCard;
			var txn = document.getElementById('mam-txn');
			var rcp = document.getElementById('mam-receipt');
			if (txn) { txn.required = !!isCard; }
			if (rcp) { rcp.required = !!isCard; }
		}
		// Highlight the selected gateway tile.
		document.querySelectorAll('.mam-gw').forEach(function (tile) {
			var r = tile.querySelector('input[name="mam_method"]');
			tile.classList.toggle('is-selected', !!(r && r.checked));
		});
	}

	/* -------- Discount code -------- */
	function applyDiscount(root) {
		var codeEl = root.querySelector('.mam-discount-code');
		var msgEl  = root.querySelector('.mam-discount-msg');
		var hidden = root.querySelector('.mam-discount-applied');
		if (!codeEl) { return; }

		var code = (codeEl.value || '').trim();
		if (!code) {
			msgEl.textContent = 'کد تخفیف را وارد کنید.';
			msgEl.className = 'mam-discount-msg err';
			return;
		}

		var data = new FormData();
		data.append('action', 'mam_apply_discount');
		data.append('nonce', root.getAttribute('data-nonce') || '');
		data.append('plan', root.getAttribute('data-plan') || '');
		data.append('code', code);

		msgEl.textContent = 'در حال بررسی...';
		msgEl.className = 'mam-discount-msg';

		fetch(root.getAttribute('data-ajax'), { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res && res.success) {
					var d = res.data;
					if (hidden) { hidden.value = d.code; }
					var offRow = root.querySelector('.mam-summary-off');
					var offEl  = root.querySelector('.mam-sum-off');
					var totEl  = root.querySelector('.mam-sum-total');
					if (offRow) { offRow.hidden = false; }
					if (offEl)  { offEl.textContent = d.off_html; }
					if (totEl)  { totEl.textContent = d.final_html; }
					msgEl.textContent = d.message;
					msgEl.className = 'mam-discount-msg ok';
				} else {
					if (hidden) { hidden.value = ''; }
					var offRow2 = root.querySelector('.mam-summary-off');
					var totEl2  = root.querySelector('.mam-sum-total');
					var priceEl = root.querySelector('.mam-sum-price');
					if (offRow2) { offRow2.hidden = true; }
					if (totEl2 && priceEl) { totEl2.textContent = priceEl.textContent; }
					msgEl.textContent = (res && res.data && res.data.message) ? res.data.message : 'کد تخفیف نامعتبر است.';
					msgEl.className = 'mam-discount-msg err';
				}
			})
			.catch(function () {
				msgEl.textContent = 'خطا در ارتباط. دوباره تلاش کنید.';
				msgEl.className = 'mam-discount-msg err';
			});
	}

	document.addEventListener('change', function (e) {
		if (e.target && e.target.name === 'mam_method') { syncCardBox(); }
	});

	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('.mam-discount-apply') : null;
		if (btn) {
			e.preventDefault();
			var root = btn.closest('.mam-checkout');
			if (root) { applyDiscount(root); }
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.target && e.target.classList && e.target.classList.contains('mam-discount-code') && e.key === 'Enter') {
			e.preventDefault();
			var root = e.target.closest('.mam-checkout');
			if (root) { applyDiscount(root); }
		}
	});

	document.addEventListener('DOMContentLoaded', function () {
		syncCardBox();

		// Reveal the countdown ring on load.
		document.querySelectorAll('.mam-ring-fg').forEach(function (ring) {
			var target = ring.getAttribute('stroke-dashoffset');
			var full = ring.getAttribute('stroke-dasharray');
			ring.setAttribute('stroke-dashoffset', full);
			requestAnimationFrame(function () {
				setTimeout(function () { ring.setAttribute('stroke-dashoffset', target); }, 80);
			});
		});
	});
})();
