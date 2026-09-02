#!/usr/bin/env php
<?php
chdir(dirname(__FILE__));

$imageSizes = [
    16,
    24,
    32,
    36,
    48,
    64,
    72,
    96,
    120,
    128,
    144,
    160,
    192,
    224,
    240,
    248,
    256,
    300,
    320,
    384,
    512,
    1024,
];
$icoSizes = [
    16,
    32,
    64,
    96,
    128,
    256,
];
$icnsSizes = [
    16,
    32,
    128,
    256,
    512,
];

$original = 'logo/14564x14564.png';
$maskableBackgroundRgb = [38, 50, 56];
[$originalW, $originalH] = getimagesize($original);
is_dir('format') || mkdir('format', 02755, true);
array_map('unlink', glob('format/*.png'));
array_map('unlink', glob('format/*.webp'));
is_dir('logo/maskable') || mkdir('logo/maskable', 02755, true);
array_map('unlink', glob('logo/maskable/*.png'));
array_map('unlink', glob('logo/maskable/*.webp'));
is_dir('logo/monochrome') || mkdir('logo/monochrome', 02755, true);
array_map('unlink', glob('logo/monochrome/*.png'));
array_map('unlink', glob('logo/monochrome/*.webp'));
is_dir('icon') || mkdir('icon', 02755, true);
is_dir('logo.iconset') || mkdir('logo.iconset', 02755, true);
chmod('format', 02755);
chmod('logo/maskable', 02755);
chmod('logo/monochrome', 02755);
chmod('icon', 02755);
chmod('logo.iconset', 02755);

#region 生成图标
$originalImage = imagecreatefrompng($original);
foreach ($imageSizes as $size) {
    if ($size > 1024) {
        echo "Skipping size larger than original: {$size}\n";
        continue;
    }
    $image = imagecreatetruecolor($size, $size);
    $alpha = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagecolortransparent($image, $alpha);
    imagefill($image, 0, 0, $alpha);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagecopyresampled($image, $originalImage, 0, 0, 0, 0, $size, $size, $originalW, $originalH);
    imagepng($image, "format/{$size}x{$size}.png");
    imagewebp($image, "format/{$size}x{$size}.webp");

    $maskable = imagecreatetruecolor($size, $size);
    $maskableBackgroundColor = imagecolorallocate($maskable, ...$maskableBackgroundRgb);
    imagefill($maskable, 0, 0, $maskableBackgroundColor);
    imagealphablending($maskable, true);
    imagecopyresampled($maskable, $originalImage, 0, 0, 0, 0, $size, $size, $originalW, $originalH);
    imagepng($maskable, "logo/maskable/{$size}x{$size}.png");
    imagewebp($maskable, "logo/maskable/{$size}x{$size}.webp");
    imagedestroy($maskable);

    $monochrome = imagecreatetruecolor($size, $size);
    $monochromeAlpha = imagecolorallocatealpha($monochrome, 0, 0, 0, 127);
    imagecolortransparent($monochrome, $monochromeAlpha);
    imagefill($monochrome, 0, 0, $monochromeAlpha);
    imagealphablending($monochrome, false);
    imagesavealpha($monochrome, true);
    imagecopyresampled($monochrome, $originalImage, 0, 0, 0, 0, $size, $size, $originalW, $originalH);
    imagefilter($monochrome, IMG_FILTER_GRAYSCALE);
    imagefilter($monochrome, IMG_FILTER_BRIGHTNESS, -255);
    imagepng($monochrome, "logo/monochrome/{$size}x{$size}.png");
    imagewebp($monochrome, "logo/monochrome/{$size}x{$size}.webp");
    imagedestroy($monochrome);

    imagedestroy($image);
    echo "Generated: format/{$size}x{$size}.[png|webp]\n";
    echo "Generated: logo/maskable/{$size}x{$size}.[png|webp]\n";
    echo "Generated: logo/monochrome/{$size}x{$size}.[png|webp]\n";
}
imagedestroy($originalImage);
#endregion

#region 生成 ICO
/** @disregard Undefined type 'Imagick' */
$ico = new Imagick;
foreach ($icoSizes as $size) {
    $filename = "format/{$size}x{$size}.png";
    if (!file_exists($filename)) {
        echo "Missing size for ICO: {$size}\n";
        continue;
    }
    /** @disregard Undefined type 'Imagick' */
    $img = new Imagick($filename);
    $img->setImageFormat('ico');
    $ico->addImage($img);
    echo "Added to ICO: {$filename}\n";
}
$ico->writeImages('icon/favicon.ico', true);
echo "Generated: icon/favicon.ico\n";
#endregion

#region 生成 ICNS
foreach ($icnsSizes as $size) {
    $size2x = $size * 2;
    $filename1x = "format/{$size}x{$size}.png";
    $filename2x = "format/{$size2x}x{$size2x}.png";
    if (!file_exists($filename1x) || !file_exists($filename2x)) {
        echo "Missing size for ICNS: {$size} or {$size2x}\n";
        continue;
    }
    $newName1x = "logo.iconset/icon_{$size}x{$size}.png";
    $newName2x = "logo.iconset/icon_{$size}x{$size}@2x.png";
    copy($filename1x, $newName1x);
    copy($filename2x, $newName2x);
    echo "Copied to ICNS set: {$newName1x}\n";
    echo "Copied to ICNS set: {$newName2x}\n";
}
shell_exec('iconutil -c icns logo.iconset -o icon/logo.icns');
echo "Generated: logo.icns\n";
array_map('unlink', glob('logo.iconset/*.png'));
echo "Cleanup: Removed PNG files from logo.iconset\n";
rmdir('logo.iconset');
echo "Cleanup: Removed logo.iconset directory\n";
#endregion

copy('format/1024x1024.png', 'OpenList.png');
copy('format/1024x1024.webp', 'OpenList.webp');
copy('format/1024x1024.png', 'logo.png');
copy('format/1024x1024.webp', 'logo.webp');

array_map(fn($file) => rename($file, str_replace('format/', 'logo/', $file)), glob('format/*.*'));
rmdir('format');
echo "Cleanup: Removed format directory\n";
