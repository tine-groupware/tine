version_check_update () {
    version_check_update_file htdocs%2FversionCheck%2Fversion.json
    version_check_update_file htdocs%2FversionCheck%2Fbe%2Fversion.json
}

version_check_update_file () {
    path=$1
    
    release_time="$(date "+%Y-%m-%d 00:00:00")"
    version="{\\\"codeName\\\":\\\"${CODE_NAME}\\\",\\\"packageString\\\":\\\"${PACKAGE_STRING}\\\",\\\"releaseTime\\\":\\\"${release_time}\\\",\\\"critical\\\":\\\"false\\\",\\\"build\\\":\\\"${BUILD}\\\"}"

    curl --request PUT --header "PRIVATE-TOKEN: ${VERSION_CHECK_UPDATE_TOKEN}" \
     --header "Content-Type: application/json" \
     --data '{"branch": "master", "commit_message": "update version", "content": "'"$version"'"}' \
     "${CI_API_V4_URL}/projects/${VERSION_CHECK_PROJECT_ID}/repository/files/$path"
}