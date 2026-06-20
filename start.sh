#!/bin/bash

MODE="${1:-prod}"

echo "Starting in $MODE mode..."

APP_ENV="$MODE" docker compose build symfony
APP_ENV="$MODE" docker compose up -d --force-recreate symfony

echo ""
echo "Application running at http://localhost:8000 ($MODE mode)"
if [ "$MODE" = "dev" ]; then
    echo "  - OPcache disabled, file changes reflect immediately"
    echo "  - Xdebug in develop mode"
else
    echo "  - OPcache enabled for fast responses"
    echo "  - Xdebug disabled"
fi
