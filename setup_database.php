<?php
/*
 ============================================
 DATABASE SETUP SCRIPT
 Run this script ONCE to create all tables,
 views, and stored procedures
 ============================================
*/

include('config/db.php');

$errors = [];
$success = [];

echo "============================================<br>";
echo "SKILLSWAP DATABASE SETUP<br>";
echo "============================================<br><br>";

/*
 STEP 1: Read and execute schema SQL
*/
echo "Step 1: Creating tables...<br>";

$schema_sql = file_get_contents('database_schema.sql');
$queries = array_filter(array_map('trim', explode(';', $schema_sql)));

foreach($queries as $query) {
    if(empty($query)) continue;
    
    if(!mysqli_multi_query($conn, $query . ';')) {
        $errors[] = "Schema error: " . mysqli_error($conn);
    } else {
        // Clear all results
        while(mysqli_next_result($conn)) {
            if($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        }
        $success[] = "✓ " . substr($query, 0, 50);
    }
}

echo "Schema queries executed.<br><br>";

/*
 STEP 2: Read and execute queries SQL (views and procedures)
*/
echo "Step 2: Creating views and stored procedures...<br>";

$queries_sql = file_get_contents('database_queries.sql');
$procedures = array_filter(array_map('trim', explode('$$', $queries_sql)));

foreach($procedures as $proc) {
    if(strlen($proc) < 50) continue; // Skip empty parts
    
    // Split by DELIMITER
    if(strpos($proc, 'DELIMITER') !== false) {
        $parts = explode('DELIMITER', $proc);
        foreach($parts as $part) {
            $part = trim($part);
            if(empty($part) || $part == '$$' || $part == ';') continue;
            
            if(!mysqli_query($conn, $part)) {
                // Check if it's already created
                if(strpos(mysqli_error($conn), 'already exists') === false) {
                    $errors[] = "Procedure error: " . mysqli_error($conn) . " (Query: " . substr($part, 0, 30) . "...)";
                }
            } else {
                $success[] = "✓ " . substr($part, 0, 50);
            }
        }
    } else {
        // Regular query (CREATE VIEW)
        if(!mysqli_query($conn, $proc)) {
            if(strpos(mysqli_error($conn), 'already exists') === false) {
                $errors[] = "Query error: " . mysqli_error($conn);
            }
        } else {
            $success[] = "✓ " . substr($proc, 0, 50);
        }
    }
}

echo "Procedures and views created.<br><br>";

/*
 STEP 3: Verify tables were created
*/
echo "Step 3: Verifying tables...<br><br>";

$tables = [
    'users',
    'skill_categories',
    'skills',
    'user_skills',
    'sessions',
    'session_schedules',
    'reviews',
    'messages'
];

$created_tables = [];
foreach($tables as $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if(mysqli_num_rows($check) > 0) {
        $created_tables[] = $table;
        echo "✓ $table<br>";
    } else {
        echo "✗ $table (NOT CREATED)<br>";
        $errors[] = "Table $table was not created";
    }
}

echo "<br>";

/*
 STEP 4: Insert sample categories
*/
echo "Step 4: Inserting sample data...<br>";

$categories = [
    ['Languages', 'Learn foreign languages'],
    ['Technology', 'Programming and tech skills'],
    ['Arts', 'Drawing, painting, music'],
    ['Business', 'Professional and business skills'],
    ['Sports', 'Physical activities and sports'],
    ['Academic', 'Academic subjects and tutoring']
];

foreach($categories as $cat) {
    $name = mysqli_real_escape_string($conn, $cat[0]);
    $desc = mysqli_real_escape_string($conn, $cat[1]);
    
    $check = mysqli_query($conn, "SELECT * FROM skill_categories WHERE category_name = '$name'");
    if(mysqli_num_rows($check) == 0) {
        if(mysqli_query($conn, "INSERT INTO skill_categories (category_name, description) VALUES ('$name', '$desc')")) {
            echo "✓ Category: $name<br>";
        }
    }
}

echo "<br>";

/*
 STEP 5: Show summary
*/
echo "============================================<br>";
echo "SETUP SUMMARY<br>";
echo "============================================<br>";

echo "✓ Tables created: " . count($created_tables) . " / " . count($tables) . "<br>";
echo "✓ Success operations: " . count($success) . "<br>";

if(count($errors) > 0) {
    echo "⚠ Errors: " . count($errors) . "<br><br>";
    echo "<strong>Error Details:</strong><br>";
    foreach($errors as $error) {
        echo "- $error<br>";
    }
} else {
    echo "✓ No errors!<br>";
}

echo "<br>";
echo "<strong style='color: green;'>✓ Database setup complete!</strong><br>";
echo "Next step: Update your application code to use the new tables and functions.<br>";

mysqli_close($conn);
?>
