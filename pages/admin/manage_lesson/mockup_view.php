<?php
include '../../../include/ad_header.php';
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Lesson Management</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="/pages/admin/dashboard.php">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Students</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Lesson Management</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <!-- Student Selection -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Student Lesson Management</div>
          <div class="card-category">Manage the 4 driving lessons for each student</div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="studentSelect">Select Student</label>
                <select class="form-control" id="studentSelect">
                  <option value="">-- Select Student --</option>
                  <option value="1">John Doe</option>
                  <option value="2">Jane Smith</option>
                  <option value="3">Michael Johnson</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="instructorSelect">Assign Instructor</label>
                <select class="form-control" id="instructorSelect">
                  <option value="">-- Select Instructor --</option>
                  <option value="1">Instructor A</option>
                  <option value="2">Instructor B</option>
                  <option value="3">Instructor C</option>
                </select>
                <small class="form-text text-muted">The same instructor must be assigned to all 4 lessons</small>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-md-12">
              <button class="btn btn-primary" id="assignInstructor">Assign/Update Instructor</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs for lesson status -->
      <div class="card">
        <div class="card-header">
          <ul class="nav nav-pills nav-secondary" id="lessonTabs" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" id="unassigned-tab" data-toggle="pill" href="#unassigned" role="tab" aria-controls="unassigned" aria-selected="true">
                <i class="fas fa-exclamation-circle"></i> Unassigned Lessons
                <span class="badge badge-danger">8</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="assigned-tab" data-toggle="pill" href="#assigned" role="tab" aria-controls="assigned" aria-selected="false">
                <i class="fas fa-user-check"></i> Assigned Lessons
                <span class="badge badge-info">12</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="upcoming-tab" data-toggle="pill" href="#upcoming" role="tab" aria-controls="upcoming" aria-selected="false">
                <i class="fas fa-calendar-alt"></i> Upcoming Lessons
                <span class="badge badge-warning">5</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="past-tab" data-toggle="pill" href="#past" role="tab" aria-controls="past" aria-selected="false">
                <i class="fas fa-history"></i> Past Lessons
                <span class="badge badge-success">24</span>
              </a>
            </li>
          </ul>
        </div>
        <div class="card-body">
          <div class="tab-content" id="lessonTabsContent">
            <!-- Unassigned Lessons Tab -->
            <div class="tab-pane fade show active" id="unassigned" role="tabpanel" aria-labelledby="unassigned-tab">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Student Name</th>
                      <th>Course Package</th>
                      <th>Registration Date</th>
                      <th>Lessons Left</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>John Doe</td>
                      <td>Basic Course (4 Lessons)</td>
                      <td>10 Apr 2025</td>
                      <td>4 / 4</td>
                      <td>
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#assignLessonsModal">
                          <i class="fas fa-user-plus"></i> Assign Instructor
                        </button>
                      </td>
                    </tr>
                    <tr>
                      <td>Sarah Williams</td>
                      <td>Basic Course (4 Lessons)</td>
                      <td>12 Apr 2025</td>
                      <td>4 / 4</td>
                      <td>
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#assignLessonsModal">
                          <i class="fas fa-user-plus"></i> Assign Instructor
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Assigned Lessons Tab -->
            <div class="tab-pane fade" id="assigned" role="tabpanel" aria-labelledby="assigned-tab">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Student Name</th>
                      <th>Instructor</th>
                      <th>Lessons Left</th>
                      <th>Next Lesson</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Michael Johnson</td>
                      <td>Instructor A</td>
                      <td>4 / 4</td>
                      <td>Not Scheduled</td>
                      <td>
                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#scheduleLessonsModal">
                          <i class="fas fa-calendar-plus"></i> Schedule Lessons
                        </button>
                        <button class="btn btn-sm btn-warning">
                          <i class="fas fa-exchange-alt"></i> Change Instructor
                        </button>
                      </td>
                    </tr>
                    <tr>
                      <td>Emily Parker</td>
                      <td>Instructor C</td>
                      <td>4 / 4</td>
                      <td>Not Scheduled</td>
                      <td>
                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#scheduleLessonsModal">
                          <i class="fas fa-calendar-plus"></i> Schedule Lessons
                        </button>
                        <button class="btn btn-sm btn-warning">
                          <i class="fas fa-exchange-alt"></i> Change Instructor
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Upcoming Lessons Tab -->
            <div class="tab-pane fade" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Student Name</th>
                      <th>Instructor</th>
                      <th>Lesson #</th>
                      <th>Date & Time</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>David Wilson</td>
                      <td>Instructor B</td>
                      <td>Lesson 1 of 4</td>
                      <td>21 Apr 2025, 10:00 AM</td>
                      <td>
                        <button class="btn btn-sm btn-warning">
                          <i class="fas fa-calendar-alt"></i> Reschedule
                        </button>
                        <button class="btn btn-sm btn-danger">
                          <i class="fas fa-times"></i> Cancel
                        </button>
                      </td>
                    </tr>
                    <tr>
                      <td>Jessica Brown</td>
                      <td>Instructor A</td>
                      <td>Lesson 3 of 4</td>
                      <td>22 Apr 2025, 2:00 PM</td>
                      <td>
                        <button class="btn btn-sm btn-warning">
                          <i class="fas fa-calendar-alt"></i> Reschedule
                        </button>
                        <button class="btn btn-sm btn-danger">
                          <i class="fas fa-times"></i> Cancel
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Past Lessons Tab -->
            <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Student Name</th>
                      <th>Instructor</th>
                      <th>Lesson #</th>
                      <th>Date & Time</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Alex Martin</td>
                      <td>Instructor C</td>
                      <td>Lesson 2 of 4</td>
                      <td>15 Apr 2025, 11:00 AM</td>
                      <td><span class="badge badge-success">Completed</span></td>
                      <td>
                        <button class="btn btn-sm btn-primary">
                          <i class="fas fa-eye"></i> View Details
                        </button>
                      </td>
                    </tr>
                    <tr>
                      <td>Thomas Anderson</td>
                      <td>Instructor B</td>
                      <td>Lesson 1 of 4</td>
                      <td>14 Apr 2025, 9:00 AM</td>
                      <td><span class="badge badge-danger">No Show</span></td>
                      <td>
                        <button class="btn btn-sm btn-primary">
                          <i class="fas fa-eye"></i> View Details
                        </button>
                        <button class="btn btn-sm btn-info">
                          <i class="fas fa-redo"></i> Reschedule
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Assign Lessons Modal -->
<div class="modal fade" id="assignLessonsModal" tabindex="-1" role="dialog" aria-labelledby="assignLessonsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignLessonsModalLabel">Assign Instructor for John Doe</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="modalInstructorSelect">Select Instructor</label>
          <select class="form-control" id="modalInstructorSelect">
            <option value="">-- Select Instructor --</option>
            <option value="1">Instructor A</option>
            <option value="2">Instructor B</option>
            <option value="3">Instructor C</option>
          </select>
          <small class="form-text text-muted">The same instructor will be assigned to all 4 lessons</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary">Assign Instructor</button>
      </div>
    </div>
  </div>
