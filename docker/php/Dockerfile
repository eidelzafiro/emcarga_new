# Imagen app/reverb — overlay offline sobre base local.
#
# FROM emcarga-php-base:8.4 — imagen local con PHP 8.4-fpm + extensiones
# (pdo_mysql, mbstring, gd, intl, zip...) + Composer 2.10.2.
#
# ¿Por qué no FROM php:8.4-fpm? Docker Hub bloquea Cuba (403) y Debian
# tampoco es alcanzable para apt-get. La base completa se construye con
# docker/php/Dockerfile.full cuando HAY acceso a Hub (VPN/staging):
#
#   docker build -f docker/php/Dockerfile.full -t emcarga-php-base:8.4 .
#
# Si no hay acceso a Hub, la base ya existe localmente y este build
# funciona 100% offline. NO borrar la imagen emcarga-php-base:8.4.
FROM emcarga-php-base:8.4

# Config PHP horneada en la imagen (blindaje anti bind-mounts rotos en WSL2)
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
