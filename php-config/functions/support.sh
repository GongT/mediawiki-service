#!/usr/bin/env bash

function ensure-dir() {
	if ! [ -d "$1" ]; then
		mkdir -p "$1"
	fi
}
function ensure-parent-dir() {
	ensure-dir "$(dirname "$1")"
}
function ensure-pushd() {
	ensure-dir "$1"
	pushd "$1"
}

function log_error() {
	echo -ne "\e[38;5;9m" >&2
	echo -n "$@" >&2
	echo -e "\e[0m" >&2
}
function die() {
	log_error "$@"
	exit 1
}

function is_run_in_docker() {
	[ "${RUN_IN_DOCKER}" = "yes" ]
}
function is_build() {
	[ "${IS_BUILD_MODE}" = "yes" ]
}
function save-file() {
	cat > "${DOCUMENT_ROOT}/$1"
}
