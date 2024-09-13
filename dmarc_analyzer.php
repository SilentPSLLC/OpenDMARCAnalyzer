<?php
// Enable error reporting for debugging
error_reporting(E_ALL);

// Function to sanitize filenames
function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $filename); // Sanitize file name
}

// Function to check DMARC compliance (SPF and DKIM pass)
function checkCompliance($spf, $dkim) {
    return $spf && $dkim;  // Returns true if both SPF and DKIM pass
}

// Define the base uploads directory
$base_upload_dir = '/var/www/html/uploads/'; #asumes this is the default path
$generated_png = "";  // Variable to store the generated PNG path
$xml_file = "";  // Track the XML file name
$analysis_done = false;  // Track if the analysis has been done
$analysis_output = "";  // Store analysis output
$results_file = "";  // The file that will store the results HTML
$archive_created = false;  // Track if the archive has been created

// Check if the analysis has been completed in previous submissions
if (isset($_POST['analysis_done']) && $_POST['analysis_done'] === "true") {
    $analysis_done = true;  // Retain the state of analysis being completed
    $extracted_dir = $_POST['extracted_dir'];  // Retain the extracted directory
    $xml_file_base = $_POST['xml_file_base'];  // Retain the XML file base name
    $results_file = $_POST['results_file'];  // Retain the HTML file
    $generated_png = $_POST['generated_png'];  // Retain the PNG file
    $xml_file = $_POST['xml_file'];  // Retain the XML file
}

// Check if a file is uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dmarc_zip'])) {

    // Sanitize the uploaded ZIP filename
    $original_filename = $_FILES['dmarc_zip']['name'];
    $sanitized_filename = sanitize_filename(basename($original_filename));

    // Create a directory for the extracted files, named after the ZIP file (without the .zip extension)
    $extracted_dir = $base_upload_dir . pathinfo($sanitized_filename, PATHINFO_FILENAME) . '/';
    
    // Create the extracted directory if it doesn't exist
    if (!is_dir($extracted_dir)) {
        mkdir($extracted_dir, 0755, true);
    }

    // Full path to the uploaded ZIP file
    $zip_file = $extracted_dir . $sanitized_filename;

    // Move the uploaded file to the server
    if (move_uploaded_file($_FILES['dmarc_zip']['tmp_name'], $zip_file)) {
        // Extract the ZIP file into the extracted directory
        $zip = new ZipArchive;
        if ($zip->open($zip_file) === TRUE) {
            $zip->extractTo($extracted_dir);
            $zip->close();

            // Get the XML files from the extracted contents
            $xml_files = glob($extracted_dir . '*.xml');
            if (count($xml_files) > 0) {
                $xml_file = $xml_files[0]; // Get the first XML file found

                // Load the XML file
                $xml = simplexml_load_file($xml_file);

                // Process the XML and extract DMARC data
                $data = [];
                $analysis_output = "<h2>DMARC Analysis Completed</h2><table><thead><tr><th>IP Address</th><th>Email Volume</th><th>SPF Authentication</th><th>DKIM Authentication</th><th>Compliance Status</th></tr></thead><tbody>";
                
                foreach ($xml->record as $record) {
                    $ip = (string) $record->row->source_ip;
                    $email_volume = (int) $record->row->count;
                    $spf_pass = (string) $record->auth_results->spf->result === 'pass';
                    $dkim_pass = (string) $record->auth_results->dkim->result === 'pass';
                    $compliance_status = checkCompliance($spf_pass, $dkim_pass) ? '✔ Passed' : '✘ Failed';

                    // Store the analysis data
                    $data[] = [
                        'ip' => $ip,
                        'email_volume' => $email_volume,
                        'spf_auth' => $spf_pass,
                        'dkim_auth' => $dkim_pass,
                        'compliance' => $compliance_status
                    ];

                    // Add to the output string
                    $analysis_output .= "<tr><td>{$ip}</td><td>{$email_volume}</td><td>" . ($spf_pass ? 'Pass' : 'Fail') . "</td><td>" . ($dkim_pass ? 'Pass' : 'Fail') . "</td><td>{$compliance_status}</td></tr>";
                }

                $analysis_output .= "</tbody></table>";
                $analysis_done = true;  // Mark that the analysis is completed

                // Use the same base name as the XML file for the HTML and PNG
                $xml_file_base = pathinfo($xml_file, PATHINFO_FILENAME); // Get the XML filename without the extension
                $results_file = $extracted_dir . $xml_file_base . ".html"; // Save the results as an HTML file
                $generated_png = $extracted_dir . $xml_file_base . ".png"; // Name the PNG file the same as the XML file

                // Save the HTML results with a reference to the CSS file
                $html_output = '<!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>DMARC Analysis Results</title>
                    <link rel="stylesheet" href="/style.css"> <!-- Link to CSS file -->
                </head>
                <body>';
                $html_output .= $analysis_output;
                $html_output .= '</body></html>';
                
                // Save the results as an HTML file with the same name as the XML file
                file_put_contents($results_file, $html_output);  
            }
        }
    }
}

