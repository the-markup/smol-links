import '../css/manager.scss';

class ShlinkManager {

	constructor() {
		this.load().then(this.showResults.bind(this));
	}

	load() {
		return fetch('/wp-admin/admin-ajax.php?action=shlinks');
	}

	async showResults(result) {
		let response = await result.json();
		console.log(response);
		let html = 'Oops, something unexpected happened';

		if (! response.ok) {
			html = 'Error: ' + (response.error || 'mystery error');
		} else {
			html = this.getListHtml(response.shlinks.data);
		}

		let el = document.querySelector('.shlink-manager');
		el.innerHTML = html;
	}

	getListHtml(data) {
		let html = '<ul class="shlink-list">';
		for (let shlink of data) {
			html += this.getItemHtml(shlink);
		}
		html += '</ul>';
		return html;
	}

	getItemHtml(shlink) {
		let title = shlink.title || shlink.longUrl;
		return `<li class="shlink-item">
			<a href="${shlink.longUrl}" class="shlink-long-url">${title}</a>
			<a href="${shlink.shortUrl}" class="shlink-short-url">${shlink.shortUrl}</a>
		</li>`;
	}

}

new ShlinkManager();
