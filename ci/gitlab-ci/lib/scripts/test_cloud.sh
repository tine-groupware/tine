test_cloud_deploy() {
    export DEPLOYMENT_NAME=${CI_ENVIRONMENT_SLUG}
    export DEPLOYMENT_IMAGE_TAG=${TEST_CLOUD_DEPLOY_DEPLOYMENT_IMAGE_TAG_OVERWRITE:-$(release_get_package_version)}

    echo $DEPLOYMENT_NAME $DEPLOYMENT_IMAGE_TAG

    # todo: but later (is not mvp)
    # if [ "$RELEASE_TYPE" == "nightly" ]; then
    #     helmfile -f path/to/helmfile.yaml destroy
    #     # remove jobs 
    #     # fail for pvc to be deleted
    # fi

    # other functions need thees values
    helmfile -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/test-cloud/helmfile.yaml write-values --output-file-template=/tmp/values.yaml

    test_cloud_setup_database

    helmfile -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/test-cloud/helmfile.yaml sync
}

test_cloud_teardown() {
    export DEPLOYMENT_NAME=${CI_ENVIRONMENT_SLUG}
    export DEPLOYMENT_IMAGE_TAG=${TEST_CLOUD_DEPLOY_DEPLOYMENT_IMAGE_TAG_OVERWRITE:-$(release_get_package_version)}

    echo $DEPLOYMENT_NAME $DEPLOYMENT_IMAGE_TAG

    # other functions need thees values
    helmfile -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/test-cloud/helmfile.yaml write-values --output-file-template=/tmp/values.yaml

    helmfile -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/test-cloud/helmfile.yaml destroy

    test_cloud_teardown_database

    kubectl --context tine20/gitlab-agent:k8s-se01 -n test-tine delete jobs.batch -l app.kubernetes.io/instance=tine-${DEPLOYMENT_NAME},app.kubernetes.io/name=tine
}

test_cloud_mariadb() {
    # helmfile write-values needs to run before!

    db_host=$(cat /tmp/values.yaml | yq .database.host)
    db_username=$(cat /tmp/values.yaml | yq .database.username)
    db_password=$(cat /tmp/values.yaml | yq .database.password)

    mysql -h ${db_host} -u ${db_username} -p${db_password} --skip-ssl
}

test_cloud_setup_database() {
    db_name=$(cat /tmp/values.yaml | yq .database.name)

    echo 'CREATE DATABASE IF NOT EXISTS `'${db_name}'`;' | test_cloud_mariadb
}

test_cloud_teardown_database() {
    db_name=$(cat /tmp/values.yaml | yq .database.name)

    echo 'DROP DATABASE IF EXISTS `'${db_name}'`;' | test_cloud_mariadb
}