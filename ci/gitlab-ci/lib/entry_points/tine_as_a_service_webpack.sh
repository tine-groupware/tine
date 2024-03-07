#!/bin/sh
apk add git;

echo -n 'wait for signal_files_ready ...'; while [ ! -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_files_ready ]; do sleep 1; done; echo ' done'

test_npm_install --ignore-scripts;
touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_js_deps_installed

echo -n 'wait for signal_php_deps_installed ...'; while [ ! -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_php_deps_installed ]; do sleep 1; done; echo ' done'
npm --prefix $TINE20ROOT/tine20/Tinebase/js/ start
