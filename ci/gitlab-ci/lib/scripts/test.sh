test_preapre_working_dir() {
    if [ "${TINE20ROOT}" != "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20" ]; then
        log "test_preapre_wirking_dir is only requires the tine root to be: ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20"
        # This function is only intended to work with the source from gitlab...
        # intended: for the main repo => do basicly nothing. Or for customapps => clone main repo and include customapp
        # and setup vars as if we where running in the main repo. 
        return 1
    fi

    if [ "${CI_PROJECT_NAME}" != "tine20" ] && [ "$CI_IS_CUSTOMAPP" != "true" ]; then
        log "project name needs to be tine20 (for this, and) other test scrips to work"
        # In many places ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20 is used. If we use TINE20ROOT root any where
        # the project name can be anything. As long as TINE20ROOT points to the correct CI_POOJECT_DIR.
        # todo remove this if. if we removed all occurents of ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20
        return 1
    fi

    # Clone tine repo, if pipeline runs for a customapp
    if [ "${CI_IS_CUSTOMAPP}" == "true" ]; then
        log "cloning tine ..."
        git clone -b $TINE20_BRANCH --single-branch --depth 1 $TINE20_REPO_URL ${TINE20ROOT};
    fi

    # todo: move into customapp case, and let gitlab hanled submodule init for the main repo
    log "init git submodules ..."
    cd ${TINE20ROOT}
    git submodule init
    git submodule update
    
    # Install source customapp, if pipeline runs for a customapp
    if [ "${CI_IS_CUSTOMAPP}" == "true" ]; then
        log "instaling custom app ..."
        customappname=$(cat ${CI_PROJECT_DIR}/composer.json | jq -r '.name')
        cd ${TINE20ROOT}/tine20
        composer config "repositories.ci" git "${CI_REPOSITORY_URL}";
        composer require "$name dev-master#${CI_COMMIT_SHA}";
        popd
    fi

    # the shell should be left in the new working dir
    cd ${TINE20ROOT}
}

test_composer_install() {
    # todo presed vendor dir from gitlab cache
    # set composer home to custom shared cache
    log "composer install ..."
    cd ${TINE20ROOT}/tine20
    composer install --no-ansi --no-progress --no-suggest
    popd
}

log() {
    echo -e "\033[0;34m"$@"\033[0m"
}