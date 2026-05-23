<?php
namespace Digidennis\FabricColors\Model\Image;

class PixelAnalyzer
{
    public function analyze(string $absolutePath): ?array
    {
        if (!file_exists($absolutePath)) {
            return null;
        }

        $info = getimagesize($absolutePath);
        if (!$info) {
            return null;
        }

        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $img = imagecreatefromjpeg($absolutePath);
                break;
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($absolutePath);
                break;
            default:
                return null;
        }

        $width  = imagesx($img);
        $height = imagesy($img);

        $stepX = max(1, (int)floor($width / 50));
        $stepY = max(1, (int)floor($height / 50));

        $r = $g = $b = 0;
        $count = 0;

        for ($x = 0; $x < $width; $x += $stepX) {
            for ($y = 0; $y < $height; $y += $stepY) {
                $rgb = imagecolorat($img, $x, $y);
                $r += ($rgb >> 16) & 0xFF;
                $g += ($rgb >> 8) & 0xFF;
                $b += $rgb & 0xFF;
                $count++;
            }
        }

        imagedestroy($img);

        if ($count === 0) {
            return null;
        }

        $r = (int)round($r / $count);
        $g = (int)round($g / $count);
        $b = (int)round($b / $count);

        $hex = sprintf('#%02X%02X%02X', $r, $g, $b);

        return [
            'r'   => $r,
            'g'   => $g,
            'b'   => $b,
            'hex' => $hex
        ];
    }
}
