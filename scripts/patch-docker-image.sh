#!/bin/bash
set -e

if [ $# -lt 2 ] || [ $# -gt 4 ]; then
    echo usage:
    echo $0 '<release image> <release tag> [<patch ref>] [<target image>]'
    echo
    echo 'release image = image to be patched'
    echo '  release tag = git tag / git ref of the release image'
    echo '    patch ref = git ref of the patch. default: HEAD'
    echo ' target image = name of new image. default: <release-image>-patch<short commit sha of patch ref>'
    echo 
    echo example:
    echo './scripts/patch-docker-image.sh tinegroupware/tine:2023.11.15 2023.11.15'
    echo './scripts/patch-docker-image.sh tinegroupware/tine:2023.11.15 2023.11.15 HEAD~1 registry.rz1.metaways.net/tine/tine:2023.11.15-patch1'
    exit 1
fi
release_image=$1
release_ref=$2
patch_ref=${3:-HEAD}
new_image=${4:-$release_image-patch$(git rev-parse --short HEAD)}

tempdir=$(mktemp -d)

mkdir -p $tempdir/patch/usr/share

cd $(git rev-parse --show-toplevel)
# -r only needed to copy the icon set
cp -p -r --parents $(git diff --name-only $release_ref $patch_ref) $tempdir/patch/usr/share

printf '\033[0;32m'
echo changes:
(cd $tempdir/patch; find . -type f -printf '/%P\n')
printf '\033[0m'

cat > $tempdir/Dockerfile << EOF
FROM $release_image
COPY ./patch /
EOF

docker build --pull -t $new_image $tempdir

printf '\033[0;32m'
echo image name: $new_image
printf '\033[0m'

rm -rf $tempdir