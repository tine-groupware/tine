function prevent_upstream_change() {
    downstream=${DOWNSTREAM_BRANCH}
    upstream=${CI_COMMIT_REFNAME}

    if [ -z "downstream" ]; then
        echo "downstream not set"
        return 1
    fi

    if [ "$downstream" == "$upstream" ]; then
        return
    fi

    changes="$(git diff-tree --name-only -r --diff-filter=a origin/$downstream origin/$upstream)"

    for allowed in $(cat ci/upstream-change-global-whitelist.txt) $(cat ci/upstream-change-whitelist.txt || true); do
        changes="$(echo "$changes" | grep -v -E "^$allowed$")"
    done

    if [[ -z "$changes" ]]; then
        return
    fi

    echo 'Changing downstream files upstream is not encouraged! If changing them is nesasary, add an exception to "ci/upstream-change-whitelist.txt".'
    echo Changes:
    echo "$changes"

    return 1
}