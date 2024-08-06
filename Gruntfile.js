module.exports = function (grunt) {
	'use strict';

	// Project configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		addtextdomain: {
			options: {
				textdomain: 'smol-links',
			},
			update_all_domains: {
				options: {
					updateDomains: true,
				},
				src: [
					'*.php',
					'**/*.php',
					'!.git/**/*',
					'!bin/**/*',
					'!node_modules/**/*',
					'!tests/**/*',
				],
			},
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt',
				},
				options: {
					screenshot_url: "assets/{screenshot}.jpg",
					post_convert: readme => {
						// Omit the WordPress readme.txt header tags
						readme = readme.replace(/^\*\*(.+?):\*\* .+\s\s\n/gm, '');
						return readme;
					}
				}
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					exclude: ['.git/*', 'bin/*', 'node_modules/*', 'tests/*'],
					mainFile: 'smol-links.php',
					potFilename: 'smol-links.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true,
					},
					type: 'wp-plugin',
					updateTimestamp: true,
				},
			},
		},
	});

	grunt.loadNpmTasks('grunt-wp-i18n');
	grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
	grunt.registerTask('default', ['i18n', 'readme']);
	grunt.registerTask('i18n', ['addtextdomain', 'makepot']);
	grunt.registerTask('readme', ['wp_readme_to_markdown']);

	grunt.util.linefeed = '\n';
};
