# TheSchool

A modernized school management application built with Laravel, jQuery, Docker, automated testing, and CI quality gates.

The project originated as a legacy PHP application and was rebuilt around Laravel while preserving the original application's core domain: administrators, students, courses, authentication, and role-based authorization.

The main purpose of the project is to demonstrate backend modernization, automated testing, containerization, and DevOps practices.

## Features

### Administration

- Administrator authentication
- Role-based authorization
- Administrator management
- Student management
- Course management
- Student-to-course relationships
- Image upload and replacement
- Protected owner account
- Validation and error handling

### Roles

The application supports three administrator roles:

| Role | Administrators | Students | Courses |
| --- | --- | --- | --- |
| Owner | Manage all | Manage | Manage |
| Manager | Manage non-owner accounts | Manage | Manage |
| Sales | No access | Manage | View |

Additional restrictions protect the `owner` role:

- New owners cannot be created through the administrator API.
- The owner role cannot be assigned to another administrator.
- Non-owner administrators cannot modify the owner.
- The owner account cannot be deleted.

## Technology Stack

### Backend

- PHP 8.5
- Laravel 13
- MariaDB
- Redis

### Frontend

- JavaScript
- jQuery
- Vite
- HTML/CSS

### Infrastructure

- Docker
- Docker Compose
- Apache
- MariaDB
- Redis

### Testing and Code Quality

- PHPUnit
- Cypress
- PHPStan / Larastan
- PHP_CodeSniffer
- PHP-CS-Fixer
- Infection mutation testing
- Semgrep
- Checkov
- Trivy

### CI/CD

GitHub Actions runs automated testing and quality checks on application changes. Version tags trigger a separate Docker release workflow that scans images with Trivy before publishing them to Docker Hub.

The pipeline includes:

- PHPUnit feature tests
- PHPUnit integration tests against the production MariaDB schema using `docker-compose.test.yml`
- Cypress end-to-end tests
- frontend build validation
- PHPStan static analysis
- PHPCS
- PHP-CS-Fixer validation
- Infection mutation testing
- Semgrep security analysis
- Checkov infrastructure/container checks
- Trivy container-image scanning for tagged releases

## Mutation Testing

The project uses Infection to evaluate the effectiveness of the PHPUnit test suite rather than relying only on code coverage.

Current result:

```text
Mutation Code Coverage: 100%
Mutation Score Indicator (MSI): 80.86%
Killed mutants: 207
Escaped mutants: 49
Total mutants: 256
```

Mutation testing helped identify tests that executed code without sufficiently verifying its behavior.

## Project Structure

```text
TheSchool/
├── .github/
│   └── workflows/
├── cypress/
├── redis/
├── server/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   │   └── Resources/
│   │   └── Models/
│   ├── bootstrap/
│   │   └── cache/
│   ├── config/
│   ├── database/
│   │   └── seeders/
│   ├── public/
│   │   └── img/
│   ├── resources/
│   ├── routes/
│   ├── tests/
│   │   └── Feature/
│   ├── uploads/
│   ├── .dockerignore
│   ├── .editorconfig
│   ├── .env.example
│   ├── .gitattributes
│   ├── .gitignore
│   ├── .php-cs-fixer.php
│   ├── artisan
│   ├── composer.json
│   ├── composer.lock
│   ├── docker/
│   │   ├── docker-entrypoint.sh
│   │   └── dev-entrypoint.sh
│   ├── Dockerfile
│   ├── infection.json5
│   ├── package-lock.json
│   ├── package.json
│   ├── phpcs.xml
│   ├── phpstan.neon
│   ├── phpunit.xml
│   └── vite.config.js
├── sql/
├── .gitignore
├── .hintrc
├── cypress.config.js
├── docker-compose.yml
├── LICENSE
├── package-lock.json
├── package.json
└── README.md
```

## API Endpoints

All endpoints use the `/api` prefix. Except for login, all endpoints require an authenticated administrator session. Access is further restricted by role.

### Authentication

