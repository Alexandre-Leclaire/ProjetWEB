<?php

function get_league_id(): int
{
    return 4334;
}

function get_API($endpoint): mixed {
    $url = "https://www.thesportsdb.com/api/v1/json/123/$endpoint";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    try {
        return json_decode(curl_exec($curl), true);
    } catch (Exception $e) {
        echo "Error of json: " . $e->getMessage();
        return null;
    }
}