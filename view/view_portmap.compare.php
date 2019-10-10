    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Portmap
        <small>Compare portmap</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Portmap</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="col-md-6">
            <form method="get" action="<?php echo base_url(); ?>">
                <div class="hidden">
                    <input type="hidden" name="route" value="portmap.compare">..
                    <input type="hidden" name="device_id" value="<?php echo $device_id; ?>">
                    <input type="hidden" name="after_dt" value="<?php echo $after_dt; ?>">
                </div>

                <div class="box box-info">
                    <div class="box-header">
                        <h3 class="box-title">Start datetime</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="before_dt">Datetime</label>
                            <input type="text" id="before_dt" name="before_dt" class="form-control" value="<?php echo $before_dt; ?>">
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
            <div class="box box-info">
                <div class="box-header">
                    <h3 class="box-title">Before Map</h3>
                </div>
                <div class="box-body">
                    <div id="map0"></div>
                </div>
            </div>
            <div class="box box-info">
                <div class="box-header">
                    <h3 class="box-title">Before Table</h3>
                </div>
                <div class="box-body">
                    <div id="tbl0_wapper">
                        <table id="tbl0" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Process name</th>
                                    <th>Address</th>
                                    <th>Port</th>
                                    <th>State</th>
                                    <th>PID</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>Process name</th>
                                    <th>Address</th>
                                    <th>Port</th>
                                    <th>State</th>
                                    <th>PID</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <form method="get" action="<?php echo base_url(); ?>">
                <div class="box box-info">
                    <div class="box-header">
                        <h3 class="box-title">After datetime</h3>
                    </div>
                    <div class="box-body">
                        <div class="hidden">
                            <input type="hidden" name="route" value="portmap.compare">..
                            <input type="hidden" name="device_id" value="<?php echo $device_id; ?>">
                            <input type="hidden" name="before_dt" value="<?php echo $before_dt; ?>">
                        </div>
                        <div class="form-group">
                            <label for="after_dt">Datetime</label>
                            <input type="text" id="after_dt" name="after_dt" class="form-control" value="<?php echo $after_dt; ?>">
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
            <div class="box box-info">
                <div class="box-header">
                    <h3 class="box-title">After Map</h3>
                </div>
                <div class="box-body">
                    <div id="map1"></div>
                </div>
            </div>
            <div class="box box-info">
                <div class="box-header">
                    <h3 class="box-title">After Table</h3>
                </div>
                <div class="box-body">
                    <div id="tbl1_wapper">
                        <table id="tbl1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Process name</th>
                                    <th>Address</th>
                                    <th>Port</th>
                                    <th>State</th>
                                    <th>PID</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>Process name</th>
                                    <th>Address</th>
                                    <th>Port</th>
                                    <th>State</th>
                                    <th>PID</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div style="clear: both;"></div>