| Method | Endpoint | Description | Access |
| --- | --- | --- | --- |
| `POST` | `/api/login` | Authenticate an administrator | Public |
| `POST` | `/api/logout` | End the current session | Authenticated |
| `GET` | `/api/auth` | Return the authenticated administrator | Authenticated |

### Administrators

| Method | Endpoint | Description | Roles |
| --- | --- | --- | --- |
| `GET` | `/api/administrator` | List all administrators | Owner, Manager |
| `GET` | `/api/administrator/{id}` | Get an administrator by ID | Owner, Manager |
| `GET` | `/api/administrator/count` | Get the total number of administrators | Owner, Manager |
| `POST` | `/api/administrator` | Create an administrator | Owner, Manager |
| `PUT` | `/api/administrator/{id}` | Update an administrator | Owner, Manager |
| `DELETE` | `/api/administrator/{id}` | Delete an administrator | Owner, Manager |

The owner account has additional protection and cannot be created, reassigned, or deleted through these endpoints.

### Students

| Method | Endpoint | Description | Roles |
| --- | --- | --- | --- |
| `GET` | `/api/student` | List all students | Owner, Manager, Sales |
| `GET` | `/api/student/{id}` | Get a student by ID | Owner, Manager, Sales |
| `POST` | `/api/student` | Create a student | Owner, Manager, Sales |
| `PUT` | `/api/student/{id}` | Update a student | Owner, Manager, Sales |
| `DELETE` | `/api/student/{id}` | Delete a student | Owner, Manager, Sales |

### Courses

| Method | Endpoint | Description | Roles |
| --- | --- | --- | --- |
| `GET` | `/api/course` | List all courses | Owner, Manager, Sales |
| `GET` | `/api/course/{id}` | Get a course by ID | Owner, Manager, Sales |
| `POST` | `/api/course` | Create a course | Owner, Manager |
| `PUT` | `/api/course/{id}` | Update a course | Owner, Manager |
| `DELETE` | `/api/course/{id}` | Delete a course | Owner, Manager |

### Utils

| Method | Endpoint | Description | Access |
| --- | --- | --- | --- |
| `GET` | `/api/help` | Display the API documentation | Authenticated |
| `GET` | `/api/health` | Check the application health status | Public |

Authenticated administrators can access detailed API documentation at `/api/help`.

## Running the Application

### Requirements

The recommended way to run the project is Docker.

You need:

- Docker
- Docker Compose

The PHP application targets PHP 8.5 or later.

### Start the environment

From the project root:

```bash
docker compose up -d --build
```

The application is available at:

```text
http://localhost:8090
```

### Stop the environment

```bash
docker compose down
```

## Development with Docker

The development stack includes PHP development dependencies, GD for image
validation, PCOV for mutation testing, and a Vite server with live reload. Source
changes are shared with the containers. Dependencies and application data use
Docker volumes, separate from the production stack and host dependencies.

From the project root, start it with:

```bash
docker compose -f docker-compose.dev.yml up -d --build
```

Open `http://localhost:8090`. The first start installs Composer and npm
dependencies and initializes the sample database from `sql/theschool.sql`. No root
`.env` file is required: the app loads `docker/dev.env` with development
overrides. Its sample credentials and application key are for local development.
The owner login is `joe@mail.com` / `abc123!!`.

Stop the production stack first if it is using port 8090. The development web
and Vite ports are bound to localhost. Vite uses polling so edits also reload
when running Docker Desktop on Windows.

Run checks inside the development container:

```bash
docker compose -f docker-compose.dev.yml exec app vendor/bin/phpstan analyse
docker compose -f docker-compose.dev.yml exec app vendor/bin/phpcs
docker compose -f docker-compose.dev.yml exec app vendor/bin/php-cs-fixer fix --dry-run --diff
```

Run PHPUnit and Infection with the isolated test stack described below.
After changing dependency lockfiles, restart the affected development service
to install the updated dependencies:

```bash
docker compose -f docker-compose.dev.yml restart app vite
```

Stop the development environment with:

```bash
docker compose -f docker-compose.dev.yml down
```

Add `-v` only when you want to delete all development data and dependency volumes.
Before returning to the production frontend build, stop Vite and remove its
generated `server/public/hot` file if it remains after shutdown.

