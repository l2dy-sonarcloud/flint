#! /usr/bin/env bash

target='x86_64-generic-linux-musl'

fossil_version='2.12.1'
fossil_url="https://fossil-scm.org/home/uv/fossil-src-${fossil_version}.tar.gz"
fossil_sha256='822326ffcfed3748edaf4cfd5ab45b23225dea840304f765d1d55d2e6c7d6603'
fossil_archive="fossil-${fossil_version}.tar.gz"
fossil_dir='fossil'

libressl_version='3.1.4'
libressl_url="http://ftp.openbsd.org/pub/OpenBSD/LibreSSL/libressl-${libressl_version}.tar.gz"
libressl_sha256='414c149c9963983f805a081db5bd3aec146b5f82d529bb63875ac941b25dcbb6'
libressl_archive="libressl-${libressl_version}.tar.gz"
libressl_dir='libressl'

kitcreator_version="0.12.0"
kitcreator_url="http://www.rkeene.org/devel/kitcreator-${kitcreator_version}.tar.gz"
kitcreator_sha256="ee01de0457653aceb8df503196190c134c8129dc14de5e3cf5520b10844f32bd"
kitcreator_archive="kitcreator-${kitcreator_version}.tar.gz"
kitcreator_dir='kitcreator'

# Base options
fossil_configure_options=( --json )

# Platform-specific options
case "${target}" in
	x86_64-*-linux-musl)
		fossil_configure_options=( "${fossil_configure_options[@]}" --static --disable-fusefs --with-openssl=auto )
		;;
esac

# File to create
result="fossil-${fossil_version}-${target}"

# Clean-up the result file before starting
rm -f "${result}" "${result}.info" "${result}.log"

# Helper functions
function random() {
	openssl rand -base64 6 | tr '/+' 'ZZ'
}

function downloadFile() {
	local downloadProgram downloadProgramPath file urls
	local downloadProgramArgs
	local url
	local authoritativeURL

	downloadProgram="$1"
	downloadProgramPath="$2"
	file="$3"
	shift 3

	urls=("$@")

	authoritativeURL="${urls[@]: -1}"

	case "${downloadProgram}" in
		curl)
			downloadProgramArgs=(--header "X-Cache-URL: ${authoritativeURL}" --location --insecure --fail --output "${file}")
			;;
		wget)
			downloadProgramArgs=(--header="X-Cache-URL: ${authoritativeURL}" --no-check-certificate --output-document="${file}")
			;;
	esac

	for url in "${urls[@]}" __fail__; do
		rm -f "${file}"

		if [ "${url}" = '__fail__' ]; then
			return 1
		fi

		"${downloadProgramPath}" "${downloadProgramArgs[@]}" "${url}" && break
	done

	return 0
}

function verifyHash() {
	local file hash hashMethod
	local checkHash

	file="$1"
	hash="$2"
	hashMethod="$3"

	if [ "${hashMethod}" = 'null' ]; then
		return 0
	fi

	checkHash="$(openssl dgst "-${hashMethod}" "${file}" | sed 's@.*= *@@')"

	if [ "${checkHash}" = "${hash}" ]; then
		return 0
	fi

	echo "Hash (${hashMethod}) mismatch: Got: ${checkHash}; Expected: ${hash}" >&2

	return 1
}

function download() {
	local url file hash
	local tryDownloadProgram tryDownloadProgramPath downloadProgram downloadProgramPath
	local hashMethod
	local urls

	url="$1"
	file="$2"
	hash="$3"
	hashMethod='sha256'

	for tryDownloadProgram in wget curl; do
		tryDownloadProgramPath="$(command -v "${tryDownloadProgram}" 2>/dev/null)"

		if [ -z "${tryDownloadProgramPath}" ]; then
			continue
		fi

		if [ -x "${tryDownloadProgramPath}" ]; then
			downloadProgram="${tryDownloadProgram}"
			downloadProgramPath="${tryDownloadProgramPath}"

			break
		fi
	done


	urls=("${url}")

	if [ "${hashMethod}" != 'null' ]; then
		urls=(
			"http://hashcache.rkeene.org/${hashMethod}/${hash}"
			"${urls[@]}"
		)
	fi

	rm -f "${file}.new" || exit 1
	downloadFile "${downloadProgram}" "${downloadProgramPath}" "${file}.new" "${urls[@]}" || return 1

	verifyHash "${file}.new" "${hash}" "${hashMethod}" || return 1

	mv "${file}.new" "${file}" || return 1

	return 0
}

