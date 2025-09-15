<?php

namespace Scacchilatorre\Management\Services;

use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImporterService implements Service
{
    private bool $dryRun = false;

    public function __construct(
        private readonly DbService $dbService,
        private readonly ExtractorService $extractorService,
    )
    {
    }

    private SymfonyStyle $io;

    public function import(string $dumpPath): void
    {
        $this->io->info('Import started');

        if (!$this->dryRun) {
            $this->dbService->resetDb();
            $this->io->writeln('- Database has been reset');
        }

        if (!is_dir($dumpPath)) {
            $this->io->error('Dir not found: ' . $dumpPath);
            return;
        }

        $fileList = $this->extractorService
                        ->withIO($this->io)
                        ->getFileListIterator($dumpPath);

        $categoriesCache = [];
        $connection = $this->dbService->getConnection();
        $stmtPosts = $connection->prepare("INSERT INTO articles (author_id, title, content, created_at) VALUES (?, ?, ?, ?)");
        $stmtCategories = $connection->prepare("INSERT INTO categories (name, created_at) VALUES (?, CURRENT_TIMESTAMP)");
        $stmtPostsCategories = $connection->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");

        try {
            foreach($fileList['file_list_iterator'] as $file) {
                $year = (int)substr($file, 0, 4);

                if (($year >= 2012 && $year <= 2024) || $year === 0) {
                    $this->io->writeln("- Importing $file...");
                    $content = $this->extractorService->getFileContent(
                        join(DIRECTORY_SEPARATOR, [$fileList['path'], $file])
                    );
                    $posts = $this->extractorService->extractPostsFromHtml($content);
                    $this->io->writeln('- Found ' . count($posts) . ' posts');

                    if (!$this->dryRun) {
                        foreach ($posts as $post) {
                            $stmtPosts->execute(
                                [1, $post['title'], $post['content'], $post['creation_date'] ?? '0000-00-00 00:00:00']
                            );
                            $postId = $connection->lastInsertId();
                            $categories = $this->extractorService->deriveCategories(
                                $post['title'] . ' ' . $post['content']
                            );
                            $this->io->writeln('    - ' . $post['title'] . ' - ' . json_encode($categories));

                            foreach ($categories as $category) {
                                if (!in_array($category, array_keys($categoriesCache))) {
                                    $stmtCategories->execute([$category]);
                                    $categoryId = $connection->lastInsertId();
                                    $categoriesCache[$category] = $categoryId;
                                } else {
                                    $categoryId = $categoriesCache[$category];
                                }

                                $stmtPostsCategories->execute([$postId, $categoryId]);
                            }
                        }
                    } else {
                        $this->io->writeln(json_encode($posts, JSON_PRETTY_PRINT));
                    }
                } else {
                    $this->io->writeln('- Skipping ' . $year . ' in ' . $file);
                }
            }
        } catch (Exception $e) {
            $this->io->error($e->getMessage());
        } finally {
            $stmtPosts = null;
            $stmtCategories = null;
            $stmtPostsCategories = null;
            $connection = null;
        }
    }

    public function withIO(SymfonyStyle $io): ServiceInterface
    {
        $this->io = $io;
        return $this;
    }


    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;
        return $this;
    }
}
