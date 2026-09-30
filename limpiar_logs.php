<?php
$log = ini_get("error_log");

if ($log && file_exists($log)) {
    file_put_contents($log, "");
    echo "ok";
} else {
    echo "no_log";
}
<<<<<<< HEAD
?>
=======
?>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
