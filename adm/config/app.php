<?php
declare(strict_types=1);

define('APP_NAME', 'SiteTools');
define('APP_ENV', 'local'); // local | production
define('BASE_URL', '/SiteTools'); // Ex.: '' em domínio na raiz
define('ADMIN_SESSION_NAME', 'sitetools_admin');
define('TIMEZONE', 'America/Sao_Paulo');

date_default_timezone_set(TIMEZONE);

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
