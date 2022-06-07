import '../css/manager.scss';
import { copy, check } from '@primer/octicons';

class ShlinkifyManager {

	constructor() {
		this.load().then(this.showResults.bind(this));
	}

	load() {
		let tab = 'All';
		let tabQueryString = location.search.match(/tab=([^&]+)/);
		if (tabQueryString) {
			tab = tabQueryString[1];
		}
		return fetch(`/wp-admin/admin-ajax.php?action=shlinkify_load&tab=${tab}&_wpnonce=${shlinkify_nonces.load}`);
	}

	async showResults(result) {
		try {
			let response = await result.json();
			let html = 'Oops, something unexpected happened';

			if (! response.ok || ! response.shlinkify) {
				html = 'Error: ' + (response.error || 'something went wrong loading shlinks. Try again?');
			} else {
				html = this.getListHTML(response.shlinkify.shortUrls.data);
			}

			let el = document.querySelector('.shlinkify-list');
			el.innerHTML = html;
			el.addEventListener('click', this.clickHandler.bind(this));

			let form = document.querySelector('.shlinkify-create');
			form.addEventListener('submit', this.createShlink.bind(this));
		} catch(err) {
			let loading = document.querySelector('.shlinkify-loading');
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
		return `<li class="shlinkify-item"${dataAttrs}>
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
		return `<div class="shlinkify-item__content">
			<div class="shlinkify-item__clicks">
				${shlink.visitsCount} clicks
			</div>
			<div class="shlinkify-item__links">
				<div class="shlinkify-item__long-url">${title}</div>
				<div class="shlinkify-item__short-url">
					${this.getCopyHTML()}
					${shlink.shortUrl}
				</div>
				<div class="shlinkify-loading">
					<span class="shlinkify-loading-dot shlinkify-loading-dot--1"></span>
					<span class="shlinkify-loading-dot shlinkify-loading-dot--2"></span>
					<span class="shlinkify-loading-dot shlinkify-loading-dot--3"></span>
				</div>
			</div>
		</div>`;
	}

	getCopyHTML() {
		return `
			<span class="shlinkify-item__copy">
				<span class="shlinkify-item__copy-link">
					${copy.toSVG()}
				</span>
				<span class="shlinkify-item__copy-success">
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
		let feedback = document.querySelector('.shlinkify-create-feedback');
		feedback.innerHTML = '';

		let longURLField = form.querySelector('.shlinkify-long-url');
		let shortCodeField = form.querySelector('.shlinkify-short-code');
		let titleField = form.querySelector('.shlinkify-title');
		let domainField = form.querySelector('.shlinkify-domain');

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

		let list = document.querySelector('.shlinkify-list ul');
		list.innerHTML = this.getItemHTML({
			longUrl: longURLField.value,
			shortCode: shortCodeField.value,
			title: titleField.value,
			shortUrl: '',
			visitsCount: 0
		}) + list.innerHTML;

		let item = list.querySelectorAll('.shlinkify-item')[0];
		item.classList.add('shlinkify-item--is-saving');

		let result = await fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: this.encodeFormData({
				action: 'shlinkify_create',
				long_url: longURLField.value,
				short_code: shortCodeField.value,
				title: titleField.value,
				domain: this.getDomain(),
				_wpnonce: shlinkify_nonces.create
			})
		});

		let response = await result.json();
		item.classList.remove('shlinkify-item--is-saving');

		form.classList.remove('is-saving');
		longURLField.removeAttribute('readonly');
		shortCodeField.removeAttribute('readonly');
		titleField.removeAttribute('readonly');
		domainField.removeAttribute('disabled');

