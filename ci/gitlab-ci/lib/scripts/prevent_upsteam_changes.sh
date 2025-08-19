function prevent_upstream_change() {
    downstream=${MAJOR_COMMIT_REF_NAME}

    if [ "$downstream" == "$CI_COMMIT_REF_NAME" ]; then
        return
    fi

    git rebase origin/$downstream

    changes="$(git diff --name-only -r --diff-filter=a origin/$downstream)"

    for allowed in $(cat ci/upstream-change-global-whitelist.txt) $(cat ci/upstream-change-whitelist.txt || true); do
        changes="$(echo "$changes" | grep -v -E "^$allowed$")"
    done

    if [[ -z "$changes" ]]; then
        return
    fi

    echo 'Changing downstream files upstream is not encouraged! If changing them is necessary, either:'
    echo '* disable this job with the merge request label "allow-failure-prevent-upstream-changes"'
    echo '* or add an permanent exception to "ci/upstream-change-whitelist.txt".'
    echo Changes:
    echo "$changes"

    return 1
}