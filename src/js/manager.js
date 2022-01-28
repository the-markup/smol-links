import '../css/manager.scss';
import { copy, check } from '@primer/octicons';

class ShlinkManager {

	constructor() {
		this.load().then(this.showResults.bind(this));
	}

	load() {
		return fetch('/wp-admin/admin-ajax.php?action=get_shlinks');
	}

	async showResults(result) {
		let response = await result.json();
		let html = 'Oops, something unexpected happened';

		if (! response.ok || ! response.shlink) {
			html = 'Error: ' + (response.error || 'something went wrong loading shlinks. Try again?');
		} else {
			html = this.getListHTML(response.shlink.shortUrls.data);
		}

		let el = document.querySelector('.shlink-manager');
		el.innerHTML = html;
		el.addEventListener('click', this.clickHandler.bind(this));

		let form = document.querySelector('.shlink-create');
		form.addEventListener('submit', this.createShlink.bind(this));
	}

	getListHTML(data) {
		let html = '<ul class="shlink-list">';
		for (let shlink of data) {
			html += this.getItemHTML(shlink);
		}
		html += '</ul>';
		return html;
	}

	getItemHTML(shlink) {
		let dataAttrs = this.getItemDataAttrs(shlink);
		let contentHTML = this.getItemContentHTML(shlink);
		return `<li class="shlink-item"${dataAttrs}>
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
		return `<div class="shlink-item__content">
			<div class="shlink-item__clicks">
				${shlink.visitsCount} clicks
			</div>
			<div class="shlink-item__links">
				<div class="shlink-item__long-url">${title}</div>
				<div class="shlink-item__short-url">
					${this.getCopyHTML()}
					${shlink.shortUrl}
				</div>
				<div class="shlink-loading">
					<span class="shlink-loading-dot shlink-loading-dot--1"></span>
					<span class="shlink-loading-dot shlink-loading-dot--2"></span>
					<span class="shlink-loading-dot shlink-loading-dot--3"></span>
				</div>
			</div>
		</div>`;
	}

	getCopyHTML() {
		return `
			<span class="shlink-item__copy">
				<span class="shlink-item__copy-link">
					${copy.toSVG()}
				</span>
				<span class="shlink-item__copy-success">
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
		let feedback = document.querySelector('.shlink-create-feedback');
		feedback.innerHTML = '';

		let longURLField = form.querySelector('.shlink-long-url');
		let shortCodeField = form.querySelector('.shlink-short-code');
		let titleField = form.querySelector('.shlink-title');

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

		let list = document.querySelector('.shlink-list');
		list.innerHTML = this.getItemHTML({
			longUrl: longURLField.value,
			shortCode: shortCodeField.value,
			title: titleField.value,
			shortUrl: '',
			visitsCount: 0
		}) + list.innerHTML;

		let item = list.querySelectorAll('.shlink-item')[0];
		item.classList.add('shlink-item--is-saving');

		let result = await fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: this.encodeFormData({
				action: 'create_shlink',
				long_url: longURLField.value,
				short_code: shortCodeField.value,
				title: titleField.value
			})
		});

		let response = await result.json();
		item.classList.remove('shlink-item--is-saving');

		form.classList.remove('is-saving');
		longURLField.removeAttribute('readonly');
		shortCodeField.removeAttribute('readonly');
		titleField.removeAttribute('readonly');

		if (response.shlink && response.shlink.shortCode) {
			item.innerHTML = this.getItemContentHTML(response.shlink);
			item.setAttribute('data-title', response.shlink.title || '');
			item.setAttribute('data-short-code', response.shlink.shortCode);
			item.setAttribute('data-short-url', response.shlink.shortUrl);
			longURLField.value = '';
			shortCodeField.value = '';
			titleField.value = '';
		} else {
			list.removeChild(item);
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

	clickHandler(event) {
		var item;
		if (event.target.classList.contains('button')) {
			return;
		}
		if (event.target.classList.contains('shlink-item')) {
			item = event.target;
		} else if (event.target.closest('.shlink-item')) {
			item = event.target.closest('.shlink-item');
		} else {
			return true;
		}
		let copy = event.target.classList.contains('shlink-item__copy') ||
		           event.target.closest('.shlink-item__copy');
		if (copy) {
			this.copyLink(item, copy);
			return;
		}
		this.editShlink(item);
	}

	editShlink(item) {
		let current = document.querySelector('.shlink-item--is-editing');
		if (current) {
			current.classList.remove('shlink-item--is-editing');
		}
		item.classList.add('shlink-item--is-editing');

		var form;
		if (item.querySelector('.shlink-item__edit')) {
			form = item.querySelector('.shlink-item__edit');
		} else {
			let shortUrl = item.getAttribute('data-short-url');
			form = item.appendChild(document.createElement('form'));
			form.setAttribute('action', '/wp-admin/admin-ajax.php');
			form.setAttribute('method', 'POST');
			form.classList.add('shlink-item__edit');
			form.innerHTML = `
					<h3 class="shlink-edit-heading">${this.getCopyHTML()} ${shortUrl}</h3>
					<div class="shlink-edit-field">
						<label for="shlink-edit-title" class="shlink-label">Title</label>
						<input type="text" id="shlink-edit-title" name="title" class="shlink-title regular-text ltr" value="${item.getAttribute('data-title')}">
					</div>
					<div class="shlink-edit-field">
						<label for="shlink-edit-long-url" class="shlink-label">Long URL</label>
						<input type="text" id="shlink-edit-long-url" name="long_url" class="shlink-long-url regular-text ltr" value="${item.getAttribute('data-long-url')}">
					</div>
					<div class="shlink-edit-buttons">
						<input type="submit" value="Save" class="shlink-save button button-primary">
						<input type="button" value="Cancel" class="shlink-cancel button">
					</div>
			`;
			form.addEventListener('submit', this.saveShlink.bind(this));

			let cancel = form.querySelector('.shlink-cancel');
			cancel.addEventListener('click', event => {
				event.preventDefault();
				let item = event.target.closest('.shlink-item');
				item.classList.remove('shlink-item--is-editing');
			});
		}
	}

	async saveShlink(event) {
		event.preventDefault();

		let item = event.target.closest('.shlink-item');
		let title = item.querySelector('.shlink-title').value;
		let longUrl = item.querySelector('.shlink-long-url').value;

		item.classList.remove('shlink-item--is-editing');
		item.classList.add('shlink-item--is-saving');

		let result = await fetch('/wp-admin/admin-ajax.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: this.encodeFormData({
				action: 'update_shlink',
				title: title,
				long_url: longUrl,
				short_code: item.getAttribute('data-short-code')
			})
		});
		let response = await result.json();
		if (response && response.ok && response.shlink) {
			let shlink = response.shlink;

			if (shlink.shortCode) {
				item.innerHTML = this.getItemContentHTML(response.shlink);
				item.classList.remove('shlink-item--is-saving');

				item.setAttribute('data-title', shlink.title);
				item.setAttribute('data-long-url', shlink.longUrl);
				item.setAttribute('data-short-code', shlink.shortCode);

				let longURL = item.querySelector('.shlink-item__long-url');
				longURL.innerHTML = title || longUrl;
				return true;
			} else {
				item.classList.add('shlink-item--is-editing');
				item.classList.remove('shlink-item--is-saving');

				if (shlink.title && shlink.detail) {
					alert(`Error: ${shlink.title}. ${shlink.detail}.`);
					return false;
				}
			}
		}
		alert('Sorry, there was a problem saving changes to the Shlink.');
		return false;
	}

	async copyLink(item, copy) {
		if (! navigator || ! navigator.clipboard) {
			return;
		}
		await navigator.clipboard.writeText(item.getAttribute('data-short-url'));
		copy.classList.add('shlink-item__copy--success');
		setTimeout(() => {
			copy.classList.remove('shlink-item__copy--success');
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

new ShlinkManager();
