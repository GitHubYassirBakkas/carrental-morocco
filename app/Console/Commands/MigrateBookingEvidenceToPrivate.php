<?php

namespace App\Console\Commands;

use App\Models\BookingDamage;
use App\Models\BookingPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateBookingEvidenceToPrivate extends Command
{
    protected $signature = 'booking-evidence:migrate-private
        {--execute : Copy files to private storage and update database paths without deleting public source files}
        {--delete-public-after-verify : After --execute, delete legacy public source files after private copy and DB update verification}';

    protected $description = 'Dry-run or migrate legacy public booking evidence files into private storage.';

    private array $stats = [
        'legacy_db_references' => 0,
        'legacy_files_existing' => 0,
        'dry_run' => 0,
        'copied' => 0,
        'db_updated' => 0,
        'public_deleted' => 0,
        'already_private' => 0,
        'missing' => 0,
        'orphan_public_files' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    private array $referencedLegacySources = [];

    public function handle(): int
    {
        $this->resetStats();

        $execute = (bool) $this->option('execute');
        $deletePublicAfterVerify = (bool) $this->option('delete-public-after-verify');

        if ($deletePublicAfterVerify && ! $execute) {
            $this->error('--delete-public-after-verify requires --execute.');

            return self::FAILURE;
        }

        BookingPhoto::query()
            ->whereNotNull('path')
            ->chunkById(100, function ($photos) use ($execute, $deletePublicAfterVerify): void {
                foreach ($photos as $photo) {
                    $this->migrateInspectionPhoto($photo, $execute, $deletePublicAfterVerify);
                }
            });

        BookingDamage::query()
            ->whereNotNull('photos')
            ->chunkById(100, function ($damages) use ($execute, $deletePublicAfterVerify): void {
                foreach ($damages as $damage) {
                    $this->migrateDamagePhotos($damage, $execute, $deletePublicAfterVerify);
                }
            });

        $this->countOrphanPublicFiles();

        $this->info($execute ? 'EXECUTE complete. Public source files were preserved unless --delete-public-after-verify was used.' : 'DRY-RUN complete. Re-run with --execute to copy files and update database paths.');

        foreach ($this->stats as $name => $count) {
            $this->line($name.': '.$count);
        }

        return $this->stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resetStats(): void
    {
        $this->stats = [
            'legacy_db_references' => 0,
            'legacy_files_existing' => 0,
            'dry_run' => 0,
            'copied' => 0,
            'db_updated' => 0,
            'public_deleted' => 0,
            'already_private' => 0,
            'missing' => 0,
            'orphan_public_files' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $this->referencedLegacySources = [];
    }

    private function migrateInspectionPhoto(BookingPhoto $photo, bool $execute, bool $deletePublicAfterVerify): void
    {
        $result = $this->prepareMigration($photo->path, 'inspections', $execute);

        if (($result['status'] ?? null) !== 'ready') {
            return;
        }

        try {
            DB::transaction(function () use ($photo, $result): void {
                $updated = BookingPhoto::query()
                    ->whereKey($photo->id)
                    ->where('path', $photo->path)
                    ->update(['path' => $result['destination']]);

                if ($updated !== 1) {
                    throw new \RuntimeException('Inspection photo path changed before migration completed.');
                }
            });

            $this->stats['db_updated']++;

            if ($deletePublicAfterVerify) {
                $this->deletePublicSourceIfVerified($result['source'], $result['destination']);
            }
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($result['destination']);
            report($e);
            $this->stats['failed']++;
        }
    }

    private function migrateDamagePhotos(BookingDamage $damage, bool $execute, bool $deletePublicAfterVerify): void
    {
        $photos = $damage->photos ?? [];

        if (! is_array($photos) || $photos === []) {
            $this->stats['skipped']++;

            return;
        }

        $newPhotos = $photos;
        $copied = [];

        foreach ($photos as $index => $path) {
            if (! is_string($path)) {
                $this->stats['skipped']++;

                continue;
            }

            $result = $this->prepareMigration($path, 'damages', $execute);

            if (($result['status'] ?? null) !== 'ready') {
                continue;
            }

            $newPhotos[$index] = $result['destination'];
            $copied[] = $result;
        }

        if ($copied === []) {
            return;
        }

        try {
            DB::transaction(function () use ($damage, $photos, $newPhotos): void {
                $fresh = BookingDamage::query()->whereKey($damage->id)->lockForUpdate()->firstOrFail();

                if ($fresh->photos !== $photos) {
                    throw new \RuntimeException('Damage photo paths changed before migration completed.');
                }

                $fresh->photos = array_values($newPhotos);
                $fresh->save();
            });

            foreach ($copied as $result) {
                $this->stats['db_updated']++;

                if ($deletePublicAfterVerify) {
                    $this->deletePublicSourceIfVerified($result['source'], $result['destination']);
                }
            }
        } catch (\Throwable $e) {
            foreach ($copied as $result) {
                Storage::disk('local')->delete($result['destination']);
            }

            report($e);
            $this->stats['failed']++;
        }
    }

    private function prepareMigration(?string $path, string $category, bool $execute): array
    {
        $source = $this->evidencePath($path, $category);

        if ($source === null) {
            $this->stats['skipped']++;

            return ['status' => 'skipped'];
        }

        if (str_starts_with($source, "booking-evidence/{$category}/")) {
            if (Storage::disk('local')->exists($source)) {
                $this->stats['already_private']++;

                return ['status' => 'already_private'];
            }

            $this->stats['missing']++;

            return ['status' => 'missing'];
        }

        $this->stats['legacy_db_references']++;
        $this->referencedLegacySources[$source] = true;

        if (! Storage::disk('public')->exists($source)) {
            $this->stats['missing']++;

            return ['status' => 'missing'];
        }

        $this->stats['legacy_files_existing']++;

        $destination = $this->privateDestination($source, $category);

        if (! $execute) {
            $this->stats['dry_run']++;
            $this->line("Would migrate {$source} to {$destination}");

            return ['status' => 'dry_run'];
        }

        $contents = Storage::disk('public')->get($source);

        if ($contents === null || $contents === false) {
            $this->stats['failed']++;

            return ['status' => 'failed'];
        }

        if (! Storage::disk('local')->put($destination, $contents)) {
            $this->stats['failed']++;

            return ['status' => 'failed'];
        }

        $this->stats['copied']++;

        return [
            'status' => 'ready',
            'source' => $source,
            'destination' => $destination,
        ];
    }

    private function evidencePath(?string $path, string $category): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#^/?storage/app/public/#', '', $path);
        $path = preg_replace('#^/?public/storage/#', '', $path);
        $path = preg_replace('#^/?storage/#', '', $path);
        $path = ltrim($path, '/');

        if (
            str_contains($path, '..')
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)
        ) {
            return null;
        }

        if (! str_starts_with($path, "{$category}/") && ! str_starts_with($path, "booking-evidence/{$category}/")) {
            return null;
        }

        return $path;
    }

    private function deletePublicSourceIfVerified(string $source, string $destination): void
    {
        if (! Storage::disk('local')->exists($destination)) {
            $this->stats['failed']++;

            return;
        }

        if (! Storage::disk('public')->exists($source)) {
            return;
        }

        if (Storage::disk('public')->delete($source)) {
            $this->stats['public_deleted']++;

            return;
        }

        $this->stats['failed']++;
    }

    private function countOrphanPublicFiles(): void
    {
        foreach (['inspections', 'damages'] as $category) {
            foreach (Storage::disk('public')->allFiles($category) as $path) {
                if (! isset($this->referencedLegacySources[$path])) {
                    $this->stats['orphan_public_files']++;
                }
            }
        }
    }

    private function privateDestination(string $source, string $category): string
    {
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $extension = $extension ? '.'.$extension : '';
        $base = pathinfo($source, PATHINFO_FILENAME);
        $base = Str::slug($base) ?: 'evidence';

        do {
            $destination = sprintf(
                'booking-evidence/%s/%s-%s%s',
                $category,
                Str::uuid()->toString(),
                $base,
                $extension
            );
        } while (Storage::disk('local')->exists($destination));

        return $destination;
    }
}
