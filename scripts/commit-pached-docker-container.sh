#!/bin/env -S bash -e

# This script builds a new image, with the same tag, form a running tine counter. This image will
# contains all changes made to /usr/share/tine20/ at runtime.

name="$1"

if [[ -z "$name" ]]; then
    echo "Missing container name or id" > /dev/stderr
    echo "usage: $0 <container name or id>" > /dev/stderr
    echo "run: \"docker compose ps web --format '{{ .Name }}'\" to find container name" > /dev/stderr
    exit 1
fi

if [[ $(whoami) != "root" ]]; then
    echo "This script must be run as root!"> /dev/stderr
    exit 1
fi

# Get tag of currently running image. It needs to be looked up by the image sha, as `image:tag` might
# already be overwritten. `RepoTags` must be used instead of (the seemingly better) `RepoDigests`, as 
# locally patched images, do not have any repo `RepoDigests`
imageSha=$(docker container inspect "$name" -f '{{ .Image }}')
imageRepoTag=$(docker image inspect $imageSha -f '{{ index .RepoTags 0 }}')

# Get image name. This is the `image:tag` used to create the container. This might not be the actually
# running image anymore.
imageName=$(docker container inspect "$name" -f '{{ .Config.Image }}')

# Tag currently running image with unix timestamp. This is needed to ensure the currently running
# container keeps atlas one tag. Otherwise creating a second patch image from a running container (, 
# with out recreate,) will fail.
docker tag $imageSha $imageName-$(date +%s)

# Get diff dir path of overlay fs. Here are all changes made at runtime.
runtimeDiffDir=$(docker container inspect "$name" -f '{{ .GraphDriver.Data.UpperDir }}')

if [[ ! -d $runtimeDiffDir/usr/share/tine20/ ]]; then
    echo 'No Changes found in "/usr/share/tine20/"' > /dev/stderr
    exit 1
fi

# (Dockerfile can not be written to /tmp)
dockerfile=$(dirname $runtimeDiffDir)/Dockerfile

# Create a Dockerfile merging usr/share/tine20/, from the runtime diff, onto the currently running image.
cat > $dockerfile << EOF
FROM $imageRepoTag
COPY usr/share/tine20/ /usr/share/tine20
EOF

# Build docker image. And overwrite container image:tag with the newly build image. This will cause the
# container (Not actual the same container, but a container which is expected to have the same spec.) to
# use the patched image, when recreated.
docker build -f $dockerfile -t "$imageName" $runtimeDiffDir

echo "Container $name with image $imageName has been patched. If the container is restarted the patched image will be used."

rm -f $dockerfile