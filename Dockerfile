FROM wordpress:php8.1

# Dependencies
RUN apt-get update && apt-get install -y \
	less \
	mariadb-client \
	subversion \
	unzip;

# Setup WP-CLI
# See: https://wp-cli.org/
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; \
	chmod +x wp-cli.phar; \
	mv wp-cli.phar /usr/local/bin/; \
	echo "#!/bin/bash\n\n/usr/local/bin/wp-cli.phar \"\$@\" --allow-root\n" > /usr/local/bin/wp; \
	chmod +x /usr/local/bin/wp;

# Setup debug.log
# See: https://wordpress.org/support/article/debugging-in-wordpress/#wp_debug_log
RUN touch /var/log/wordpress.debug.log; \
	chown www-data:www-data /var/log/wordpress.debug.log;

# Setup composer
# See: https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
ADD ./scripts/composer-install.sh /script/install-composer.sh
RUN chmod +x /script/install-composer.sh; \
	/script/install-composer.sh; \
	mv composer.phar /usr/local/bin/composer; \
	chmod +x /usr/local/bin/composer;
