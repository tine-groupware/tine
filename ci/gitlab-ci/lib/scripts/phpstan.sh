phpstan_analyse() {
    if test "${CI_PROJECT_NAME}" == "tine20"; then
        dir=tine20
    else
        dir=tine20/vendor/$(cat ${CI_PROJECT_DIR}/composer.json | jq -r '.name')/lib;
    fi

    log "fixing symlinks ..."
    # fix: phpstan fails if custom apps are symlinked. They need to be analysed in the vendor dir.
    #    exclude symlinks
    find $TINE20ROOT/tine20 -maxdepth 1 -type l -exec echo "        - '{}'" \; >> excludes;
    #    unexclude vendor/metaways
    find $TINE20ROOT/tine20/vendor -mindepth 1 -maxdepth 1 -type d -exec echo "        - '{}'" \; >> excludes;
    sort -o excludes excludes
    sed -i '/tine20\/vendor\*/r excludes' $TINE20ROOT/phpstan.neon;
    sed -i '/tine20\/vendor\/metaways/d' $TINE20ROOT/phpstan.neon;
    rm excludes

    log "setting max processes ..."
    sed -i "s/maximumNumberOfProcesses: 32/maximumNumberOfProcesses: $KUBERNETES_CPU_REQUEST/g" $TINE20ROOT/phpstan.neon

    log "setting up cache ..."
    # CI_CUSTOM_CACHE_DIR is a volume shared betwean runners
    # todo: monitor if composer.lock file hash prodoces to mutch dead cache
    phpstan_cache_key=$(echo $MAJOR_COMMIT_REF_NAME $PHP_VERSION $(sha256sum $TINE20ROOT/tine20/composer.lock | cut -d ' ' -f 1) | sha256sum | cut -d ' ' -f 1)
    export PHPSTAN_CACHE_DIR=${CI_CUSTOM_CACHE_DIR}/${CI_PROJECT_NAMESPACE}/${CI_PROJECT_NAME}/phpstan-cache/v2/$phpstan_cache_key
    echo PHPSTAN_CACHE_DIR=$PHPSTAN_CACHE_DIR
    mkdir -p ${PHPSTAN_CACHE_DIR}
    sed -i "s%tmpDir:%tmpDir: $PHPSTAN_CACHE_DIR%g" $TINE20ROOT/phpstan.neon
    # create marker for cache cleanup
    date --utc +%FT%TZ > $PHPSTAN_CACHE_DIR-lastused

    $TINE20ROOT/tine20/vendor/bin/phpstan --version
    log "analyse target: $dir"
    set -o pipefail
    php -d memory_limit=2G $TINE20ROOT/tine20/vendor/bin/phpstan analyse --autoload-file=$TINE20ROOT/tine20/vendor/autoload.php --error-format=gitlab --no-progress -vvv $dir | tee ${CI_PROJECT_DIR}/code-quality-report.json
}