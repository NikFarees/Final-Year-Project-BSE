<?php
include '../../../include/st_header.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Dashboard</h4>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">My Licenses</h4>
                    </div>
                    <div class="card-body">
                        <!-- First Level Nav Pills (Licenses) -->
                        <ul class="nav nav-pills nav-secondary" id="license-tab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="manual-tab" data-bs-toggle="pill" href="#manual" role="tab" aria-controls="manual" aria-selected="true">Manual Car</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="auto-tab" data-bs-toggle="pill" href="#auto" role="tab" aria-controls="auto" aria-selected="false">Auto Car</a>
                            </li>
                        </ul>

                        <div class="tab-content mt-3" id="license-tabContent">
                            <!-- Manual Car License Section -->
                            <div class="tab-pane fade show active" id="manual" role="tabpanel" aria-labelledby="manual-tab">
                                <h5>Manual Car Sessions</h5>

                                <!-- Second Level Nav Pills (Sessions) -->
                                <ul class="nav nav-pills nav-primary mt-2" id="manual-session-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="manual-upcoming-tab" data-bs-toggle="pill" href="#manual-upcoming" role="tab" aria-controls="manual-upcoming" aria-selected="true">Upcoming Sessions</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="manual-past-tab" data-bs-toggle="pill" href="#manual-past" role="tab" aria-controls="manual-past" aria-selected="false">Past Sessions</a>
                                    </li>
                                </ul>

                                <div class="tab-content mt-3" id="manual-session-tabContent">
                                    <!-- Manual Car - Upcoming Sessions -->
                                    <div class="tab-pane fade show active" id="manual-upcoming" role="tabpanel">
                                        <table id="manual-upcoming-table" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Session Name</th>
                                                    <th>Date</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1</td>
                                                    <td>Basic Driving</td>
                                                    <td>2025-04-10</td>
                                                    <td>10:00 AM</td>
                                                    <td>12:00 PM</td>
                                                    <td>Upcoming</td>
                                                </tr>
                                                <tr>
                                                    <td>2</td>
                                                    <td>Highway Practice</td>
                                                    <td>2025-04-12</td>
                                                    <td>02:00 PM</td>
                                                    <td>04:00 PM</td>
                                                    <td>Upcoming</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>


                                    <!-- Manual Car - Past Sessions -->
                                    <div class="tab-pane fade" id="manual-past" role="tabpanel">
                                        <table id="manual-past-table" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Session Name</th>
                                                    <th>Date</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1</td>
                                                    <td>City Driving</td>
                                                    <td>2025-03-05</td>
                                                    <td>09:00 AM</td>
                                                    <td>11:00 AM</td>
                                                    <td>Completed</td>
                                                </tr>
                                                <tr>
                                                    <td>2</td>
                                                    <td>Parallel Parking</td>
                                                    <td>2025-03-10</td>
                                                    <td>01:00 PM</td>
                                                    <td>03:00 PM</td>
                                                    <td>Completed</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Auto Car License Section -->
                            <div class="tab-pane fade" id="auto" role="tabpanel" aria-labelledby="auto-tab">
                                <h5>Auto Car Sessions</h5>

                                <!-- Second Level Nav Pills (Sessions) -->
                                <ul class="nav nav-pills nav-primary mt-2" id="auto-session-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="auto-upcoming-tab" data-bs-toggle="pill" href="#auto-upcoming" role="tab" aria-controls="auto-upcoming" aria-selected="true">Upcoming Sessions</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="auto-past-tab" data-bs-toggle="pill" href="#auto-past" role="tab" aria-controls="auto-past" aria-selected="false">Past Sessions</a>
                                    </li>
                                </ul>

                                <div class="tab-content mt-3" id="auto-session-tabContent">
                                    <!-- Auto Car - Upcoming Sessions -->
                                    <div class="tab-pane fade show active" id="auto-upcoming" role="tabpanel">
                                        <table id="auto-upcoming-table" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Session Name</th>
                                                    <th>Date</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1</td>
                                                    <td>Automatic Transmission Basics</td>
                                                    <td>2025-04-08</td>
                                                    <td>11:00 AM</td>
                                                    <td>01:00 PM</td>
                                                    <td>Upcoming</td>
                                                </tr>
                                                <tr>
                                                    <td>2</td>
                                                    <td>City Driving</td>
                                                    <td>2025-04-14</td>
                                                    <td>03:00 PM</td>
                                                    <td>05:00 PM</td>
                                                    <td>Upcoming</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Auto Car - Past Sessions -->
                                    <div class="tab-pane fade" id="auto-past" role="tabpanel">
                                        <table id="auto-past-table" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Session Name</th>
                                                    <th>Date</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1</td>
                                                    <td>Roundabout Navigation</td>
                                                    <td>2025-03-02</td>
                                                    <td>08:30 AM</td>
                                                    <td>10:30 AM</td>
                                                    <td>Completed</td>
                                                </tr>
                                                <tr>
                                                    <td>2</td>
                                                    <td>Emergency Braking</td>
                                                    <td>2025-03-15</td>
                                                    <td>02:45 PM</td>
                                                    <td>04:45 PM</td>
                                                    <td>Completed</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- End of tab-content -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../../../include/footer.html';
?>

<script>
    $(document).ready(function() {
        $("#manual-upcoming-table").DataTable();
        $("#manual-past-table").DataTable();
        $("#auto-upcoming-table").DataTable();
        $("#auto-past-table").DataTable();
    });
</script>