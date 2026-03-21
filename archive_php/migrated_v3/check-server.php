<!DOCTYPE html>
<html>
<head>
    <title>Server Check</title>
</head>
<body>
    <h1>Server Check</h1>
    <p>If you can see this page with PHP info below, your server is working:</p>
    
    <h2>Current URL:</h2>
    <p><strong id="current-url"></strong></p>
    
    <h2>Test API Directly:</h2>
    <button onclick="testAPI()">Test API</button>
    <div id="api-result"></div>
    
    <h2>PHP Info:</h2>
    <?php
    echo "PHP is working! Version: " . phpversion();
    echo "<br>Current time: " . date('Y-m-d H:i:s');
    echo "<br>Server: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    ?>
    
    <script>
        document.getElementById('current-url').textContent = window.location.href;
        
        async function testAPI() {
            try {
                const response = await fetch('/api/v2/direct-test.php');
                const result = await response.json();
                document.getElementById('api-result').innerHTML = 
                    '<div style="background: #d4edda; padding: 10px; border-radius: 4px;">' +
                    '<strong>✅ API Working!</strong><br>' +
                    '<pre>' + JSON.stringify(result, null, 2) + '</pre>' +
                    '</div>';
            } catch (error) {
                document.getElementById('api-result').innerHTML = 
                    '<div style="background: #f8d7da; padding: 10px; border-radius: 4px;">' +
                    '<strong>❌ API Error:</strong> ' + error.message + '<br>' +
                    'Current URL: ' + window.location.href +
                    '</div>';
            }
        }
    </script>
</body>
</html>