## Running Tests

### Isolated MariaDB tests

Run PHPUnit against the same MariaDB image and SQL initialization used by
production, without starting or changing the development or production stacks:

```bash
mkdir -p server/vendor server/storage server/bootstrap/cache server/uploads
docker compose -f docker-compose.test.yml up --build --abort-on-container-exit --exit-code-from tests
docker compose -f docker-compose.test.yml down
```

The test stack publishes no ports and uses an isolated `school_test` database
stored in memory. It imports `sql/theschool.sql` and removes sample rows before
running tests. Each test's fixtures are rolled back in a transaction. Production,
development, and tests all use the SQL script as the schema source; Laravel
migrations are not used. Queues run synchronously by default, so no Laravel jobs
tables are required.

GitHub Actions runs this stack in the `Phpunit` job,
uploads its JUnit report and Docker logs, and requires it to pass before Cypress
runs. Infection also runs against MariaDB in a separate CI job. The old SQLite
PHPUnit job has been removed.

Dependency and writable application directories use separate test volumes, and
the source mount is read-only. Stop the stack after each run; `down -v` also
removes cached test dependencies and application files. Run PHPUnit and Infection
separately: both services use the same isolated database. Future SQL schema
changes require an explicit upgrade script for existing databases; the init
script runs only when MariaDB initializes a fresh data directory.

To rerun selected tests while the database container is running:

```bash
docker compose -f docker-compose.test.yml run --rm tests --filter=AuthenticationTest
```

### PHPUnit

```bash
docker compose -f docker-compose.test.yml up --build --abort-on-container-exit --exit-code-from tests
```

### PHPStan

```bash
docker compose exec app vendor/bin/phpstan analyse
```

### PHP_CodeSniffer

```bash
docker compose exec app vendor/bin/phpcs
```

### PHP-CS-Fixer

```bash
docker compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff
```

### Infection

```bash
mkdir -p server/vendor server/storage server/bootstrap/cache server/uploads
docker compose -f docker-compose.test.yml up --build --abort-on-container-exit --exit-code-from infection infection
docker compose -f docker-compose.test.yml --profile mutation down
```

Infection uses PCOV and one worker, so mutation processes do not overlap their
database transactions. The minimum MSI remains 80%. CI uploads the mutation
reports and Docker logs as `infection-results`.

### Cypress

Cypress tests cover authentication, authorization, navigation, students, courses, and administrator workflows.

Run the application first and then execute the Cypress test suite using the project's configured frontend environment.

## Testing Strategy

The test suite focuses on observable application behavior rather than implementation details.

Examples include:

- authentication success and failure
- role authorization
- CRUD operations
- validation failures
- duplicate email protection
- owner-account restrictions
- password hashing and preservation
- image upload, replacement, preservation, and deletion
- missing image-file edge cases
- student/course relationship synchronization
- missing-resource responses

Mutation testing is used as an additional quality gate to find weak assertions and untested behavioral branches.

## Security and Quality

The project applies several automated checks:

- Laravel request validation
- role-based access control
- password hashing
- protected authentication routes
- static analysis
- coding-standard validation
- mutation testing
- source-code security scanning
- Docker/infrastructure scanning

Secrets and environment-specific configuration are not intended to be committed to the repository.

## Modernization

This project is based on an older school-management application and demonstrates a modernization workflow.

The modernization includes:

```text
Legacy application
       ↓
Laravel backend
       ↓
Authentication and authorization
       ↓
Automated PHPUnit tests
       ↓
Cypress E2E tests
       ↓
Static analysis and code quality
       ↓
Security scanning
       ↓
Mutation testing
       ↓
GitHub Actions CI
       ↓
Dockerized application
```

Rather than rewriting only the application code, the project adds automated mechanisms for verifying that the modernized application continues to behave correctly.

## Purpose

This repository is a portfolio project demonstrating a combination of:

**PHP backend development + testing + security + DevOps automation**

It is intended to show how a legacy web application can be progressively modernized and surrounded by automated quality gates.

## License

This project is licensed under the [MIT License](LICENSE).
