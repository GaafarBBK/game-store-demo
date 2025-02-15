<?php


// To generate a redeem code for a purchase, I used this base62 encoding function to encode the purchase ID.
function base62_encode(int $number): string {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $base = strlen($chars);
    $encoded = '';
    while ($number > 0) {
        $remainder = $number % $base;
        $encoded = $chars[$remainder] . $encoded;
        $number = (int)($number / $base);
    }
    return $encoded;
}

// To decode the redeem code for a purchase, I used this base62 decoding function to decode the purchase ID.   
function base62_decode(string $encoded): int {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $base = strlen($chars);
    $decoded = 0;
    for ($i = 0; $i < strlen($encoded); $i++) {
        $decoded = $decoded * $base + strpos($chars, $encoded[$i]);
    }
    return $decoded;
}