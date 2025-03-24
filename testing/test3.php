<?php
include '../include/ad_header.php';
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Dashboard</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="#">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Pages</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Starter Page</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">

      <div class="col-md-4">
        <div class="card card-post card-round">
          <img
            class="card-img-top"
            src="assets/img/blogpost.jpg"
            alt="Card image cap" />
          <div class="card-body">
            <div class="d-flex">
              <div class="avatar">
                <img
                  src="assets/img/profile2.jpg"
                  alt="..."
                  class="avatar-img rounded-circle" />
              </div>
              <div class="info-post ms-2">
                <p class="username">Joko Subianto</p>
                <p class="date text-muted">20 Jan 18</p>
              </div>
            </div>
            <div class="separator-solid"></div>
            <p class="card-category text-info mb-1">
              <a href="#">Design</a>
            </p>
            <h3 class="card-title">
              <a href="#"> Best Design Resources This Week </a>
            </h3>
            <p class="card-text">
              Some quick example text to build on the card title and
              make up the bulk of the card's content.
            </p>
            <a href="#" class="btn btn-primary btn-rounded btn-sm">Read More</a>
          </div>
        </div>
      </div>

    </div>

  </div>
</div>

<?php
include '../include/footer.html';
?>