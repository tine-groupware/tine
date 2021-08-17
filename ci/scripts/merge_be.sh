cd $CI_BUILDS_DIR/tine20/tine20/tine20

if ! $CI_BUILDS_DIR/tine20/buildscripts/githelpers/merge/auto_merge_be.sh; then
    ${CI_BUILDS_DIR}/tine20/tine20/ci/scripts/send_matrix_message.sh $MATRIX_ROOM "Auto merge be failed in $CI_JOB_URL."
    exit 1
fi