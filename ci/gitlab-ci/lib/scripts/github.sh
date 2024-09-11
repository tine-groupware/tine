github_create_release() {
    tag=$1
    user=$2
    token=$3

    if [ "${RELEASE_TYPE}" != "nightly" ]; then
        body="$(repo_release_notes "$tag" | jq -Rs .)"
        draft="false"
    else
        echo "only publshing as draft: nigtly release detected" > /dev/stderr
        body="$(repo_release_notes "HEAD" | jq -Rs .)"
        draft="true"
    fi

    if [ "${RELEASE_TYPE}" != "weekly" ]; then
        prerelease="false"
    else
        prerelease="true"
    fi

    curl -s \
        -X POST \
        -u "$user:$token" \
        -H "accept: application/vnd.github.v3+json" \
        "https://api.github.com/repos/tine-groupware/tine/releases" \
        -d '{"name":"'"$tag"'", "tag_name":"'"$tag"'", "body":'"$body"', "draft":'$draft'}'
}

github_release_add_asset() {
    release_json=$1
    name=$2
    path_to_asset=$3
    user=$4
    token=$5

    upload_url=$(echo $release_json | jq -r '.upload_url')
    upload_url="${upload_url%\{*}"

    curl -s \
        -X POST \
        -u "$user:$token" \
        -T "$path_to_asset" \
        -H "accept: application/vnd.github.v3+json" \
        -H "content-type: $(file -b --mime-type $path_to_asset)" \
        "$upload_url?name=$name.tar.bz2"
}