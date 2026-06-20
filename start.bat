@echo off
setlocal

set APP_ENV=prod
if /i "%~1"=="dev" set APP_ENV=dev

echo Starting in %APP_ENV% mode...

docker compose build symfony
docker compose up -d --force-recreate symfony

echo.
echo Application running at http://localhost:8000 (%APP_ENV% mode)
if /i "%APP_ENV%"=="dev" (
    echo   - OPcache disabled, file changes reflect immediately
    echo   - Xdebug in develop mode
)
if /i "%APP_ENV%"=="prod" (
    echo   - OPcache enabled for fast responses
    echo   - Xdebug disabled
)

endlocal
