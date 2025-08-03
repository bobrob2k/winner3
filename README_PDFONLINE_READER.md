# PDF Online Reader Connector

This PHP connector provides an interface to connect and extract data from the pdfonline-reader.rusptg.com spreadsheet service.

## URL Analysis

Your provided URL:
```
https://pdfonline-reader.rusptg.com/spreadsheets/d/1z1BL-Jv24Iuyw9bT7brl0sYEQ3_OFTiLxxjqEsROmEo/edit?pli=1&gid=0gid=0#YXJzaGRlZXBAc3BlZWR3YXlzdHlyZXMuY29t
```

**Components:**
- **Domain:** pdfonline-reader.rusptg.com
- **Service:** spreadsheets/d/{spreadsheet_id}/edit
- **Spreadsheet ID:** 1z1BL-Jv24Iuyw9bT7brl0sYEQ3_OFTiLxxjqEsROmEo
- **Parameters:** pli=1&gid=0gid=0
- **Fragment:** #YXJzaGRlZXBAc3BlZWR3YXlzdHlyZXMuY29t (base64 encoded email: arshdeep@speedwaystyres.com)

## Files Created

1. **`pdfonline_reader_connector.php`** - Main connector class
2. **`pdfonline_reader_example.php`** - Usage examples and demonstrations
3. **`README_PDFONLINE_READER.md`** - This documentation file

## Features

### PDFOnlineReaderConnector Class

- **Multi-format Support:** CSV, JSON, HTML, XLSX, PDF
- **Sheet Management:** Access multiple sheets by GID
- **Data Export:** Save data to local files
- **URL Fragment Decoding:** Decode base64 encoded emails
- **Error Handling:** Comprehensive error reporting
- **HTTP Headers:** Proper browser simulation for compatibility

### Supported Actions

1. **get_data** - Retrieve spreadsheet data in specified format
2. **get_all_sheets** - Get data from all available sheets
3. **decode_email** - Decode base64 encoded email from URL fragment
4. **save_data** - Save spreadsheet data to local file

## Usage Examples

### Basic PHP Usage

```php
<?php
require_once 'pdfonline_reader_connector.php';

// Initialize with your spreadsheet ID
$connector = new PDFOnlineReaderConnector('1z1BL-Jv24Iuyw9bT7brl0sYEQ3_OFTiLxxjqEsROmEo');

// Get data as JSON
$result = $connector->getSpreadsheetData('json', 0);

if ($result['success']) {
    print_r($result['data']);
} else {
    echo "Error: " . $result['error'];
}
?>
```

### API Endpoints

#### Get Spreadsheet Data
```
GET /pdfonline_reader_connector.php?action=get_data&format=json&gid=0
```

#### Get All Sheets
```
GET /pdfonline_reader_connector.php?action=get_all_sheets&format=csv
```

#### Decode Email from Fragment
```
GET /pdfonline_reader_connector.php?action=decode_email&fragment=YXJzaGRlZXBAc3BlZWR3YXlzdHlyZXMuY29t
```

#### Save Data to File
```
GET /pdfonline_reader_connector.php?action=save_data&format=json&filename=backup.json
```

### cURL Examples

```bash
# Get spreadsheet data as JSON
curl "http://your-domain.com/pdfonline_reader_connector.php?action=get_data&format=json&gid=0"

# Get all sheets as CSV
curl "http://your-domain.com/pdfonline_reader_connector.php?action=get_all_sheets&format=csv"

# Decode email from fragment
curl "http://your-domain.com/pdfonline_reader_connector.php?action=decode_email&fragment=YXJzaGRlZXBAc3BlZWR3YXlzdHlyZXMuY29t"
```

### JavaScript/AJAX Usage

```javascript
// Fetch spreadsheet data
fetch('pdfonline_reader_connector.php?action=get_data&format=json&gid=0')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Spreadsheet data:', data.data);
        } else {
            console.error('Error:', data.error);
        }
    });

// jQuery example
$.ajax({
    url: 'pdfonline_reader_connector.php',
    data: {
        action: 'get_data',
        format: 'json',
        gid: 0
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data);
        }
    }
});
```

## Response Format

All API responses follow this JSON structure:

```json
{
    "success": true|false,
    "data": "response_data",
    "url": "requested_url",
    "format": "response_format",
    "timestamp": "2023-12-07 10:30:45",
    "error": "error_message_if_failed"
}
```

## Supported Formats

1. **CSV** - Comma-separated values
2. **JSON** - JavaScript Object Notation (parsed from CSV)
3. **HTML** - Raw HTML format
4. **XLSX** - Excel format
5. **PDF** - PDF format
6. **EDIT** - Edit page information

## Configuration

The connector uses the existing `config.php` file for basic configuration. You can modify the following settings in the connector:

- `$timeout` - Request timeout (default: 30 seconds)
- `$baseUrl` - Base URL for the service
- Headers for HTTP requests

## Error Handling

The connector includes comprehensive error handling:

- **CURL Errors** - Network connectivity issues
- **HTTP Errors** - Server response errors (404, 500, etc.)
- **Data Parsing Errors** - Invalid or malformed data
- **Access Errors** - Permission or authentication issues

## Security Considerations

1. **SSL Verification** - Currently disabled for development (should be enabled in production)
2. **Access Control** - The service may require authentication
3. **Rate Limiting** - Be mindful of request frequency
4. **Data Privacy** - Handle extracted data according to privacy policies

## Testing the Connection

Run the example file to test the connection:

```bash
php pdfonline_reader_example.php
```

Or access it via web browser:
```
http://your-domain.com/pdfonline_reader_example.php
```

## Troubleshooting

### Common Issues

1. **Connection Refused**
   - Check if the domain is accessible
   - Verify network connectivity
   - Check firewall settings

2. **Access Denied**
   - The spreadsheet may require authentication
   - Check if the spreadsheet is publicly accessible
   - Verify the spreadsheet ID is correct

3. **Empty Data Response**
   - The sheet might be empty
   - Wrong GID (sheet number)
   - Data format might not be supported

4. **SSL Certificate Issues**
   - Enable proper SSL verification for production
   - Check certificate validity

### Debug Mode

To enable debug mode, modify the connector:

```php
// Enable CURL debug output
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, fopen('curl_debug.log', 'a+'));
```

## License

This connector is provided as-is for interfacing with the pdfonline-reader.rusptg.com service. Make sure to comply with the service's terms of use and data privacy policies.

## Support

For issues related to this connector, check:
1. Network connectivity to pdfonline-reader.rusptg.com
2. PHP cURL extension is installed and enabled
3. Proper permissions for file operations (if saving data)
4. Service-specific authentication requirements