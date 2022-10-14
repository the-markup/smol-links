import '../css/settings.scss';

class SmolLinksSettings {
	constructor() {
		this.setupDomainsRefresh();
	}

	setupDomainsRefresh() {
		let link = document.querySelector('.shlink-reload-domains');
		link.addEventListener('click', this.reloadDomains.bind(this));
	}

	async reloadDomains(event) {
		event.preventDefault();

		let link = document.querySelector('.shlink-reload-domains');
		let linkOriginalLabel = link.innerHTML;
		link.innerHTML = 'Loading domains...';
		link.classList.add('is-loading');
		link.blur();
		let select = document.querySelector('.shlink-domain-list');
		select.setAttribute('disabled', 'disabled');
		let selectedDomain = select.options[select.selectedIndex].value;

		let response = await fetch(
			'/wp-admin/admin-ajax.php?action=reload_domains'
		);
		let result = await response.json();
		if (result.ok && result.domains) {
			let options = '';
			let index = 0;
			let defaultIndex = 0;
			let selectedIndex = null;
			for (let domain of result.domains) {
				if (domain == result.default_domain) {
					selectedIndex = index;
				}
				if (domain == selectedDomain) {
					selectedIndex = index;
				}
				index++;
				options += `<option>${domain}</option>`;
			}
			select.innerHTML = options;
			if (typeof selectedIndex == 'number') {
				select.selectedIndex = selectedIndex;
			} else {
				select.selectedIndex = defaultIndex;
			}
			link.innerHTML = linkOriginalLabel;
		} else {
			link.innerHTML = result.error || 'Error reloading domains';
		}
		link.classList.remove('is-loading');
		select.removeAttribute('disabled');
	}
}

window.addEventListener('DOMContentLoaded', () => {
	new SmolLinksSettings();
});
