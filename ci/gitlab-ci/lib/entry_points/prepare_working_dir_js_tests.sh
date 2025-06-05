#!/bin/bash
echo -n 'wait for signal_mount_ready ...'; while [ ! -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/signal_mount_ready ]; do sleep 1; done; echo ' done'

test_prepare_working_dir
touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_files_ready

test_composer_install
touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_php_deps_installed
