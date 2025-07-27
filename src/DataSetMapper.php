<?php

declare(strict_types=1);
 namespace DataCanvas;
/**
 * Class DataSetMapper
 *
 * This class provides functionality to load data from various sources (CSV, JSON, TXT, Array)
 * and map it to a predefined schema based on client requirements.
 * It is designed for PHP 8.1 and above, leveraging features like strict types and constructor property promotion.
 */
class DataSetMapper
{
    /**
     * @var array $inputData Stores the raw data loaded from the source.
     */
    private array $inputData = [];

    /**
     * @var array $mappingSchema Defines how source fields map to target fields.
     * Format: ['target_field_name' => 'source_field_name']
     */
    private array $mappingSchema;

    /**
     * @var array $mappedData Stores the data after applying the mapping schema.
     */
    private array $mappedData = [];

    /**
     * Constructor for DataSetMapper.
     *
     * @param array $mappingSchema The schema defining the mapping from source to target fields.
     * Example: ['client_id' => 'user_id', 'client_name' => 'full_name']
     */
    public function __construct(array $mappingSchema)
    {
        $this->mappingSchema = $mappingSchema;
    }

    /**
     * Loads data into the mapper from a specified source type.
     *
     * @param string $sourceType The type of the input source (e.g., 'csv', 'json', 'txt', 'array').
     * @param string|array $source The path to the file (for 'csv', 'json', 'txt') or the actual array data (for 'array').
     * @param string $delimiter The delimiter character for CSV and TXT files (default: ',').
     * @throws InvalidArgumentException If an unsupported source type is provided or array data is malformed.
     * @throws RuntimeException If there are issues reading files or parsing data.
     */
    public function loadData(string $sourceType, string|array $source, string $delimiter = ','): void
    {
        // Clear previous input data before loading new data
        $this->inputData = [];
        $this->mappedData = []; // Also clear mapped data as input has changed

        switch (strtolower($sourceType)) {
            case 'csv':
                if (!is_string($source)) {
                    throw new InvalidArgumentException("CSV source must be a file path string.");
                }
                $this->loadFromCsv($source, $delimiter);
                break;
            case 'json':
                if (!is_string($source)) {
                    throw new InvalidArgumentException("JSON source must be a file path string or JSON string.");
                }
                $this->loadFromJson($source);
                break;
            case 'txt':
                if (!is_string($source)) {
                    throw new InvalidArgumentException("TXT source must be a file path string.");
                }
                $this->loadFromTxt($source, $delimiter);
                break;
            case 'array':
                if (!is_array($source)) {
                    throw new InvalidArgumentException("Array source must be an array.");
                }
                $this->loadFromArray($source);
                break;
            default:
                throw new InvalidArgumentException("Unsupported source type: {$sourceType}. Supported types: 'csv', 'json', 'txt', 'array'.");
        }
    }

