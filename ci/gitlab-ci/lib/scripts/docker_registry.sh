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
    desitination="$2"
    latest="$3"

    from="${REGISTRY}/${name}-commit:${IMAGE_TAG}"

    if [ -z "$CI_COMMIT_TAG" ]; then
        echo "pushing nightly"
        docker_registry_push "${from}" "${desitination}:$(release_get_package_version)" # release_get_package_version => tag or nightly name
        return
    fi

    if [ "$latest" == "true" ]; then
        docker_registry_push "${from}" "${desitination}:latest"
    fi

    docker_registry_push "${from}" "${desitination}:${CI_COMMIT_TAG}"
    docker_registry_push "${from}" "${desitination}:$(echo ${CI_COMMIT_TAG} | cut -d '.' -f 1)"
}

docker_registry_push() {
    from="$1"
    to="$2"

    docker pull "${from}"
    docker tag "${from}" "${to}"
    docker push "${to}"
}