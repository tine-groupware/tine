release_tag() {
    branch="$(echo "$CI_COMMIT_REF_NAME" | sed sI/I-Ig)"
    tag_prefix="$branch-$(date '+%Y.%m.%d.')"

    last_counter="$(curl -H "Authorization: Bearer $GITLAB_TOKEN" "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/tags?search=^$tag_prefix" | jq -r '.[].name' | sort --version-sort | tail -n 1 | awk -F '.' '{print $NF}')"
    counter="$((${last_counter:-0}+1))"

    tag="$tag_prefix$counter"

    echo "tag: $tag"

    curl -H "Authorization: Bearer $GITLAB_TOKEN" -XPOST "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/tags?tag_name=$tag&ref=$CI_COMMIT_SHA&message=version+$tag"
}

release_weekly_tag() {
    tag_prefix="weekly-$(date '+%Y.%V.')"

    last_counter="$(curl -H "Authorization: Bearer $GITLAB_TOKEN" "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/tags?search=^$tag_prefix" | jq -r '.[].name' | sort --version-sort | tail -n 1 | awk -F '.' '{print $NF}')"
    counter="$((${last_counter:-0}+1))"

    tag="$tag_prefix$counter"

    echo "tag: $tag"

    curl -H "Authorization: Bearer $GITLAB_TOKEN" -XPOST "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/tags?tag_name=$tag&ref=$CI_COMMIT_SHA&message=version+$tag"
}

release_to_gitlab() {
    tag="${CI_COMMIT_TAG}"
    customer="$(repo_get_customer_for_branch ${MAJOR_COMMIT_REF_NAME})"

    release-cli create --description "$(repo_release_notes "$tag")" --tag-name "$tag" --ref "$tag" --name "$tag" \
        --assets-link "{\"name\":\"all.tar\",\"url\":\"${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${tag}/all.tar\"}"
}
