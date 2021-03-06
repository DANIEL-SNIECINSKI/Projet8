# PrestaShop UI Tests (Docker version)
This file will explain to you how to run tests with docker-compose.

### LinkChecker
To create shop and run LinkChecker

```bash
docker-compose -f docker-compose.nightly.yml -f docker-compose.tests.yml up --build
docker-compose -f docker-compose.nightly.yml -f docker-compose.tests.yml exec -e COMMAND="check:links" tests bash /tmp/run-tests.sh
```

### Sanity tests
To create shop and run sanity tests

```bash
docker-compose -f docker-compose.nightly.yml -f docker-compose.tests.yml up --build
docker-compose -f docker-compose.nightly.yml -f docker-compose.tests.yml exec -e COMMAND="test:sanity" tests bash /tmp/run-tests.sh
```

### Functional tests
To create shop and run functional tests

```bash
docker-compose -f docker-compose.nightly.yml -f docker-compose.tests.yml up --build
docker-compose -f docker-compose.nightly.yml -f docker-compose.tests.yml exec -e COMMAND="test:functional" tests bash /tmp/run-tests.sh
```

Enjoy :wink: :v:
