/* global dstLogin */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Insert "Powered by LeagueApps Design Shop" below the logo
    var h1 = document.querySelector('#login h1');
    if (h1 && !document.querySelector('.ag-login-powered-by')) {
        var powered = document.createElement('div');
        powered.className = 'ag-login-powered-by';
        powered.textContent = 'Powered by LeagueApps Design Shop';
        h1.parentNode.insertBefore(powered, h1.nextSibling);
    }

    // Insert support link below the login form
    var form = document.querySelector('#loginform');
    if (form && !document.querySelector('.ag-login-support')) {
        var support = document.createElement('div');
        support.className = 'ag-login-support';
        support.innerHTML = 'Need help?<br>Visit <a href="' + dstLogin.academyUrl + '" target="_blank" rel="noopener noreferrer">Design Shop Academy</a> to manage your site.';
        form.parentNode.insertBefore(support, form.nextSibling);
    }
});