function extract() {
	local file dir
	local dir_parent
	local decompress extract

	file="$1"
	dir="$2"

	dir_parent="$(cd "$(dirname "${dir}")" && pwd)" || return 1

	case "${file}" in
		*.tar.gz|*.tgz)
			decompress=(gzip -dc)
			extract=(tar -xf -)
			;;
		*.tar.bz2|*.tbz2)
			decompress=(bzip2 -dc)
			extract=(tar -xf -)
			;;
		*.tar.xz|*.tar.xz)
			decompress=(xz -dc)
			extract=(tar -xf -)
			;;
		*)
			echo "Unsupported file type: ${file}" >&2
			return 1
			;;
	esac

	cat "${file}" | (
		cd "${dir_parent}" || exit 1
		tmpdir=".extract-$(random)"

		rm -rf "${tmpdir}" || exit 1
		mkdir "${tmpdir}" || exit 1

		(
			cd "${tmpdir}" || exit 1
			"${decompress[@]}" | "${extract[@]}" || exit 1

			shopt -s dotglob

			check_file="$(echo *)"
			if [ -e "${check_file}" ]; then
				mv "${check_file}"/* .
				rmdir "${check_file}"
			fi
		) || exit 1

		rm -rf "${dir}"
		mv "${tmpdir}" "${dir}" || exit 1
	) || return 1

	return 0
}

workdir="${TMPDIR:-/tmp}/build-fossil-static.$(random)"
mkdir -p "${workdir}" || exit 1
trap cleanup EXIT

function cleanup() {
	if [ -n "${workdir}" ]; then
echo		rm -rf "${workdir}"
		workdir=''
	fi
}

# Begin the build
retval='0'

## Build LibreSSL
echo -n 'Building LibreSSL...'
(
	# Operate this sub-shell in the work directory
	cd "${workdir}"

	download "${libressl_url}" "${libressl_archive}" "${libressl_sha256}" || exit 1

	extract "${libressl_archive}" "${libressl_dir}" || exit 1
	cd "${libressl_dir}" || exit 1

	# Setup cross-compiler
	## From Build-CC 0.9+
	eval $(~/root/cross-compilers/setup-cc "${target}") || exit 1

	# This defeats hardening attempts that break on various platforms
	CFLAGS=' -g -O0 '
	export CFLAGS

	./configure --with-pic --disable-shared --enable-static  --host="${target}" "${libressl_configure_options[@]}" --prefix="$(pwd)/INST" --with-openssldir=/etc/ssl || exit 1

	# Disable building the apps -- they do not get used
	rm -rf apps
	mkdir apps
	cat << \_EOF_ > apps/Makefile
%:
	@echo Nothing to do
_EOF_

	${MAKE:-make} V=1 || exit 1

	${MAKE:-make} V=1 install || exit 1
) > "${result}.log" 2>&1 || retval='1'
if [ "${retval}" = '0' ]; then
	# Notify of success
	echo ' success!'
else
	echo ' failed.'
	exit 1
fi

## Build static KitDLL
echo -n 'Building KitDLL...'
(
	# Operate this sub-shell in the work directory
	cd "${workdir}"

	download "${kitcreator_url}" "${kitcreator_archive}" "${kitcreator_sha256}" || exit 1

	extract "${kitcreator_archive}" "${kitcreator_dir}" || exit 1
	cd "${kitcreator_dir}" || exit 1

	# Setup cross-compiler
	## From Build-CC 0.9+
	eval $(~/root/cross-compilers/setup-cc "${target}") || exit 1

	export KITCREATOR_PKGS=' kitdll '
	export KITCREATOR_STATIC_KITDLL='1'
	export KITCREATOR_MINBUILD='1'

	./kitcreator --host="${target}" tcl_cv_strtoul_unbroken=ok || exit 1

	mv libtclkit-sdk-*.tar.gz libtclkit-sdk.tar.gz || exit 1

	# Extract to INST directory
	extract libtclkit-sdk.tar.gz INST || exit 1

	# Replace TCLKIT_SDK_DIR to allow Fossil's naive parser to handle it
	export TCLKIT_SDK_DIR="${workdir}/kitcreator/INST/"
	sed 's|${TCLKIT_SDK_DIR}|'"${TCLKIT_SDK_DIR}"'|g' < "${TCLKIT_SDK_DIR}/lib/tclConfig.sh" > "${TCLKIT_SDK_DIR}/lib/tclConfig.sh.1"
	mv "${TCLKIT_SDK_DIR}/lib/tclConfig.sh.1" "${TCLKIT_SDK_DIR}/lib/tclConfig.sh"

	# Dump logs
	grep '^' */build.log
) >> "${result}.log" 2>&1 || retval='1'
if [ "${retval}" = '0' ]; then
	# Notify of success
	echo ' success!'
else
	echo ' failed.'
	exit 1
fi

## Give status
echo -n 'Building Fossil...'

## Really build
(
	# Operate this sub-shell in the work directory
	cd "${workdir}"

	# Download the archive
	download "${fossil_url}" "${fossil_archive}" "${fossil_sha256}" || exit 1

	# Extract the archive
	extract "${fossil_archive}" "${fossil_dir}" || exit 1
	cd "${fossil_dir}" || exit 1

	# Setup cross-compiler
	## From Build-CC 0.9+
	eval $(~/root/cross-compilers/setup-cc "${target}") || exit 1

	# Setup to use LibreSSL we just compiled
	export PKG_CONFIG_PATH="${workdir}/libressl/INST/lib/pkgconfig"

	# Setup to use KitCreator's KitDLL
	export TCLKIT_SDK_DIR="${workdir}/kitcreator/INST/"

	# Configure fossil as required for this platform
	./configure --host="${target}" --with-tcl="${TCLKIT_SDK_DIR}/lib" "${fossil_configure_options[@]}" || exit 1

	# Build fossil
	make || exit 1

	# Strip executable of debugging symbols
	"${target}-strip" fossil
) >> "${result}.log" 2>&1 || retval='1'

if [ "${retval}" = '0' ]; then
	# Notify of success
	echo ' success!'

	# Copy built executable to current directory
	cp "${workdir}/${fossil_dir}/fossil" "${result}" || exit 1

	# Print out information related to the build
	(
		ls -lh "${result}" || exit 1
		file "${result}" || exit 1
		openssl sha256 "${result}" || exit 1
		strings "${result}" | egrep '^(TLSv1 part of |LibreSSL)' | head -n 1
	) | tee "${result}.info"
else
	# Notify of failure
	echo ' failed.'
fi

# Exit
exit "${retval}"
