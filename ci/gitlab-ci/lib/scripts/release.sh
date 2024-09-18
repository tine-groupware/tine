release_tag() {
    branch="$(echo "$CI_COMMIT_REF_NAME" | sed sI/I-Ig)"
    tag_prefix="$branch-$(date '+%Y.%m.%d.')"

    last_counter="$(curl -H "Authorization: Bearer $GITLAB_TOKEN" "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/tags?search=^$tag_prefix" | jq -r '.[].name' | sort --version-sort | tail -n 1 | awk -F '.' '{print $NF}')"
    counter="$((${last_counter:-0}+1))"

    tag="$tag_prefix$counter"

    echo "tag: $tag"

    curl -H "Authorization: Bearer $GITLAB_TOKEN" -XPOST "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/tags?tag_name=$tag&ref=$CI_COMMIT_SHA&message=version+$tag"
}

release_to_gitlab() {
    tag="${CI_COMMIT_TAG}"
    package_repo="$(release_packages_determin_package_repo_name)"

    release-cli create --description "$(repo_release_notes "$tag")" --tag-name "$tag" --ref "$tag" --name "$tag" \
        --assets-link "{\"name\":\"all.tar\",\"url\":\"${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${package_repo}/${tag}/all.tar\"}"
}

# possible values tine20.org tine20.com <customer> ""
release_determin_customer () {
    if test -z "${BASE_MAJOR_COMMIT_REF_NAME}"; then
        # For branches without BASE_MAJOR_COMMIT_REF_NAME and CUSTOMER_MAJOR_COMMIT_REF_NAME variables
        # todo remove is these kind of branches do not exist any more
        if ! echo "${branch}" | grep -q '/'; then
            echo tine20.com
            return
        else
            if [ $(echo "${branch}" | awk -F"/" '{print NF-1}') != 1 ]; then
                return 1
            fi

            echo "${branch}" | cut -d '/' -f1
            return
        fi
    else
        # For branches with BASE_MAJOR_COMMIT_REF_NAME and CUSTOMER_MAJOR_COMMIT_REF_NAME variables
        if test -z "${CUSTOMER_MAJOR_COMMIT_REF_NAME}"; then
            if echo ${CI_COMMIT_TAG} | grep -Eq 'weekly'; then
                echo "tine20.org"
                return
            fi

            echo "tine20.com"
            return
        else
            echo "${CUSTOMER_MAJOR_COMMIT_REF_NAME}" | sed 's:/*$::'
            return
        fi 
    fi
}