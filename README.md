## Run locally using Docker
The application can be run on your local machine using [Docker](https://www.docker.com/products/docker-desktop). You will need to have PHP 8.0 and [Composer](https://getcomposer.org/download/) installed.

1. Clone the git repository to your local machine.
2. Install dependencies:
   ```sh
   composer install
   ```
3. Start the containers:
   ```sh
   docker compose up -d
   ```
4. Run database migrations:
   ```sh
   php bin/console doctrine:migrations:migrate
   ```
5. Load database fixtures:
   ```sh
   php bin/console doctrine:fixtures:load
   ```

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
| Port          | `13306`              |
| User          | `symfony` or `root` |
| Password      | `symfony`           |
| Database Name | `app`               |

----

## Testing
[PHPUnit](https://phpunit.readthedocs.io/en/9.5/) unit tests, functional tests and application tests are located in the `tests/` directory. The test suite will be automatically run on each commit when pushed to GitHub.

To run the test suite in your workspace, follow these steps:

1. Create the test database:
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
   php ./vendor/bin/phpunit
   ```
