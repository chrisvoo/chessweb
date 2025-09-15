<?php

namespace Scacchilatorre\Management\Services;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SplQueue;
use Symfony\Component\Console\Style\SymfonyStyle;

class Crawler implements Service
{
    /**
     * The absolute path to the website's root directory.
     * @var string
     */
    private string $rootDir;

    /**
     * An associative array mapping a file path to the links found within it.
     * @var array
     */
    private array $pathMap = [];

    /**
     * Keeps track of files that have already been processed to avoid infinite loops.
     * @var array
     */
    private array $scannedFiles = [];

    /**
     * A list of all unique public paths found across the entire site.
     * @var array
     */
    private array $allFoundPaths = [];

    private SymfonyStyle $io;

    public function withIO(SymfonyStyle $io): Service
    {
        $this->io = $io;
        return $this;
    }

    private function hasPathToBeFilteredOut(string $path, array $toBeFilteredOut): bool
    {
        if (in_array($path, $toBeFilteredOut)) {
            return true;
        }

        foreach ($toBeFilteredOut as $filteredPath) {
            if (str_starts_with($path, $filteredPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scans the root directory to find all files with the given extensions.
     *
     * @param array $extensions The file extensions to look for (e.g., ['php', 'inc']).
     * @param array $toBeFilteredOut Paths to be excluded
     * @return array A list of file paths relative to the root directory.
     */
    private function discoverEntryPoints(array $extensions, array $toBeFilteredOut): array
    {
        $entryPoints = [];
        $directoryIterator = new RecursiveDirectoryIterator($this->rootDir, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directoryIterator);

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (
                $file->isFile() &&
                !$this->hasPathToBeFilteredOut(
                    str_replace($this->rootDir, '', $file->getPath()),
                    $toBeFilteredOut
                ) &&
                in_array(strtolower($file->getExtension()), $extensions)
            ) {
                // Get the path relative to the root directory
                $relativePath = str_replace($this->rootDir . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $entryPoints[] = $relativePath;
            }
        }
        return $entryPoints;
    }

    /**
     * Crawls a local PHP website dump to find all public-facing paths.
     *
     * @param string $directoryPath The path to the website's root directory.
     * @param array $toBeFilteredOut Paths to be excluded
     * @return array An array containing the 'path_map' and the 'unique_paths' summary.
     */
    public function crawl(string $directoryPath, array $toBeFilteredOut = []): array
    {
        $this->rootDir = realpath($directoryPath);
        if ($this->rootDir === false || !is_dir($this->rootDir)) {
            $this->io->error("Error: Provided path '{$directoryPath}' is not a valid directory.");
            return [];
        }

        // 2. Automatically discover all .php and .inc files to use as entry points
        $entryPoints = $this->discoverEntryPoints(['php', 'inc'], $toBeFilteredOut);
        if (empty($entryPoints)) {
            $this->io->error("Error: No .php or .inc files found in the specified directory.");
            return [];
        }

        // 3. Setup the crawler queue
        $filesToScan = new SplQueue();
        foreach ($entryPoints as $entryFile) {
            // The file discovery guarantees they exist and are unique.
            $filesToScan->enqueue($entryFile);
        }

        // Mark all entry points as scanned initially to avoid re-processing them
        // if they are linked from other entry points.
        $this->scannedFiles = $entryPoints;

        // Process the initial set of files
        foreach ($filesToScan as $file) {
            $this->processFile($file, $filesToScan, true);
        }

        // 4. Process the queue for any newly discovered files
        while (!$filesToScan->isEmpty()) {
            $currentRelativePath = $filesToScan->dequeue();
            $this->processFile($currentRelativePath, $filesToScan, false);
        }

        // 4. Prepare the final output
        $uniquePaths = array_unique($this->allFoundPaths);
        sort($uniquePaths); // For clean, alphabetical ordering

        return [
            'path_map' => $this->pathMap,
            'unique_paths' => array_values($uniquePaths),
        ];
    }

    /**
     * Processes a single file: reads content, extracts paths, and updates the queue.
     *
     * @param string $relativeFilePath The file path relative to the root directory.
     * @param SplQueue $queue The crawler's queue to add new files to.
     * @param bool $isEntryPoint Flag to indicate if this is one of the initial files.
     */
    private function processFile(string $relativeFilePath, SplQueue $queue, bool $isEntryPoint): void
    {
        if (!$isEntryPoint && in_array($relativeFilePath, $this->scannedFiles)) {
            return;
        }
        $this->scannedFiles[] = $relativeFilePath;

        $fullPath = $this->rootDir . DIRECTORY_SEPARATOR . $relativeFilePath;
        $content = file_get_contents($fullPath);

        // Extract raw href/src values using regex
        preg_match_all('/<(?:a|img)[^>]+(?:href|src)=["\'](.*?)["\']/i', $content, $matches);
        $foundPaths = $matches[1] ?? [];

        $cleanedPaths = [];
        foreach ($foundPaths as $path) {
            // Filter out non-public or external paths
            if ($this->isPublicPath($path)) {
                // Resolve relative paths (e.g., ../, ./) to be relative to the root
                $resolvedPath = $this->resolvePath($relativeFilePath, $path);
                $cleanedPaths[] = $resolvedPath;

                // If we found a new PHP file, add it to the queue to be scanned
                if (pathinfo($resolvedPath, PATHINFO_EXTENSION) === 'php') {
                    $fullResolvedPath = $this->rootDir . DIRECTORY_SEPARATOR . $resolvedPath;
                    if (file_exists($fullResolvedPath) && !in_array($resolvedPath, $this->scannedFiles)) {
                        $queue->enqueue($resolvedPath);
                        $this->scannedFiles[] = $resolvedPath; // Mark it so we don't add it again
                    }
                }
            }
        }

        $uniqueCleanedPaths = array_unique($cleanedPaths);
        sort($uniqueCleanedPaths);

        $this->pathMap[$relativeFilePath] = $uniqueCleanedPaths;
        $this->allFoundPaths = array_merge($this->allFoundPaths, $uniqueCleanedPaths);
    }

    /**
    * Checks if a path is a public-facing, local path.
    *
    * @param string $path The path string to check.
    * @return bool
    */
    private function isPublicPath(string $path): bool
    {
        $path = trim($path);

        // Filter out anchors, javascript, mailto, tel, empty paths, etc.
        return !(
            empty($path) ||
            str_starts_with($path, '#') ||
            str_starts_with($path, 'javascript:') ||
            str_starts_with($path, 'mailto:') ||
            str_starts_with($path, 'tel:') ||
            preg_match('/^(https?:)?\/\//i', $path) // external URLs
        );
    }

    /**
     * Resolves a relative or absolute link path into a path relative to the website root.
     *
     * @param string $baseFilePath The path of the file containing the link.
     * @param string $linkPath The path from the href or src attribute.
     * @return string The resolved path relative to the root directory.
     */
    private function resolvePath(string $baseFilePath, string $linkPath): string
    {
        // If path starts with '/', it's relative to the root.
        if (str_starts_with($linkPath, '/')) {
            return ltrim($linkPath, '/');
        }

        // Resolve relative paths like './' or '../'
        $baseDir = dirname($baseFilePath);
        // If the base file is in the root, its dirname is '.'
        if ($baseDir === '.') {
            $baseDir = '';
        }

        $fullPath = $baseDir ? $baseDir . DIRECTORY_SEPARATOR . $linkPath : $linkPath;

        // Normalize path by resolving '..' and '.' segments
        $parts = explode(DIRECTORY_SEPARATOR, $fullPath);
        $resolvedParts = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($resolvedParts);
            } else {
                $resolvedParts[] = $part;
            }
        }

        return implode(DIRECTORY_SEPARATOR, $resolvedParts);
    }

    public function setDryRun(bool $dryRun): ServiceInterface
    {
        return $this;
    }
}
