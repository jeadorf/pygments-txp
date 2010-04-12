.PHONY: info
info:
	@php -f src/plugin_util.php info

.PHONY: plugin
plugin:
	@php -f src/plugin_util.php base64

.PHONY: plugin-sql
plugin-sql:
	@php -f src/plugin_util.php sql

# Workaround to avoid shell and Makefile $txpcfg expansion
t=$$t

include Makefile.conf
TXPATH=${TESTENV}/textpattern

# Generate a test environment that can be used for testing the plugin. In order
# to use this target you must configure Makefile.conf to provide user credentials
# for your local MYSQL database. Make sure that the SQL user has access to the
# test database only.  The database must already exist.
.PHONY: test-env
test-env:
	# Install textpattern
	mkdir -p ${TESTENV}
	rm -r ${TESTENV}/*
	cd ${TESTENV} && wget http://textpattern.com/file_download/56/textpattern-4.2.0.tar.gz
	cd ${TESTENV} && tar xzf textpattern-4.2.0.tar.gz && mv textpattern-4.2.0/* . && rm -r textpattern-4.2.0
	chmod 777 ${TESTENV}/files
	chmod 777 ${TESTENV}/images
	# Generate config.php
	echo "<?php\
		\$txpcfg['db'] = '${DBNAME}';\
		\$txpcfg['user'] = '${DBUSER}';\
		\$txpcfg['pass'] = '${DBPASSWD}';\
		\$txpcfg['host'] = '${DBHOST}';\
		\$txpcfg['table_prefix'] = '';\
		\$txpcfg['txpath'] = '${TXPATH}';\
		\$txpcfg['dbcharset'] = 'utf8';\
		?>" > ${TESTENV}/textpattern/config.php
	# Populate database
	mysql --user=${DBUSER} --password=${DBPASSWD} --host=${DBHOST} ${DBNAME} < src/txp.sql

# Installs the plugin into the textpattern installation
.PHONY: test-deploy
test-deploy:
	# Install plugin
	php -f src/plugin_util.php sql | mysql --user=${DBUSER} --password=${DBPASSWD} --host=${DBHOST} ${DBNAME}

