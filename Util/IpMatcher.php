<?php declare(strict_types=1);

namespace Yireo\AdminAutoLogin\Util;

class IpMatcher
{
    /**
     * @param string $ip
     * @param string[] $ranges
     * @return bool
     */
    public function matches(string $ip, array $ranges): bool
    {
        $binaryIp = $this->toBinary($ip);
        if ($binaryIp === null) {
            return false;
        }

        foreach ($ranges as $range) {
            if ($this->matchesRange($binaryIp, $range)) {
                return true;
            }
        }

        return false;
    }

    private function matchesRange(string $binaryIp, string $range): bool
    {
        $parts = explode('/', $range, 2);
        $binarySubnet = $this->toBinary($parts[0]);
        if ($binarySubnet === null || strlen($binarySubnet) !== strlen($binaryIp)) {
            return false;
        }

        $maxBits = strlen($binaryIp) * 8;
        $bits = $maxBits;
        if (isset($parts[1])) {
            if (!ctype_digit($parts[1]) || (int)$parts[1] > $maxBits) {
                return false;
            }

            $bits = (int)$parts[1];
        }

        $fullBytes = intdiv($bits, 8);
        if (substr($binaryIp, 0, $fullBytes) !== substr($binarySubnet, 0, $fullBytes)) {
            return false;
        }

        $remainingBits = $bits % 8;
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($binaryIp[$fullBytes]) & $mask) === (ord($binarySubnet[$fullBytes]) & $mask);
    }

    private function toBinary(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $binaryIp = inet_pton($ip);
        return $binaryIp === false ? null : $binaryIp;
    }
}
