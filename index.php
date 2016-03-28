<?php 

session_start();

include($_SERVER['DOCUMENT_ROOT'].'/classes/Google/autoload.php');

// Autoloading of all needed classes
spl_autoload_register(function ($class_name) {
	include $_SERVER['DOCUMENT_ROOT'].'/classes/' . $class_name . '.php';
});

// Call the app
$app = new Application();
$app->run(array_merge($_GET, $_POST, $_SESSION));