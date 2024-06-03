import '../css/settings.scss';

class SmolLinksSettings {
	constructor() {
		this.setupDomainsRefresh();
	}

	setupDomainsRefresh() {
		let link = document.querySelector('.smol-links-reload-domains');
		if (link) {
			link.addEventListener('click', this.reloadDomains.bind(this));
		}
	}

	async reloadDomains(event) {
		event.preventDefault();

		let link = document.querySelector('.smol-links-reload-domains');
		let linkOriginalLabel = link.innerHTML;
		link.innerHTML = 'Loading domains...';
		link.classList.add('is-loading');
		link.blur();
		let select = document.querySelector('.smol-links-domain-list');
		select.setAttribute('disabled', 'disabled');
		let selectedDomain = select.options[select.selectedIndex].value;

		let response = await fetch(
			'/wp-admin/admin-ajax.php?action=smol_links_reload_domains'
		);
		let result = await response.json();
		if (result.ok) {
			window.location = window.location.href;
		} else {
			link.innerHTML = 'Error reloading domains';
		}
		link.classList.remove('is-loading');
		select.removeAttribute('disabled');
	}
}

window.addEventListener('DOMContentLoaded', () => {
	new SmolLinksSettings();
});
