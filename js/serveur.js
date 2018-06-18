switch (document.location.hostname) {
    case 'localhost':
		var root = window.location.protocol + "//" + window.location.host + "/temporease/";
		var server = root + "php/";
        break;
    default:
        // for other servers : go to online server
		var root = "http://temporease.000webhostapp.com/";
		var server = root + "php/";
}