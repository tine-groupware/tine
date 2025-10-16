test_prepare_working_dir() {
    if [ "${TINE20ROOT}" != "${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20" ]; then
        log "test_preapre_working_dir requires the tine root to be: \${CI_BUILDS_DIR}/\${CI_PROJECT_NAMESPACE}/tine20"
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
        # COMPOSER custom cache
        # CI_CUSTOM_CACHE_DIR is a volume shared betwean runners
        # export COMPOSER_CACHE_DIR=${CI_CUSTOM_CACHE_DIR}/${CI_PROJECT_NAMESPACE}/composer-cache/v1/
        # mkdir -p ${COMPOSER_CACHE_DIR}

        log "instaling custom app ..."
        customappname=$(cat ${CI_PROJECT_DIR}/composer.json | jq -r '.name')
        pushd ${TINE20ROOT}/tine20

        composer config "repositories.ci" git "${CI_REPOSITORY_URL}";
        COMPOSER_ALLOW_SUPERUSER=1 composer require "$customappname dev-master#${CI_COMMIT_SHA}";
        popd
    fi

    # the shell should be left in the new working dir
    cd ${TINE20ROOT}
}

test_prepare_global_configs() {
    log "Preparing global configs ..."
    rm /etc/supervisor.d/worker.ini || true
    rm /etc/crontabs/tine20 || true
    gomplate --config /etc/gomplate/config.yaml
    # cp ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/dockerimage/supervisor.d/webpack.ini /etc/supervisor.d/; # 2023.11 >= todo have/need this file
    # todo move config dir to ${TINE20ROOT}/etc build test should be ablte to handle that
    rsync -a -I --delete ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/etc/ /config;
    echo "$TINE20_SETUP_HTPASSWD" > /etc/tine20/setup.htpasswd
}

test_prepare_mail_db() {
    log "Preparing databases for mail setup ..."
    mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"SET GLOBAL wait_timeout=31536000; SET GLOBAL interactive_timeout=31536000"
    mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE DATABASE IF NOT EXISTS dovecot"
    mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE DATABASE IF NOT EXISTS postfix"
    mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"CREATE USER IF NOT EXISTS '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_PASSWORD';"
    mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"GRANT ALL PRIVILEGES ON postfix.* TO '$MYSQL_USER'@'%'"
    mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" -e"GRANT ALL PRIVILEGES ON dovecot.* TO '$MYSQL_USER'@'%'"
    mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" "dovecot" < /config/sql/dovecot_tables.sql
    mysql -h$MAIL_DB_HOST -uroot -p"$MYSQL_ROOT_PASSWORD" "postfix" < $ARG_POSTFIX_INIT_SQL_PATH
}

test_composer_install() {
    log "trying to use cached vendor dir"
    composer_lock_hash=$(cd ${TINE20ROOT}/tine20; sha1sum composer.json composer.lock | sha1sum | cut -d ' ' -f 1)
    export VENDOR_CACHE_DIR=${CI_CUSTOM_CACHE_DIR}/${CI_PROJECT_NAMESPACE}/tine20/composer-cache/v1/${composer_lock_hash}

    mkdir -p $(dirname $VENDOR_CACHE_DIR)

    if [ -d $VENDOR_CACHE_DIR ] && [ ! -d ${TINE20ROOT}/tine20/vendor ]; then
        log "found cached vendor dir using it..."
        echo VENDOR_CACHE_DIR=$VENDOR_CACHE_DIR

        cp -r $VENDOR_CACHE_DIR ${TINE20ROOT}/tine20/vendor
        # create marker for cache cleanup
        date --utc +%FT%TZ > $VENDOR_CACHE_DIR-lastused
        # do not return here, we need to run composer install, so it creates the customapps links for us
    fi

    log "prepearing composer cache ..."
    # CI_CUSTOM_CACHE_DIR is a volume shared betwean runners
    export COMPOSER_CACHE_DIR=${CI_CUSTOM_CACHE_DIR}/${CI_PROJECT_NAMESPACE}/composer-cache/v1/
    mkdir -p ${COMPOSER_CACHE_DIR}

    log "composer install ..."
    pushd ${TINE20ROOT}/tine20
    # trigger customapploader plugin, to create links
    rm -rf vendor/metaways
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-ansi --no-progress --no-suggest
    popd

    if [ ! -d $VENDOR_CACHE_DIR ]; then
        log "storing vendor dir as cache"
        cp -r ${TINE20ROOT}/tine20/vendor $VENDOR_CACHE_DIR || log "storing vendor dir failed. continuing"
    fi
}

