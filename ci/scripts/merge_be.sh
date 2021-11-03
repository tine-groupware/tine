#!/bin/bash
source ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/ci/scripts/merge_helper.sh

git config merge.pofile.name "merge po-files driver"
git config merge.pofile.driver "./scripts/git/merge-po-files %A %O %B"
git config merge.pofile.recursive "binary"

cd ${CI_BUILDS_DIR}/${CI_PROJECT_NAMESPACE}/tine20/tine20

####### 2019.11
memerge_upwardsrge 2019.11 tine20.com/2019.11-custom customers
merge_upwards tine20.com/2019.11-custom tine20.com/2019.11 customers

### 2020.11
merge_upwards 2020.11 tine20.com/2020.11-custom customers
merge_upwards tine20.com/2020.11-custom tine20.com/2020.11 customers

### 2021.11
merge_upwards tine20.com/2020.11-custom tine20.com/2021.11-custom customers
merge_upwards 2021.11 tine20.com/2021.11-custom customers
merge_upwards tine20.com/2021.11-custom tine20.com/2021.11 customers

### 2022.11
merge_upwards tine20.com/2021.11-custom tine20.com/2022.11-custom customers
merge_upwards 2022.11 tine20.com/2022.11-custom customers
merge_upwards tine20.com/2022.11-custom tine20.com/2022.11 customers

### saas
merge_upwards 2021.11 saas/2021.11-custom customers
merge_upwards tine20.com/2021.11-custom saas/2021.11 customers
merge_upwards saas/2021.11-custom saas/2021.11 customers