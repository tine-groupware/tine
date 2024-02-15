#!/bin/sh
set -e

echo $0 installing ...

if [ "$CI_IS_CUSTOMAPP" = "true" ]; then
	name=$(cat ${CI_PROJECT_DIR}/composer.json | jq -r '.name')

	cd ${TINE20ROOT}/tine20
	echo $0: composer config "repositories.ci" git "${CI_REPOSITORY_URL}";
	composer config "repositories.ci" git "${CI_REPOSITORY_URL}";
	echo $0: composer require "$name dev-master#${CI_COMMIT_SHA}";
	COMPOSER_ALLOW_SUPERUSER=1 composer require "$name dev-master#${CI_COMMIT_SHA}";
fi

echo $0 ... done
