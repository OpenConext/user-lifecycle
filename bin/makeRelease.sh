#!/bin/sh

PREVIOUS_SF_ENV=${SYMFONY_ENV}
export SYMFONY_ENV="prod"

RELEASE_DIR=${HOME}/Releases
GITHUB_USER=OpenConext
PROJECT_NAME=OpenConext-user-lifecycle

if [ -z "$1" ]
then

cat << EOF
Please specify the tag or branch to make a release of.

Examples:

    sh makeRelease.sh 0.1.0
    sh makeRelease.sh master
    sh makeRelease.sh develop

If you want to GPG sign the release, you can specify the "sign" parameter, this will
invoke the gpg command line tool to sign it.

   sh makeRelease 0.1.0 sign

EOF
exit 1
else
    TAG=$1
fi

PROJECT_DIR_NAME=${PROJECT_NAME}-${TAG//\//_} &&
PROJECT_DIR=${RELEASE_DIR}/${PROJECT_DIR_NAME} &&

echo "Preparing environment" &&
mkdir -p ${RELEASE_DIR} &&
rm -rf ${PROJECT_DIR} &&

echo "Cloning repository" &&
cd ${RELEASE_DIR} &&
git clone https://github.com/${GITHUB_USER}/${PROJECT_NAME}.git ${PROJECT_DIR_NAME} &&

echo "Checking out ${TAG}" &&
cd ${PROJECT_DIR} &&
git checkout ${TAG} &&

echo "Running Composer Install" &&
composer install -n --no-dev --prefer-dist -o &&

echo "Tagging the release in RELEASE file" &&
COMMITHASH=`git rev-parse HEAD` &&
echo "Tag: ${TAG}" > ${PROJECT_DIR}/RELEASE &&
echo "Commit: ${COMMITHASH}" >> ${PROJECT_DIR}/RELEASE &&

echo "Cleaning build of dev files" &&
rm -rf ${PROJECT_DIR}/.idea &&
rm -rf ${PROJECT_DIR}/.git &&
rm -f ${PROJECT_DIR}/.gitignore &&
rm -f ${PROJECT_DIR}/bin/makeRelease.sh &&
rm -rf ${PROJECT_DIR}/*.xml &&
rm -rf ${PROJECT_DIR}/tests &&
rm -rf ${PROJECT_DIR}/.travis.yml &&
rm -rf ${PROJECT_DIR}/travis.php.ini &&
rm -rf ${PROJECT_DIR}/ansible &&
rm -rf ${PROJECT_DIR}/Vagrantfile &&
rm -rf ${PROJECT_DIR}/docs &&
rm -rf ${PROJECT_DIR}/.docheader &&

echo "Create tarball" &&
cd ${RELEASE_DIR} &&
tar -czf ${PROJECT_DIR_NAME}.tar.gz ${PROJECT_DIR_NAME}


echo "Create checksum file" &&
cd ${RELEASE_DIR} &&
if hash sha1sum 2>/dev/null; then
    sha1sum ${PROJECT_DIR_NAME}.tar.gz > ${PROJECT_DIR_NAME}.sha
else
    shasum ${PROJECT_DIR_NAME}.tar.gz > ${PROJECT_DIR_NAME}.sha
fi

if [ -n "$2" ]
then
	if [ "$2" == "sign" ]
	then
	    echo "Signing build"
		cd ${RELEASE_DIR}
		gpg -o ${PROJECT_DIR_NAME}.sha.gpg  --clearsign ${PROJECT_DIR_NAME}.sha
	fi
fi

export SYMFONY_ENV=${PREVIOUS_SF_ENV}
