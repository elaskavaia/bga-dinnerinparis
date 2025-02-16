const path = require("path");
const WebpackAutoInject = require("webpack-auto-inject-version-next");

module.exports = {
	mode: "production",
	entry: {
		main: "./src/js/app.js",
	},
	plugins: [
		new WebpackAutoInject(),
	],
	output: {
		// BGA won't resolve translations in modules/dist, only in modules
		path: path.resolve(__dirname, "modules/js"),
		filename: "[name].bundle.js",
		library: {
			type: "amd",
			export: "default",
		},
	},
};
