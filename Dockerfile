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
# See: https://getcomposer.org/download/
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
	php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"; \
	php composer-setup.php; \
	php -r "unlink('composer-setup.php');"; \
	mv composer.phar /usr/local/bin/composer; \
	chmod +x /usr/local/bin/composer