		if (response.shlinkify && response.shlinkify.shortCode) {
			item.innerHTML = this.getItemContentHTML(response.shlinkify);
			item.setAttribute('data-title', response.shlinkify.title || '');
			item.setAttribute('data-short-code', response.shlinkify.shortCode);
			item.setAttribute('data-short-url', response.shlinkify.shortUrl);
			longURLField.value = '';
			shortCodeField.value = '';
			titleField.value = '';
		} else {
			list.removeChild(item);
			let title = 'Error';
			let detail = 'Could not create shlink';
			if (response.shlinkify && response.shlinkify.title) {
				title = response.shlinkify.title;
			}
			if (response.shlinkify && response.shlinkify.detail) {
				detail = response.shlinkify.detail;
			}
			feedback.innerHTML = `
				<div class="notice notice-error is-dismissible">
					<p>${title}. ${detail}.</p>
				</div>
			`;
		}
	}

	getDomain() {
		let field = document.querySelector('.shlinkify-domain');
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
		if (event.target.classList.contains('shlinkify-item')) {
			item = event.target;
		} else if (event.target.closest('.shlinkify-item')) {
			item = event.target.closest('.shlinkify-item');
		} else {
			return true;
		}
		let copy = event.target.classList.contains('shlinkify-item__copy') ||
		           event.target.closest('.shlinkify-item__copy');
		if (copy) {
			this.copyLink(item, copy);
			return;
		}
		this.editShlink(item);
	}

	editShlink(item) {
		let current = document.querySelector('.shlinkify-item--is-editing');
		if (current) {
			current.classList.remove('shlinkify-item--is-editing');
		}
		item.classList.add('shlinkify-item--is-editing');

		var form;
		if (item.querySelector('.shlinkify-item__edit')) {
			form = item.querySelector('.shlinkify-item__edit');
		} else {
			let shortUrl = item.getAttribute('data-short-url');
			form = item.appendChild(document.createElement('form'));
			form.setAttribute('action', '/wp-admin/admin-ajax.php');
			form.setAttribute('method', 'POST');
			form.classList.add('shlinkify-item__edit');
			form.innerHTML = `
					<input type="hidden" name="_wpnonce" value="${this.escape(shlinkify_nonces.update)}">
					<h3 class="shlinkify-edit-heading">${this.getCopyHTML()} ${shortUrl}</h3>
					<div class="shlinkify-edit-field">
						<label for="shlinkify-edit-title" class="shlinkify-label">Title</label>
						<input type="text" id="shlinkify-edit-title" name="title" class="shlinkify-title regular-text ltr" value="${item.getAttribute('data-title')}">
					</div>
					<div class="shlinkify-edit-field">
						<label for="shlinkify-edit-long-url" class="shlinkify-label">Long URL</label>
						<input type="text" id="shlinkify-edit-long-url" name="long_url" class="shlinkify-long-url regular-text ltr" value="${item.getAttribute('data-long-url')}">
					</div>
					<div class="shlinkify-edit-buttons">
						<input type="submit" value="Save" class="shlinkify-save button button-primary">
						<input type="button" value="Cancel" class="shlinkify-cancel button">
					</div>
			`;
			form.addEventListener('submit', this.saveShlink.bind(this));

			let cancel = form.querySelector('.shlinkify-cancel');
			cancel.addEventListener('click', event => {
				event.preventDefault();
				let item = event.target.closest('.shlinkify-item');
				item.classList.remove('shlinkify-item--is-editing');
			});
		}
	}

	async saveShlink(event) {
		event.preventDefault();

		let item = event.target.closest('.shlinkify-item');
		let title = item.querySelector('.shlinkify-title').value;
		let longUrl = item.querySelector('.shlinkify-long-url').value;

		item.classList.remove('shlinkify-item--is-editing');
		item.classList.add('shlinkify-item--is-saving');

		let result = await fetch('/wp-admin/admin-ajax.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: this.encodeFormData({
				action: 'shlinkify_update',
				title: title,
				long_url: longUrl,
				short_code: item.getAttribute('data-short-code'),
				_wpnonce: shlinkify_nonces.update
			})
		});
		let response = await result.json();
		if (response && response.ok && response.shlink) {
			let shlink = response.shlink;

			if (shlink.shortCode) {
				item.innerHTML = this.getItemContentHTML(response.shlink);
				item.classList.remove('shlinkify-item--is-saving');

				item.setAttribute('data-title', shlink.title);
				item.setAttribute('data-long-url', shlink.longUrl);
				item.setAttribute('data-short-code', shlink.shortCode);

				let longURL = item.querySelector('.shlinkify-item__long-url');
				longURL.innerHTML = title || longUrl;
				return true;
			} else {
				item.classList.add('shlinkify-item--is-editing');
				item.classList.remove('shlinkify-item--is-saving');

				if (shlink.title && shlink.detail) {
					alert(`Error: ${shlink.title}. ${shlink.detail}.`);
					return false;
				}
			}
		}
		item.classList.add('shlinkify-item--is-editing');
		item.classList.remove('shlinkify-item--is-saving');
		alert('Sorry, there was a problem saving changes to the Shlink.');
		return false;
	}

	async copyLink(item, copy) {
		if (! navigator || ! navigator.clipboard) {
			return;
		}
		await navigator.clipboard.writeText(item.getAttribute('data-short-url'));
		copy.classList.add('shlinkify-item__copy--success');
		setTimeout(() => {
			copy.classList.remove('shlinkify-item__copy--success');
		}, 2000);
	}

	encodeFormData(data) {
		let assignments = Object.keys(data).map(key => {
			key = encodeURIComponent(key);
			let value = encodeURIComponent(data[key]);
			return `${key}=${value}`;
		});
		return assignments.join('&');
	}

	escape(value) {
		if (! value) {
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

new ShlinkifyManager();
