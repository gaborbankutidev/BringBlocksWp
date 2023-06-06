/** @type {import("prettier").Config} */
const config = {
	plugins: [require.resolve("@prettier/plugin-php")],
	arrowParens: "always",
	bracketSameLine: false,
	bracketSpacing: false,
	endOfLine: "lf",
	htmlWhitespaceSensitivity: "css",
	printWidth: 100,
	proseWrap: "preserve",
	quoteProps: "as-needed",
	semi: true,
	singleQuote: false,
	tabWidth: 1,
	trailingComma: "all",
	useTabs: true,

	phpVersion: "8.1",
	trailingCommaPHP: true,
	braceStyle: "1tbs",
};

module.exports = config;
