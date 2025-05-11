<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database Issues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            padding: 20px;
        }
        .result-container {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Database Fix Utility</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Fix Database Issues</h5>
            </div>
            <div class="card-body">
                <p>This utility will check and fix common issues with the narrative database:</p>
                <ul>
                    <li>Check if the narrative_entries table exists</li>
                    <li>Create the table if it doesn't exist</li>
                    <li>Add any missing columns</li>
                    <li>Test database insertion</li>
                </ul>
                <button id="fixDatabaseBtn" class="btn btn-primary">Run Database Fix</button>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Check Database Table</h5>
            </div>
            <div class="card-body">
                <p>Check if the narrative_entries table exists and its structure:</p>
                <button id="checkTableBtn" class="btn btn-info">Check Table Structure</button>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Test Form Submission</h5>
            </div>
            <div class="card-body">
                <p>Test if form data can be properly saved to the database:</p>
                <button id="testSubmissionBtn" class="btn btn-success">Test Form Submission</button>
            </div>
        </div>
        
        <div id="resultContainer" class="result-container d-none">
            <h4>Results:</h4>
            <div id="resultContent"></div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            // Fix database button
            $('#fixDatabaseBtn').click(function() {
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Running...');
                
                $.ajax({
                    url: 'fix_narrative_handler.php',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        $('#fixDatabaseBtn').prop('disabled', false).text('Run Database Fix');
                        
                        showResult(response);
                    },
                    error: function(xhr, status, error) {
                        $('#fixDatabaseBtn').prop('disabled', false).text('Run Database Fix');
                        
                        showResult({
                            success: false,
                            message: 'Error: ' + error,
                            response: xhr.responseText
                        });
                    }
                });
            });
            
            // Check table button
            $('#checkTableBtn').click(function() {
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Checking...');
                
                $.ajax({
                    url: '../check_table.php',
                    type: 'GET',
                    dataType: 'html',
                    success: function(response) {
                        $('#checkTableBtn').prop('disabled', false).text('Check Table Structure');
                        
                        $('#resultContainer').removeClass('d-none');
                        $('#resultContent').html(response);
                    },
                    error: function(xhr, status, error) {
                        $('#checkTableBtn').prop('disabled', false).text('Check Table Structure');
                        
                        showResult({
                            success: false,
                            message: 'Error: ' + error,
                            response: xhr.responseText
                        });
                    }
                });
            });
            
            // Test submission button
            $('#testSubmissionBtn').click(function() {
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing...');
                
                $.ajax({
                    url: '../test_narrative_submission.php',
                    type: 'GET',
                    dataType: 'html',
                    success: function(response) {
                        $('#testSubmissionBtn').prop('disabled', false).text('Test Form Submission');
                        
                        $('#resultContainer').removeClass('d-none');
                        $('#resultContent').html(response);
                    },
                    error: function(xhr, status, error) {
                        $('#testSubmissionBtn').prop('disabled', false).text('Test Form Submission');
                        
                        showResult({
                            success: false,
                            message: 'Error: ' + error,
                            response: xhr.responseText
                        });
                    }
                });
            });
            
            // Function to display results
            function showResult(data) {
                $('#resultContainer').removeClass('d-none');
                
                let html = '';
                
                if (data.success) {
                    html += '<div class="alert alert-success">Operation completed successfully!</div>';
                    
                    if (data.results) {
                        html += '<h5>Details:</h5>';
                        html += '<pre>' + JSON.stringify(data.results, null, 2) + '</pre>';
                    }
                } else {
                    html += '<div class="alert alert-danger">Error: ' + data.message + '</div>';
                    
                    if (data.response) {
                        html += '<h5>Server Response:</h5>';
                        html += '<pre>' + data.response + '</pre>';
                    }
                }
                
                $('#resultContent').html(html);
            }
        });
    </script>
</body>
</html> 