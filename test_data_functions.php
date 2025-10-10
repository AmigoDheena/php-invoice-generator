<?php
require_once 'includes/functions.php';

// Test the data management functions
echo "<h1>Testing Data Management Functions</h1>";

// Test getDataStorageStats
echo "<h2>Testing getDataStorageStats()</h2>";
$stats = getDataStorageStats();
echo "<pre>";
print_r($stats);
echo "</pre>";

// Test createBackupArchive
echo "<h2>Testing createBackupArchive()</h2>";
$backupFile = createBackupArchive();
echo "Backup file created: " . ($backupFile ? $backupFile : "Failed");
echo "<br>";

// Test generateMySQLSchema
echo "<h2>Testing generateMySQLSchema()</h2>";
$schemaFile = generateMySQLSchema();
echo "MySQL schema file created: " . ($schemaFile ? $schemaFile : "Failed");
echo "<br>";

// Test exportAllData
echo "<h2>Testing exportAllData()</h2>";
$exportFile = exportAllData();
echo "Export file created: " . ($exportFile ? $exportFile : "Failed");
echo "<br>";

// Don't test importAllData or migrateToMySQL as they modify data

// Test formatFileSize
echo "<h2>Testing formatFileSize()</h2>";
echo "1024 bytes = " . formatFileSize(1024) . "<br>";
echo "1048576 bytes = " . formatFileSize(1048576) . "<br>";
echo "10485760 bytes = " . formatFileSize(10485760) . "<br>";
?>