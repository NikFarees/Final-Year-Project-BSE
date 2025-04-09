<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Management - Driving School</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Test Management</h1>
            <button class="btn btn-primary" id="scheduleTestBtn">Schedule New Test</button>
        </div>

        <div class="tabs">
            <div class="tab active" data-tab="upcoming">Upcoming Tests</div>
            <div class="tab" data-tab="past">Past Tests</div>
            <div class="tab" data-tab="eligibility">Eligibility</div>
        </div>

        <!-- Upcoming Tests Tab -->
        <div class="tab-content active" id="upcoming-tab">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Upcoming Tests</h2>
                    <div class="filter-section">
                        <select class="form-control">
                            <option value="all">All Test Types</option>
                            <option value="computer">Computer Test</option>
                            <option value="qti">QTI Test</option>
                            <option value="circuit">Circuit Test</option>
                            <option value="onroad">On-Road Test</option>
                        </select>
                        <input type="date" class="form-control" placeholder="From Date">
                        <input type="date" class="form-control" placeholder="To Date">
                        <button class="btn btn-secondary">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Test Date</th>
                                <th>Test Time</th>
                                <th>Test Type</th>
                                <th>Location</th>
                                <th>Capacity</th>
                                <th>Students</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Mar 18, 2025</td>
                                <td>10:00 AM</td>
                                <td>Computer</td>
                                <td>Room 101</td>
                                <td>10</td>
                                <td>8</td>
                                <td><span class="badge badge-blue">Scheduled</span></td>
                                <td>
                                    <button class="btn btn-success">Edit</button>
                                    <button class="btn btn-danger">Cancel</button>
                                </td>
                            </tr>
                            <!-- Additional rows as needed -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Past Tests Tab -->
        <div class="tab-content" id="past-tab">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Past Tests</h2>
                    <div class="filter-section">
                        <select class="form-control">
                            <option value="all">All Test Types</option>
                            <option value="computer">Computer Test</option>
                            <option value="qti">QTI Test</option>
                            <option value="circuit">Circuit Test</option>
                            <option value="onroad">On-Road Test</option>
                        </select>
                        <input type="date" class="form-control" placeholder="From Date">
                        <input type="date" class="form-control" placeholder="To Date">
                        <button class="btn btn-secondary">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Test Date</th>
                                <th>Test Time</th>
                                <th>Test Type</th>
                                <th>Location</th>
                                <th>Capacity</th>
                                <th>Students</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Feb 10, 2025</td>
                                <td>09:00 AM</td>
                                <td>QTI</td>
                                <td>Room 102</td>
                                <td>10</td>
                                <td>10</td>
                                <td><span class="badge badge-green">Completed</span></td>
                                <td>
                                    <button class="btn btn-success">View</button>
                                </td>
                            </tr>
                            <!-- Additional rows as needed -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Eligibility Tab -->
        <div class="tab-content" id="eligibility-tab">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Eligibility</h2>
                </div>
                <div class="card-body">
                    <div class="eligibility-section">
                        <h3>Eligible Students</h3>
                        <div class="search-bar">
                            <input type="text" placeholder="Search students...">
                        </div>
                        <div class="student-list">
                            <div class="student-item">
                                <span>John Doe</span>
                                <input type="checkbox" class="select-student">
                            </div>
                            <div class="student-item">
                                <span>Jane Smith</span>
                                <input type="checkbox" class="select-student">
                            </div>
                            <!-- Additional student items as needed -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Schedule Test Modal -->
    <div class="modal" id="scheduleTestModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Schedule a Test</h2>
                <button class="modal-close" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="testType">Test Type</label>
                    <select id="testType" class="form-control">
                        <option value="computer">Computer Test</option>
                        <option value="qti">QTI Test</option>
                        <option value="circuit">Circuit Test</option>
                        <option value="onroad">On-Road Test</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="testDate">Test Date</label>
                        <input type="date" id="testDate" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="testTime">Test Time</label>
                        <input type="time" id="testTime" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label for="testLocation">Location</label>
                    <input type="text" id="testLocation" class="form-control">
                </div>
                <div class="form-group">
                    <label for="testCapacity">Capacity</label>
                    <input type="number" id="testCapacity" class="form-control">
                </div>
                <div class="eligibility-section">
                    <h3>Eligible Students</h3>
                    <div class="search-bar">
                        <input type="text" placeholder="Search students...">
                    </div>
                    <div class="student-list">
                        <div class="student-item">
                            <span>John Doe</span>
                            <input type="checkbox" class="select-student">
                        </div>
                        <div class="student-item">
                            <span>Jane Smith</span>
                            <input type="checkbox" class="select-student">
                        </div>
                        <!-- Additional student items as needed -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelTest">Cancel</button>
                <button class="btn btn-primary" id="saveTest">Save Test</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('scheduleTestBtn').addEventListener('click', function() {
            document.getElementById('scheduleTestModal').style.display = 'flex';
        });
        document.getElementById('closeModalBtn').addEventListener('click', function() {
            document.getElementById('scheduleTestModal').style.display = 'none';
        });

        // Tab switching logic
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));

                this.classList.add('active');
                document.getElementById(this.getAttribute('data-tab') + '-tab').classList.add('active');
            });
        });
    </script>
</body>

</html>