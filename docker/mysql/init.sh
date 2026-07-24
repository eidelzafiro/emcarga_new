#!/bin/bash
set -e

echo "=== EMCARGA: Creating databases ==="
mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<EOF
CREATE DATABASE IF NOT EXISTS emcarga CHARACTER SET utf8 COLLATE utf8_unicode_ci;
CREATE DATABASE IF NOT EXISTS emcarga_new CHARACTER SET utf8 COLLATE utf8_unicode_ci;
EOF

echo "=== EMCARGA: Importing legacy data ==="
mysql -u root -p"$MYSQL_ROOT_PASSWORD" emcarga < /docker-entrypoint-initdb.d/emcarga-dump.data

echo "=== EMCARGA: Init complete ==="
