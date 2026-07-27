# Imagen mysql con scripts de init horneados (blindaje anti bind-mounts rotos en WSL2)
# init.sh + emcarga-dump.data solo se ejecutan en la PRIMERA inicialización
# del volumen de datos. Si cambian: docker compose build mysql
FROM mysql:8.0

COPY docker/mysql/ /docker-entrypoint-initdb.d/
