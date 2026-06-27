<?php

class ClassifyJobService {

    public static function classify($cn, $data) {

        # =====================================
        # INPUT — received from Python bot
        # =====================================

        $docket_no     = $data['docket_no']     ?? '';
        $category      = $data['category']      ?? '';
        $sub_category  = $data['sub_category']  ?? '';
        $err_message   = $data['err_message']   ?? '';
        $automail      = $data['automail']      ?? 'NO';
        $send_mail     = $data['send_mail']     ?? false;
        $mail_reason   = $data['mail_reason']   ?? '';
        $mail_content  = $data['mail_content']  ?? [];
        $delete_case   = $data['delete_case']   ?? false;
        $delete_reason = $data['delete_reason'] ?? '';
        $gro_case      = $data['gro_case']      ?? false;
        $gro_status    = $data['gro_status']    ?? '';
        $duplicate_found   = $data['duplicate_found']   ?? false;
        $duplicate_docket  = $data['duplicate_docket']  ?? '';
        $duplicate_mobile  = $data['duplicate_mobile']  ?? '';
        $send_sms      = $data['send_sms']      ?? false;
        $sms_payload   = $data['sms_payload']   ?? [];
        $validated_cons_no = $data['validated_cons_no'] ?? '';
        $validated_cust_id = $data['validated_cust_id'] ?? '';
        $login_by      = $data['login_by']      ?? 'BOT';
        $remote_addr   = $data['remote_addr']   ?? '';

        # =====================================
        # LOG INPUT
        # =====================================

        error_log("=== CLASSIFY INPUT ===");
        error_log("docket_no    : " . $docket_no);
        error_log("category     : " . $category);
        error_log("sub_category : " . $sub_category);
        error_log("err_message  : " . $err_message);
        error_log("automail     : " . $automail);
        error_log("send_mail    : " . ($send_mail ? 'true' : 'false'));
        error_log("delete_case  : " . ($delete_case ? 'true' : 'false'));
        error_log("gro_case     : " . ($gro_case ? 'true' : 'false'));
        error_log("duplicate    : " . ($duplicate_found ? 'true' : 'false'));

        # =====================================
        # DECISION LOGIC
        # =====================================

        $action         = '';
        $action_detail  = '';
        $db_updated     = false;

        # =====================================
        # DELETE CASE
        # =====================================
        if (
    ($category === 'ZSPAM' && $sub_category === 'JUNK') ||
    ($category === 'JUNK' && $sub_category === 'SPAM MAIL ID') ||
    $err_message === 'DUPLICATE IDENTIFIED'
) {
    $delete_case   = true;
    $delete_reason = $delete_reason ?: 'ZSPAM_JUNK_OR_DUPLICATE';
}
        if ($delete_case) {

            $action        = 'DELETE';
            $action_detail = $delete_reason;

            $stmt = mysqli_prepare($cn,
                "UPDATE email_parse SET
                    web_accepted_flag = 'd',
                    web_accepted_datetime = NOW(),
                    web_updated_by = ?,
                    web_updated_ip = ?
                WHERE docket_no = ?"
            );

            mysqli_stmt_bind_param(
                $stmt, 'sss',
                $login_by,
                $remote_addr,
                $docket_no
            );

            $db_updated = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        # =====================================
        # SEND MAIL CASE
        # =====================================

        elseif ($send_mail) {

            $action        = 'SEND_MAIL';
            $action_detail = $mail_reason;

            # Mail not actually sent — return content as response
            # When mail service is ready, plug in here

        }

        # =====================================
        # FORWARD CASE
        # BILLING / SUPPLY / NEW CONNECTION
        # =====================================

        elseif (
            in_array($category, ['BILLING', 'SUPPLY', 'NEW CONNECTION'])
            && $err_message !== 'DUPLICATE IDENTIFIED'
        ) {

            $action        = 'FORWARD';
            $action_detail = $category;

            $stmt = mysqli_prepare($cn,
                "UPDATE email_parse SET
                    web_accepted_flag = 'y',
                    web_accepted_datetime = NOW(),
                    web_updated_by = ?,
                    web_updated_ip = ?,
                    cons_no = ?,
                    cust_id = ?
                WHERE docket_no = ?"
            );

            mysqli_stmt_bind_param(
                $stmt, 'sssss',
                $login_by,
                $remote_addr,
                $validated_cons_no,
                $validated_cust_id,
                $docket_no
            );

            $db_updated = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        # =====================================
        # NO ACTION
        # =====================================

        else {

            $action        = 'NO_ACTION';
            $action_detail = $err_message;
        }

        # =====================================
        # RETURN RESPONSE
        # =====================================

        return [
            'success'          => true,
            'docket_no'        => $docket_no,
            'category'         => $category,
            'sub_category'     => $sub_category,
            'action'           => $action,
            'action_detail'    => $action_detail,
            'db_updated'       => $db_updated,
            'send_mail'        => $send_mail,
            'mail_content'     => $mail_content,
            'delete_case'      => $delete_case,
            'delete_reason'    => $delete_reason,
            'duplicate_found'  => $duplicate_found,
            'duplicate_docket' => $duplicate_docket,
            'send_sms'         => $send_sms,
            'sms_payload'      => $sms_payload,
            'gro_case'         => $gro_case,
            'gro_status'       => $gro_status
        ];
    }
}