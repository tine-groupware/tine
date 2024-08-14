repo_release_notes() {
    tag=$1
    previous_tag="$(git describe --abbrev=0 --tags HEAD~1 2> /dev/null || git fetch --unshallow --quiet && git describe --abbrev=0 --tags HEAD~1)" # if describe fails unshallow repo and try again

    echo '# Releasenotes'
    echo '## Updating from Community Edition'
    echo 'If you update to this version from an older Community Edition (like https://github.com/tine20/tine20/releases/tag/2023.12.1), you might have to check the number of activated users. Without an activation key, this version only supports 5 enabled users.'
    echo '# Changelog'
    ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/scripts/git/changelog.sh "$tag" "$previous_tag"
}