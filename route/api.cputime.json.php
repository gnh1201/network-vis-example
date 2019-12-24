<?php
$device_id = get_requested_value("device_id");
$start_dt = get_requested_value("start_dt");
$end_dt = get_requested_value("end_dt");
$adjust = get_requested_value("adjust");

if(empty($device_id)) {
    set_error("device_id is required");
}

if(empty($end_dt)) {
    $end_dt = get_current_datetime();
}

if(empty($adjust)) {
    $adjust = "-1 hour";
}

if(empty($start_dt)) {
    $start_dt = get_current_datetime(array(
        "now" => $end_dt,
        "adjust" => $adjust
    ));
}

$a = array();
$b = array();

// get $a
$sql = get_bind_to_sql_select("autoget_sheets", false, array(
    "setwheres" => array(
        array("and", array("eq", "device_id", $device_id)),
        array("and", array("in", "pos_y", array(2, 3))),
        array("and", array("eq", "command_id", 47)),
        array("and", array("lte", "datetime", $end_dt)),
        array("and", array("gte", "datetime", $start_dt))
    )
));
$_tbl1 = exec_db_temp_start($sql, false);

$sql = "
select a.pos_y as pos_y, if(a.pos_y = 2, ((a.pos_x + 1) / 6), (a.pos_x - 2)) as pos_x, b.term as term, a.datetime as datetime
    from $_tbl1 a left join autoget_terms b on a.term_id = b.id
        where (pos_y = 2 and mod(pos_x + 1, 6) = 0) or (pos_y = 3 and pos_x - 2 > 0)
";
$_tbl2 = exec_db_temp_start($sql, false);

$sql = "select group_concat(if(pos_y = 2, term, null)) as name, group_concat(if(pos_y = 3, term, null)) as value, datetime from $_tbl2 group by pos_x, datetime";
$_tbl3 = exec_db_temp_start($sql, false);

$sql = "select name, concat(avg(value), '%') as value from $_tbl3 where name not in ('Idle', '_Total') group by name";
$rows = exec_db_fetch_all($sql);

$data = array(
    "data" => $rows
);
header("Content-Type: application/json");
echo json_encode($data);
