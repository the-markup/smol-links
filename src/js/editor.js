import '../css/editor.scss';

(wp => {

	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { TextControl } = wp.components;
	const { __ } = wp.i18n;

	var ShortUrl = () => {

		var shortUrl = wp.data.useSelect(select => {
			var meta = select('core/editor').getEditedPostAttribute('meta');
			if (meta.shlink) {
				var shlink = JSON.parse(meta.shlink);
				return shlink.shortUrl;
			}
			return null;
		}, []);

		if (shortUrl) {
			return (
				<TextControl
					className="shlink-input"
					label={__('Short URL', 'wp-shlink')}
					value={shortUrl}
					readOnly="readOnly"
					onFocus={event => {
						event.target.select();
						if (navigator.clipboard) {
							var container = event.target.closest('.shlink-container');
							var copied = container.querySelector('.shlink-copied');
							var text = event.target.value;
							navigator.clipboard.writeText(text).then(function() {
								copied.classList.add('visible');
							}, function(err) {
								console.log('error copying');
							});
						}
					}}
					onBlur={event => {
						var container = event.target.closest('.shlink-container');
						var copied = container.querySelector('.shlink-copied');
						copied.classList.remove('visible');
					}}
				/>
			);
		} else {
			return 'This post has no short URL.';
		}
	};

	registerPlugin('shlink', {
		render: () => {
			return (
				<PluginDocumentSettingPanel
					name="shlink-panel"
					title="Shlink"
					className="shlink-editor-sidebar">
					<div className="shlink-container">
						<ShortUrl />
						<div className="shlink-copied">copied to clipboard</div>
					</div>
				</PluginDocumentSettingPanel>
			)
		},
		icon: false
	});

})(window.wp);
