FROM wordpress:php7.4-fpm-alpine

# Dependencies
RUN apk update && apk add --no-cache \
	mariadb-client \
	composer \
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
# See: https://getcomposer.org/
RUN chmod +x /usr/bin/composer.phar; \
	# Override default script that forces PHP 8.
	echo -e "#!/bin/sh\n\n/usr/bin/composer.phar \"\$@\"" > /usr/bin/composer;
