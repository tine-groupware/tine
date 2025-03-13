docker_registry_login () {
    registry="$1"
    username="$2"
    password="$3"

    for i in {1..6}; do
        if docker login "$registry" --username "$username" --password "$password"; then
            return 0
        fi

        echo "($i) docker login failed, retrying it in 5 second ..."
        curl https://${REGISTRY}/fail-${CI_PIPELINE_ID}-${CI_JOB_ID} # create a marker in the log if login fails
        sleep 5
    done

    echo "docker login failed, aborting ..."
    return 1
}

docker_registry_release_image() {
    name="$1"
    destination="$2"
    latest="$3"

    from="${REGISTRY}/${name}-commit:${IMAGE_TAG}"

    if [ -z "$CI_COMMIT_TAG" ]; then
        echo "pushing nightly"
        docker_registry_push "${from}" "${destination}:$(release_get_package_version)" # release_get_package_version => tag or nightly name
        return
    fi

    if [ "$latest" == "true" ]; then
        docker_registry_push "${from}" "${destination}:latest"
    fi

    docker_registry_push "${from}" "${destination}:${CI_COMMIT_TAG}"
    docker_registry_push "${from}" "${destination}:$(echo ${CI_COMMIT_TAG} | cut -d '.' -f 1)"
}

docker_registry_release_dev_image() {
    name=dev
    targetRegistry="$1"

    from="${REGISTRY}/${name}-commit:${IMAGE_TAG}"

    docker_registry_push_multi_platform ${from} ${targetRegistry}:$(echo $CI_COMMIT_REF_NAME | sed sI/I-Ig)-${PHP_VERSION}
}

docker_registry_push() {
    from="$1"
    to="$2"

    docker pull "${from}"
    docker tag "${from}" "${to}"
    docker push "${to}"
}

# needs to be run one time per architecture, with $ARCH set.
# Why?: This allows us push new architectures as their build finishes
docker_registry_push_multi_platform() {
    from="$1"
    to="$2"

    skopeo copy docker://${from}-${ARCH} docker://${to}-${ARCH}

    # create multi platform manifest, for platform specific images, available during runtime. Overwrites existing manifest.
    manifest-tool push from-args --platforms linux/amd64,linux/arm64 --template ${to}-ARCH --target ${to} --ignore-missing
}