#!/usr/bin/env bash

cd "$(realpath "$(dirname "${BASH_SOURCE[0]}")")"
docker exec php-fpm sh -c "
cd '$(pwd)/document-root/'
php \
	./maintenance/changePassword.php \
	--user=admin \
	'--password=$1'
"
