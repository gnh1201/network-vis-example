<?php
loadHelper("networktool");
loadHelper("string.utils");
loadHelper("colona.v1.format");

$requests = get_requests();

$ne = get_network_event();
$ua = $ne['agent'] . DOC_EOL;

$jobargs = decode_colona_format($requests['_RAW']);
$jobdata = decode_colona_format(base64_decode(get_value_in_array("DATA", $jobargs)));

// get device
$device = array();
if(!array_key_empty("UUID", $jobargs)) {
    $bind = array(
        "uuid" => $jobargs['UUID']
    );
    $sql = get_bind_to_sql_select("autoget_devices", $bind);
    $device = exec_db_fetch($sql, $bind);
}

// init
if(array_key_equals("JOBKEY", $jobargs, "init")) {
    if(array_key_empty("uuid", $device)) {
        $bind = array(
            "uuid" => $jobdata['UUID'],
            "is_elevated" => $jobdata['IsElevated'],
            "uri" => $jobdata['URI'],
            "computer_name" => $jobdata['ComputerName'],
            "os" => $jobdata['OS'],
            "arch" => $jobdata['Arch'],
            "cwd" => $jobdata['CWD'],
            "net_ip" => implode(",", split_by_line($jobdata['Net_IP'])),
            "net_mac" => implode(",", split_by_line($jobdata['Net_MAC'])),
            "datetime" => get_current_datetime(),
            "last" => get_current_datetime()
        );
        $sql = get_bind_to_sql_insert("autoget_devices", $bind);
        exec_db_query($sql, $bind);
    }
}

if(array_key_equals("JOBKEY", $jobargs, "cmd")) {
    // get response
    $command_id = get_value_in_array("JOBSTAGE", $jobargs, "");
    $device_id = $device['id'];
    $response = base64_decode(get_value_in_array("DATA", $jobargs, ""));

    // tokenize response
    $terms = get_tokenized_text($response);
    foreach($terms as $term) {
        // add terms
        $bind = array(
            "name" => $term,
            "count" => 0,
            "datetime" => get_current_datetime(),
            "last" => get_current_datetime()
        );
        $sql = get_bind_to_sql_insert("autoget_terms", $bind, array(
            "ignore" => array(
                array("and", array("eq", "name", $term))
            )
        ));
        exec_db_query($sql, $bind);
    }

    // save response
    $bind = array(
        "command_id" => $command_id,
        "device_id" => $device_id,
        "response" => $response,
        "datetime" => get_current_datetime()
    );
    $sql = get_bind_to_sql_insert("autoget_responses", $bind);
    exec_db_query($sql, $bind);
    $response_id = get_db_last_id();

    // make sheet
    $row_n = 0;
    $col_n = 0;
    $lines = split_by_line($response);
    foreach($lines as $line) {
        $row_n++;
        $col_n = 0;
        $words = get_tokenized_text($line);
        foreach($words as $word) {
            $col_n++;

            if($word != "") {

                // get term id
                $bind = array(
                    "name" => $word
                );
                $sql = get_bind_to_sql_select("autoget_terms", $bind);
                $row = exec_db_fetch($sql, $bind);
                $term_id = get_value_in_array("id", $row, 0);

                // count up
                /*
                $bind = array(
                    "count" =>  array(
                        "add" => 1
                    ),
                    "last" => get_current_datetime()
                );
                $sql = get_bind_to_sql_update("autoget_terms", $bind);
                */

                exec_db_query(
                    "update autoget_terms set count = count + 1, last = :last where id = :id", array(
                        "id" => $term_id,
                        "last" => get_current_datetime()
                    )
                );

                // add word to sheet
                $bind = array(
                    "response_id" => $response_id,
                    "command_id" => $command_id,
                    "device_id" => $device_id,
                    "row_n" => $row_n,
                    "col_n" => $col_n,
                    "term_id" => $term_id,
                    "datetime" => get_current_datetime()
                );
                $sql = get_bind_to_sql_insert("autoget_sheets", $bind);
                exec_db_query($sql, $bind);

            }
        }
    }

    // last status up
    $bind = array(
        "jobkey" => $jobargs['JOBKEY'],
        "jobstage" => $jobargs['JOBSTAGE']
    );
    $sql = get_bind_to_sql_update("autoget_devices", $bind, array(
        "setwheres" => array(
            array("and", array("eq", "uuid", $jobargs['UUID']))
        )
    ));
    exec_db_query($sql, $bind);
}

// get device
if(!array_key_empty("id", $device)) {
    $device_os = strtolower($device['os']);

    // check TX queue
    $bind = array(
        "device_id" => $device['id']
    );
    $sql = get_bind_to_sql_select("autoget_tx_queue", $bind, array(
        "setwheres" => array(
            array("and", array("gte", "expired_on", get_current_datetime())),
            array("and", array("not", "is_read", 1))
        ),
        "setlimit" => 1,
        "setpage" => 1,
        "setorders" => array(
            array("asc", "id")
        )
    ));
    $rows = exec_db_fetch_all($sql, $bind);

    // if rows count is 0
    if(count($rows) == 0) {
        $_bind = false;
        $_sql = get_bind_to_sql_select("autoget_commands", $_bind, array(
            "setwheres" => array(
                array("and", array("not", "disabled", 1))
            ),
            "setorders" => array(
                array("asc", "id")
            )
        ));
        $_rows = exec_db_fetch_all($_sql, $_bind);
        foreach($_rows as $_row) {
            $_pos = strpos($device_os, strtolower($_row['platform']));
            if($_pos !== false) {
                $__bind = array(
                    "device_id" => $device['id'],
                    "jobkey" => "cmd",
                    "jobstage" => $_row['id'],
                    "message" => $_row['command'],
                    "created_on" => get_current_datetime(),
                    "expired_on" => get_current_datetime(array(
                        "adjust" => "+1 hour"
                    ))
                );
                $__sql = get_bind_to_sql_insert("autoget_tx_queue", $__bind);
                exec_db_query($__sql, $__bind);
            }
        }

        // pull a empty job (ping)
        echo "jobkey: ping" . DOC_EOL;
        echo "jobstage: 0" . DOC_EOL;
        exit;
    }

    // pull a job
    foreach($rows as $row) {
        echo sprintf("jobkey: %s", $row['jobkey']) . DOC_EOL;
        echo sprintf("jobstage: %s", $row['jobstage']) . DOC_EOL;
        //echo sprintf("data.cmd: %s", $row['message']) . DOC_EOL;

        // update is_read flag of queue
        $_bind = array(
            "is_read" => 1
        );
        $_sql = get_bind_to_sql_update("autoget_tx_queue", $_bind, array(
            "setwheres" => array(
                array("and", array("eq", "id", $row['id']))
            )
        ));
        //echo get_db_binded_sql($_sql, $_bind);
        exec_db_query($_sql, $_bind);

        // if remote command execution
        if(array_key_equals("jobkey", $row, "cmd")) {
            echo sprintf("data.cmd: %s", $row['message']) . DOC_EOL;

            // update last datetime
            $_bind = array(
                "last" => get_current_datetime()
            );
            $_sql = get_bind_to_sql_update("autoget_commands", $_bind, array(
                "setwheres" => array(
                    array("and", array("eq", "id", $row['jobstage']))
                )
            ));
            exec_db_query($_sql, $_bind);
        } else {
            echo sprintf("data.message: %s", $row['message']) . DOC_EOL;
        }
    }
}
