## Architecture

This is a balance transfer application with a **Symfony 5 JSON API** backend and a **Vue.js 3** frontend.

- **Backend:** Symfony 5.3 / PHP 8.0 — serves API endpoints at `/api/*`
- **Frontend:** Vue 3 + Vite + Tailwind CSS — single-page application served as static files
- **Database:** MySQL 8.0
- **Web server:** Nginx serves the Vue app at `/` and proxies `/api` requests to PHP-FPM

All services run in Docker. Port 8000 is the only port exposed.

---

## Quick Start

You only need [Docker Desktop](https://www.docker.com/products/docker-desktop) installed.

1. Clone the git repository to your local machine.
2. Start the application:

   **Windows:**
   ```
   start.bat
   ```

   **Linux / Mac:**
   ```sh
   chmod +x start.sh
   ./start.sh
   ```

   This builds and starts in **prod mode** by default (OPcache enabled, Xdebug off, Symfony cache warmed).

3. Open the application at `http://localhost:8000`

#### Development Mode

To run with dev-friendly settings (OPcache off, Xdebug in develop mode, file changes reflect immediately):

**Windows:**
```
start.bat dev
```

**Linux / Mac:**
```sh
./start.sh dev
```

---

## Manual Setup (alternative)

If you prefer not to use the start scripts:

1. Build the Vue frontend:
   ```sh
   cd frontend
   npm install
   npm run build
   cd ..
   ```
2. Start the containers:
   ```sh
   docker compose up -d --build
   ```
3. Run database migrations and load fixtures:
   ```sh
   docker exec -it balance-transfer-symfony-1 /bin/bash
   ```
   then
   ```sh
   php bin/console doctrine:migrations:migrate --no-interaction
   php bin/console doctrine:fixtures:load --no-interaction
   ```

> **Note:** If using VS Code with the [Dev Containers](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) extension, you can open this project with "Reopen in Container". The `post-create.sh` script will automatically install dependencies, build the frontend, run migrations, and load fixtures.

---

## Environment Modes

| Setting         | prod (default)                   | dev                              |
|-----------------|----------------------------------|----------------------------------|
| OPcache         | Enabled, no file revalidation    | Disabled                         |
| Xdebug          | Completely removed               | Develop mode (nice error pages)  |
| Symfony cache   | Pre-warmed at startup            | Built on first request           |
| Performance     | Fast (sub-second responses)      | Slower (convenience over speed)  |

The mode is controlled by the `APP_ENV` environment variable, which the start scripts set for you.

---

## API Endpoints

| Method | Endpoint             | Description              |
|--------|----------------------|--------------------------|
| GET    | `/api/customers`     | List all customers       |
| GET    | `/api/customers/{id}`| Get a single customer    |
| GET    | `/api/transfers`     | List all transfers       |
| POST   | `/api/transfers`     | Create a new transfer    |

**POST `/api/transfers`** expects JSON:
```json
{
  "senderId": 1,
  "recipientId": 2,
  "amount": 50.00
}
```

---

## Database

You can access the application database using a MySQL client:

| Parameter     | Value               |
|---------------|---------------------|
| Hostname      | `127.0.0.1`         |
| Port          | `13306`             |
| User          | `symfony` or `root` |
| Password      | `symfony`           |
| Database Name | `app`               |

---

## Frontend Development

The Vue.js frontend lives in `frontend/`. After making changes:

**Option A — Rebuild inside the container:**
```sh
docker exec -it balance-transfer-symfony-1 bash -c "cd frontend && npm run build"
```

**Option B — Rebuild locally:**
```sh
cd frontend
npm run build
```

**Option C — Use Vite dev server for hot-reload (local Node.js required):**
```sh
cd frontend
npm run dev
```
This starts a dev server at `http://localhost:5173` with API requests proxied to `http://localhost:8000`.

---

## Testing

[PHPUnit](https://phpunit.readthedocs.io/en/9.5/) unit tests, functional tests and application tests are located in the `tests/` directory.

To run the test suite, exec into the container first:
```sh
docker exec -it balance-transfer-symfony-1 /bin/bash
```

Then follow these steps:

1. Create the test database (if not already created):
   ```sh
   php bin/console --env=test doctrine:database:create
   ```
2. Run migrations in the test database:
   ```sh
   php bin/console --env=test doctrine:migrations:migrate
   ```
3. Load fixtures in the test database:
   ```sh
   php bin/console --env=test doctrine:fixtures:load
   ```
4. Run the tests:
   ```sh
   php ./vendor/bin/phpunit tests/
   ```
