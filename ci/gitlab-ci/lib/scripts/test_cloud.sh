test_cloud_generate_deployment_name() {
    deployment_name=$MAJOR_COMMIT_REF_NAME

    if [ "$RELEASE_TYPE" == "nightly" ]; then
        deployment_name=nightly-$deployment_name
    fi

    echo -n $deployment_name | sed 's/\./-/g' | sed 's/\//-/g'
}

test_cloud_deploy() {
    export DEPLOYMENT_NAME=$(test_cloud_generate_deployment_name)
    export DEPLOYMENT_IMAGE_TAG=${TEST_CLOUD_DEPLOY_DEPLOYMENT_IMAGE_TAG_OVERWRITE:-$(release_get_package_version)}

    echo $DEPLOYMENT_NAME $DEPLOYMENT_IMAGE_TAG

    # todo: but later (is not mvp)
    # if [ "$RELEASE_TYPE" == "nightly" ]; then
    #     helmfile -f path/to/helmfile.yaml destroy
    #     # remove jobs 
    #     # fail for pvc to be deleted
    # fi

    helmfile -f ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/test-cloud/helmfile.yaml sync
}