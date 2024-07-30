import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import ShortUrl from './short-url';

import '../css/editor.scss';

console.log('hey?');

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
