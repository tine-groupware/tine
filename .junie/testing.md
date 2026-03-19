### Running Tests in tine Groupware

TODO: this is also referenced in the developer documentation (docs/developers/server/phpunit.md) - we should make sure they're in sync.

To run tests in the tine Groupware environment, use the `console` script located in the `tine-docker` directory.

The tine-docker (or called tine-dev / docker-dev) repository can be found here: https://github.com/tine-groupware/tine-dev

#### Configuration
The path to the `tine-docker` environment can be different for each developer. To make the commands below work, set the `TINE_DOCKER_PATH` environment variable:

```bash
export TINE_DOCKER_PATH=/path/to/your/tine-docker
```

On this machine, the path is `/data/workspace/tine-docker`.

You can also add this to your `~/.bashrc` or `~/.zshrc` to make it permanent.

#### Basic Command
The general syntax for running a test is:
```bash
cd $TINE_DOCKER_PATH
./console tine:test <TestClassName>::<TestMethodName>
```

#### Example
To run the `testCreateSchedulerTask` method from the `Admin_Controller_SchedulerTaskTest` class:
```bash
cd $TINE_DOCKER_PATH
./console tine:test Admin_Controller_SchedulerTaskTest::testCreateSchedulerTask
```

#### Notes
- The command should be executed from the `$TINE_DOCKER_PATH` directory.
- The `tine:test` command uses PHPUnit under the hood to execute the specified tests.