    /**
     * Loads data from a CSV file.
     * Assumes the first row is the header.
     *
     * @param string $filePath The path to the CSV file.
     * @param string $delimiter The delimiter used in the CSV file.
     * @throws RuntimeException If the file cannot be found or read.
     */
    private function loadFromCsv(string $filePath, string $delimiter): void
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("CSV file not found: {$filePath}");
        }

        // Open the CSV file for reading
        if (($handle = fopen($filePath, 'r')) === false) {
            throw new RuntimeException("Could not open CSV file: {$filePath}");
        }

        $header = fgetcsv($handle, 0, $delimiter); // Read the header row
        if ($header === false) {
            fclose($handle);
            throw new RuntimeException("Could not read CSV header from: {$filePath}. File might be empty or malformed.");
        }

        $data = [];
        // Read each row and combine with the header to create associative arrays
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($header) === count($row)) {
                $data[] = array_combine($header, $row);
            } else {
                // Log a warning for malformed rows instead of throwing an error to allow partial processing
                error_log("Warning: Skipping malformed CSV row (column count mismatch) in {$filePath}: " . implode($delimiter, $row));
            }
        }
        fclose($handle); // Close the file handle

        $this->inputData = $data;
    }

    /**
     * Loads data from a JSON source (file path or direct JSON string).
     *
     * @param string $source The path to the JSON file or the JSON string itself.
     * @throws RuntimeException If the JSON is invalid or cannot be read.
     */
    private function loadFromJson(string $source): void
    {
        $jsonString = '';
        if (file_exists($source)) {
            $jsonString = file_get_contents($source);
            if ($jsonString === false) {
                throw new RuntimeException("Could not read JSON file: {$source}");
            }
        } else {
            // Assume $source is a JSON string if it's not a file path
            $jsonString = $source;
        }

        $jsonData = json_decode($jsonString, true);

        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON data: " . json_last_error_msg());
        }

        // Ensure the decoded JSON is an array (of objects/records)
        if (!is_array($jsonData)) {
            throw new RuntimeException("JSON data is not a valid array of objects/records.");
        }

        // If it's an associative array (single object), wrap it in an array for consistency
        if (!array_is_list($jsonData) && !empty($jsonData)) { // PHP 8.1 array_is_list
            $jsonData = [$jsonData];
        }

        $this->inputData = $jsonData;
    }

    /**
     * Loads data from a delimited text file.
     * Assumes the first line is the header.
     *
     * @param string $filePath The path to the text file.
     * @param string $delimiter The delimiter used in the text file.
     * @throws RuntimeException If the file cannot be found or read.
     */
    private function loadFromTxt(string $filePath, string $delimiter): void
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("Text file not found: {$filePath}");
        }

        // Read all lines into an array, ignoring empty lines
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Could not read text file: {$filePath}");
        }

        if (empty($lines)) {
            $this->inputData = [];
            return;
        }

        // Use the first line as the header
        $header = str_getcsv($lines[0], $delimiter);
        $data = [];
        // Process subsequent lines as data rows
        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i], $delimiter);
            if (count($header) === count($row)) {
                $data[] = array_combine($header, $row);
            } else {
                error_log("Warning: Skipping malformed TXT row (column count mismatch) in {$filePath}: " . $lines[$i]);
            }
        }
        $this->inputData = $data;
    }

    /**
     * Loads data directly from a PHP array.
     * The array is expected to be an array of associative arrays (records).
     *
     * @param array $data The input array data.
     * @throws InvalidArgumentException If the array data is not an array of associative arrays.
     */
    private function loadFromArray(array $data): void
    {
        if (empty($data)) {
            $this->inputData = [];
            return;
        }

        // Validate that each element in the array is an associative array (record)
        foreach ($data as $record) {
            if (!is_array($record) || array_is_list($record)) { // PHP 8.1 array_is_list check
                throw new InvalidArgumentException("Array data must be an array of associative arrays (records). Each record must be an associative array.");
            }
        }
        $this->inputData = $data;
    }

    /**
     * Performs the data mapping based on the configured mapping schema.
     * Iterates through each input record and maps fields according to the schema.
     *
     * @return array The array of mapped records.
     */
    public function mapData(): array
    {
        $this->mappedData = []; // Clear previous mapped data

        if (empty($this->inputData)) {
            // No input data to map
            return [];
        }

        foreach ($this->inputData as $record) {
            $mappedRecord = [];
            foreach ($this->mappingSchema as $targetField => $sourceField) {
                // Check if the source field exists in the current input record
                if (array_key_exists($sourceField, $record)) {
                    $mappedRecord[$targetField] = $record[$sourceField];
                } else {
                    // If the source field is missing, set the target field to null.
                    // This behavior can be customized (e.g., throw an error for required fields).
                    $mappedRecord[$targetField] = null;
                    error_log("Warning: Source field '{$sourceField}' not found in input record. Setting target field '{$targetField}' to null.");
                }
            }
            $this->mappedData[] = $mappedRecord;
        }
        return $this->mappedData;
    }

    /**
     * Returns the currently mapped data.
     *
     * @return array The mapped data.
     */
    public function getMappedData(): array
    {
        return $this->mappedData;
    }

    /**
     * Returns the raw input data that was loaded.
     *
     * @return array The raw input data.
     */
    public function getInputData(): array
    {
        return $this->inputData;
    }
}

// --- Example Usage ---

// Create dummy data files for demonstration
file_put_contents('users.csv', "id,name,email,status\n1,John Doe,john@example.com,active\n2,Jane Smith,jane@example.com,inactive\n");
file_put_contents('products.json', '[{"product_id": "P001", "product_name": "Laptop", "price": 1200.00}, {"product_id": "P002", "product_name": "Mouse", "price": 25.00}]');
file_put_contents('orders.txt', "order_id|customer_name|total_amount\nORD001|Alice Wonderland|150.75\nORD002|Bob The Builder|99.50\n");

echo "<h3>PHP DataSetMapper Example (PHP 8.1+)</h3>";
echo "<pre>"; // Use pre tags for better formatting of var_dump output

