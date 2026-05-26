<?php
require_once '../includes/config.php';
session_destroy();
redirect('admin/index.php');
