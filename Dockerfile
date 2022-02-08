FROM wordpress:5.9.0-php7.4-apache

# Dependencies
RUN apt-get update && apt-get install -y \
	mariadb-client \
	subversion;

# Setup WP-CLI
# See: https://wp-cli.org/
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; \
	chmod +x wp-cli.phar; \
	mv wp-cli.phar /usr/local/bin/; \
	# Workaround for root usage scolding.
	echo -e "#!/bin/bash\n\n/usr/local/bin/wp-cli.phar \"\$@\" --allow-root\n" > /usr/local/bin/wp; \
	chmod +x /usr/local/bin/wp;

# Setup debug.log
# See: https://wordpress.org/support/article/debugging-in-wordpress/#wp_debug_log
RUN touch /tmp/debug.log; \
	chown www-data:www-data /tmp/debug.log;

# Setup composer
# See: https://getcomposer.org/download/
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
	php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"; \
	php composer-setup.php; \
	php -r "unlink('composer-setup.php');"; \
	mv composer.phar /usr/local/bin/composer; \
	chmod +x /usr/local/bin/composer
