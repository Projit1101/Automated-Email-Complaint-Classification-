<?php

require_once '../controllers/JobController.php';
require_once '../controllers/AuthController.php';

$controller = new JobController();
$auth       = new AuthController();

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

if (
    $method === 'POST'
    && strpos($uri, '/api/auth/login') !== false
) {
    $auth->login();
    exit;
}

elseif (
    $method === 'GET'
    && strpos($uri, '/api/jobs/pending') !== false
) {
    $controller->pendingJobs();
}

elseif (
    $method === 'POST'
    && strpos($uri, '/api/jobs/update') !== false
) {
    $controller->updateJob();
}

elseif (
    $method === 'POST'
    && strpos($uri, '/api/jobs/classify') !== false
) {
    $controller->classifyJob();
}