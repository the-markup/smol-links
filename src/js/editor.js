import '../css/editor.scss';

( ( wp ) => {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { TextControl } = wp.components;
	const { __ } = wp.i18n;

	var ShortUrl = () => {
		var shortUrl = wp.data.useSelect( ( select ) => {
			var meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
			return meta.shlinkify_short_url || null;
		}, [] );

		if ( shortUrl ) {
			return (
				<TextControl
					className="shlinkify-input"
					label={ __( 'Short URL', 'shlinkify' ) }
					value={ shortUrl }
					readOnly="readOnly"
					onFocus={ ( event ) => {
						event.target.select();
						if ( navigator.clipboard ) {
							var container = event.target.closest(
								'.shlinkify-container'
							);
							var copied =
								container.querySelector( '.shlinkify-copied' );
							var text = event.target.value;
							navigator.clipboard.writeText( text ).then(
								function () {
									copied.classList.add( 'visible' );
								},
								function ( err ) {
									console.log( 'error copying' );
								}
							);
						}
					} }
					onBlur={ ( event ) => {
						var container = event.target.closest(
							'.shlinkify-container'
						);
						var copied =
							container.querySelector( '.shlinkify-copied' );
						copied.classList.remove( 'visible' );
					} }
				/>
			);
		} else {
			return 'This post has no short URL.';
		}
	};

	registerPlugin( 'shlinkify', {
		render: () => {
			return (
				<PluginDocumentSettingPanel
					name="shlinkify-panel"
					title="Shlinkify"
					className="shlinkify-editor-sidebar"
				>
					<div className="shlinkify-container">
						<ShortUrl />
						<div className="shlinkify-copied">
							copied to clipboard
						</div>
					</div>
				</PluginDocumentSettingPanel>
			);
		},
		icon: false,
	} );
} )( window.wp );
