// Change for local enviroment, example code in sample-env.js
var envSettings = {
	"stageserver":"externalweb.stage.example.com",
	"prodserver":"externalweb.common.example.com",
	"basefolder":"/var/www/sites/",
	"themefolder":"/wp-content/themes/zonemaster-frontend",
	"stagesite": "stage.zonemaster.example.com",
	"prodsite": "zonemaster.example.com",
};

// Avoid js errors
if ( typeof module !== "undefined" && module.exports !== "undefined" ) {
	module.exports = envSettings;
}
