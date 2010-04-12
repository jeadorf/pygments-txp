# Generate the base-64-encoded plugin content
compile:
	php pygments_txp_plugin.php

# Workaround to avoid shell and Makefile $txpcfg expansion 
t=$$t

include Makefile.conf
TXPATH=${TESTENV}/textpattern

# Generate a test environment that can be used for testing the plugin and the
# pygments CGI.  In order to use this target you must configure Makefile.conf
# to provide user credentials for your local MYSQL database. Make sure that the
# SQL user has access to the test database only.  The database must already
# exist.
.PHONY: test-env
test-env:
	# Install textpattern
	mkdir -p ${TESTENV} 
	rm -r ${TESTENV}/*
	cd ${TESTENV} && wget http://textpattern.com/file_download/56/textpattern-4.2.0.tar.gz
	cd ${TESTENV} && tar xzf textpattern-4.2.0.tar.gz && mv textpattern-4.2.0/* . && rm -r textpattern-4.2.0
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
	mysql --user=${DBUSER} --password=${DBPASSWD} --host=${DBHOST} ${DBNAME} < txp.sql

# Installs the plugin into the textpattern installation and copies the python
# CGI to the test environment.
test-deploy:
	# Install plugin
	php5 pygments_txp_plugin.php | mysql --user=${DBUSER} --password=${DBPASSWD} --host=${DBHOST} ${DBNAME}
	# Install python CGI
	cp pygmentize_cgi.py ${TESTENV}
	chmod 755 ${TESTENV}/pygmentize_cgi.py

