<?php
require_once '../includes/config.php';
session_destroy();
redirect('student/login.php');
