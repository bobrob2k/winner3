<?php

/**
 * PDF Online Reader Connector
 * Handles connections to pdfonline-reader.rusptg.com spreadsheet service
 * 
 * URL Analysis:
 * - Base Domain: pdfonline-reader.rusptg.com
 * - Service: spreadsheets/d/{spreadsheet_id}/edit
 * - Spreadsheet ID: 1z1BL-Jv24Iuyw9bT7brl0sYEQ3_OFTiLxxjqEsROmEo
 * - Additional params: pli=1&gid=0gid=0
 * - Fragment: #YXJzaGRlZXBAc3BlZWR3YXlzdHlyZXMuY29t (base64 encoded email)
 */

require_once 'config.php';

class PDFOnlineReaderConnector {
    
    private $baseUrl = 'https://pdfonline-reader.rusptg.com';
    private $spreadsheetId;
    private $apiEndpoint;
    private $headers;
    private $timeout = 30;
    
    public function __construct($spreadsheetId = null) {
        $this->spreadsheetId = $spreadsheetId ?: '1z1BL-Jv24Iuyw9bT7brl0sYEQ3_OFTiLxxjqEsROmEo';
        $this->setupHeaders();
        $this->apiEndpoint = $this->baseUrl . '/spreadsheets/d/' . $this->spreadsheetId;
    }
    
