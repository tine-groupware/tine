docker_hub_deploy () {
    set -e
    name=$1
    dockerhubname=$2
    dockerhubtag=$3

    docker login -u "${DOCKERHUB_USER}" -p "${DOCKERHUB_TOKEN}" "docker.io"

    FROM_IMAGE="${REGISTRY}/${name}-commit:${IMAGE_TAG}"
    DESTINATION_IMAGE="docker.io/tinegroupware/${dockerhubname}:${dockerhubtag}"

    docker pull "${FROM_IMAGE}"
    docker tag "${FROM_IMAGE}" "${DESTINATION_IMAGE}"
    docker push "${DESTINATION_IMAGE}"
}