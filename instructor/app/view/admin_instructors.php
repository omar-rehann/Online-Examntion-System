
<?php
if (!defined('NotDirectAccess')){
	die('Direct Access is not allowed to this page');
}
require_once 'header.php';
require_once 'navbar.php';
$_admin = new admin();
?>
<div class="card">
	<?php
		if(isset($_SESSION['error']))
		foreach($_SESSION['error'] as $err){
		echo '<div class="sufee-alert alert alert-danger alert-dismissible fade show">
		<span class="badge badge-pill badge-danger">Failed</span>'. $err . '</div>';
		}
		unset($_SESSION['error']);
		if (isset($_SESSION['info']))
		foreach($_SESSION['info'] as $info){
			echo '<div class="sufee-alert alert alert-success alert-dismissible fade show">
			<span class="badge badge-pill badge-success">Success</span>'. $info . '</div>';
		}
		unset($_SESSION['info']);
	?>
  <div class="card-header">
    <strong class="card-title">All Registered Instructors</strong>
	<button type="button" class="btn btn-outline-success float-right" style="margin-right:20px" data-toggle="modal" data-target="#addInstructor">
		<i class="fa fa-plus"></i> Add Instructor
	</button>
  </div>

  <div class="card-body">
    <table id="allInstructors" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone Number</th>
		  <th>Password</th>
          <th>Status</th>
          <th>-</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $instructors = $_admin->getAllInstructors();
        foreach ($instructors as $instructor) { ?>
          <tr>
            <td><?php echo $instructor->name ?></td>
            <td><?php echo $instructor->email ?></td>

            <td><?php echo $instructor->phone ?></td>
			<td><?php echo $instructor->password ?></td>

            <?php
              if($instructor->suspended){ ?>
                <td><span class="badge badge-danger">Suspended</span></td>
                <td>
                  <button type="button" class="btn btn-outline-success btn-block"
								onclick="customConfirm('app/controller/admin.inc.php?activateInstructor=<?php echo $instructor->id ?>','Are you sure you want to activate This Instructor?','The Instructor has been activated.')">
                    <i class="fa fa-repeat"></i> Activate</button>
                </td>
              <?php }else{ ?>
                <td><span class="badge badge-success">Active</span></td>
                <td>
                  <button type="button" class="btn btn-outline-danger btn-block"
								onclick="customConfirm('app/controller/admin.inc.php?suspendInstructor=<?php echo $instructor->id ?>','Are you sure you want to suspend This Instructor?','The Instructor has been suspended.')">
                  <i class="fa fa-ban"></i> Suspend</button>
                </td>
              <?php } ?>
          </tr>
          <?php } ?>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="addInstructor" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Add New Instructor</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form id="addInstructorForm" action="app/controller/instructor.inc.php?action=manualinsert" method="post">
						<div class="form-group">
							<label for="name" class="col-form-label">Full Name:</label>
							<input type="text" class="form-control" id="name" name="name" placeholder="Enter Name">
						</div>
						<div class="form-group">
							<label for="email" class="col-form-label">Email Address:</label>
							<input type="email" class="form-control" id="email" name="email" placeholder="Enter Email">
						</div>
						<div class="form-group">
							<label for="phone" class="col-form-label">Phone Number:</label>
							<input type="number" class="form-control" id="phone" name="phone" placeholder="Enter Phone Number">
						</div>
						<div class="form-group">
							<label for="password" class="col-form-label">Password:</label>
							<input type="password" class="form-control" id="password" name="password" placeholder="Enter Password">
						</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				<button type="submit" form="addInstructorForm" class="btn btn-primary">Add</button>
			</div>
		</div>
	</div>
</div>
<?php
  define('ContainsDatatables', true);
  require_once 'footer.php';
?>
