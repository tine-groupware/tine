function docs_deploy () {
    set -x

    # determine alias based on BASE_MAJOR_COMMIT_REF_NAME. Using BASE_MAJOR_COMMIT_REF_NAME instead of CI_COMMIT_REF_NAME makes the code work for ecclesias too 
    if [[ "$BASE_MAJOR_COMMIT_REF_NAME" == "$TINE_VERSION_NEXT" ]]; then
        alias=next
    elif [[ "$BASE_MAJOR_COMMIT_REF_NAME" == "$TINE_VERSION_BETA" ]] && [[ "$TINE_VERSION_BETA" != "" ]]; then
        alias=beta
    elif [[ "$BASE_MAJOR_COMMIT_REF_NAME" == "$TINE_VERSION_BE" ]]; then
        alias=be
    elif [[ "$BASE_MAJOR_COMMIT_REF_NAME" == "$TINE_VERSION_LTS" ]]; then
        alias=lts
    else
        echo "$BASE_MAJOR_COMMIT_REF_NAME not supported"
        exit 1
    fi

    version=$BASE_MAJOR_COMMIT_REF_NAME

    echo $version $alias

    docs_build_php_doc

    # build and sync doc with correct site url for version an alias
    docs_build_and_sync $version
    docs_build_and_sync $alias

    s3cmd --access_key=${DOCS_S3_ACCESS_KEY} --secret_key=${DOCS_S3_SECRET_KEY} --host ${DOCS_S3_HOST} --host-bucket "%(bucket)s.${DOCS_S3_HOST}" --acl-public --delete-removed --no-mime-magic sync ./site/ s3://${DOCS_S3_BUCKET}/$alias/

    docs_update_versions $version $alias
}

function docs_build_php_doc() {
    cd tine20
    composer install --ignore-platform-reqs
    vendor/bin/phpdoc-md
    cd ..

}

function docs_build_and_sync() {
    target=$1

    sed -e "s%^site_url:.*$%site_url: $DOCS_URL/$target%g" -i mkdocs.yml

    mkdocs build

    s3cmd --access_key=${DOCS_S3_ACCESS_KEY} --secret_key=${DOCS_S3_SECRET_KEY} --host ${DOCS_S3_HOST} --host-bucket "%(bucket)s.${DOCS_S3_HOST}" --acl-public --delete-removed --no-mime-magic sync ./site/ s3://${DOCS_S3_BUCKET}/$target/
}

function docs_update_versions() {
    version=$1
    alias=$2

    versions_path=./versions.json

    s3cmd --access_key=${DOCS_S3_ACCESS_KEY} --secret_key=${DOCS_S3_SECRET_KEY} --host ${DOCS_S3_HOST} --host-bucket "%(bucket)s.${DOCS_S3_HOST}" sync s3://${DOCS_S3_BUCKET}/versions.json $versions_path

    # create or update version entry for this version
    if [[ $(jq --arg version $version '.[] | select(.version == $version)' $versions_path) == "" ]]; then
        jq --arg version $version --arg alias $alias '.[ . | length ] = {"version": $version, "title": $version, "aliases": [$alias]}' $versions_path > $versions_path.tmp
    else
        jq --arg version $version --arg alias $alias '(.[] | select(.version == $version)) = {"version": $version, "title": $version, "aliases": [$alias]}' $versions_path > $versions_path.tmp
    fi

    # remove this alias from other versions
    jq --arg version $version --arg alias $alias 'del((.[] | select(.version != $version)).aliases[] | select(. == $alias))' $versions_path.tmp > $versions_path

    rm $versions_path.tmp

    cat $versions_path

    s3cmd --access_key=${DOCS_S3_ACCESS_KEY} --secret_key=${DOCS_S3_SECRET_KEY} --host ${DOCS_S3_HOST} --host-bucket "%(bucket)s.${DOCS_S3_HOST}" --acl-public --delete-removed --no-mime-magic sync $versions_path s3://${DOCS_S3_BUCKET}/versions.json
}

function docs_build_docker_image() {
    image="${REGISTRY}/docs-commit:${IMAGE_TAG}"

    docker build \
        --target manual \
        --tag $image \
        --file ./docs/Dockerfile \
        --build-arg SCREENSHOTS_S3_BUCKET \
        --build-arg SCREENSHOTS_S3_HOST \
        --build-arg SCREENSHOTS_S3_ACCESS_KEY \
        --build-arg SCREENSHOTS_S3_SECRET_KEY \
        .

}

function docs_push_docker_image() {
    target=$1
    tag=$(echo ${CI_COMMIT_REF_NAME} | sed sI/I-Ig)
    image="${REGISTRY}/docs-commit:${IMAGE_TAG}"

    docker tag $image $target:$tag
    docker push $target:$tag
}

function docs_find_missing_screenshots() {
    touch ${CI_PROJECT_DIR}/missing-screenshots.txt

    for f in $(grep --recursive --no-filename --only-matching --perl-regexp '(?<=img_url_desktop }}).*(?=#only-light)' ${CI_PROJECT_DIR}/docs | tail -n +2 | sort | uniq ); do
        if ! [[ -f ${CI_PROJECT_DIR}/tests/e2etests/screenshots/$f ]]; then
            echo ${CI_COMMIT_REF_NAME}/${CI_RESOLUTION}/$f >> ${CI_PROJECT_DIR}/missing-screenshots.txt
        fi
    done
}

function docs_update_screenshots() {
    if [ "${CI_COMMIT_REF_NAME}" == "${MAJOR_COMMIT_REF_NAME}" ] && [ -n "${CI_RESOLUTION}" ]; then
        s3cmd sync ${CI_PROJECT_DIR}/tests/e2etests/screenshots/ s3://${SCREENSHOTS_S3_BUCKET}/${CI_COMMIT_REF_NAME}/${CI_RESOLUTION}/ \
            --access_key=${SCREENSHOTS_S3_ACCESS_KEY} \
            --secret_key=${SCREENSHOTS_S3_SECRET_KEY} \
            --host ${SCREENSHOTS_S3_HOST} \
            --host-bucket "%(bucket)s.${SCREENSHOTS_S3_HOST}" \
            --acl-public \
            --no-mime-magic \
            --no-delete-removed
    else
        echo skipping screenshot upload
    fi
}