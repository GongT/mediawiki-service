#!/usr/bin/env bash

export TEMP="${BUILD_ROOT}/temp"
export EXTRACT="${BUILD_ROOT}/temp/extract"

function fetch-remote() {
	local REMOTE_TYPE="$1"
	local REMOTE_URL="$2"
	
	local FILE_NAME=$(basename "${REMOTE_URL}")
	if [ -e "${TEMP}/${FILE_NAME}" ]; then
		echo "    skip download: target file exists"
		echo "        -> ${TEMP}/${FILE_NAME}"
		return
	fi
	
	local FILE_PATH=$(download "${REMOTE_URL}")
	
	case "${REMOTE_TYPE}" in
	zip|tar)
		echo "  extract file at ${EXTRACT}"
		rm -fr "${EXTRACT}"
		ensure-pushd "${EXTRACT}"
		
		if [ "${REMOTE_TYPE}" = "zip" ]; then
			unzip -qo "${FILE_PATH}"
		elif [ "${REMOTE_TYPE}" = "tar" ]; then
			tar x --overwrite -f "${FILE_PATH}"
		fi
		
		if [ $(ls | wc -l) -eq 1 ]; then
			cp -fr ./*/. "${DOCUMENT_ROOT}"
		else
			cp -fr . "${DOCUMENT_ROOT}"
		fi
		popd
		rm -fr "${EXTRACT}"
	;;
	*)
		die "failed: unknown remote type: ${REMOTE_TYPE}"
	esac
}



function download() {
	local FILE_NAME=$(basename "${1}")
	ensure-dir "${TEMP}"
	wget -c "$1" -O "${TEMP}/${FILE_NAME}" --progress=bar:force:noscroll >&2
	echo "${TEMP}/${FILE_NAME}"
}
