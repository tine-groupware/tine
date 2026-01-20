prevent_wrong_update_script_changes() {
    branchVersion=$(echo "$BASE_MAJOR_COMMIT_REF_NAME" | grep -Po 20..)
    wrong=""
    unset failed

    for f in $(find tine20/*/Setup/Update/*.php); do
        if ! versionLine=$(cat $f | grep -P '^ \* this ist? 20..\.11 \(ONLY!\)$'); then
            continue
        fi

        version=$(echo "$versionLine" | grep -Po 20..)
        if (( $version >= $branchVersion )); then
            continue
        fi

        if ! git diff origin/$CUSTOMER_MAJOR_COMMIT_REF_NAME$BASE_MAJOR_COMMIT_REF_NAME --quiet -- $f; then
            failed=1
            wrong="$wrong\n$f"
            git --no-pager diff origin/$CUSTOMER_MAJOR_COMMIT_REF_NAME$BASE_MAJOR_COMMIT_REF_NAME -- $f
        fi
    done

    if [ $failed ]; then
        echo
        echo -e -n "\033[1;31m"
        echo Update scripts should only be changed, on the corresponding versions branch.
        echo If you are sure you need to update this update script, you can skip this job,
        echo 'by setting the merge request label "allow-failure-prevent-wrong-update-script-changes"'.
        echo -e -n "\033[0;31m"
        echo -e "Changes: $wrong"
        echo -n -e "\033[0m"
        return 1
    fi
}