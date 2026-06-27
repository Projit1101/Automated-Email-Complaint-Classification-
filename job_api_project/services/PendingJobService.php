<?php

class PendingJobService {

    public static function getPendingJobs($cn) {

        $sql = "
        SELECT
            docket_no,
            subject,
            `from`,
            `to`,
            cc,
            body,
            creation_date,
            attachment,
            DATE_FORMAT(creation_date, '%d/%m/%y %H:%i') AS creation_date_txt,
            cons_no,
            cust_id,
            cons_no_status,
            mr_no
        FROM email_parse p
        WHERE web_accepted_flag IS NULL
        AND NOT EXISTS (
            SELECT 1 FROM rpa_junk_mail_id j
            WHERE LOWER(j.mailid) = LOWER(p.`from`)
        )
        ORDER BY creation_date DESC
        ";

        $query = mysqli_query($cn, $sql);

        $data = [];

        while ($row = mysqli_fetch_assoc($query)) {
            $data[] = $row;
        }

        return $data;
    }
}