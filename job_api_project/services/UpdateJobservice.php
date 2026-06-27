<?php

class UpdateJobService {

    public static function processJob($cn, $data) {
        return self::update_complaint($cn, $data);
    }

    private static function update_complaint($cn, $dataArr) {
        if (empty($dataArr['docket'])) return false;
        if (empty($dataArr['comp_cat']) || empty($dataArr['comp_type'])) return false;

        # Get Mail Complaint Details
        $stmt = mysqli_prepare($cn, 'SELECT *, DATE_FORMAT(`creation_date`, \'%d/%m/%y\') AS `docket_dt` FROM `email_parse` WHERE `web_accepted_flag` IS NULL AND `docket_no` = ?');
        mysqli_stmt_bind_param($stmt, 's', $dataArr['docket']);
        mysqli_stmt_execute($stmt);
        $query = mysqli_stmt_get_result($stmt);
        if (!$query) {
            echo '<p align="center">' . mysqli_error($cn) . '</p>';
            return false;
        }
        $mail_complaint_dtl = array();
        $rs = mysqli_fetch_assoc($query);
        $mail_complaint_dtl = $rs;
        mysqli_free_result($query);
        mysqli_stmt_close($stmt);

        # Consumer No. & MR No.
        $consno_11 = $dataArr['consno'];
        $cons_id = $dataArr['custid'];
        $mr_no = $dataArr['mrno'];

        # Complaint Type
        switch ($dataArr['comp_cat']) {
            case 'billing':
                $complaint_type = 'C';
                break;
            case 'supply':
                $complaint_type = 'S';
                break;
            case 'newconn':
                $complaint_type = 'M';
                break;
        }

        $consno_10 = '';
        $chk_dgt = '';
        if (empty($cons_id)) {
            $consno_10 = (!empty($consno_11)) ? substr($consno_11, 0, -1) : '';
            $chk_dgt = (!empty($consno_11)) ? substr($consno_11, 10, 1) : '';
        } elseif (empty($consno_11) && !empty($cons_id)) {
            $consno_10 = (!empty($consno_11)) ? substr($consno_11, 0, -1) : '';
            $chk_dgt = (!empty($consno_11)) ? substr($consno_11, 10, 1) : '';
            $cons_id = substr($cons_id, 0, 10);
        } elseif (!empty($cons_id) && !empty($consno_11)) {
            $consno_10 = substr($consno_11, 0, -1);
            $chk_dgt = substr($consno_11, 10, 1);
            $cons_id = substr($cons_id, 0, 10);
        }

        # Complaint Category -> Complaint Type (from drop-down options)
        $itCompType = self::getItComType($cn, $dataArr['comp_type']);

        # Check Docket Exist via dummy API
        $docketExistReturn = self::docketExist($consno_10, $itCompType);
        if ($docketExistReturn !== false) return $docketExistReturn;

        # Update Email Parse
        $updateResult = self::updateEmailParse($cn, $dataArr);
        if (!$updateResult) {
            return false;
        }

        return true;
    }

    private static function updateEmailParse($cn, $dataArr) {
        $stmt = mysqli_prepare($cn, 'UPDATE `email_parse` SET `web_accepted_flag` = \'y\', `web_accepted_datetime` = ?, `web_updated_by` = ?, `web_updated_ip` = ?, `cons_no` = ?, `cust_id` = ?, `mr_no` = ? WHERE `docket_no` = ?');
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param(
            $stmt,
            'sssssss',
            $dataArr['curdt'],
            $dataArr['login_by'],
            $dataArr['remote_addr'],
            $dataArr['consno'],
            $dataArr['custid'],
            $dataArr['mrno'],
            $dataArr['docket']
        );
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    private static function getItComType($cn, $compCode) {
        $itCompCode = '';
        if (empty($compCode)) return $itCompCode;

        $stmt = mysqli_prepare($cn, 'SELECT it_com_type FROM lkup_complaint WHERE complaint_type = "1" AND com_code = ?');
        if (!$stmt) {
            echo 'Invalid query: ' . mysqli_error($cn);
            return $itCompCode;
        }
        mysqli_stmt_bind_param($stmt, 's', $compCode);
        mysqli_stmt_execute($stmt);
        $query = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($query) == 0) return $itCompCode;
        $rs = mysqli_fetch_assoc($query);
        $itCompCode = empty($rs['it_com_type']) ? '' : $rs['it_com_type'];
        mysqli_stmt_close($stmt);

        return $itCompCode;
    }


private static function docketExist($con_10, $itCompType) {
       $url = 'http://localhost/dummy_docket/index.php'
        . '?module=SEARCH'
        . '&consno=' . urlencode($con_10)
        . '&constp=' . urlencode($itCompType)
        . '&src=E';
    /* $url = 'https://itwebwaf.cesc.co.in:8030/itltweb/ws_complaint_duplicate.jsp'
        . '?module=SEARCH'
        . '&consno=' . urlencode($con_10)
        . '&constp=' . urlencode($itCompType)
        . '&src=E'; */

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    /* curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); */
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return false;

    $result = json_decode($response, true);
    $dkt = $result['DKT'] ?? '';

    if ($dkt === 'NODUPLDKT') return false;
    else return $dkt;
}

/* function delete_complaint($cn = '', $docket = '', $login_by = '') {
    if (empty($docket)) return false;

    if(empty($login_by)) return false;

    $curdt = date('Y-m-d H:i:s');
    $remote_addr = $_SERVER['REMOTE_ADDR'];
   
    $sql = 'UPDATE `email_parse` SET `web_accepted_flag`=\'d\',`web_accepted_datetime`=' . set_strval($cn, $curdt) . ',';
    $sql .= '`web_updated_by`=' . set_strval($cn, $login_by) . ',`web_updated_ip`=' . set_strval($cn, $remote_addr) . ' WHERE `docket_no` = ' . set_strval($cn, $docket);
    //echo $sql . '<br><br>';
    if (!mysqli_query($cn, $sql)) {
        //echo mysqli_error($cn) . '<br>' . $sql;
        return false;
    }
    return true;*/
} 
