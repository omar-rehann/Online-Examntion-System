<?php
session_start();
include_once 'autoloader.inc.php';

if (isset($_GET['addGroup'])){
  $groupName = !empty($_POST['groupName']) ? trim($_POST['groupName']) : null;
  $newGroup = new group();
  if($groupName == null)
    $_SESSION['error'][] = 'The Group Name is not valid.';
  if($newGroup->checkName($groupName))
    $_SESSION['error'][] = 'The Group name is already used.';
  if(empty($_SESSION['error'])){
    $newGroup->insert($groupName);
    $_SESSION['info'][] = 'Group Added successfully';
  }
  header('Location: ' . $_SERVER['HTTP_REFERER']);
}elseif(isset($_GET['editGroup'])){
    $id = isset($_POST['id']) ? trim($_POST['id']) : null;
    $name = isset($_POST['groupName']) ? trim($_POST['groupName']) : null;
    $Group = new group();
    $checkNameAvailable = $Group->checkName($name);
    if($id == null)
      $_SESSION['error'][] = 'The Group ID is not valid.';
    if($name == null || empty($name))
      $_SESSION['error'][] = 'The Group Name is not valid.';
    if($checkNameAvailable)
      $_SESSION['error'][] = 'The Group name is already used.';
    if(empty($_SESSION['error'])){
      $Group->update($id,$name);
      $_SESSION['info'][] = 'Group Added successfully';
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);

}else if (isset($_GET['deleteGroup'])){
  $id = !empty($_GET['deleteGroup']) ? trim($_GET['deleteGroup']) : null;
  $newGroup = new group();
  $newGroup->delete($id);
  header('Location: ' . $_SERVER['HTTP_REFERER']);

}else if (isset($_GET['addStudents'])){
  $groupID = !empty($_POST['groupID']) ? trim($_POST['groupID']) : null;
  $ids = explode("\n", str_replace("\r", "", $_POST['studentIDs']));
  $nonValid = [];
  $valid = [];
  $std = new student();
  $allIDs = $std->getAllIDs();
  $idArray = [];
  foreach($allIDs as $id)
    $idArray[] = $id->id;

  $grp = new group();
  foreach ($ids as $key => $val)
    if (!is_numeric($val) || !in_array($val,$idArray)){
        $nonValid[] = $val;
      }else{
        $valid[] = $val;
      }

      if(!empty($valid)) $grp->addMembers($groupID,$valid);
     $_SESSION['valid'] = $valid;
     $_SESSION['nonValid'] = $nonValid;
     header('Location:../../?groups&viewMembers&id=' . $groupID);

}else if (isset($_GET['deleteStudent']) && isset($_GET['group'])){
  $sid = !empty($_GET['deleteStudent']) ? trim($_GET['deleteStudent']) : null;
  $gid = !empty($_GET['group']) ? trim($_GET['group']) : null;
  if(($sid != null) && ($gid != null)){
  $newGroup = new group();
  $newGroup->removeMember($gid,$sid);
  }
  header('Location: ' . $_SERVER['HTTP_REFERER']);
}else if(isset($_GET['createInvites'])) {
    $groupID = !empty($_POST['groupID']) ? trim($_POST['groupID']) : null;
    $count = !empty($_POST['count']) ? trim($_POST['count']) : null;
    $newGroup = new group();
    $newGroup->generateInvitations($groupID, $count);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}else if (isset($_GET['deleteInvite'])){
  $id = !empty($_GET['deleteInvite']) ? trim($_GET['deleteInvite']) : null;
  if($id != null){
    $newGroup = new group();
    $newGroup->deleteOneInvite($id);
  }
  header('Location: ' . $_SERVER['HTTP_REFERER']);

}else if (isset($_GET['clearInvites'])){
  $id = !empty($_GET['clearInvites']) ? trim($_GET['clearInvites']) : null;
  $newGroup = new group();
  $newGroup->deleteInvitations($id);
  header('Location: ' . $_SERVER['HTTP_REFERER']);

}

