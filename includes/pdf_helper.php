<?php
// PDF Helper - Downloads and extracts Dompdf if not already present

function init_dompdf() {
    $dompdf_dir = __DIR__ . '/dompdf';
    $autoload = $dompdf_dir . '/autoload.inc.php';

    if (!file_exists($autoload)) {
        // Zip URL from GitHub releases for a pre-packaged Dompdf (includes dependencies)
        $zip_url = 'https://github.com/dompdf/dompdf/releases/download/v2.0.8/dompdf_2-0-8.zip';
        $zip_file = __DIR__ . '/dompdf.zip';

        // Check if curl is available
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $zip_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 mins max
            $data = curl_exec($ch);
            $err = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);
            $data = @file_get_contents($zip_url, false, $context);
            $http_code = $data ? 200 : 500;
        }

        if (empty($data) || $http_code !== 200) {
            die("<div style='padding:20px; font-family:sans-serif; color:#721c24; background-color:#f8d7da; border:1px solid #f5c6cb; border-radius:5px;'>
                    <h4>Dompdf Library Missing</h4>
                    <p>The system tried to automatically download the Dompdf PDF generation library from GitHub, but the request failed.</p>
                    <p><strong>Manual setup instructions:</strong></p>
                    <ol>
                        <li>Download the packaged release zip from: <a href='{$zip_url}'>{$zip_url}</a></li>
                        <li>Extract the contents of the zip file into the folder: <code>" . htmlspecialchars($dompdf_dir) . "</code> (so that <code>autoload.inc.php</code> is directly under it).</li>
                        <li>Refresh this page to generate your PDF.</li>
                    </ol>
                 </div>");
        }

        // Save zip file
        file_put_contents($zip_file, $data);

        // Extract using ZipArchive
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($zip_file) === TRUE) {
                // Extracts into a subfolder named 'dompdf' inside the zip,
                // so we extract into __DIR__ which creates __DIR__/dompdf/
                $zip->extractTo(__DIR__);
                $zip->close();
                @unlink($zip_file);
            } else {
                @unlink($zip_file);
                die("Error: Failed to open the downloaded Dompdf zip file. Please extract it manually inside <code>includes/dompdf</code>.");
            }
        } else {
            @unlink($zip_file);
            die("Error: PHP class <code>ZipArchive</code> is not enabled on this server. Please manually extract the Dompdf zip file into <code>includes/dompdf</code>.");
        }
    }

    // Require the autoloader
    require_once $autoload;
}
?>
