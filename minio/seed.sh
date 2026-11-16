#!/bin/sh
set -eu

# These credentials are used only by the temporary build process.
export MINIO_ROOT_USER=build_seed
export MINIO_ROOT_PASSWORD=build_seed_password
minio server /data --address 127.0.0.1:9000 >/tmp/minio.log 2>&1 &
minio_pid=$!
trap 'kill "$minio_pid" 2>/dev/null || true; wait "$minio_pid" 2>/dev/null || true' EXIT

attempt=0
until curl --fail --silent http://127.0.0.1:9000/minio/health/ready >/dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        cat /tmp/minio.log
        exit 1
    fi
    sleep 1
done

mc alias set seed http://127.0.0.1:9000 "$MINIO_ROOT_USER" "$MINIO_ROOT_PASSWORD"
mc mb "seed/$MINIO_BUCKET"
mc cp --recursive /seeds/ "seed/$MINIO_BUCKET/"
mc anonymous set none "seed/$MINIO_BUCKET"
