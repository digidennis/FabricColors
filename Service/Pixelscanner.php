<?php

namespace Digidennis\FabricColors\Service;

class Pixelscanner
{
    public function scan(string $absolutePath): ?array
    {
        if (!is_readable($absolutePath)) {
            return null;
        }

        if (class_exists(\Imagick::class)) {
            return $this->scanWithImagick($absolutePath);
        }

        return $this->scanWithGd($absolutePath);
    }

    private function scanWithImagick(string $path): ?array
    {
        try {
            $img = new \Imagick($path);
            $img->resizeImage(1, 1, \Imagick::FILTER_BOX, 1);

            $pixel = $img->getImagePixelColor(0, 0);
            $rgb = $pixel->getColor();

            return $this->formatOutput($rgb['r'], $rgb['g'], $rgb['b']);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function scanWithGd(string $path): ?array
    {
        $info = getimagesize($path);
        if (!$info) {
            return null;
        }

        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $img = imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($path);
                break;
            case IMAGETYPE_GIF:
                $img = imagecreatefromgif($path);
                break;
            default:
                return null;
        }

        if (!$img) {
            return null;
        }

        $tmp = imagecreatetruecolor(1, 1);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, 1, 1, imagesx($img), imagesy($img));

        $rgb = imagecolorat($tmp, 0, 0);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        imagedestroy($img);
        imagedestroy($tmp);

        return $this->formatOutput($r, $g, $b);
    }

    private function formatOutput(int $r, int $g, int $b): array
    {
        return [
            'avg_color_r'   => $r,
            'avg_color_g'   => $g,
            'avg_color_b'   => $b,
            'avg_color_hex' => sprintf('#%02X%02X%02X', $r, $g, $b),
            'avg_color_lab' => $this->rgbToLab($r, $g, $b)
        ];
    }

    private function rgbToLab(int $r, int $g, int $b): string
    {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        $r = ($r > 0.04045) ? pow(($r + 0.055) / 1.055, 2.4) : ($r / 12.92);
        $g = ($g > 0.04045) ? pow(($g + 0.055) / 1.055, 2.4) : ($g / 12.92);
        $b = ($b > 0.04045) ? pow(($b + 0.055) / 1.055, 2.4) : ($b / 12.92);

        $x = ($r * 0.4124 + $g * 0.3576 + $b * 0.1805) / 0.95047;
        $y = ($r * 0.2126 + $g * 0.7152 + $b * 0.0722) / 1.00000;
        $z = ($r * 0.0193 + $g * 0.1192 + $b * 0.9505) / 1.08883;

        $x = ($x > 0.008856) ? pow($x, 1/3) : (7.787 * $x + 16/116);
        $y = ($y > 0.008856) ? pow($y, 1/3) : (7.787 * $y + 16/116);
        $z = ($z > 0.008856) ? pow($z, 1/3) : (7.787 * $z + 16/116);

        $l = (116 * $y) - 16;
        $a = 500 * ($x - $y);
        $b = 200 * ($y - $z);

        return sprintf('%.2f,%.2f,%.2f', $l, $a, $b);
    }
}