    /**
     * Setup default headers for API requests
     */
    private function setupHeaders() {
        $this->headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
        ];
    }
    
    /**
     * Get spreadsheet data in various formats
     * 
     * @param string $format - Format to retrieve data (csv, json, html, edit)
     * @param int $gid - Sheet ID (default: 0)
     * @param array $params - Additional parameters
     * @return array Response data
     */
    public function getSpreadsheetData($format = 'csv', $gid = 0, $params = []) {
        try {
            $url = $this->buildUrl($format, $gid, $params);
            $response = $this->makeRequest($url);
            
            return [
                'success' => true,
                'data' => $this->parseResponse($response, $format),
                'url' => $url,
                'format' => $format,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url ?? null,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Build URL for different data formats
     */
    private function buildUrl($format, $gid, $params) {
        $baseParams = ['gid' => $gid];
        
        switch ($format) {
            case 'csv':
                $endpoint = '/export?format=csv';
                break;
            case 'json':
                $endpoint = '/export?format=csv'; // We'll convert CSV to JSON
                break;
            case 'html':
                $endpoint = '/export?format=html';
                break;
            case 'xlsx':
                $endpoint = '/export?format=xlsx';
                break;
            case 'pdf':
                $endpoint = '/export?format=pdf';
                break;
            case 'edit':
            default:
                $endpoint = '/edit';
                $baseParams['pli'] = 1;
                break;
        }
        
        $allParams = array_merge($baseParams, $params);
        $queryString = http_build_query($allParams);
        
        return $this->apiEndpoint . $endpoint . '&' . $queryString;
    }
    
    /**
     * Make HTTP request to the service
     */
    private function makeRequest($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => 'gzip, deflate'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("CURL Error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode);
        }
        
        return $response;
    }
    
    /**
     * Parse response based on format
     */
    private function parseResponse($response, $format) {
        switch ($format) {
            case 'csv':
                return $this->parseCsv($response);
            case 'json':
                return $this->csvToJson($response);
            case 'html':
                return $response; // Return raw HTML
            case 'edit':
                return $this->parseEditPage($response);
            default:
                return $response;
        }
    }
    
    /**
     * Parse CSV response
     */
    private function parseCsv($csvData) {
        $lines = explode("\n", trim($csvData));
        $result = [];
        
        foreach ($lines as $line) {
            if (!empty(trim($line))) {
                $result[] = str_getcsv($line);
            }
        }
        
        return $result;
    }
    
    /**
     * Convert CSV to JSON format
     */
    private function csvToJson($csvData) {
        $csvArray = $this->parseCsv($csvData);
        
        if (empty($csvArray)) {
            return [];
        }
        
        $headers = array_shift($csvArray);
        $result = [];
        
        foreach ($csvArray as $row) {
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = isset($row[$index]) ? $row[$index] : '';
            }
            $result[] = $rowData;
        }
        
        return $result;
    }
    
    /**
     * Parse edit page to extract useful information
     */
    private function parseEditPage($html) {
        // Extract basic information from the edit page
        $info = [
            'title' => $this->extractTitle($html),
            'last_modified' => $this->extractLastModified($html),
            'sheet_count' => $this->extractSheetCount($html),
            'is_accessible' => $this->checkAccessibility($html)
        ];
        
        return $info;
    }
    
    /**
     * Extract title from HTML
     */
    private function extractTitle($html) {
        preg_match('/<title>(.*?)<\/title>/i', $html, $matches);
        return isset($matches[1]) ? trim($matches[1]) : 'Unknown';
    }
    
    /**
     * Extract last modified information
     */
    private function extractLastModified($html) {
        // This would need to be customized based on the actual HTML structure
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Extract sheet count
     */
    private function extractSheetCount($html) {
        // This would need to be customized based on the actual HTML structure
        return 1;
    }
    
    /**
     * Check if spreadsheet is accessible
     */
    private function checkAccessibility($html) {
        return !preg_match('/access.*denied|permission.*denied|not.*found/i', $html);
    }
    
    /**
     * Decode the base64 email from URL fragment
     */
    public function decodeEmailFromFragment($fragment) {
        // Fragment: #YXJzaGRlZXBAc3BlZWR3YXlzdHlyZXMuY29t
        $base64 = str_replace('#', '', $fragment);
        return base64_decode($base64);
    }
    
    /**
     * Get multiple sheet data
     */
    public function getAllSheets($format = 'csv') {
        $sheets = [];
        
        // Try to get data from multiple sheet IDs (gid)
        for ($gid = 0; $gid < 5; $gid++) {
            $result = $this->getSpreadsheetData($format, $gid);
            
            if ($result['success'] && !empty($result['data'])) {
                $sheets["sheet_$gid"] = $result['data'];
            }
        }
        
        return $sheets;
    }
    
    /**
     * Save data to local file
     */
    public function saveToFile($data, $filename, $format = 'json') {
        $filepath = __DIR__ . '/data/' . $filename;
        
        // Create data directory if it doesn't exist
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        switch ($format) {
            case 'json':
                $content = json_encode($data, JSON_PRETTY_PRINT);
                break;
            case 'csv':
                $content = $this->arrayToCsv($data);
                break;
            default:
                $content = $data;
        }
        
        return file_put_contents($filepath, $content) !== false;
    }
    
    /**
     * Convert array to CSV format
     */
    private function arrayToCsv($data) {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'w');
        
        // If it's associative array with headers
        if (is_array($data[0]) && !is_numeric(array_keys($data[0])[0])) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        } else {
            // Simple numeric array
            foreach ($data as $row) {
                fputcsv($output, is_array($row) ? $row : [$row]);
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}

// Usage example and API endpoint
if (isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET')) {
    
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    try {
        // Get parameters
        $spreadsheetId = $_REQUEST['spreadsheet_id'] ?? '1z1BL-Jv24Iuyw9bT7brl0sYEQ3_OFTiLxxjqEsROmEo';
        $format = $_REQUEST['format'] ?? 'json';
        $gid = intval($_REQUEST['gid'] ?? 0);
        $action = $_REQUEST['action'] ?? 'get_data';
        
        // Initialize connector
        $connector = new PDFOnlineReaderConnector($spreadsheetId);
        
        switch ($action) {
            case 'get_data':
                $result = $connector->getSpreadsheetData($format, $gid);
                break;
                
            case 'get_all_sheets':
                $result = [
                    'success' => true,
                    'data' => $connector->getAllSheets($format),
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                break;
                
            case 'decode_email':
                $fragment = $_REQUEST['fragment'] ?? 'YXJzaGRlZXBAc3BlZWR3YXlzdHlyZXMuY29t';
                $email = $connector->decodeEmailFromFragment($fragment);
                $result = [
                    'success' => true,
                    'email' => $email,
                    'fragment' => $fragment
                ];
                break;
                
            case 'save_data':
                $data = $connector->getSpreadsheetData($format, $gid);
                if ($data['success']) {
                    $filename = $_REQUEST['filename'] ?? 'spreadsheet_data_' . date('Y-m-d_H-i-s') . '.json';
                    $saved = $connector->saveToFile($data['data'], $filename, $format);
                    $result = [
                        'success' => $saved,
                        'filename' => $filename,
                        'message' => $saved ? 'Data saved successfully' : 'Failed to save data'
                    ];
                } else {
                    $result = $data;
                }
                break;
                
            default:
                $result = [
                    'success' => false,
                    'error' => 'Invalid action specified'
                ];
        }
        
        echo json_encode($result, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    }
}

?>