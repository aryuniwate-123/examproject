<?php
include 'db.php';

function desc($conn, $table) {
    echo "Table: $table\n";
    $q = mysqli_query($conn, "DESCRIBE $table");
    while($r = mysqli_fetch_assoc($q)) {
        echo $r['Field'] . " - " . $r['Type'] . "\n";
    }
    echo "\n";
}

desc($conn, 'students');
desc($conn, 'batch');
?>
