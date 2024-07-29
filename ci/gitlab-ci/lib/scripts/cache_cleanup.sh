# requres gnu date - busybox date wont work
function cache_cleanup_atomic_dir_cache () {
    MAX_AGE_USED_CACHE=$1
    MAX_AGE_CACHE=$2

    dirs=$(find . -type d -path './*' -prune -print)

    threshold_used=$(( $(date +%s) -  60 * 60 * 24 * $MAX_AGE_USED_CACHE ))
    threshold=$(( $(date +%s) -  60 * 60 * 24 * $MAX_AGE_CACHE ))

    for dir in $dirs; do
        if [ -f $dir-lastused ]; then
            lastused=$(date -d $(cat $dir-lastused) +%s)

            if [ $lastused -gt $threshold_used ]; then
                continue
            fi

            rm -f $dir-lastused
        fi

        modified=$(stat -c %Y $dir)
        if [ $modified -gt $threshold ]; then
            continue
        fi

        rm -rf $dir
    done
}

function cache_cleanup_vendor_dir_cache () {
    MAX_AGE_USED_CACHE=14
    MAX_AGE_CACHE=2

    cd ${CI_CUSTOM_CACHE_DIR}/${CI_PROJECT_NAMESPACE}/tine20/composer-cache/v1/

    cache_cleanup_atomic_dir_cache $MAX_AGE_USED_CACHE $MAX_AGE_CACHE
}

function cache_cleanup_npm_dir_cache () {
    MAX_AGE_USED_CACHE=14
    MAX_AGE_CACHE=2

    cd ${CI_CUSTOM_CACHE_DIR}/${CI_PROJECT_NAMESPACE}/tine20/npm-cache/v1/

    cache_cleanup_atomic_dir_cache $MAX_AGE_USED_CACHE $MAX_AGE_CACHE
}

function cache_cleanup_phpstan_cache () {
    MAX_AGE_USED_CACHE=31
    MAX_AGE_CACHE=7

    dirs=$(find ${CI_CUSTOM_CACHE_DIR}/${CI_PROJECT_NAMESPACE} -type d -path ${CI_CUSTOM_CACHE_DIR}/${CI_PROJECT_NAMESPACE}/'*' -prune -print)
    for dir in $dirs; do
        if ! [ -d $dir/phpstan-cache/v2/ ]; then
            continue
        fi

        cd $dir/phpstan-cache/v2/
        cache_cleanup_atomic_dir_cache $MAX_AGE_USED_CACHE $MAX_AGE_CACHE
    done
}