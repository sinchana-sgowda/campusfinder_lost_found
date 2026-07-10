<?php
session_start();
session_destroy();
header("Location: lostfound_index.php");
exit();
