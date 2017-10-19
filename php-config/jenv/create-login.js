const JsonEnv = require(process.env.JENV_FILE_NAME);
const {readFileSync} = require("fs");

const templateFile = __dirname + '/login.php';
let phpText = readFileSync(templateFile, {encoding: 'utf-8'});

define('CONIG_DB_SERVER', JsonEnv.database.mysql.hostname);
define('CONIG_DB_SUPER_PASSWORD', JsonEnv.database.mysql.superPassword);
define('CONIG_DB_PORT', 3306);
define('ACCOUNTS_HTTPS', !!JsonEnv.services.accounts.SSL);
define('PROXY_SERVER_NAME', proxy(JsonEnv.gfw.proxy || ''));
define('BASE_DOMAIN', JsonEnv.baseDomainName);

console.log(phpText);

/* funcs */

function proxy(url) {
	return require('url').parse(url).host;
}

function define(name, value) {
	const reg = new RegExp(`('|")JENV::${name}\\1`, 'ig');
	if (!reg.test(phpText)) {
		const php = `const ${name} = 'JENV::${name}';`;
		console.error('put this to file: %s\n\n\t%s\n', templateFile, php);
		process.exit(100);
	}
	phpText = phpText.replace(reg, s(value));
}

function s(value) {
	if (typeof value === 'string') {
		return JSON.stringify(value);
	} else if (typeof value === 'number') {
		return value.toString();
	} else if (typeof value === 'boolean') {
		return value? 'true' : 'false';
	} else {
		throw new Error(`invalid value: (${typeof value}) ${value}`);
	}
}
