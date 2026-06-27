<?php

require_once __DIR__ . '/../config/Database.php';

require_once __DIR__ . '/../services/PendingJobService.php';

require_once __DIR__ . '/../services/UpdateJobService.php';

class JobController {

    public function pendingJobs() {

        $cn = Database::connect();

        $data = PendingJobService::getPendingJobs($cn);

        echo json_encode($data);
    }

    public function updateJob() {

        $cn = Database::connect();

        $input = json_decode(
            file_get_contents("php://input"),
            true
        );

        $result = UpdateJobService::processJob(
            $cn,
            $input
        );

        echo json_encode([
            "success" => $result
        ]);
    }

public function classifyJob() {

    $cn = Database::connect();

    $input = json_decode(
        file_get_contents("php://input"),
        true
    );

    require_once __DIR__ . '/../services/ClassifyJobService.php';

    $result = ClassifyJobService::classify(
        $cn,
        $input
    );

    echo json_encode($result);
}

}