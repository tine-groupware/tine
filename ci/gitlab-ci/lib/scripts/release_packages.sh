release_packages_github_create_release() {
    if [ "$MAJOR_COMMIT_REF_NAME" != "main" ]; then
        echo "skip pushing to github: $MAJOR_COMMIT_REF_NAME"
        return 0
    fi

    customer=$(release_determin_customer)
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id ${customer})}
    release=$(echo ${version} | sed sI-I~Ig)

    cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/
    asset_name="tine-$(date '+%Y.%m.%d')-$(git rev-parse --short HEAD)-nightly"

    curl "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${version}/tine20-allinone_${release}.tar.bz2" -o "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20-allinone_${release}.tar.bz2"

    release_json=$(github_create_release "$GITHUB_RELEASE_REPO_OWNER" "$GITHUB_RELEASE_REPO" "$version" "$GITHUB_RELEASE_USER" "$GITHUB_RELEASE_TOKEN")
    if [ "$?" != "0" ]; then
        echo "$release_json"
        return 1
    fi

    echo "$release"

    github_release_add_asset "$release_json" "$asset_name" "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20-allinone_${release}.tar.bz2" "$GITHUB_RELEASE_USER" "$GITHUB_RELEASE_TOKEN"

    matrix_send_message $MATRIX_ROOM "🟢 Packages for ${version} have been released to github."

    if [ "${MAJOR_COMMIT_REF_NAME}" == "2023.11" ]; then
        matrix_send_message "!gGPNgDOyMWwSPjFFXa:matrix.org" 'We just released the new version "${CODENAME}" ${version} 🎉\nCheck https://www.tine-groupware.de/ and https://github.com/tine-groupware/tine/releases for more information and the downloads.\nYou can also pull the image from dockerhub: https://hub.docker.com/r/tinegroupware/tine'
    fi
}

release_packages_vpackages_push() {
    customer=$(release_determin_customer)
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id ${customer})}
    release=$(echo ${version} | sed sI-I~Ig)

    echo "publishing ${release} (${version}) for ${customer} from ${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${version}/all.tar"

    if ! ssh ${VPACKAGES_SSH_URL} -o StrictHostKeyChecking=no -C  "sudo -u www-data curl ${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/${version}/all.tar -o /tmp/${release}-source-${customer}.tar"; then
        echo "Failed to download packages to vpackages"
        return 1
    fi

    if ! ssh ${VPACKAGES_SSH_URL} -o StrictHostKeyChecking=no -C  "sudo -u www-data /srv/packages.tine20.com/www/scripts/importTine20Repo.sh /tmp/${release}-source-${customer}.tar; sudo -u www-data rm -f /tmp/${release}-source-${customer}.tar"; then
        echo "Failed to import package to repo"
        return 1
    fi
}

release_packages_vpackages_create_current_link() {
    customer=$(release_determin_customer)
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id ${customer})}
    release=$(echo ${version} | sed sI-I~Ig)

    if [ "$customer" == "tine20.com" ]; then
        customer="maintenance"
    fi

    if ! ssh ${VPACKAGES_SSH_URL} -o StrictHostKeyChecking=no -C  "if test -d /srv/packages.tine20.com/www/htdocs/${customer}/source/; then sudo -u www-data rm -f /srv/packages.tine20.com/www/htdocs/${customer}/source/tine-groupware-current.tar.bz2; sudo -u www-data ln -s ${version}/tine20-allinone_${version}.tar.bz2 /srv/packages.tine20.com/www/htdocs/${customer}/source/tine-groupware-current.tar.bz2; fi"; then
        echo "Failed to set current link"
        return 1
    fi
}

release_packages_gitlab_set_current_link() {
    customer=$(release_determin_customer)
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id ${customer})}

    if [ -n "${CUSTOMER_VERSION_POSTFIX}" ]; then
        customer=${customer}-${CUSTOMER_VERSION_POSTFIX}
    fi

    curl \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        -XPUT --data "${version}" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${customer}/links/current"

    matrix_send_message $MATRIX_ROOM "🟢 Package for ${version} is ready."
}