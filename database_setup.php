<?php
// database_setup.php - Database setup script
// Run this script to set up the complete database with tables and sample data

require_once __DIR__ . '/src/config/db.php';

echo "=== Informatics A Database Setup ===\n\n";

try {
    // Read and execute schema file
    echo "Creating database tables...\n";
    $schema = file_get_contents(__DIR__ . '/database_schema.sql');

    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
        }
    }

    echo "✓ Database tables created successfully!\n\n";

    // Read and execute sample data file
    echo "Inserting sample data...\n";
    $data = file_get_contents(__DIR__ . '/sample_data.sql');

    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $data)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
        }
    }

    echo "✓ Sample data inserted successfully!\n\n";

    // Verify setup
    echo "Verifying setup...\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "✓ Users table: {$userCount} records\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts WHERE status = 'approved'");
    $postCount = $stmt->fetch()['count'];
    echo "✓ Approved posts: {$postCount} records\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM post_images");
    $imageCount = $stmt->fetch()['count'];
    echo "✓ Post images: {$imageCount} records\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM comments");
    $commentCount = $stmt->fetch()['count'];
    echo "✓ Comments: {$commentCount} records\n";

    echo "\n=== Setup Complete! ===\n";
    echo "You can now access your application.\n";
    echo "Default admin login:\n";
    echo "Username: admin\n";
    echo "Password: password\n";
    echo "\nDefault user logins are also available for testing.\n";

} catch (Exception $e) {
    echo "❌ Error during setup: " . $e->getMessage() . "\n";
    echo "Make sure your database connection is properly configured in src/config/db.php\n";
}
?>
