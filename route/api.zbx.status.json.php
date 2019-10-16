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
    foreach($targets as $target) {
        switch($target->target) {
            case "hostip":
                $hostips = array();
                foreach($target->data as $v) {
                    $d = explode("*", $v);
                    $hostips[] = current($d);
                }
                break;
        }
    }

    // get hosts by IP
    $sql = "select * from $_tbl1 where 1";
    foreach($hostips as $ip) {
        $sql .= " or hostip like '{$ip}%'";
    }
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

    // post-processing problems
    $sql = "select hostid, hostname, max(severity) as severity from $_tbl2 group by hostid";
    $rows = exec_db_fetch_all($sql);
    $_rows = array();
    foreach($rows as $row) {
        $_rows[] = array_values($row);
    }

    // make output data
    /*
    $_data[] = array(
        "columns" => array(
            array("text" => "Hostid", "type" => "number"},
            array("text" => "Hostname", "type" => "string"),
            array("text" => "Severity", "type" => "number")
        ),
        "rows" => $_rows,
        "type" => "table"
    );
    */

    foreach($_rows as $row) {
        $_data[] = array(
            "target" => $row[1],
            "datapoints" => array(
                array(intval($row[2]), get_current_datetime())
            )
        );
    }
}

write_common_log($uri, "api.zbx.status.json");
write_common_log($requests['_RAW'], "api.zbx.status.json");

header("Content-Type: application/json");
echo json_encode($_data);
