<?php
$data = trim($_GET['data'] ?? 'PREVIEW');
$safeData = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

header('Content-Type: image/svg+xml');

$hash = md5($data);
$bars = [];
for ($i = 0; $i < 24; $i++) {
    $bars[] = hexdec($hash[$i % 32]) % 2 === 0 ? 1 : 0;
}
?>
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="180" viewBox="0 0 640 180" role="img" aria-label="Barcode preview">
    <rect width="640" height="180" rx="18" fill="#ffffff"/>
    <rect x="20" y="20" width="600" height="140" rx="14" fill="#f8fbff" stroke="#d8e0ea"/>
    <text x="40" y="54" fill="#102033" font-family="Arial, Helvetica, sans-serif" font-size="18" font-weight="700">GateFlow Barcode Preview</text>
    <text x="40" y="80" fill="#607081" font-family="Arial, Helvetica, sans-serif" font-size="12"><?php echo $safeData; ?></text>
    <?php
        $x = 40;
        for ($i = 0; $i < count($bars); $i++) {
            $height = $bars[$i] ? 84 : 58;
            $fill = $bars[$i] ? '#132238' : '#155eef';
            echo '<rect x="' . $x . '" y="' . (120 - $height) . '" width="10" height="' . $height . '" rx="2" fill="' . $fill . '" />';
            $x += 16;
        }
    ?>
    <text x="40" y="154" fill="#607081" font-family="Arial, Helvetica, sans-serif" font-size="11">This is a visual preview generated from the provided data.</text>
</svg>