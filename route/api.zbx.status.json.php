<?php
loadHelper("json.format");
loadHelper("zabbix.api");

$uri = get_uri();

$_p = explode("/", $uri);
$_data = array();
if(in_array("query", $_p)) {
    // get requested data
    $targets = get_requested_value("targets", array("_JSON"));

    // get hosts from zabbix server
    zabbix_authenticate();
    $hosts = zabbix_retrieve_hosts();

    // make temporary database by hosts
    $_tbl1 = exec_db_temp_create(array(
        "hostid" => array("int", 11),
        "hostname" => array("varchar", 255),
        "hostip" => array("varchar", 255)
    ));
    foreach($hosts->result as $host) {
        $bind = array(
            "hostid" => $host->hostid,
            "hostname" => $host->host,
            "hostip" => $host->interfaces[0]->ip
        );
        $sql = get_bind_to_sql_insert($_tbl1, $bind);
        exec_db_query($sql, $bind);
    }

    // get IPs by range
    $hostips = array();
    $types = array("polystat"); // polystat is default
    $severities = array();
    foreach($targets as $target) {
        switch($target->target) {
            case "hostips":
                $hostips = array();
                foreach($target->data as $v) {
                    $d = explode("*", $v);
                    $hostips[] = current($d);
                }
                break;
            case "types":
                $types = array();
                foreach($target->data as $v) {
                    $types[] = $v;
                }
                break;
            case "severities":
                $severities = array();
                foreach($target->data as $v) {
                    $severities[] = $v;
                }
                break;
        }
    }

    // get hosts by IP
    $setwheres = array();
    $_setwheres = array();
    foreach($hostips as $ip) {
        $_setwheres[] = array("or", array("eq", "hostip", $ip));
        $_setwheres[] = array("or", array("left", "hostip", $ip));
    }
    $setwheres[] = array("and", $_setwheres);

    // make SQL statement
    $sql = get_bind_to_sql_select($_tbl1, false, array(
        "setwheres" => $setwheres
    ));

    // get rows
    $rows = exec_db_fetch_all($sql);

    // get problems
    $_tbl2 = exec_db_temp_create(array(
        "hostid" => array("int", 11),
        "eventid" => array("int", 11),
        "hostname" => array("varchar", 255),
        "description" => array("varchar", 255),
        "severity" => array("int", 11)
    ));
    foreach($rows as $row) {
        $problems = zabbix_get_problems($row['hostid']);
        foreach($problems->result as $problem) {
            $bind = array(
                "hostid" => $row['hostid'],
                "eventid" => $problem->eventid,
                "hostname" => $row['hostname'],
                "description" => $problem->name,
                "severity" => $problem->severity
            );
            $sql = get_bind_to_sql_insert($_tbl2, $bind);
            exec_db_query($sql, $bind);
        }
    }

    // if panel type is polystat
    if(in_array("polystat", $types)) {
        // post-processing problems
        $sql = "select hostid, hostname, max(severity) as severity from $_tbl2 group by hostid";
        $rows = exec_db_fetch_all($sql, false, array(
            "getvalues" => true
        ));

        foreach($rows as $row) {
            $_data[] = array(
                "target" => $row[1],
                "datapoints" => array(
                    array(intval($row[2]), get_current_datetime())
                )
            );
        }
    }

    // if panel type is singlestat
    if(in_array("singlestat", $types)) {
        // post-processing problems
        $sql = "select hostid, hostname, max(severity) as severity from $_tbl2 group by hostid";
        $_tbl3 = exec_db_temp_start($sql);

        if(count($severities) > 0) {
            $sql = sprintf("select concat('upper_', severity) as name, count(*) as lastvalue from $_tbl3 where severity in (%s) group by severity", implode(",", $severities));
        } else {
            $sql = "select concat('upper_', severity) as name, count(*) as lastvalue from $_tbl3";
        }
        $rows = exec_db_fetch_all($sql, false, array(
            "getvalues" => true,
            "display_errors" => true,
            "show_debug" => true,
            "show_sql" => true
        ));

        foreach($rows as $row) {
            $_data[] = array(
                "target" => $row[0],
                "datapoints" => array(
                    array(intval($row[1]), get_current_datetime())
                )
            );
        }

        exec_db_temp_end($_tbl3);
    }

    // if panel type is table
    if(in_array("list", $types)) {
        $sql = "select hostname, description, severity from $_tbl2";
        $rows = exec_db_fetch_all($sql, false, array(
            "getvalues" => true
        ));

        $_data[] = array(
            "columns" => array(
                array("text" => "Hostname", "type" => "text"),
                array("text" => "Description", "type" => "text"),
                array("text" => "Severity", "type" => "number")
            ),
            "rows" => $rows,
            "type" => "table"
        );
    }
}

write_common_log($uri, "api.zbx.status.json");
write_common_log($requests['_RAW'], "api.zbx.status.json");

header("Content-Type: application/json");
echo json_encode($_data);

