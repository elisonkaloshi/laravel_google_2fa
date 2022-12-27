<?php

namespace Elison\GoogleAuthenticator\Helpers;

use App\Models\TwoFaCredential;

class GoogleAuthenticator
{
    protected static $base32Table = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
        'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z', '2', '3', '4', '5', '6', '7',
        '=',
    ];

    protected static $codeLength = 6;
    protected static $modeOfCodeLength = 1000000;

    private static function generateSecret()
    {
        $secretStringLength = 16;

        $base32Table = self::$base32Table;

        $secretString = '';

        $random = random_bytes($secretStringLength);

        for ($i = 0; $i < $secretStringLength; $i++) {
            $secretString .= $base32Table[ord($random[$i]) & 31];
        }

        return $secretString;
    }

    public static function generateQrCodeUrl($applicationName, $width, $height, $level = 'M')
    {
        $secretString = self::generateSecret();;

        $encodedParameters = urlencode('otpauth://totp/' . $applicationName . '?secret=' . $secretString);

        return [$secretString, "https://api.qrserver.com/v1/create-qr-code/?data=$encodedParameters&size=${width}x${height}&ecc=$level"];
    }
    public static function setCredentials($userId, $secretKey)
    {
        TwoFaCredential::updateOrCreate(
            ['user_id' => $userId],
            ['secret_key' => $secretKey]);
    }

    public static function base32Decode($secretKey)
    {
        $base32Table = self::$base32Table;
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

    private static function get2FaCode($secretKey)
    {
        $timeToResetCode = floor(time() / 30);

        $secretKeyBase32Decoded = self::base32Decode($secretKey);

        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeToResetCode);
        $hash = hash_hmac('SHA1', $time, $secretKeyBase32Decoded, true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $hashPart = substr($hash, $offset, 4);
        $unpackedBinaryValue = unpack('N', $hashPart);
        $firstUnpackedBinaryValue = $unpackedBinaryValue[1];

        $firstUnpackedBinaryValue = $firstUnpackedBinaryValue & 0x7FFFFFFF;

        return str_pad($firstUnpackedBinaryValue % self::$modeOfCodeLength, self::$codeLength, '0', STR_PAD_LEFT);
    }
    public static function checkIfTwoFaIsActive($userId)
    {
        return TwoFaCredential::where('user_id', $userId)->exists();
    }

    public static function verifyIsCodeIsValid($userId, $code)
    {
        if (self::checkIfTwoFaIsActive($userId)) {
            $secretKeyOfTheUser = TwoFaCredential::where('user_id', $userId)
                ->value('secret_key');

            return $code === self::get2FaCode($secretKeyOfTheUser);
        }

        return false;
    }

}
