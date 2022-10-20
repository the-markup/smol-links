FROM wordpress

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
RUN touch /tmp/debug.log; \
	chown www-data:www-data /tmp/debug.log;

# Setup composer
# See: https://getcomposer.org/download/
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
	php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"; \
	php composer-setup.php; \
	php -r "unlink('composer-setup.php');"; \
	mv composer.phar /usr/local/bin/composer; \
	chmod +x /usr/local/bin/composer
