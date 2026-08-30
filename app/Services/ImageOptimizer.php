<?php

namespace App\Services;

/**
 * 图片压缩服务（规格书 §14.9）
 * 压缩引擎：PHP GD（imagejpeg / imagepng / imagegif）。
 * 支持 JPG / PNG / GIF；记录原始/优化后体积到 image_optimizer_stats。
 */
class ImageOptimizer
{
    /**
     * 压缩图片文件（原地覆盖或生成副本）
     *
     * @param  string  $path  图片绝对路径
     * @param  int  $quality  JPEG 质量 0-100
     * @param  bool  $keepOriginal  保留原图（生成 .original 后缀备份）
     * @return array{ok: bool, original_size: int, optimized_size: int, file_type: string, error?: string}
     */
    public function process(string $path, int $quality = 82, bool $keepOriginal = false, ?int $userId = null): array
    {
        if (! file_exists($path)) {
            return ['ok' => false, 'original_size' => 0, 'optimized_size' => 0, 'file_type' => '', 'error' => 'file_not_found'];
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return ['ok' => false, 'original_size' => 0, 'optimized_size' => 0, 'file_type' => '', 'error' => 'not_an_image'];
        }

        [$width, $height, $type] = $info;
        $originalSize = (int) filesize($path);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($path);
                $fileType = 'jpeg';
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($path);
                $fileType = 'png';
                break;
            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($path);
                $fileType = 'gif';
                break;
            default:
                return ['ok' => false, 'original_size' => $originalSize, 'optimized_size' => $originalSize, 'file_type' => '', 'error' => 'unsupported_type'];
        }

        if ($image === false) {
            return ['ok' => false, 'original_size' => $originalSize, 'optimized_size' => 0, 'file_type' => '', 'error' => 'gd_failed'];
        }

        // PNG 保留透明通道
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        if ($keepOriginal) {
            @copy($path, $path . '.original');
        }

        // 先写入临时文件，成功后再替换原文件（失败不破坏原图）
        $tmpPath = $path . '.optimized';

        $success = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $tmpPath, max(1, min(100, $quality))),
            IMAGETYPE_PNG => imagepng($image, $tmpPath, 6),
            IMAGETYPE_GIF => imagegif($image, $tmpPath),
            default => false,
        };

        imagedestroy($image);

        if (! $success || ! file_exists($tmpPath)) {
            @unlink($tmpPath);

            return ['ok' => false, 'original_size' => $originalSize, 'optimized_size' => $originalSize, 'file_type' => $fileType, 'error' => 'compress_failed'];
        }

        // 优化后反而更大则放弃
        $optimizedSize = (int) filesize($tmpPath);
        if ($optimizedSize >= $originalSize) {
            @unlink($tmpPath);

            return ['ok' => true, 'original_size' => $originalSize, 'optimized_size' => $originalSize, 'file_type' => $fileType];
        }

        rename($tmpPath, $path);

        // 记录统计
        \App\Models\ImageOptimizerStat::create([
            'user_id' => $userId,
            'file_type' => $fileType,
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'datetime' => now(),
        ]);

        return ['ok' => true, 'original_size' => $originalSize, 'optimized_size' => $optimizedSize, 'file_type' => $fileType];
    }
}