test_npm_install() {
    additional_npm_args="$1"
    log "trying to use cache..."
    package_shrinkwrap_hash=$(cd ${TINE20ROOT}/tine20/Tinebase/js; echo $(sha1sum npm-shrinkwrap.json package.json)"$additional_npm_args" | sha1sum | cut -d ' ' -f 1)
    export NODE_MODULE_CACHE_DIR=${CI_CUSTOM_CACHE_DIR}/${CI_PROJECT_NAMESPACE}/tine20/npm-cache/v1/${package_shrinkwrap_hash}

    mkdir -p $(dirname $NODE_MODULE_CACHE_DIR)

    if [ -d $NODE_MODULE_CACHE_DIR ] && [ ! -d ${TINE20ROOT}/tine20/Tinebase/js/node_modules ]; then
        log "found cached node_modules using it..."
        echo NODE_MODULE_CACHE_DIR=$NODE_MODULE_CACHE_DIR

        cp -r $NODE_MODULE_CACHE_DIR ${TINE20ROOT}/tine20/Tinebase/js/node_modules

        # create marker for cache cleanup
        date --utc +%FT%TZ > $NODE_MODULE_CACHE_DIR-lastused
        return 0
    fi

    log "installing npm ..."
    pushd ${TINE20ROOT}/tine20/Tinebase/js
    npm --no-optional install $additional_npm_args
    popd

    if [ ! -d $NODE_MODULE_CACHE_DIR ]; then
        log "storing node_modles dir as cache"
        cp -r ${TINE20ROOT}/tine20/Tinebase/js/node_modules $NODE_MODULE_CACHE_DIR || log "storing node_modles dir failed. continuing"
    fi
}


test_phpunit() {
    log "Preparing test .."
    if [ -f ${TINE20ROOT}/scripts/postInstallGitlab.sh ]; then
        ${TINE20ROOT}/scripts/postInstallGitlab.sh
    fi
    
    php -v
    echo ${NODE_TOTAL} ${NODE_INDEX};
    echo cd ${TINE20ROOT}/${ARG_TEST_PATH_FROM_TINE20ROOT}

    cd ${TINE20ROOT}/${ARG_TEST_PATH_FROM_TINE20ROOT}


    log "testing ..."
    cmd="php ${TINE20ROOT}/tine20/vendor/bin/phpunit --color --log-junit ${CI_PROJECT_DIR}/phpunit-report.xml --debug";

    if test -n "${ARG_FILTER}"; then
        cmd="${cmd} --filter ${ARG_FILTER}"
    fi
    
    if test -n "${ARG_EXCLUDE_GROUP}"; then
        cmd="${cmd} --exclude-group ${ARG_EXCLUDE_GROUP}"
    fi

    if test -n "${ARG_GROUP}"; then
        cmd="${cmd} --group ${ARG_GROUP}"
    fi

    cmd="${cmd} ${ARG_TEST}";

    echo ${cmd};
    ${cmd}
}

# log in blue
log() {
    echo -e "\033[0;34m"$@"\033[0m"
}

test_release_update_test_determine_start_version () {
    # we can automatically determine the start version for main, beta and be. - git describe only works for lts and be, as other branches are "contaminated" with different tags 
    if [ -z "${CUSTOMER_MAJOR_COMMIT_REF_NAME}" ]; then
        if [ "${BASE_MAJOR_COMMIT_REF_NAME}" == "${TINE_VERSION_NEXT}" ] || [ "${BASE_MAJOR_COMMIT_REF_NAME}" == "${TINE_VERSION_BETA}" ]; then
            git describe --tags --abbrev=0 origin/${TINE_VERSION_BE}
            return
        fi

        if [ "${BASE_MAJOR_COMMIT_REF_NAME}" == "${TINE_VERSION_BE}" ]; then
            git describe --tags --abbrev=0 origin/${TINE_VERSION_LTS}
            return
        fi
    fi

    if [ -n "${RELEASE_UPDATE_TEST_START_REF}" ]; then
        echo ${RELEASE_UPDATE_TEST_START_REF}
        return
    fi

    if [ -n "${CUSTOMER_MAJOR_COMMIT_REF_NAME}" ]; then
        if [ "${BASE_MAJOR_COMMIT_REF_NAME}" == "${TINE_VERSION_NEXT}" ] || [ "${BASE_MAJOR_COMMIT_REF_NAME}" == "${TINE_VERSION_BETA}" ]; then
            git describe --tags --abbrev=0  --match="*${CUSTOMER_MAJOR_COMMIT_REF_NAME::-1}*"  origin/${CUSTOMER_MAJOR_COMMIT_REF_NAME}${TINE_VERSION_BE}
            return
        fi

        if [ "${BASE_MAJOR_COMMIT_REF_NAME}" == "${TINE_VERSION_BE}" ]; then
            git describe --tags --abbrev=0 --match="*${CUSTOMER_MAJOR_COMMIT_REF_NAME::-1}*" origin/${CUSTOMER_MAJOR_COMMIT_REF_NAME}${TINE_VERSION_LTS}
            return
        fi
    fi
}
