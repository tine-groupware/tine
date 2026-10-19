function prevent_dot_agents_changes() {
    downstream=${MAJOR_COMMIT_REF_NAME}

    if [ "$downstream" == "$CI_COMMIT_REF_NAME" ]; then
        return
    fi

    git config user.email "ci@gitlab.metaways.net"
    git config user.name "ci"
    git rebase origin/$downstream

    changes="$(git diff --name-only -r --diff-filter=a origin/$downstream -- ./.agents/)"

    if [[ -z "$changes" ]]; then
        return
    fi

    echo -e "\033[0;31m"
    echo 'Changing .agents files downstream is not encouraged! If changing them is necessary disable this job'
    echo 'with the merge request label "allow-failure-prevent-dot-agents-changes"'
    echo -n -e "\033[0m"
    echo Changes:
    echo "$changes"

    return 1
}