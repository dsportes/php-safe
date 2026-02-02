<?php
/*
// Select a standard curve, P-256 is widely compatible
$curve_name = 'P-256';

// Create a new EC key resource
$private_key_resource = openssl_pkey_new([
    'curve_name' => $curve_name,
    'private_key_type' => OPENSSL_KEYTYPE_EC,
]);

// Export the private key in PEM format
openssl_pkey_export($private_key_resource, $private_key_pem);

// Retrieve and format the public key
$public_key_details = openssl_pkey_get_details($private_key_resource);
$public_key_pem = $public_key_details['key'];

echo "Private Key (PEM):\n" . $private_key_pem . "\n\n";
echo "Public Key (PEM):\n" . $public_key_pem . "\n";
*/
$jwk = <<<EOTEOTEOTEOT
{
    "key_ops": [
        "sign"
    ],
    "ext": true,
    "kty": "EC",
    "x": "AMiogCyO2QuZ68f6Kb0noXiuv2V67kGxLMwj7-77TXhgEOZCcDBlOMdhap4Rr4Kda6K45ONxOGrn-1jqTYUB_VlB",
    "y": "AHc_ztKyX9vmhCekgNtc5s_KoEjx7z_rv_ByYf-blMbn5MGP0vMZgREpjY1CmA0Ehsryqzj4JwuWboPiI5-Z5ZsW",
    "crv": "P-521",
    "d": "AQPFFFs0XnNb3KbgHOd5k-NGbu0xjvZw8RLzCMtmt8QBPDVsJoaKIYfNGE0pg1g7y_UYG6hWN-X44spAYp3McMu8"
}
EOTEOTEOTEOT;

$pem = "-----BEGIN PUBLIC KEY-----
BADIqIAsjtkLmevH+im9J6F4rr9leu5BsSzMI+/u+014YBDmQnAwZTjHYWqeEa+CnWuiuOTjcThq5/tY6k2FAf1ZQQB3P87Ssl/b5oQnpIDbXObPyqBI8e8/67/wcmH/m5TG5+TBj9LzGYERKY2NQpgNBIbK8qs4+CcLlm6D4iOfmeWbFg==
-----END PUBLIC KEY-----";

$conf = [
  "private_key_type" => OPENSSL_KEYTYPE_EC,
  "curve_name"       => "secp521r1",   // P‑521
];
$privKey = json_decode($jwk);
// $privKey = openssl_pkey_new($conf);
// $pubKey  = openssl_pkey_get_details($privKey)['key'];
$pubKey = $pem;

$message   = "Texte à signer";
$signature = '';
openssl_sign($message, $signature, $privKey, OPENSSL_ALGO_SHA256);
$signatureBase64 = base64_encode($signature);

$publicKeyResource = openssl_pkey_get_public($pubKey);
$signatureBinary   = base64_decode($signatureBase64);

$isValid = openssl_verify(
    $message,
    $signatureBinary,
    $publicKeyResource,
    OPENSSL_ALGO_SHA256
);

if ($isValid === 1) {
    echo "Signature valide !";
} elseif ($isValid === 0) {
    echo "Signature invalide .";
} else {
    echo "Erreur de vérification : " . openssl_error_string();
}
openssl_free_key($publicKeyResource);

?>