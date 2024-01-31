repo_release_notes() {
    tag=$1
    previous_tag=$2

    echo '# Releasenotes'
    echo '# Changelog'
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/changelog.sh "$tag" "$previous_tag"
}