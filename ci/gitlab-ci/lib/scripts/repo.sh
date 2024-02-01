repo_release_notes() {
    tag=$1
    previous_tag="$(git describe --abbrev=0 --tags HEAD~1 2> /dev/null || git fetch --unshallow --quiet && git describe --abbrev=0 --tags HEAD~1)" # if describe fails unshallow repo and try again

    echo '# Releasenotes'
    echo '# Changelog'
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/changelog.sh "$tag" "$previous_tag"
}