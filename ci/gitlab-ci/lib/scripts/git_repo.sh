git_repo_clone () {
    git clone ${CI_REPOSITORY_URL} --branch ${CI_COMMIT_REF_NAME} --depth 1 --single-branch ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
}

git_repo_clone_cached () {
    set -x
    targetDir=${1:-$CI_PROJECT_DIR}

    cacheRepoPath=${CI_CUSTOM_CACHE_DIR}/git-cache/v1/$(echo "${CI_REPOSITORY_URL}" | cut -d '@' -f2)

    mkdir -p $cacheRepoPath

    pushd $cacheRepoPath
    if ! git rev-parse --is-inside-work-tree 2> /dev/null; then
        log "not a git repo. Initialzing new bare git repo ..."
        git init --bare
    fi

    log "setting up remote"
    if git remote | grep origin > /dev/null; then
        git remote rm origin
    fi

    git remote add origin "${CI_REPOSITORY_URL}"

    log "fetching from gitlab" 
    git fetch --force origin '*:*'
    popd


    log "cloning into working dir"
    git clone $cacheRepoPath --branch "${CI_COMMIT_REF_NAME}" --local --no-hardlinks "${targetDir}"
    pushd ${targetDir}
    # switch orign from cache to gitlab
    git remote rm origin
    git remote add origin "${CI_REPOSITORY_URL}"
    # fetch new origin again
    git fetch
    # setup branch upstream again
    git branch --set-upstream-to="origin/${CI_COMMIT_REF_NAME}" "${CI_COMMIT_REF_NAME}"
    popd
}