try {
    // --- Example 1: Mapping CSV Data ---
    echo "<h4>Example 1: Mapping CSV Data</h4>";
    $csvMapping = [
        'client_user_id' => 'id',
        'client_full_name' => 'name',
        'client_email_address' => 'email',
        'client_account_status' => 'status'
    ];
    $csvMapper = new DataSetMapper($csvMapping);
    $csvMapper->loadData('csv', 'users.csv');
    $mappedCsvData = $csvMapper->mapData();

    echo "Original CSV Data:\n";
    print_r($csvMapper->getInputData());
    echo "\nMapped CSV Data:\n";
    print_r($mappedCsvData);

    // --- Example 2: Mapping JSON Data ---
    echo "<h4>Example 2: Mapping JSON Data</h4>";
    $jsonMapping = [
        'item_identifier' => 'product_id',
        'item_description' => 'product_name',
        'item_price_usd' => 'price'
    ];
    $jsonMapper = new DataSetMapper($jsonMapping);
    $jsonMapper->loadData('json', 'products.json');
    $mappedJsonData = $jsonMapper->mapData();

    echo "Original JSON Data:\n";
    print_r($jsonMapper->getInputData());
    echo "\nMapped JSON Data:\n";
    print_r($mappedJsonData);

    // --- Example 3: Mapping TXT (Pipe-delimited) Data ---
    echo "<h4>Example 3: Mapping TXT (Pipe-delimited) Data</h4>";
    $txtMapping = [
        'order_ref' => 'order_id',
        'customer_name' => 'customer_name',
        'order_total' => 'total_amount'
    ];
    $txtMapper = new DataSetMapper($txtMapping);
    $txtMapper->loadData('txt', 'orders.txt', '|'); // Specify pipe as delimiter
    $mappedTxtData = $txtMapper->mapData();

    echo "Original TXT Data:\n";
    print_r($txtMapper->getInputData());
    echo "\nMapped TXT Data:\n";
    print_r($mappedTxtData);

    // --- Example 4: Mapping Array Data (e.g., from database) ---
    echo "<h4>Example 4: Mapping Array Data (from database/dynamic array)</h4>";
    $dbData = [
        ['db_user_id' => 101, 'db_username' => 'alice', 'db_email' => 'alice@domain.com', 'db_status' => 'active'],
        ['db_user_id' => 102, 'db_username' => 'bob', 'db_email' => 'bob@domain.com', 'db_status' => 'suspended']
    ];
    $arrayMapping = [
        'client_id' => 'db_user_id',
        'client_login' => 'db_username',
        'client_contact_email' => 'db_email',
        'client_account_state' => 'db_status'
    ];
    $arrayMapper = new DataSetMapper($arrayMapping);
    $arrayMapper->loadData('array', $dbData);
    $mappedArrayData = $arrayMapper->mapData();

    echo "Original Array Data:\n";
    print_r($arrayMapper->getInputData());
    echo "\nMapped Array Data:\n";
    print_r($mappedArrayData);

    // --- Example 5: Handling Missing Source Fields ---
    echo "<h4>Example 5: Handling Missing Source Fields</h4>";
    $missingFieldMapping = [
        'target_id' => 'id',
        'target_name' => 'name',
        'non_existent_field' => 'this_field_does_not_exist_in_source', // This will be null
        'target_email' => 'email'
    ];
    $missingFieldMapper = new DataSetMapper($missingFieldMapping);
    $missingFieldMapper->loadData('csv', 'users.csv');
    $mappedMissingFieldData = $missingFieldMapper->mapData();

    echo "Mapped Data with Missing Source Field (check error_log for warnings):\n";
    print_r($mappedMissingFieldData);

    // --- Example 6: Error Handling - Non-existent file ---
    echo "<h4>Example 6: Error Handling - Non-existent file</h4>";
    try {
        $errorMapper = new DataSetMapper($csvMapping);
        $errorMapper->loadData('csv', 'non_existent_file.csv');
        $errorMapper->mapData();
    } catch (RuntimeException $e) {
        echo "Caught expected error: " . $e->getMessage() . "\n";
    }

    // --- Example 7: Error Handling - Malformed JSON ---
    echo "<h4>Example 7: Error Handling - Malformed JSON</h4>";
    try {
        $errorMapper = new DataSetMapper($jsonMapping);
        $errorMapper->loadData('json', '{"product_id": "P003", "product_name": "Keyboard", "price": 50.00, "extra_comma":}'); // Malformed JSON
        $errorMapper->mapData();
    } catch (RuntimeException $e) {
        echo "Caught expected error: " . $e->getMessage() . "\n";
    }

    // --- Example 8: Error Handling - Malformed Array Data (not associative) ---
    echo "<h4>Example 8: Error Handling - Malformed Array Data (not associative)</h4>";
    try {
        $errorMapper = new DataSetMapper($arrayMapping);
        $errorMapper->loadData('array', [
            ['value1', 'value2'], // This is a list, not an associative array
            ['value3', 'value4']
        ]);
        $errorMapper->mapData();
    } catch (InvalidArgumentException $e) {
        echo "Caught expected error: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "An unexpected error occurred: " . $e->getMessage() . "\n";
} finally {
    echo "</pre>";
    // Clean up dummy files
    unlink('users.csv');
    unlink('products.json');
    unlink('orders.txt');
}

?>