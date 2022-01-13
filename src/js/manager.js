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
		console.log(response);
		let html = 'Oops, something unexpected happened';

		if (! response.ok) {
			html = 'Error: ' + (response.error || 'mystery error');
		} else {
			html = this.getListHTML(response.shlink.shortUrls.data);
		}

		let el = document.querySelector('.shlink-manager');
		el.innerHTML = html;

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
		let title = shlink.title || shlink.longUrl;
		return `<li class="shlink-item">
			<a href="${shlink.longUrl}" class="shlink-long-url">${title}</a>
			<a href="${shlink.shortUrl}" class="shlink-short-url">${shlink.shortUrl}</a>
		</li>`;
	}

	async createShlink(event) {
		event.preventDefault();
		let url = event.target.getAttribute('action');
		let longURLField = document.querySelector('input.shlink-long-url');

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

		let response = await result.json();
		longURLField.value = '';
		console.log(response);
	}

	encodeFormData(data) {
		let assignments = Object.keys(data).map(key => {
			key = encodeURIComponent(key);
			let value = encodeURIComponent(data[key]);
			return `${key}=${value}`;
		});
		return assignments.join('&');
	}

}

new ShlinkManager();
