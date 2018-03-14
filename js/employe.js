switch (document.location.hostname) {
    case 'localhost':
        var rootFolder = 'temporease/';
        break;
    default:
        // for other servers
        var rootFolder = '';
}
var root = window.location.protocol + "//" + window.location.host + "/" + rootFolder;
var server = root + "php/";
