import '../css/editor.scss';

((wp) => {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { TextControl } = wp.components;
	const { __ } = wp.i18n;

	var ShortUrl = () => {
		var shortUrl = wp.data.useSelect((select) => {
			var meta = select('core/editor').getEditedPostAttribute('meta');
			return meta.smol_links_short_url || null;
		}, []);

		if (shortUrl) {
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
		} else {
			return 'This post has no short URL.';
		}
	};

	registerPlugin('smol-links', {
		render: () => {
			return (
				<PluginDocumentSettingPanel
					name="smol-links-panel"
					title="Smol Links"
					className="smol-links-editor-sidebar"
				>
					<div className="smol-links-container">
						<ShortUrl />
						<div className="smol-links-copied">
							copied to clipboard
						</div>
					</div>
				</PluginDocumentSettingPanel>
			);
		},
		icon: false,
	});
})(window.wp);
