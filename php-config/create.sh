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

node "${BUILD_ROOT}/jenv/create-login.js" | save-file "LocalSettings.jenv.php"
{
	echo "<?php"
	echo "require './LocalSettings.jenv.php';"
	node "${BUILD_ROOT}/mediawiki/filter.js" |  sed "/<\\?php/d; /\\?>/d"
	echo ""
} | save-file "LocalSettings.php"
