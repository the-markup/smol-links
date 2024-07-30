import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

export default function ShortUrl() {

	const shortUrl = useSelect((select) => {
		const meta = select('core/editor').getEditedPostAttribute('meta');
		return meta.smol_links_short_url || null;
	}, []);

	if (!shortUrl) {
		return 'This post has no short URL.';
	}
	
	return (
		<TextControl
			className="smol-links-input"
			label={__('Short URL', 'smol-links')}
			value={shortUrl}
			readOnly="readOnly"
			onFocus={(event) => {
				event.target.select();
				if (navigator.clipboard) {
					var container = event.target.closest(
						'.smol-links-container'
					);
					var copied =
						container.querySelector('.smol-links-copied');
					var text = event.target.value;
					navigator.clipboard.writeText(text).then(
						function () {
							copied.classList.add('visible');
						},
						function (err) {
							console.log('error copying');
						}
					);
				}
			}}
			onBlur={(event) => {
				var container = event.target.closest(
					'.smol-links-container'
				);
				var copied =
					container.querySelector('.smol-links-copied');
				copied.classList.remove('visible');
			}}
		/>
	);
}