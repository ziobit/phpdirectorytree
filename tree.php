<?php
function getDirectoryDetails($path) {
    $fileSizes = [];
    $totalSize = 0;
    $totalFiles = 0;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    foreach ($files as $file) {
        if ($file->isFile()) {
            $totalSize += $file->getSize();
            $totalFiles++;
            $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            if (!isset($fileSizes[$ext])) {
                $fileSizes[$ext] = ['size' => 0, 'count' => 0];
            }
            $fileSizes[$ext]['size'] += $file->getSize();
            $fileSizes[$ext]['count']++;
        }
    }

    $maxExt = array_reduce(array_keys($fileSizes), function ($a, $b) use ($fileSizes) {
        return $fileSizes[$a]['size'] > $fileSizes[$b]['size'] ? $a : $b;
    }, array_key_first($fileSizes));

    return [
        'size' => $totalSize,
        'files' => $totalFiles,
        'maxExt' => $maxExt,
        'maxExtSize' => $fileSizes[$maxExt]['size'],
        'maxExtCount' => $fileSizes[$maxExt]['count']
    ];
}

function formatSize($size) {
    if ($size >= 1073741824) {
        return number_format($size / 1073741824, 2) . ' GB';
    } elseif ($size >= 1048576) {
        return number_format($size / 1048576, 2) . ' MB';
    } elseif ($size >= 1024) {
        return number_format($size / 1024, 2) . ' KB';
    }
    return $size . ' bytes';
}

$currentDir = isset($_GET['path']) ? $_GET['path'] : '.';
$currentDir = realpath($currentDir);

if (strpos($currentDir, realpath('.')) !== 0) {
    $currentDir = '.';
}

$parentDir = realpath($currentDir . '/..');
$parentLink = strpos($parentDir, realpath('.')) === 0 ? '?path=' . urlencode($parentDir) : '';

$dirs = [];
$totalSizeUsed = 0;
$totalFilesCount = 0;
foreach (new DirectoryIterator($currentDir) as $fileInfo) {
    if ($fileInfo->isDot() || !$fileInfo->isDir()) continue;
    $filePath = $fileInfo->getRealPath();
    $details = getDirectoryDetails($filePath);
    $dirs[$filePath] = $details;
    $totalSizeUsed += $details['size'];
    $totalFilesCount += $details['files'];
}

$averageFileSize = $totalFilesCount > 0 ? round($totalSizeUsed / $totalFilesCount / 1024) : 0;

uasort($dirs, function ($a, $b) {
    return $b['size'] - $a['size'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; }
        table { width: 100%; margin-top: 20px; }
        .size-bar { height: 20px; background-color: #007bff; color: black; position: relative; white-space: nowrap; }
        .size-bar span { position: absolute; left: 5px; }
    </style>
    <title>Directory Sizes</title>
</head>
<body>
<div class="container">
    <h1>Directory Sizes in: <?= htmlspecialchars($currentDir) ?></h1>
    <?php if ($parentLink): ?>
    <a href="<?= $parentLink ?>" class="btn btn-secondary mb-3">Go Up One Level</a>
    <?php endif; ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Size</th>
                <th>Directory Name</th>
                <th>Total Files</th>
                <th>Top Extension</th>
                <th>Usage Bar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dirs as $path => $details): ?>
            <tr>
                <td><?= formatSize($details['size']) ?></td>
                <td><a href="?path=<?= urlencode($path) ?>"><?= htmlspecialchars(basename($path)) ?></a></td>
                <td><?= $details['files'] ?></td>
                <td><?= htmlspecialchars($details['maxExt']) ?> (<?= $details['maxExtCount'] ?> files, <?= formatSize($details['maxExtSize']) ?>)</td>
                <td>
                    <div class="size-bar" style="width: <?= ($details['size'] / $totalSizeUsed) * 100 ?>%;">
                        <span><?= formatSize($details['size']) ?></span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div><strong>Total Space Used:</strong> <?= formatSize($totalSizeUsed) ?></div>
    <div><strong>Total Files:</strong> <?= $totalFilesCount ?></div>
    <div><strong>Average File Size:</strong> <?= $averageFileSize ?> KB</div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
