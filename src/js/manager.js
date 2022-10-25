import '../css/manager.scss';
import { copy, check } from '@primer/octicons';

class SmolLinksManager {
	constructor() {
		this.load().then(this.showResults.bind(this));
	}

	load() {
		let tab = this.getTab();
		return fetch(
			`/wp-admin/admin-ajax.php?action=smol_links_load&tab=${tab}&_wpnonce=${smol_links_nonces.load}`
		);
	}

	getTab() {
		let tab = 'all';
		let tabQueryString = location.search.match(/tab=([^&]+)/);
		if (tabQueryString) {
			tab = tabQueryString[1];
		}
		return tab;
	}

	async showResults(result) {
		try {
			let response = await result.json();
			let html = 'Oops, something unexpected happened';

			if (!response.ok || !response.shlink) {
				html =
					'Error: ' +
					(response.error ||
						'something went wrong loading shlinks. Try again?');
			} else {
				html = this.getListHTML(response.shlink.shortUrls.data);
			}

			let el = document.querySelector('.smol-links-list');
			el.innerHTML = html;
			el.addEventListener('click', this.clickHandler.bind(this));

			let form = document.querySelector('.smol-links-create');
			form.addEventListener('submit', this.createShlink.bind(this));
		} catch (err) {
			let loading = document.querySelector('.smol-links-loading');
			loading.innerHTML = `
				<div class="notice notice-error is-dismissible">
					<p>There was an error loading shlinks.</p>
				</div>
			`;
		}
	}

	getListHTML(data) {
		let html = '<ul>';
		for (let shlink of data) {
			html += this.getItemHTML(shlink);
		}
		html += '</ul>';
		return html;
	}

	getItemHTML(shlink) {
		let dataAttrs = this.getItemDataAttrs(shlink);
		let contentHTML = this.getItemContentHTML(shlink);
		return `<li class="smol-links-item"${dataAttrs}>
			${contentHTML}
		</li>`;
	}

	getItemDataAttrs(shlink) {
		let dataAttrs = '';
		dataAttrs += ` data-title="${this.escape(shlink.title)}"`;
		dataAttrs += ` data-long-url="${this.escape(shlink.longUrl)}"`;
		dataAttrs += ` data-short-url="${this.escape(shlink.shortUrl)}"`;
		dataAttrs += ` data-short-code="${this.escape(shlink.shortCode)}"`;
		return dataAttrs;
	}

	getItemContentHTML(shlink) {
		let title = shlink.title || shlink.longUrl;
		return `<div class="smol-links-item__content">
			<div class="smol-links-item__clicks">
				${shlink.visitsCount} clicks
			</div>
			<div class="smol-links-item__links">
				<div class="smol-links-item__long-url">${title}</div>
				<div class="smol-links-item__short-url">
					${this.getCopyHTML()}
					${shlink.shortUrl}
				</div>
				<div class="smol-links-loading">
					<span class="smol-links-loading-dot smol-links-loading-dot--1"></span>
					<span class="smol-links-loading-dot smol-links-loading-dot--2"></span>
					<span class="smol-links-loading-dot smol-links-loading-dot--3"></span>
				</div>
			</div>
		</div>`;
	}

	getCopyHTML() {
		return `
			<span class="smol-links-item__copy">
				<span class="smol-links-item__copy-link">
					${copy.toSVG()}
				</span>
				<span class="smol-links-item__copy-success">
					${check.toSVG()}
				</span>
			</span>
		`;
	}

