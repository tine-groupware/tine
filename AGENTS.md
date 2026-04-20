# instructions for ai agents

see https://agents.md/

## testing instructions (PHP)

- TINE_DOCKER_PATH should exist in the shell env (export TINE_DOCKER_PATH=/path/to/your/tine-docker)
- make sure, the tine dev env is running: cd $TINE_DOCKER_PATH && ./console docker up -d
- run test with: cd $TINE_DOCKER_PATH && ./console tine:test <TestClassName>::<TestMethodName>
- see docs/developers/server/phpunit.md for additional info

## run phpstan and phpcs before committing

- run phpstan: ./vendor/bin/phpstan analyse -c ../phpstan.neon FILE.php
- run phpcs: cd tine20 && composer run phpcs FILE.php
