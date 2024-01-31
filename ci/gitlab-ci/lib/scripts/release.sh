release_tag() {
    branch="$(echo "$CI_COMMIT_REF_NAME" | sed sI/I-Ig)"
    tag_prefix="$branch-$(date '+%Y.%m.%d.')"

    last_counter="$(curl -H "Authorization: Bearer $GITLAB_TOKEN" "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/tags?search=^$tag_prefix" | jq -r '.[].name' | sort --version-sort | tail -n 1 | awk -F '.' '{print $NF}')"
    counter="$((${last_counter:-0}+1))"

    tag="$tag_prefix$counter"

    echo "tag: $tag"

    curl -H "Authorization: Bearer $GITLAB_TOKEN" -XPOST "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/tags?tag_name=$tag&ref=$CI_COMMIT_SHA&message=version+$tag"
}

release_tag_main_if_needed() {
    if [ "$RELEASE_CE_TO_GITHUB" != "true" ]; then
        echo "'RELEASE_CE_TO_GITHUB=$RELEASE_CE_TO_GITHUB' => do not tag main."
        return
    fi

    last_release_tag=$(github_get_latest_release_tag_name "$GITHUB_RELEASE_REPO_OWNER" "$GITHUB_RELEASE_REPO")
    if [ $? != 0 ]; then
        return 1
    fi

    git fetch origin main || return 1

    commit_diff_count=$(git rev-list "$last_release_tag..origin/main" --count)
    if [ $? != 0 ]; then
        return 1
    fi

    echo "origin/main and $last_release_tag differ in $commit_diff_count commits"

    if [ $commit_diff_count = 0 ]; then
        echo "No difference, no new tag is created."
        return 0
    fi

    tag="$(date '+%Y.%m.%d.')$commit_diff_count"
    echo "tagging origin/main as $tag"

    if ! git tag $tag; then
        if [ "$(git rev-parse "$tag")" != "$(git rev-parse origin/main)" ]; then
            echo "tag $tag already exits, but it is ponting to a different commit."
            return 1
        fi

        echo "Tag $tag already exits, for this commit. Using it..."
    fi

    # "tag push" triggers tag pipeline which publishes the release
    git push origin $tag || return 1
    git push github $tag
}

release_to_gitlab() {
    tag="${CI_COMMIT_TAG}"
    customer="$(release_determin_customer)"
    previous_tag="$(git describe --abbrev=0 --tags HEAD~1 2> /dev/null || git fetch --unshallow --quiet && git describe --abbrev=0 --tags HEAD~1)" # if describe fails unshallow repo and try again

    release-cli create --description "$(repo_release_notes "$tag" "$previous_tag")" --tag-name "$tag" --ref "$tag" --name "$tag" \
        --assets-link "{\"name\":\"all.tar\",\"url\":\"${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${tag}/all.tar\"}"
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