	async createShlink(event) {
		event.preventDefault();

		let form = event.target;
		if (form.classList.contains('is-saving')) {
			return;
		}

		let url = form.getAttribute('action');
		let feedback = document.querySelector('.smol-links-create-feedback');
		feedback.innerHTML = '';

		let longURLField = form.querySelector('.smol-links-long-url');
		let shortCodeField = form.querySelector('.smol-links-short-code');
		let titleField = form.querySelector('.smol-links-title');
		let domainField = form.querySelector('.smol-links-domain');

		if (longURLField.value == '') {
			feedback.innerHTML = `
				<div class="notice notice-error is-dismissible">
					<p>Sorry, you must specify a long URL to shorten.</p>
				</div>
			`;
			return;
		}

		form.classList.add('is-saving');

		longURLField.setAttribute('readonly', 'readonly');
		shortCodeField.setAttribute('readonly', 'readonly');
		titleField.setAttribute('readonly', 'readonly');
		domainField.setAttribute('disabled', 'disabled');

		if (this.getTab() != 'auto-generated') {
			let list = document.querySelector('.smol-links-list ul');
			list.innerHTML =
				this.getItemHTML({
					longUrl: longURLField.value,
					shortCode: shortCodeField.value,
					title: titleField.value,
					shortUrl: '',
					visitsCount: 0,
				}) + list.innerHTML;

			let item = list.querySelectorAll('.smol-links-item')[0];
			item.classList.add('smol-links-item--is-saving');
		}

		let result = await fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: this.encodeFormData({
				action: 'smol_links_create',
				long_url: longURLField.value,
				short_code: shortCodeField.value,
				title: titleField.value,
				domain: this.getDomain(),
				_wpnonce: smol_links_nonces.create,
			}),
		});

		let response = await result.json();

		if (this.getTab() != 'auto-generated') {
			item.classList.remove('smol-links-item--is-saving');
		}

		form.classList.remove('is-saving');
		longURLField.removeAttribute('readonly');
		shortCodeField.removeAttribute('readonly');
		titleField.removeAttribute('readonly');
		domainField.removeAttribute('disabled');

		if (response.shlink && response.shlink.shortCode) {
			if (this.getTab() != 'auto-generated') {
				item.innerHTML = this.getItemContentHTML(response.shlink);
				item.setAttribute('data-title', response.shlink.title || '');
				item.setAttribute('data-short-code', response.shlink.shortCode);
				item.setAttribute('data-short-url', response.shlink.shortUrl);
			}
			longURLField.value = '';
			shortCodeField.value = '';
			titleField.value = '';
		} else {
			if (this.getTab() != 'auto-generated') {
				list.removeChild(item);
			}
			let title = 'Error';
			let detail = 'Could not create shlink';
			if (response.shlink && response.shlink.title) {
				title = response.shlink.title;
			}
			if (response.shlink && response.shlink.detail) {
				detail = response.shlink.detail;
			}
			feedback.innerHTML = `
				<div class="notice notice-error is-dismissible">
					<p>${title}. ${detail}.</p>
				</div>
			`;
		}
	}

	getDomain() {
		let field = document.querySelector('.smol-links-domain');
		if (field.nodeName.toLowerCase == 'select') {
			return field.options[field.selectedIndex].value;
		} else {
			return field.value;
		}
	}

	clickHandler(event) {
		var item;
		if (event.target.classList.contains('button')) {
			return;
		}
		if (event.target.classList.contains('smol-links-item')) {
			item = event.target;
		} else if (event.target.closest('.smol-links-item')) {
			item = event.target.closest('.smol-links-item');
		} else {
			return true;
		}
		let copy =
			event.target.classList.contains('smol-links-item__copy') ||
			event.target.closest('.smol-links-item__copy');
		if (copy) {
			this.copyLink(item, copy);
			return;
		}
		this.editShlink(item);
	}

	editShlink(item) {
		let current = document.querySelector('.smol-links-item--is-editing');
		if (current) {
			current.classList.remove('smol-links-item--is-editing');
		}
		item.classList.add('smol-links-item--is-editing');

		var form;
		if (item.querySelector('.smol-links-item__edit')) {
			form = item.querySelector('.smol-links-item__edit');
		} else {
			let shortUrl = item.getAttribute('data-short-url');
			form = item.appendChild(document.createElement('form'));
			form.setAttribute('action', '/wp-admin/admin-ajax.php');
			form.setAttribute('method', 'POST');
			form.classList.add('smol-links-item__edit');
			form.innerHTML = `
					<input type="hidden" name="_wpnonce" value="${this.escape(
						smol_links_nonces.update
					)}">
					<h3 class="smol-links-edit-heading">${this.getCopyHTML()} ${shortUrl}</h3>
					<div class="smol-links-edit-field">
						<label for="smol-links-edit-title" class="smol-links-label">Title</label>
						<input type="text" id="smol-links-edit-title" name="title" class="smol-links-title regular-text ltr" value="${item.getAttribute(
							'data-title'
						)}">
					</div>
					<div class="smol-links-edit-field">
						<label for="smol-links-edit-long-url" class="smol-links-label">Long URL</label>
						<input type="text" id="smol-links-edit-long-url" name="long_url" class="smol-links-long-url regular-text ltr" value="${item.getAttribute(
							'data-long-url'
						)}">
					</div>
					<div class="smol-links-edit-buttons">
						<input type="submit" value="Save" class="smol-links-save button button-primary">
						<input type="button" value="Cancel" class="smol-links-cancel button">
					</div>
			`;
			form.addEventListener('submit', this.saveShlink.bind(this));

			let cancel = form.querySelector('.smol-links-cancel');
			cancel.addEventListener('click', (event) => {
				event.preventDefault();
				let item = event.target.closest('.smol-links-item');
				item.classList.remove('smol-links-item--is-editing');
			});
		}
	}

	async saveShlink(event) {
		event.preventDefault();

		let item = event.target.closest('.smol-links-item');
		let title = item.querySelector('.smol-links-title').value;
		let longUrl = item.querySelector('.smol-links-long-url').value;

		item.classList.remove('smol-links-item--is-editing');
		item.classList.add('smol-links-item--is-saving');

		let result = await fetch('/wp-admin/admin-ajax.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: this.encodeFormData({
				action: 'smol_links_update',
				title: title,
				long_url: longUrl,
				short_code: item.getAttribute('data-short-code'),
				_wpnonce: smol_links_nonces.update,
			}),
		});
		let response = await result.json();
		if (response && response.ok && response.shlink) {
			let shlink = response.shlink;

			if (shlink.shortCode) {
				item.innerHTML = this.getItemContentHTML(shlink);
				item.classList.remove('smol-links-item--is-saving');

				item.setAttribute('data-title', shlink.title);
				item.setAttribute('data-long-url', shlink.longUrl);
				item.setAttribute('data-short-code', shlink.shortCode);

				let longURL = item.querySelector('.smol-links-item__long-url');
				longURL.innerHTML = title || longUrl;
				return true;
			} else {
				item.classList.add('smol-links-item--is-editing');
				item.classList.remove('smol-links-item--is-saving');

				if (shlink.title && shlink.detail) {
					alert(`Error: ${shlink.title}. ${shlink.detail}.`);
					return false;
				}
			}
		}
		item.classList.add('smol-links-item--is-editing');
		item.classList.remove('smol-links-item--is-saving');
		alert('Sorry, there was a problem saving changes to the Shlink.');
		return false;
	}

	async copyLink(item, copy) {
		if (!navigator || !navigator.clipboard) {
			return;
		}
		await navigator.clipboard.writeText(
			item.getAttribute('data-short-url')
		);
		copy.classList.add('smol-links-item__copy--success');
		setTimeout(() => {
			copy.classList.remove('smol-links-item__copy--success');
		}, 2000);
	}

	encodeFormData(data) {
		let assignments = Object.keys(data).map((key) => {
			key = encodeURIComponent(key);
			let value = encodeURIComponent(data[key]);
			return `${key}=${value}`;
		});
		return assignments.join('&');
	}

	escape(value) {
		if (!value) {
			return '';
		}
		return ('' + value)
			.replace(/&/g, '&amp;')
			.replace(/'/g, '&apos;')
			.replace(/"/g, '&quot;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}
}

new SmolLinksManager();
