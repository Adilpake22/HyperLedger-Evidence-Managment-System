<?php

/**
 * SHA-256 Hash Generator
 * Generates hashes automatically for strings, files, and with salt/HMAC support
 */

// ─── 1. Basic string hash ───────────────────────────────────────────────────
function generateHash(string $input): string {
    return hash('sha256', $input);
}

// ─── 2. Salted hash (prevents rainbow table attacks) ────────────────────────
function generateSaltedHash(string $input, string $salt = ''): array {
    if ($salt === '') {
        $salt = bin2hex(random_bytes(16)); // auto-generate 32-char hex salt
    }
    $hash = hash('sha256', $salt . $input);
    return ['hash' => $hash, 'salt' => $salt];
}

function verifySaltedHash(string $input, string $hash, string $salt): bool {
    $computed = hash('sha256', $salt . $input);
    return hash_equals($computed, $hash); // timing-safe comparison
}

// ─── 3. HMAC-SHA256 (for API tokens / message integrity) ────────────────────
function generateHMAC(string $data, string $secretKey): string {
    return hash_hmac('sha256', $data, $secretKey);
}

function verifyHMAC(string $data, string $token, string $secretKey): bool {
    $expected = hash_hmac('sha256', $data, $secretKey);
    return hash_equals($expected, $token);
}

// ─── 4. File hash (verify file integrity) ───────────────────────────────────
function hashFile256(string $filePath): string|false {
    if (!file_exists($filePath)) return false;
    return hash_file('sha256', $filePath);
}

// ─── 5. Auto hash for property deal data (your use case) ────────────────────
function generateDealHash(array $dealData): string {
    // Sort keys so hash is consistent regardless of array order
    ksort($dealData);
    $payload = json_encode($dealData);
    return hash('sha256', $payload);
}


// ════════════════════════════════════════════════════════════════════════════
// DEMO OUTPUT
// ════════════════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════\n";
echo "        SHA-256 Hash Generator - Demo\n";
echo "═══════════════════════════════════════════════\n\n";

// 1. Basic hash
$input = "EasyPropertyDeals";
echo "1. Basic SHA-256 Hash\n";
echo "   Input : $input\n";
echo "   Hash  : " . generateHash($input) . "\n\n";

// 2. Salted hash
echo "2. Salted Hash (auto-generated salt)\n";
$result = generateSaltedHash("mypassword123");
echo "   Salt  : {$result['salt']}\n";
echo "   Hash  : {$result['hash']}\n";
$valid = verifySaltedHash("mypassword123", $result['hash'], $result['salt']);
echo "   Verify: " . ($valid ? "VALID" : "INVALID") . "\n\n";

// 3. HMAC
echo "3. HMAC-SHA256 (API token style)\n";
$secretKey = "your-secret-key-here";
$data      = "user_id=101&action=buy_property";
$token     = generateHMAC($data, $secretKey);
echo "   Data  : $data\n";
echo "   HMAC  : $token\n";
$verified  = verifyHMAC($data, $token, $secretKey);
echo "   Verify: " . ($verified ? "VALID" : "INVALID") . "\n\n";

// 4. Property deal hash (your real estate use case)
echo "4. Property Deal Hash\n";
$deal = [
    'property_id'  => 'PROP-2024-001',
    'buyer_name'   => 'Rahul Sharma',
    'price'        => 5750000,
    'location'     => 'Hinjewadi, Pune',
    'timestamp'    => '2024-04-06 10:30:00',
];
$dealHash = generateDealHash($deal);
echo "   Deal  : " . json_encode($deal, JSON_PRETTY_PRINT) . "\n";
echo "   Hash  : $dealHash\n\n";

// 5. Hash comparison (tamper detection)
echo "5. Tamper Detection\n";
$original   = generateHash("Deal Amount: Rs 57.5 Lakhs");
$tampered   = generateHash("Deal Amount: Rs 55.0 Lakhs");
echo "   Original hash : $original\n";
echo "   Tampered hash : $tampered\n";
echo "   Match?        : " . (hash_equals($original, $tampered) ? "YES - same" : "NO - tampered!") . "\n\n";

echo "═══════════════════════════════════════════════\n";
echo "Done.\n";
