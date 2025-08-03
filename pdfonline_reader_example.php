<?php

/**
 * PDF Online Reader Connector - Usage Examples
 * This file demonstrates how to use the PDFOnlineReaderConnector class
 */

require_once 'pdfonline_reader_connector.php';

// Example usage scenarios
echo "<h2>PDF Online Reader Connector - Usage Examples</h2>\n";

// Original URL components from your request
$originalUrl = 'https://pdfonline-reader.rusptg.com/spreadsheets/d/1z1BL-Jv24Iuyw9bT7brl0sYEQ3_OFTiLxxjqEsROmEo/edit?pli=1&gid=0gid=0#YXJzaGRlZXBAc3BlZWR3YXlzdHlyZXMuY29t';

echo "<h3>Original URL Analysis:</h3>\n";
echo "<p><strong>Full URL:</strong> " . htmlspecialchars($originalUrl) . "</p>\n";

// Parse URL components
$urlParts = parse_url($originalUrl);
$queryParams = [];
if (isset($urlParts['query'])) {
    parse_str($urlParts['query'], $queryParams);
}

echo "<h4>URL Components:</h4>\n";
echo "<ul>\n";
echo "<li><strong>Domain:</strong> " . $urlParts['host'] . "</li>\n";
echo "<li><strong>Path:</strong> " . $urlParts['path'] . "</li>\n";
echo "<li><strong>Query:</strong> " . ($urlParts['query'] ?? 'None') . "</li>\n";
echo "<li><strong>Fragment:</strong> " . ($urlParts['fragment'] ?? 'None') . "</li>\n";
echo "</ul>\n";

// Extract spreadsheet ID from path
preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $urlParts['path'], $matches);
$spreadsheetId = $matches[1] ?? '1z1BL-Jv24Iuyw9bT7brl0sYEQ3_OFTiLxxjqEsROmEo';

echo "<p><strong>Extracted Spreadsheet ID:</strong> " . $spreadsheetId . "</p>\n";

// Initialize the connector
$connector = new PDFOnlineReaderConnector($spreadsheetId);

// Decode email from fragment
if (isset($urlParts['fragment'])) {
    $email = $connector->decodeEmailFromFragment($urlParts['fragment']);
    echo "<p><strong>Decoded Email:</strong> " . htmlspecialchars($email) . "</p>\n";
}

echo "<hr>\n";

