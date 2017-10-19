const JsonEnv = require(process.env.JENV_FILE_NAME);
const {createHash} = require("crypto");
const {readFileSync} = require("fs");

const https = JsonEnv.php.mediawiki.SSL? 'https' : 'http';
let settingText = readFileSync(__dirname + '/LocalSettings.php', {encoding: 'utf8'}).trim();
settingText = settingText.replace(/\?>/, '');

const envConfig = {
	$wgServer: https + '://wiki.' + JsonEnv.baseDomainName,
	$wgSecretKey: createHash('sha256').update(__dirname).digest('hex'),
};
for (const name in JsonEnv.php.mediawiki) {
	if (!/^\$/.test(name)) {
		continue;
	}
	
	envConfig[name] = JsonEnv.php.mediawiki[name];
}

for (const name of Object.keys(envConfig)) {
	const value = envConfig[name];
	const replace = new RegExp('^(//|#|\\s)*' + escapeRegExp(name) + '\\1\\s*=.*;$', 'mg');
	const line = name + ' = ' + php_escape(value) + ';';
	settingText = replaceOrAppend(settingText, replace, line);
}

const extensions = JsonEnv.php.mediawiki.enabledExtension || [];

for (const name of extensions) {
	const replace = new RegExp('^wfLoadExtension\\(\s*([\'"])\s*' + escapeRegExp(name) + '\s*\\1\s*\\);$', 'm');
	const line = `wfLoadExtension(${JSON.stringify(name)});`;
	settingText = replaceOrAppend(settingText, replace, line);
}

console.log(settingText);

function escapeRegExp(str) {
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

function php_escape(value) {
	if (typeof value === 'string') {
		return JSON.stringify(value);
	} else if (typeof value === 'number') {
		return value.toString();
	} else if (typeof value === 'boolean') {
		return value? 'true' : 'false'
	} else {
		throw new TypeError('unknown type "' + (typeof value) + '" of value: ' + value);
	}
}

function replaceOrAppend(text, regex, line) {
	if (regex.test(text)) {
		return text.replace(regex, line);
	} else {
		return text + '\n' + line;
	}
}
