#!/bin/bash
echo -n 'wait for signal_mount_ready ...'; while [ ! -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_mount_ready ]; do sleep 1; done; echo ' done'

test_prepare_working_dir
touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_files_ready

test_composer_install
touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_php_deps_installed

test_npm_install
touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_js_deps_installed

rm /etc/confd/conf.d/worker.inc.php.toml || true # todo: is it needed? can it be moved to test_prepare_config
test_prepare_global_configs

# setup database
if ! tine20_await_db; then
    touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_wait_for_database_failed
    touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_tine_ready
    exit 1
fi

test_prepare_mail_db

# setup tine enviroment
touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/${CI_PROJECT_NAMESPACE}/tine20.log
chown tine20:tine20 ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/${CI_PROJECT_NAMESPACE}/tine20.log

echo -n 'wait for signal_js_deps_installed ...'; while [ ! -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_js_deps_installed ]; do sleep 1; done; echo ' done'

# insteall tine
tine20_install;

if [ -f ${TINE20ROOT}/scripts/postInstallGitlab.sh ]; then
    ${TINE20ROOT}/scripts/postInstallGitlab.sh;
fi;

# install demodata
if [ -z "$TINE_DEMODATASET" ]; then
    su tine20 -c "tine20.php --method Tinebase.createAllDemoData  --username=${TINE20_LOGIN_USERNAME} --password=${TINE20_LOGIN_PASSWORD}" || touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_demo_data_install_failed
else
    su tine20 -c "tine20.php --method Tinebase.createAllDemoData  --username=${TINE20_LOGIN_USERNAME} --password=${TINE20_LOGIN_PASSWORD}" -- demodata=set set=$TINE_DEMODATASET || touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_demo_data_install_failed
fi;

touch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/signal_tine_ready

# start tine
supervisord --nodaemon