// Example 1: Get data in JSON format
echo "<h3>Example 1: Get Spreadsheet Data (JSON format)</h3>\n";
try {
    $result = $connector->getSpreadsheetData('json', 0);
    
    echo "<p><strong>Status:</strong> " . ($result['success'] ? 'Success' : 'Failed') . "</p>\n";
    echo "<p><strong>URL Called:</strong> " . htmlspecialchars($result['url'] ?? 'N/A') . "</p>\n";
    
    if ($result['success']) {
        echo "<p><strong>Data Preview:</strong></p>\n";
        echo "<pre>" . htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT)) . "</pre>\n";
    } else {
        echo "<p><strong>Error:</strong> " . htmlspecialchars($result['error']) . "</p>\n";
    }
} catch (Exception $e) {
    echo "<p><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// Example 2: Get data in CSV format
echo "<h3>Example 2: Get Spreadsheet Data (CSV format)</h3>\n";
try {
    $result = $connector->getSpreadsheetData('csv', 0);
    
    echo "<p><strong>Status:</strong> " . ($result['success'] ? 'Success' : 'Failed') . "</p>\n";
    
    if ($result['success']) {
        echo "<p><strong>CSV Data Preview:</strong></p>\n";
        echo "<pre>" . htmlspecialchars(print_r($result['data'], true)) . "</pre>\n";
    } else {
        echo "<p><strong>Error:</strong> " . htmlspecialchars($result['error']) . "</p>\n";
    }
} catch (Exception $e) {
    echo "<p><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// Example 3: Get edit page information
echo "<h3>Example 3: Get Edit Page Information</h3>\n";
try {
    $result = $connector->getSpreadsheetData('edit', 0);
    
    echo "<p><strong>Status:</strong> " . ($result['success'] ? 'Success' : 'Failed') . "</p>\n";
    
    if ($result['success']) {
        echo "<p><strong>Page Information:</strong></p>\n";
        echo "<pre>" . htmlspecialchars(print_r($result['data'], true)) . "</pre>\n";
    } else {
        echo "<p><strong>Error:</strong> " . htmlspecialchars($result['error']) . "</p>\n";
    }
} catch (Exception $e) {
    echo "<p><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// Example 4: API Usage via HTTP requests
echo "<h3>Example 4: API Usage Examples</h3>\n";
echo "<p>You can also use this connector via HTTP requests:</p>\n";

$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/pdfonline_reader_connector.php';

echo "<h4>Available API Endpoints:</h4>\n";
echo "<ul>\n";
echo "<li><strong>Get Data:</strong><br><code>" . $baseUrl . "?action=get_data&format=json&gid=0</code></li>\n";
echo "<li><strong>Get All Sheets:</strong><br><code>" . $baseUrl . "?action=get_all_sheets&format=json</code></li>\n";
echo "<li><strong>Decode Email:</strong><br><code>" . $baseUrl . "?action=decode_email&fragment=YXJzaGRlZXBAc3BlZWR3YXlzdHlyZXMuY29t</code></li>\n";
echo "<li><strong>Save Data:</strong><br><code>" . $baseUrl . "?action=save_data&format=json&filename=my_data.json</code></li>\n";
echo "</ul>\n";

echo "<h4>curl Examples:</h4>\n";
echo "<pre>\n";
echo "# Get spreadsheet data as JSON\n";
echo "curl \"" . $baseUrl . "?action=get_data&format=json&gid=0\"\n\n";

echo "# Get all sheets\n";
echo "curl \"" . $baseUrl . "?action=get_all_sheets&format=csv\"\n\n";

echo "# Decode email from fragment\n";
echo "curl \"" . $baseUrl . "?action=decode_email&fragment=YXJzaGRlZXBAc3BlZWR3YXlzdHlyZXMuY29t\"\n\n";

echo "# Save data to file\n";
echo "curl \"" . $baseUrl . "?action=save_data&format=json&filename=backup.json\"\n";
echo "</pre>\n";

echo "<hr>\n";

// Example 5: PHP Integration Code
echo "<h3>Example 5: PHP Integration Code</h3>\n";
echo "<p>Here's how you can integrate this into your existing PHP application:</p>\n";

echo "<pre>\n";
echo htmlspecialchars('<?php
// Include the connector
require_once \'pdfonline_reader_connector.php\';

// Initialize with your spreadsheet ID
$spreadsheetId = \'1z1BL-Jv24Iuyw9bT7brl0sYEQ3_OFTiLxxjqEsROmEo\';
$connector = new PDFOnlineReaderConnector($spreadsheetId);

// Get data from the spreadsheet
$data = $connector->getSpreadsheetData(\'json\', 0);

if ($data[\'success\']) {
    // Process the data
    foreach ($data[\'data\'] as $row) {
        // Do something with each row
        echo "Processing row: " . print_r($row, true);
    }
    
    // Save to file
    $connector->saveToFile($data[\'data\'], \'spreadsheet_backup.json\');
} else {
    echo "Error: " . $data[\'error\'];
}
?>');
echo "</pre>\n";

echo "<hr>\n";

// Example 6: JavaScript AJAX Usage
echo "<h3>Example 6: JavaScript/AJAX Usage</h3>\n";
echo "<p>You can also call this from JavaScript:</p>\n";

echo "<pre>\n";
echo htmlspecialchars('// Get spreadsheet data via AJAX
fetch(\'' . $baseUrl . '?action=get_data&format=json&gid=0\')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log(\'Spreadsheet data:\', data.data);
            // Process the data in your web application
        } else {
            console.error(\'Error:\', data.error);
        }
    })
    .catch(error => {
        console.error(\'Network error:\', error);
    });

// jQuery example
$.ajax({
    url: \'' . $baseUrl . '\',
    method: \'GET\',
    data: {
        action: \'get_data\',
        format: \'json\',
        gid: 0
    },
    dataType: \'json\',
    success: function(response) {
        if (response.success) {
            // Handle successful response
            console.log(response.data);
        } else {
            // Handle error
            console.error(response.error);
        }
    },
    error: function(xhr, status, error) {
        console.error(\'AJAX Error:\', error);
    }
});');
echo "</pre>\n";

echo "<hr>\n";
echo "<p><strong>Note:</strong> Make sure to test the actual connectivity to the pdfonline-reader.rusptg.com service, as the service may require authentication or have specific access restrictions.</p>\n";

?>