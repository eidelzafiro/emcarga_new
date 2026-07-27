# Imagen nginx con la config horneada (blindaje anti bind-mounts rotos en WSL2)
# Si editas docker/nginx/conf.d/app.conf: docker compose build nginx
FROM nginx:alpine

COPY docker/nginx/conf.d/ /etc/nginx/conf.d/
