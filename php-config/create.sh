#!/usr/bin/env bash

set -e
if [ -z "${DOCUMENT_ROOT}" ]; then
	echo "missing required runtime environment: DOCUMENT_ROOT." >&2
	exit 1
fi

if [ -z "${REMOTE_URL}" ] || [ -z "${REMOTE_TYPE}" ]; then
	echo "missing remote url & type in build.config.ts" >&2
	exit 1
fi

cd "$(dirname "$(realpath "${BASH_SOURCE[0]}")")"
source "functions/env.sh"
source "functions/support.sh"
source "functions/fetch-remote.sh"

fetch-remote "${REMOTE_TYPE}" "${REMOTE_URL}"

cd "${BUILD_ROOT}/../extensions"
PLUGIN_DIR="${DOCUMENT_ROOT}/extensions"
for i in *; do
	if [ ! -e "${PLUGIN_DIR}/$i" ]; then
		echo copy "$i" "${PLUGIN_DIR}/$i"
		cp -r "$i" "${PLUGIN_DIR}/$i"
	fi
done

node "${BUILD_ROOT}/jenv/create-login.js" | save-file "LocalSettings.jenv.php"
{
	echo "<?php"
	echo "require './LocalSettings.jenv.php';"
	node "${BUILD_ROOT}/mediawiki/filter.js"
	if is_build ; then
	fi
	echo ""
} | save-file "LocalSettings.php"
