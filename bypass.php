<?php
// Initialize cURL for the GET request to fetch CSRF token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://unshorten.it/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt'); // Save cookies to a file
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt'); // Read cookies from the file

// Execute GET request to fetch CSRF token
$getResponse = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

// Extract CSRF token from the response if necessary
preg_match('/name="csrfmiddlewaretoken" value="([^"]+)"/', $getResponse, $matches);
$csrfToken = $matches[1] ?? ''; // Get the CSRF token

if (empty($csrfToken)) {
    echo 'Failed to extract CSRF token.';
    curl_close($ch);
    exit;
}

// Get the short URL from user input
echo "Enter the URL: ";
$shortUrl = trim(fgets(STDIN));

// Initialize cURL for the POST request
curl_setopt($ch, CURLOPT_URL, 'https://unshorten.it/main/get_long_url');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: */*',
    'accept-language: en-US,en;q=0.9',
    'content-type: application/x-www-form-urlencoded; charset=UTF-8',
    'origin: https://unshorten.it',
    'referer: https://unshorten.it/',
    'sec-ch-ua: "Not)A;Brand";v="99", "Microsoft Edge";v="127", "Chromium";v="127"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-origin',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 Edg/127.0.0.0',
    'x-requested-with: XMLHttpRequest',
]);

// Set the POST fields with the user-provided short URL and the CSRF token
$postFields = http_build_query([
    'short-url' => $shortUrl,
    'csrfmiddlewaretoken' => $csrfToken
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

// Execute POST request
$response = curl_exec($ch);

// Close the cURL session
curl_close($ch);

// Decode the JSON response
$responseData = json_decode($response, true);

// Check if decoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Failed to decode JSON response.';
    exit;
}

if ($responseData['success']) {
    // Mengambil URL
    $longUrl = $responseData['long_url'];

    // Memeriksa apakah 'product' ada dalam URL
    if (strpos($longUrl, 'product') !== false) {
        // Mengambil bagian dari URL sebelum tanda '?'
        $result = strstr($longUrl, '?', true);
        if ($result === false) {
            // Jika tidak ada tanda '?', maka gunakan URL aslinya
            $result = $longUrl;
        }
        echo "\033[32m" . $result . "\033[0m\n";
    } else {
        // Jika 'product' tidak ditemukan dalam URL
        echo "\033[32m" . $longUrl . "\033[0m\n";
        echo "\n";
    }
} else {
    echo $responseData['message'];
    echo "\n";
}
