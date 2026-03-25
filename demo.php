<?php
session_start();

if($SERVER["REQURST_METHOD"]== "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    
}