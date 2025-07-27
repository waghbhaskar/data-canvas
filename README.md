DataCanvas/DataSetMapper
A robust PHP 8.1+ class designed to facilitate the mapping of various user input datasets (CSV, JSON, Delimited TXT files, and PHP Arrays) to a predefined, client-specific output format. This library is ideal for scenarios where you need to standardize incoming data from diverse sources into a consistent structure for further processing or storage.
Features
•	Flexible Data Loading: Seamlessly load data from .csv files, .json files (or direct JSON strings), delimited .txt files, and native PHP arrays (e.g., from database queries).
•	Customizable Mapping Schema: Define a simple associative array to specify how source field names should be mapped to your desired target field names.
•	Robust Error Handling: Includes comprehensive error handling for common issues such as file not found, unreadable files, invalid JSON syntax, and malformed delimited text.
•	PHP 8.1+ Compatibility: Built with modern PHP features, ensuring compatibility and leveraging improvements in PHP 8.1 and later versions.
•	Missing Field Graceful Handling: If a source field specified in the mapping schema is not found in an input record, the corresponding target field will be set to null (with a logged warning), preventing application crashes.
Installation
This library is available via Composer.
1.	Ensure Composer is installed: If you don't have Composer, download and install it from getcomposer.org.
2.	Navigate to your project directory in your terminal.
3.	Require the package using Composer:
composer require waghbhaskar/data-canvas
Setup and Basic Usage
After installing, ensure you include Composer's autoloader and use the DataSetMapper class in your PHP scripts.
<?php

// Include Composer's autoloader at the very beginning of your script
require_once __DIR__ . '/vendor/autoload.php';

// Import the DataSetMapper class using its namespace
use DataCanvas\DataSetMapper;

// --- Step 1: Define Your Mapping Schema ---
// This array defines how fields from your input data (values)
// will be mapped to your desired output fields (keys).
$mappingSchema = [
    'client_user_id'        => 'id',
    'client_full_name'      => 'name',
    'client_email_address'  => 'email',
    'client_account_status' => 'status',
    // Add more mappings as needed
];

// --- Step 2: Instantiate the DataSetMapper ---
$mapper = new DataSetMapper($mappingSchema);

// --- Step 3: Load Data and Map It ---
// Example: Loading from a CSV file
try {
    // Create a dummy CSV file for demonstration
    file_put_contents('sample_users.csv', "id,name,email,status\n1,John Doe,john@example.com,active\n2,Jane Smith,jane@example.com,inactive\n");

    $mapper->loadData('csv', 'sample_users.csv');
    $mappedData = $mapper->mapData();

    echo "<h3>Mapped CSV Data:</h3>";
    echo "<pre>";
    print_r($mappedData);
    echo "</pre>";

    // Clean up dummy file
    unlink('sample_users.csv');

} catch (Exception $e) {
    echo "Error processing CSV: " . $e->getMessage() . "\n";
}

?>


Advanced Usage Examples
Here are more detailed examples demonstrating loading data from different sources:
1. Loading from a JSON File or String
<?php
require_once __DIR__ . '/vendor/autoload.php';
use DataCanvas\DataSetMapper;

$jsonMapping = [
    'item_identifier' => 'product_id',
    'item_description' => 'product_name',
    'item_price_usd' => 'price'
];
$jsonMapper = new DataSetMapper($jsonMapping);

// --- From a JSON file ---
try {
    file_put_contents('sample_products.json', '[{"product_id": "P001", "product_name": "Laptop", "price": 1200.00}, {"product_id": "P002", "product_name": "Mouse", "price": 25.00}]');
    $jsonMapper->loadData('json', 'sample_products.json');
    $mappedJsonData = $jsonMapper->mapData();

    echo "<h3>Mapped JSON File Data:</h3>";
    echo "<pre>";
    print_r($mappedJsonData);
    echo "</pre>";
    unlink('sample_products.json');
} catch (Exception $e) {
    echo "Error processing JSON file: " . $e->getMessage() . "\n";
}

// --- From a JSON string directly ---
$jsonString = '[{"product_id": "P003", "product_name": "Keyboard", "price": 50.00}]';
try {
    $jsonMapper->loadData('json', $jsonString);
    $mappedJsonStringData = $jsonMapper->mapData();

    echo "<h3>Mapped JSON String Data:</h3>";
    echo "<pre>";
    print_r($mappedJsonStringData);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error processing JSON string: " . $e->getMessage() . "\n";
}
?>


2. Loading from a Delimited TXT File
<?php
require_once __DIR__ . '/vendor/autoload.php';
use DataCanvas\DataSetMapper;

$txtMapping = [
    'order_reference' => 'order_id',
    'customer_full_name' => 'customer_name',
    'total_amount_paid' => 'total_amount'
];
$txtMapper = new DataSetMapper($txtMapping);

// Create a dummy pipe-delimited TXT file
file_put_contents('sample_orders.txt', "order_id|customer_name|total_amount\nORD001|Alice Wonderland|150.75\nORD002|Bob The Builder|99.50\n");

try {
    $txtMapper->loadData('txt', 'sample_orders.txt', '|'); // Specify pipe as delimiter
    $mappedTxtData = $txtMapper->mapData();

    echo "<h3>Mapped TXT Data:</h3>";
    echo "<pre>";
    print_r($mappedTxtData);
    echo "</pre>";
    unlink('sample_orders.txt');
} catch (Exception $e) {
    echo "Error processing TXT file: " . $e->getMessage() . "\n";
}
?>


3. Loading from a PHP Array (e.g., Database Results)
<?php
require_once __DIR__ . '/vendor/autoload.php';
use DataCanvas\DataSetMapper;

$dbData = [
    ['db_user_id' => 101, 'db_username' => 'alice', 'db_email' => 'alice@domain.com', 'db_status' => 'active'],
    ['db_user_id' => 102, 'db_username' => 'bob', 'db_email' => 'bob@domain.com', 'db_status' => 'suspended']
];
$arrayMapping = [
    'client_id' => 'db_user_id',
    'client_login_name' => 'db_username',
    'client_contact_email' => 'db_email',
    'client_account_state' => 'db_status'
];
$arrayMapper = new DataSetMapper($arrayMapping);

try {
    $arrayMapper->loadData('array', $dbData);
    $mappedArrayData = $arrayMapper->mapData();

    echo "<h3>Mapped Array Data:</h3>";
    echo "<pre>";
    print_r($mappedArrayData);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error processing Array data: " . $e->getMessage() . "\n";
}
?>


Running the Web-Based Example (index.php and output.php)
The provided index.php and output.php files demonstrate a practical web-based application of the DataSetMapper class, allowing users to upload files and configure mappings via a form.
1.	Web Server Setup: Ensure you have a web server (like Apache, Nginx, or PHP's built-in server) configured to serve PHP files.
2.	File Placement: Place index.php, output.php, your src/ directory (containing DataSetMapper.php), and the vendor/ directory (generated by Composer) in your web server's document root or a designated virtual host directory.
3.	Access in Browser: Open your web browser and navigate to the URL of index.php (e.g., http://localhost/your_project_folder/index.php).
4.	Interact:
o	Select a data file (CSV, JSON, or TXT).
o	Choose the corresponding "Data Type".
o	Specify the "Delimiter" if applicable (e.g., , for CSV, | for pipe-delimited TXT).
o	Adjust the "Mapping Schema" in the JSON text area to match your file's source fields and desired output fields.
o	Click "Process Data" to see the original and mapped output.
Contributing
Contributions are welcome! Please feel free to open issues or submit pull requests on the GitHub repository.
License
This project is open-sourced under the MIT License.
