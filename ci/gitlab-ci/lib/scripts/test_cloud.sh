# DEPLOYMENT_NAME should a an dns label
# DEPLOYMENT_NAME must not excide a specific length. The limiting factor is that the email user name is not allowed
# to be longer than 80 charts. The email user name contains a 40 char hash and an @ leaving 39 for DEPLOYMENT_NAME and
# DEPLOYMENT_BASE_DOMAIN (and a dot).
test_cloud_generate_deployment_name() {
    DEPLOYMENT_NAME=$(echo -n $CI_ENVIRONMENT_NAME | sed 's/\./-/g' | sed 's/\//-/g')

    if [[ $(echo -n $DEPLOYMENT_NAME.$DEPLOYMENT_BASE_DOMAIN | wc -c) -le 39 ]]; then
        echo $DEPLOYMENT_NAME
        return
    fi

    # replace nightly- with n-, if it is enough to stay under the limit 
    if [[ "$DEPLOYMENT_NAME" == "nightly-"* ]] && [[ $(echo -n $DEPLOYMENT_NAME.$DEPLOYMENT_BASE_DOMAIN | wc -c) -le 45 ]]; then
        echo n${DEPLOYMENT_NAME#"nightly"}
        return
    fi

    # replace review- with r-, if it is enough to stay under the limit 
    if [[ "$DEPLOYMENT_NAME" == "review-"* ]] && [[ $(echo -n $DEPLOYMENT_NAME.$DEPLOYMENT_BASE_DOMAIN | wc -c) -le 44 ]]; then
        echo r${DEPLOYMENT_NAME#"review"}
        return
    fi

    #add hash to prevent name collisions
    HASH=$(echo -n $DEPLOYMENT_NAME | md5sum | cut -c -4)

    echo $(echo -n $DEPLOYMENT_NAME | cut -c -$((80-41-$(echo -n "-$HASH.$DEPLOYMENT_BASE_DOMAIN" | wc -c))))-$HASH
}

test_cloud_deploy() {
    export DEPLOYMENT_NAME=$(test_cloud_generate_deployment_name)
    export DEPLOYMENT_IMAGE_TAG=${TEST_CLOUD_DEPLOY_DEPLOYMENT_IMAGE_TAG_OVERWRITE:-$(release_get_package_version)}

    echo $DEPLOYMENT_NAME $DEPLOYMENT_IMAGE_TAG

    if [ "$DEPLOY_TYPE" == "nightly" ]; then
        test_cloud_teardown
        if kubectl --context tine20/gitlab-agent:k8s-se01 -n test-tine get pvc tine-$DEPLOYMENT_NAME-tine-data; then
            kubectl --context tine20/gitlab-agent:k8s-se01 -n test-tine wait pvc tine-$DEPLOYMENT_NAME-tine-data --for=delete --timeout 5m
        fi
    fi

    # other functions need thees values
    helmfile -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/test-cloud/helmfile.yaml write-values --output-file-template=/tmp/values.yaml

    test_cloud_setup_database

    helmfile -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/test-cloud/helmfile.yaml sync

    echo DYNAMIC_ENVIRONMENT_URL=https://$DEPLOYMENT_NAME.$DEPLOYMENT_BASE_DOMAIN/ > deploy.env
}

test_cloud_teardown() {
    export DEPLOYMENT_NAME=$(test_cloud_generate_deployment_name)
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