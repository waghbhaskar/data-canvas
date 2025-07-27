<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();
$defaultMappingSchema = json_encode([
    'client_id' => 'id',
    'client_name' => 'name',
    'client_email' => 'email',
    'client_status' => 'status'
], JSON_PRETTY_PRINT);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mapper Input</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Upload Data for Mapping</h2>

        <form action="output.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label for="dataFile" class="block text-sm font-medium text-gray-700 mb-1">Select Data File:</label>
                <input type="file" id="dataFile" name="dataFile" required
                       class="mt-1 block w-full text-sm text-gray-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-full file:border-0
                              file:text-sm file:font-semibold
                              file:bg-violet-50 file:text-violet-700
                              hover:file:bg-violet-100 rounded-md border border-gray-300 p-2">
            </div>

            <div>
                <label for="sourceType" class="block text-sm font-medium text-gray-700 mb-1">Data Type:</label>
                <select id="sourceType" name="sourceType" required
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                    <option value="csv">CSV (.csv)</option>
                    <option value="json">JSON (.json)</option>
                    <option value="txt">Delimited TXT (.txt)</option>
                </select>
            </div>

            <div>
                <label for="delimiter" class="block text-sm font-medium text-gray-700 mb-1">Delimiter (for CSV/TXT):</label>
                <input type="text" id="delimiter" name="delimiter" value=","
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <p class="mt-1 text-xs text-gray-500">e.g., `,` for CSV, `|` for pipe-delimited TXT.</p>
            </div>

            <div>
                <label for="mappingSchema" class="block text-sm font-medium text-gray-700 mb-1">Mapping Schema (JSON):</label>
                <textarea id="mappingSchema" name="mappingSchema" rows="8" required
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm resize-y"
                          placeholder='{"target_field_name": "source_field_name"}'><?php echo htmlspecialchars($defaultMappingSchema); ?></textarea>
                <p class="mt-1 text-xs text-gray-500">Define how source fields map to target fields. Example: `{"client_id": "user_id", "client_name": "full_name"}`</p>
            </div>

            <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                Process Data
            </button>
        </form>
    </div>
</body>
</html>
