## Run locally using Docker

The application can be run on your local machine using [Docker](https://www.docker.com/products/docker-desktop). You only need Docker Desktop installed — PHP and Composer run inside the container.

1. Clone the git repository to your local machine.
2. Start the containers:
   ```sh
   docker compose up -d
   ```
3. Run database migrations from the php container:
   ```sh
   docker exec -it balance-transfer-symfony-1 /bin/bash
   ```
   then
   ```sh
   php bin/console doctrine:migrations:migrate
   ```
4. Load database fixtures:
   ```sh
   php bin/console doctrine:fixtures:load
   ```

> **Note:** If using VS Code with the [Dev Containers](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) extension, you can open this project with "Reopen in Container". The `post-create.sh` script will automatically install dependencies, run migrations, and load fixtures.

#### Application URL
When running locally, the development URL for your application will be:
```
http://localhost:8000
```

#### Database
You can access the application database using a MySQL client with the following parameters:

| Parameter     | Value               |
|---------------|---------------------|
| Hostname      | `127.0.0.1`         |
| Port          | `13306`             |
| User          | `symfony` or `root` |
| Password      | `symfony`           |
| Database Name | `app`               |

----

## Testing
[PHPUnit](https://phpunit.readthedocs.io/en/9.5/) unit tests, functional tests and application tests are located in the `tests/` directory.

To run the test suite, exec into the php container first:
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
