import '../css/manager.scss';

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

		if (! response.ok) {
			html = 'Error: ' + (response.error || 'mystery error');
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
				<div class="shlink-item__short-url">${shlink.shortUrl}</div>
				<div class="shlink-loading">
					<span class="shlink-loading-dot shlink-loading-dot--1"></span>
					<span class="shlink-loading-dot shlink-loading-dot--2"></span>
					<span class="shlink-loading-dot shlink-loading-dot--3"></span>
				</div>
			</div>
		</div>`;
	}

	async createShlink(event) {
		event.preventDefault();
		let url = event.target.getAttribute('action');
		let longURLField = document.querySelector('input.shlink-long-url');

		let list = document.querySelector('.shlink-list');
		list.innerHTML = this.getItemHTML({
			longUrl: longURLField.value,
			shortCode: '',
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
				long_url: longURLField.value
			})
		});

		longURLField.value = '';

		let response = await result.json();
		item.innerHTML = this.getItemContentHTML(response.shlink);
		item.classList.remove('shlink-item--is-saving');

		item.setAttribute('data-title', response.shlink.title || '');
		item.setAttribute('data-short-code', response.shlink.shortCode);
		item.setAttribute('data-short-url', response.shlink.shortUrl);
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
			let shortCode = item.getAttribute('data-short-code');
			let regex = new RegExp(`${shortCode}$`);
			let prefix = shortUrl.replace(regex, '');
			form = item.appendChild(document.createElement('form'));
			form.setAttribute('action', '/wp-admin/admin-ajax.php');
			form.setAttribute('method', 'POST');
			form.classList.add('shlink-item__edit');
			form.innerHTML = `
				<div class="shlink-edit-field">
					<label for="shlink-edit-title" class="shlink-label">Title</label>
					<input type="text" id="shlink-edit-title" name="title" class="shlink-edit-title regular-text ltr" value="${item.getAttribute('data-title')}">
				</div>
				<div class="shlink-edit-field">
					<label for="shlink-edit-long-url" class="shlink-label">Long URL</label>
					<input type="text" id="shlink-edit-long-url" name="long_url" class="shlink-edit-long-url regular-text ltr" value="${item.getAttribute('data-long-url')}">
				</div>
				<div class="shlink-edit-field">
					<label for="shlink-edit-short-code" class="shlink-label">Short URL</label>
					<span class="shlink-edit-prefix">${prefix}</span><input type="text" id="shlink-edit-short-code" name="short_code" class="shlink-edit-short-code regular-text ltr" value="${item.getAttribute('data-short-code')}">
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
		console.log('saveShlink');
		event.preventDefault();

		let item = event.target.closest('.shlink-item');
		let title = item.querySelector('.shlink-edit-title').value;
		let longUrl = item.querySelector('.shlink-edit-long-url').value;
		let shortCode = item.querySelector('.shlink-edit-short-code').value;

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
				short_code: shortCode
			})
		});
		let response = await result.json();
		if (response && response.ok && response.shlink) {
			let shlink = response.shlink;

			if (shlink.status == 200) {
				item.innerHTML = this.getItemContentHTML(response.shlink);
				item.classList.remove('shlink-item--is-saving');

				item.setAttribute('data-title', title);
				item.setAttribute('data-long-url', longUrl);
				item.setAttribute('data-short-code', shortCode);

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
