# jest tests

## run tests
run all tests (using npm command)

    ./console src:npm 'test'

run one test file only

    ./console src:npm 'test -- Array.test.js'

run on test only

    ./console src:npm 'test -- Array.test.js --testNamePattern "Array.diff"'

## debuging tests
run tests in dbg mode

    ./console src:npm 'run test-dbg'

and attach a debugger, use [phpstorm](https://www.jetbrains.com/help/phpstorm/running-and-debugging-node-js.html#ws_node_debug_remote_chrome) or [chrome](chrome://inspect)

## core concepts
* pure logic tests, no DOM interaction / testing
* no globals pollution (e.g. Tine, Ext, _, ...)
  * neither in tests nor in program code to be tested
  * you might need to rewrite / split your code so it stick to this rule