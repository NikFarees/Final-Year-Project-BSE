<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Management - Driving School</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        h1 {
            color: #2563eb;
            font-size: 24px;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-primary {
            background-color: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .btn-success {
            background-color: #10b981;
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .tab {
            padding: 12px 20px;
            cursor: pointer;
            position: relative;
            font-weight: 500;
        }

        .tab.active {
            color: #2563eb;
        }

        .tab.active:after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #2563eb;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9fafb;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
        }

        .card-body {
            padding: 20px;
        }

        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #4b5563;
        }

        .form-control {
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            font-weight: 600;
            color: #4b5563;
            background-color: #f9fafb;
        }

        .table tbody tr:hover {
            background-color: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            line-height: 1;
        }

        .badge-blue {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .badge-green {
            background-color: #d1fae5;
            color: #059669;
        }

        .badge-orange {
            background-color: #ffedd5;
            color: #ea580c;
        }

        .badge-red {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .badge-purple {
            background-color: #ede9fe;
            color: #7c3aed;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 600px;
            max-width: 95%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #9ca3af;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .student-list {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            max-height: 250px;
            overflow-y: auto;
        }

        .student-item {
            padding: 10px 15px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .student-item:last-child {
            border-bottom: none;
        }

        .student-item:hover {
            background-color: #f9fafb;
        }

        .search-bar {
            position: relative;
            margin-bottom: 15px;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination-item {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            margin: 0 5px;
            border-radius: 4px;
            cursor: pointer;
        }

        .pagination-item.active {
            background-color: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .checkbox-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .checkbox-label input {
            margin-right: 5px;
        }

        .student-selection {
            margin-top: 15px;
        }

        .selected-students {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .selected-student-tag {
            background-color: #e5e7eb;
            padding: 5px 10px;
            border-radius: 9999px;
            font-size: 12px;
            display: flex;
            align-items: center;
        }

        .remove-tag {
            margin-left: 5px;
            cursor: pointer;
        }

        .calendar {
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .calendar-controls {
            display: flex;
            gap: 10px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .calendar-cell {
            padding: 10px;
            border-right: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            min-height: 100px;
        }

        .calendar-cell:nth-child(7n) {
            border-right: none;
        }

        .calendar-weekday {
            text-align: center;
            font-weight: 600;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
        }

        .calendar-weekday:last-child {
            border-right: none;
        }

        .calendar-date {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .calendar-event {
            font-size: 12px;
            padding: 3px 5px;
            border-radius: 4px;
            margin-bottom: 3px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .calendar-event.computer {
            background-color: #2563eb;
        }

        .calendar-event.qti {
            background-color: #7c3aed;
        }

        .calendar-event.circuit {
            background-color: #10b981;
        }

        .calendar-event.onroad {
            background-color: #f59e0b;
        }

        .tabbed-section {
            margin-top: 20px;
        }

        .eligibility-section {
            margin-bottom: 20px;
        }
    </style>
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