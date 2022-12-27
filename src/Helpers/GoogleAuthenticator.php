<?php

namespace Elison\GoogleAuthenticator\Helpers;

class GoogleAuthenticator
{
    protected $base32Table = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
        'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z', '2', '3', '4', '5', '6', '7',
        '=',
    ];

    protected $codeLength = 6;
    protected $modeOfCodeLength = 1000000;

    public function generateSecret()
    {
        $secretStringLength = 16;

        $base32Table = $this->base32Table;

        $secretString = '';

        $random = random_bytes($secretStringLength);

        for ($i = 0; $i < $secretStringLength; $i++) {
            $secretString .= $base32Table[ord($random[$i]) & 31];
        }

        return $secretString;
    }

    public function generateQrCodeUrl($applicationName, $width, $height, $level)
    {
        $secretString = $this->generateSecret();

        $encodedParameters = urlencode('otpauth://totp/' . $applicationName . '?secret=' . $secretString);

        return "https://api.qrserver.com/v1/create-qr-code/?data=$encodedParameters&size=${width}x${height}&ecc=$level";
    }

    public function base32Decode($secretKey)
    {
        $base32Table = $this->base32Table;
        $base32ArrayKeys = array_flip($base32Table);

        $secretRemoveEqualSign = str_replace('=', '', $secretKey);
        $secretKey = str_split($secretRemoveEqualSign);
        $binaryString = '';

        for ($i = 0; $i < count($secretKey); $i = $i + 8) {
            $x = '';
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@$base32ArrayKeys[@$secretKey[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }

            $eightBits = str_split($x, 8);

            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }

        return $binaryString;
    }

    private function get2FaCode($secretKey)
    {
        $timeToResetCode = floor(time() / 30);

        $secretKeyBase32Decoded = $this->base32Decode($secretKey);

        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeToResetCode);
        $hash = hash_hmac('SHA1', $time, $secretKeyBase32Decoded, true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $hashPart = substr($hash, $offset, 4);
        $unpackedBinaryValue = unpack('N', $hashPart);
        $firstUnpackedBinaryValue = $unpackedBinaryValue[1];

        $firstUnpackedBinaryValue = $firstUnpackedBinaryValue & 0x7FFFFFFF;

        return str_pad($firstUnpackedBinaryValue % $this->modeOfCodeLength, $this->codeLength, '0', STR_PAD_LEFT);
    }

}
