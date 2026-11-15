<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('resources/views'));
foreach ($files as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $content = file_get_contents($file->getPathname());
        $sec = substr_count($content, '@section');
        $end = substr_count($content, '@endsection');
        if ($sec !== $end) {
            echo "Mismatch in {$file->getPathname()}: section={$sec}, endsection={$end}\n";
        }
    }
}
echo "Check completed.\n";

