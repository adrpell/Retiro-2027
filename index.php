<?php
define('RT2027_FRONT_CONTROLLER', 'index.php');
define('RT2027_DEFAULT_ROUTE', 'home');
if (!ob_get_level()) { ob_start(); }
require __DIR__ . '/app/router.php';
