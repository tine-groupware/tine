packaging_build_packages() {
    version=$(release_get_package_version)
    release=${version}

    echo "packaging_build_packages() version: $version release: $release"

    CI_COMMIT_REF_NAME_ESCAPED=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)
    MAJOR_COMMIT_REF_NAME_ESCAPED=$(echo ${MAJOR_COMMIT_REF_NAME} | sed sI/I-Ig)

    CACHE_IMAGE="${REGISTRY}/packages:${CI_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}${IMAGE_TAG_PLATFORM_POSTFIX}"
    MAJOR_CACHE_IMAGE="${REGISTRY}/packages:${MAJOR_COMMIT_REF_NAME_ESCAPED}-${PHP_VERSION}${IMAGE_TAG_PLATFORM_POSTFIX}"

    if echo "$CI_COMMIT_TAG" | grep '/'; then
        echo "Error: CI_COMMIT_TAG must not contain a /"
        exit 1
    fi

    # config via env
    export PHP_VERSION=${PHP_VERSION}
    export BASE_IMAGE="${REGISTRY}/base-commit:${IMAGE_TAG}"
    export DEPENDENCY_IMAGE="${REGISTRY}/dependency-commit:${IMAGE_TAG}"
    export SOURCE_IMAGE="${REGISTRY}/source-commit:${IMAGE_TAG}"
    export JSDEPENDENCY_IMAGE="${REGISTRY}/jsdependency-commit:${IMAGE_TAG}"
    export JSBUILD_IMAGE="${REGISTRY}/jsbuild-commit:${IMAGE_TAG}"
    export BUILD_IMAGE="${REGISTRY}/build-commit:${IMAGE_TAG}"
    export BUILT_IMAGE="${REGISTRY}/built-commit:${IMAGE_TAG}"
    export REVISION=0
    export CODENAME="${CODENAME}"
    export VERSION=$version
    export RELEASE=$release

    cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
    # create archives
    if ! ./ci/dockerimage/make.sh -o "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/packages.tar" -c "${CACHE_IMAGE}" -c "${MAJOR_CACHE_IMAGE}" packages; then
        return 1
    fi
}

packaging_extract_all_package_tar() {
    cd "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/"
    tar -xf "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/packages.tar"
}

packaging_push_packages_to_gitlab() {
    version=$1

    package_repo=$(release_packages_determin_package_repo_name)

    curl -S -s \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        --upload-file "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/packages.tar" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${package_repo}/${version}/all.tar"

    echo "published packages to ${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${package_repo}/${version}/all.tar"

    cd "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/${version}/"

    for f in *; do
        curl -S -s \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        --upload-file "$f" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${package_repo}/${version}/$f"
    done

    echo ""
    echo "published packages to ${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${package_repo}/${version}/all.tar"
    echo ""
}

packaging_gitlab_set_ci_id_link() {
    version=$1

    echo "packaging_gitlab_set_ci_id_link() CI_PIPELINE_ID: $CI_PIPELINE_ID version: $version MAJOR_COMMIT_REF_NAME: $MAJOR_COMMIT_REF_NAME"

    if ! curl -S -s \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        -XPUT --data "${version}" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/ci/links/${CI_PIPELINE_ID}"
    then
        return 1
    fi
}

packaging_gitlab_get_version_for_pipeline_id() {
    if ! curl \
        --fail \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/ci/links/${CI_PIPELINE_ID}"
    then
        return 1
    fi
}

packaging() {
    version=$(release_get_package_version)

    echo "packaging() CI_COMMIT_TAG: $CI_COMMIT_TAG version/release: $version MAJOR_COMMIT_REF_NAME: $MAJOR_COMMIT_REF_NAME"

    if ! release_determin_customer; then
        echo "No packages are build for major_commit_ref: $MAJOR_COMMIT_REF_NAME for version: $version"
        return 1
    fi

    echo "building packages ..."
    if ! packaging_build_packages; then
        echo "Failed to build packages."
        return 1
    fi

    if ! packaging_extract_all_package_tar; then
        echo "Failed to extract tar archive."
        return 1
    fi

    echo "pushing packages to gitlab ..."
    if ! packaging_push_packages_to_gitlab $version; then
        echo "Failed to push to gitlab."
        return 1
    fi

    # this is only needed for nightlitys. As calculating there name requies a fully fteched git, and deploy do not have need thah
    echo "setting ci pipeline id link"
    if ! packaging_gitlab_set_ci_id_link $version; then
        echo "Failed to set ci pipeline id link."
        return 1
    fi
}