</div>

<!-- Schedule Lessons Modal -->
<div class="modal fade" id="scheduleLessonsModal" tabindex="-1" role="dialog" aria-labelledby="scheduleLessonsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="scheduleLessonsModalLabel">Schedule Lessons for Michael Johnson</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> Instructor assigned: <strong>Instructor A</strong>
        </div>
        
        <div class="form-group">
          <label>Lesson 1</label>
          <div class="row">
            <div class="col-md-6">
              <input type="date" class="form-control" placeholder="Date">
            </div>
            <div class="col-md-6">
              <input type="time" class="form-control" placeholder="Time">
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label>Lesson 2</label>
          <div class="row">
            <div class="col-md-6">
              <input type="date" class="form-control" placeholder="Date">
            </div>
            <div class="col-md-6">
              <input type="time" class="form-control" placeholder="Time">
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label>Lesson 3</label>
          <div class="row">
            <div class="col-md-6">
              <input type="date" class="form-control" placeholder="Date">
            </div>
            <div class="col-md-6">
              <input type="time" class="form-control" placeholder="Time">
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label>Lesson 4</label>
          <div class="row">
            <div class="col-md-6">
              <input type="date" class="form-control" placeholder="Date">
            </div>
            <div class="col-md-6">
              <input type="time" class="form-control" placeholder="Time">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary">Schedule Lessons</button>
      </div>
    </div>
  </div>
</div>

<?php
include '../../../include/footer.html';
?>