release_packages_github_create_release() {
    package_repo=$(release_packages_determin_package_repo_name)
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id)}

    cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/

    echo curl "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${package_repo}/${version}/tine20-allinone_${version}.tar.bz2" -o "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20-allinone_${version}.tar.bz2"
    curl "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${package_repo}/${version}/tine20-allinone_${version}.tar.bz2" -o "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20-allinone_${version}.tar.bz2"

    release_json=$(github_create_release "$version" "$GITHUB_RELEASE_USER" "$GITHUB_RELEASE_TOKEN")
    if [ "$?" != "0" ]; then
        echo "$release_json"
        return 1
    fi

    echo "package_repo: $package_repo version: $version release_json: $release_json"

    github_release_add_asset "$release_json" "$version" "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20-allinone_${version}.tar.bz2" "$GITHUB_RELEASE_USER" "$GITHUB_RELEASE_TOKEN"
}

release_packages_notify_matrix() {
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id)}

    matrix_send_message $MATRIX_ROOM "🟢 Packages for ${version} have been released to github."

    if [ "${RELEASE_TYPE}" == "be" ]; then
        matrix_send_message "!gGPNgDOyMWwSPjFFXa:matrix.org" "We just released the new version \"${CODENAME}\" ${version} 🎉\\nCheck https://www.tine-groupware.de/ and https://github.com/tine-groupware/tine/releases for more information and the downloads.\\nYou can also pull the image from dockerhub: https://hub.docker.com/r/tinegroupware/tine"
    fi
}

release_push_release_tag_to_github() {
    if ! test "$CI_COMMIT_TAG"; then
        echo "no tag to push: '$CI_COMMIT_TAG'"
        return
    fi

    cp $DOCKER_GIT_CONFIG ~/.gitconfig
    git config --global user.email "gitlabci@metaways.de"
    git config --global user.name "gitlabci"
    git remote add github https://github.com/tine-groupware/tine.git

    git push github refs/tags/$CI_COMMIT_TAG
}

release_packages_vpackages_push() {
    customer=$(release_determin_customer)
    package_repo=$(release_packages_determin_package_repo_name)
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id)}
    release=$(echo ${version} | sed sI-I~Ig)

    echo "publishing ${release} (${version}) for ${customer} from ${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${package_repo}/${version}/all.tar"

    if ! ssh ${VPACKAGES_SSH_URL} -o StrictHostKeyChecking=no -C  "sudo -u www-data curl ${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${package_repo}/${version}/all.tar -o /tmp/${release}-source-${customer}.tar"; then
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
    package_repo=$(release_packages_determin_package_repo_name)
    version=${CI_COMMIT_TAG:-$(packaging_gitlab_get_version_for_pipeline_id ${customer})}

    curl \
        --header "JOB-TOKEN: ${CI_JOB_TOKEN}" \
        -XPUT --data "${version}" \
        "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/generic/${package_repo}/links/current"

    matrix_send_message $MATRIX_ROOM "🟢 Package for ${version} is ready."
}

release_packages_determin_package_repo_name () {
    if [ "$RELEASE_TYPE" == "weekly" ]; then
        echo "weekly"
        return
    fi

    if [ "$RELEASE_TYPE" == "monthly" ]; then
        echo "monthly"
        return
    fi

    if [ "$RELEASE_TYPE" == "beta" ]; then
        echo "beta"
        return
    fi

    if [ "$RELEASE_TYPE" == "be" ]; then
        echo "tine20.com"
        return
    fi

    if [ "$RELEASE_TYPE" == "customer" ]; then
        if [ -n "$PACKAGE_REPO_NAME_OVERWRITE" ]; then
            echo "$PACKAGE_REPO_NAME_OVERWRITE"
            return
        fi

        release_determin_customer # basicly `echo "${CUSTOMER_MAJOR_COMMIT_REF_NAME}" | sed 's:/*$::'` as all other case cant match
        return $?
    fi

    echo "ci"
    return
}