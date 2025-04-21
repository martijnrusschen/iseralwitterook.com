<?php
// Basic PHP Proxy for RSS Feeds

// Get the target URL from the query string (?url=...)
$feedUrl = isset($_GET['url']) ? $_GET['url'] : null;

// Basic validation: ensure it's a plausible URL (you might want stricter validation)
if (!$feedUrl || !filter_var($feedUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $feedUrl)) {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: Invalid or missing feed URL parameter.";
    exit;
}

// Set headers to indicate XML content type
header("Content-Type: application/xml; charset=utf-8");
// Add security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
// Removing overly restrictive CSP that was blocking the fetch
// header("Content-Security-Policy: default-src 'self'");
// IMPORTANT: You might need to configure CORS headers here if you host
// your HTML/JS separately from this PHP script, but if they are on the
// same domain (e.g., iseralwitterook.com/index.html and iseralwitterook.com/fetch_feed.php)
// you usually won't need this for same-origin requests.
// Allow CORS for the specific domain if needed
// Uncomment and adjust if needed in production
// header("Access-Control-Allow-Origin: https://iseralwitterook.com");

// Use cURL to fetch the feed content (often more reliable than file_get_contents for external URLs)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $feedUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
// Follow redirects if any
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// Set a user agent - some feeds might require one
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; WhiteSmokeFetcher/2.0)');
// Set a timeout
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 seconds timeout
// Enforce HTTPS
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$output = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($output === false || $httpcode >= 400) {
    // Log the error server-side if possible
    error_log("Proxy Error: Failed to fetch $feedUrl. HTTP Code: $httpcode. cURL Error: $curlError");
    // Send an appropriate HTTP error status back to the client
    header("HTTP/1.1 502 Bad Gateway"); // Indicate upstream failure
    echo "Error: Could not retrieve feed from the source.";
    exit;
}

// Output the fetched feed content
echo $output;

?>