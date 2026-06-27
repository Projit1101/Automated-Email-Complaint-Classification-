<?php

require_once __DIR__ . '/../middleware/JsonMiddleware.php';

JsonMiddleware::handle();

require_once __DIR__ . '/../routes/api.php';