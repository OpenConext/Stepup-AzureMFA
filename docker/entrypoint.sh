#!/bin/sh

PARAM_FILE="/var/www/html/config/openconext/parameters.yaml"
CACHE_DIR=$(grep -E '^ *federation_metadata_cache_location:' "$PARAM_FILE" \
           | cut -d ':' -f2- | tr -d '[:space:]')

if [ -z "$CACHE_DIR" ]; then
    CACHE_DIR="/var/www/html/federation-metadata"
fi

echo "Metadata cache‑map: $CACHE_DIR"

if [ -d "$CACHE_DIR" ]; then
    echo "Leegmaken van Metadata cache‑map $CACHE_DIR"
    rm -rf "$CACHE_DIR"/* 2>/dev/null
fi

exec "$@"