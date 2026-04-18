<?php
define('RT2027_FRONT_CONTROLLER', 'login.php');
define('RT2027_DEFAULT_ROUTE', 'entry');
if (!ob_get_level()) { ob_start(); }
require __DIR__ . '/app/router.php';
