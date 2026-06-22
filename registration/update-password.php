<?php
include("../config/db.php");

$token = $_POST['token'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "UPDATE users 
SET password='$password', reset_token=NULL, reset_expires=NULL 
WHERE reset_token='$token'";

if(mysqli_query($conn, $sql)){
    echo "Password updated successfully!";
} else {
    echo "Error updating password";
}
?>