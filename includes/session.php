<?php
// Session management functions

session_start();

function checkSession() {
    if(!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}