// Handle the archive creation and download
if (isset($_POST['archive'])) {
    // Ensure the ZIP is created only if the analysis is completed
    if ($analysis_done) {
        $archive_zip_file = $extracted_dir . $xml_file_base . "_archive.zip";  // Name the archive file
        $archive = new ZipArchive();
        if ($archive->open($archive_zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add the XML, HTML, and PNG to the archive
            $archive->addFile($xml_file, basename($xml_file));
            $archive->addFile($results_file, basename($results_file));

            // Ensure that the PNG file exists and is added to the archive
            if (file_exists($generated_png)) {
                $archive->addFile($generated_png, basename($generated_png));
            } else {
                echo "PNG file not found: " . $generated_png;  // Debug if PNG is not found
            }

            // Close the archive
            $archive->close();

            // Delete the original uploaded ZIP
            if (file_exists($zip_file)) {
                unlink($zip_file);  // Delete the original ZIP file
            }

            $archive_created = true;  // Mark archive as created

            // Force download the newly created ZIP
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($archive_zip_file) . '"');
            header('Content-Length: ' . filesize($archive_zip_file));
            readfile($archive_zip_file);
            exit;  // Stop script execution after downloading the archive
        } else {
            echo "Failed to create archive.";  // Debug output if ZIP creation fails
        }
    } else {
        echo "Analysis is not complete yet.";  // Debug output if analysis isn't complete
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMARC Analyzer</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to the external CSS file -->

    <!-- Include jQuery for triggering snapshot after page load -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            // If the analysis is done, trigger the snapshot generation
            <?php if ($analysis_done): ?>
            // Wait 2 seconds for full page render before capturing
            setTimeout(function() {
                $.post("generate_snapshot.php", {
                    results_file: "<?php echo $results_file; ?>"  // Pass the results HTML file for capturing
                }, function(data) {
                    console.log(data);  // Debug output
                });
            }, 2000);  // Wait 2 seconds before triggering the snapshot
            <?php endif; ?>
        });
    </script>
</head>
<body>
    <h1>DMARC Compliance Analyzer</h1>
    
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="dmarc_zip">Upload DMARC ZIP File:</label>
        <input type="file" name="dmarc_zip" id="dmarc_zip" required>
        <button type="submit">Upload and Analyze</button>
    </form>

    <?php 
    // Display analysis results if analysis is done
    if ($analysis_done) {
        echo $analysis_output; 
    ?>

    <!-- Archive button: only appears after analysis is completed -->
    <form method="POST">
        <input type="hidden" name="analysis_done" value="true">
        <input type="hidden" name="extracted_dir" value="<?php echo $extracted_dir; ?>">
        <input type="hidden" name="xml_file_base" value="<?php echo $xml_file_base; ?>">
        <input type="hidden" name="results_file" value="<?php echo $results_file; ?>">
        <input type="hidden" name="generated_png" value="<?php echo $generated_png; ?>">
        <input type="hidden" name="xml_file" value="<?php echo $xml_file; ?>">
        <button type="submit" name="archive">Archive and Download</button>
    </form>

    <?php } ?>
</body>
</html>
