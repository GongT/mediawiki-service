exports.createNginxConfig = function (projectName, nginxRoot, phpRoot, listenPort) {
	return `
server {
	server_name ${projectName}.${JsonEnv.baseDomainName};
	listen ${listenPort};
	root ${nginxRoot};
	
	set $cgi_document_root "${phpRoot}";
	
	index index.php;
	autoindex on;
	try_files $uri "$\{uri}index.php?$query_string";
	
	proxy_set_header X-Https "$https$http_x_https";
	proxy_set_header X-Http2 "$http2$http_x_http2";
	proxy_set_header Host $host;
	
	include allow_php.conf;
}
`.trim() + '\n';
};
