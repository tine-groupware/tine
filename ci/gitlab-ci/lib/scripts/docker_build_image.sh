docker_build_image_dev() {
    log dev image: building ...
    image="${REGISTRY}/dev-commit:${IMAGE_TAG}"
    # cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
    docker build \
        --target dev \
        --tag ${image} \
        --file ./ci/dockerimage/Dockerfile \
        --build-arg PHP_IMAGE=php${PHP_VERSION} \
        .

    log dev image: pushing ...
    docker push ${image}
}

docker_build_image_test() {
    log test image: building ...
    commit_ref_name_escaped=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)
    image=${TEST_IMAGE_REGISTRY}:${commit_ref_name_escaped}-${PHP_VERSION}-${TEST_IMAGE_VERSION}
    # cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
    docker build \
        --target test \
        --tag ${image} \
        --file ./ci/dockerimage/Dockerfile \
        --build-arg PHP_IMAGE=php${PHP_VERSION} \
        .

    log test image: pushing ...
    docker push ${image}
}

docker_build_image_built() {
    log built image: building ...
    image="${REGISTRY}/built-commit:${IMAGE_TAG}"
    # cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
    docker build \
        --target built \
        --tag ${image} \
        --file ./ci/dockerimage/Dockerfile \
        --build-arg PHP_IMAGE=php${PHP_VERSION} \
        --build-arg JSDEPENDENCY_IMAGE \
        --build-arg ZIP_PACKAGES \
        --build-arg RELEASE_TYPE \
        --build-arg CUSTOM_APP_VENDOR \
        --build-arg CUSTOM_APP_NAME \
        --build-arg CUSTOM_APP_GIT_URL \
        --build-arg CUSTOM_APP_VERSION \
        --build-arg RELEASE \
        --build-arg CODENAME \
        --build-arg REVISION \
        .

    log built image: pushing ...
    docker push ${image}
}

docker_build_image_built_test() {
    log test-built image: building ...
    image="${REGISTRY}/test-built-commit:${IMAGE_TAG}"
    # cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
    docker build \
        --target test-built \
        --tag ${image} \
        --file ./ci/dockerimage/Dockerfile \
        --build-arg PHP_IMAGE=php${PHP_VERSION} \
        --build-arg JSDEPENDENCY_IMAGE \
        --build-arg ZIP_PACKAGES \
        --build-arg RELEASE_TYPE \
        --build-arg CUSTOM_APP_VENDOR \
        --build-arg CUSTOM_APP_NAME \
        --build-arg CUSTOM_APP_GIT_URL \
        --build-arg CUSTOM_APP_VERSION \
        --build-arg RELEASE \
        --build-arg CODENAME \
        --build-arg REVISION \
        .

    log test-built image: pushing ...
    docker push ${image}
}

docker_build_image_packages() {
    outputPath=$1

    log packages image: building ...
    # cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
    docker build \
        --target packages \
        --file ./ci/dockerimage/Dockerfile \
        --build-arg BUILT_IMAGE \
        --build-arg ZIP_PACKAGES \
        --build-arg RELEASE \
        --build-arg CODENAME \
        --build-arg REVISION \
        -o type=tar,dest=${outputPath} \
        .
}