<?php
require_once 'phpqrcode/qrlib.php';
$conn = new mysqli("localhost", "root", "", "reforest", 3306);

// FORZAR eliminación
$files = glob('qr_codes/*');
foreach($files as $file) {
    if (is_file($file)) {
        unlink($file);
        echo "Eliminado: $file<br>";
    }
}

// FORZAR regeneración con URL específica
$result = $conn->query("SELECT id FROM arboles");
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $url = "http://localhost/SkyGreen/index.php?tree_id=" . $id . "#map";
    $qrFile = 'qr_codes/qr_' . $id . '.png';

    QRcode::png($url, $qrFile, QR_ECLEVEL_L, 4);

    $stmt = $conn->prepare("UPDATE arboles SET qrUrl = ? WHERE id = ?");
    $stmt->bind_param("si", $qrFile, $id);
    $stmt->execute();

    echo "FORZADO QR #$id: $url<br>";
}
?>
