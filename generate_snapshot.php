<?php
// Enable error reporting for debugging. Don't enable this in production
//error_reporting(E_ALL);

// Get the path to the HTML results file
if (isset($_POST['results_file'])) {
    $results_file = $_POST['results_file'];
    $output_png = str_replace('.html', '.png', $results_file);  // Use same name as HTML but with .png extension

    // Generate the PNG snapshot using wkhtmltoimage
    $command = "wkhtmltoimage --width 1024 --javascript-delay 2000 $results_file $output_png";
    exec($command, $output, $return_var);

    // Check if the PNG was successfully created
    if (file_exists($output_png)) {
        echo "PNG snapshot generated successfully!";
    } else {
        echo "Failed to generate the PNG.";
    }
    exit();  // End script
}
?>
