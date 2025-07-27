<?php
declare(strict_types=1);

// Include Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Use the namespaced DataSetMapper class
use DataCanvas\DataSetMapper;
function displayData(string $title, array $data): void {
    echo "<h4 class='text-lg font-semibold text-gray-700 mb-2'>{$title}</h4>";
    echo "<pre class='bg-gray-100 p-4 rounded-md overflow-auto text-sm text-gray-800'>";
    print_r($data);
    echo "</pre>";
}

$mappedData = [];
$inputData = [];
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['dataFile']) || $_FILES['dataFile']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "File upload failed. Error code: " . ($_FILES['dataFile']['error'] ?? 'N/A');
    } else {
        $sourceType = $_POST['sourceType'] ?? '';
        $delimiter = $_POST['delimiter'] ?? ',';
        $mappingSchemaJson = $_POST['mappingSchema'] ?? '{}';
        $mappingSchema = json_decode($mappingSchemaJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMessage = "Invalid JSON mapping schema provided: " . json_last_error_msg();
        } elseif (!is_array($mappingSchema)) {
            $errorMessage = "Mapping schema is not a valid JSON object/array.";
        } else {
            $uploadedFilePath = $_FILES['dataFile']['tmp_name'];

            try {
                $mapper = new DataSetMapper($mappingSchema);
                $mapper->loadData($sourceType, $uploadedFilePath, $delimiter);
                $inputData = $mapper->getInputData();
                $mappedData = $mapper->mapData();

            } catch (InvalidArgumentException $e) {
                $errorMessage = "Input Error: " . $e->getMessage();
            } catch (RuntimeException $e) {
                $errorMessage = "Processing Error: " . $e->getMessage();
            } catch (Exception $e) {
                $errorMessage = "An unexpected error occurred: " . $e->getMessage();
            }
        }
    }
} else {
    $errorMessage = "No data submitted. Please use the form on index.php.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapped Data Output</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="flex flex-col items-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-2xl mt-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Data Mapping Result</h2>

        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
        <?php else: ?>
            <?php displayData("Original Input Data", $inputData); ?>
            <div class="my-6 border-t border-gray-200 pt-6"></div>
            <?php displayData("Mapped Output Data", $mappedData); ?>
        <?php endif; ?>

        <div class="mt-8 text-center">
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                &larr; Go Back
            </a>
        </div>
    </div>
</body>
</html>