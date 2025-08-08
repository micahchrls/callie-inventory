<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Log;

class ExportStockoutToPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $date;
    protected ?string $platform;
    protected int $userId;
    protected string $fileName;
    protected string $userEmail;
    protected string $userName;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(string $date, ?string $platform, User $user)
    {
        $this->date = $date;
        $this->platform = $platform;
        $this->userId = $user->id;
        $this->userEmail = $user->email;
        $this->userName = $user->name;
        $this->fileName = $this->generateFileName();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting PDF export job', [
                'date' => $this->date,
                'platform' => $this->platform,
                'user' => $this->userId,
                'file' => $this->fileName
            ]);

            // Get the data
            $stockMovements = $this->getExportData();

            if ($stockMovements->isEmpty()) {
                $this->notifyUser(
                    'Export Failed',
                    'No stockout data found for the selected date.',
                    'warning'
                );
                return;
            }

            // Calculate statistics
            $orderQuantity = $stockMovements->count();
            $productQuantity = $stockMovements->unique('productVariant.id')->count();
            $itemQuantity = $stockMovements->sum(function ($item) {
                return abs($item->quantity_change);
            });

            // Prepare data for PDF
            $data = [
                'stockMovements' => $stockMovements,
                'date' => $this->date,
                'platform' => $this->platform,
                'userName' => $this->userName,
                'printTime' => Carbon::parse($this->date)->format('m-d_H-i-s'),
                'orderQuantity' => $orderQuantity,
                'productQuantity' => $productQuantity,
                'itemQuantity' => $itemQuantity,
            ];

            // Generate PDF
            $pdf = Pdf::loadView('reports.stockout-pdf', $data);
            $pdf->setPaper('A4', 'portrait');

            // Store the file in storage
            $path = 'exports/stockout/' . $this->fileName;
            Storage::disk('public')->put($path, $pdf->output());

            // Generate download URL
            $downloadUrl = Storage::disk('public')->url($path);

            // Notify user of completion with download link
            $this->notifyUserWithDownload(
                'PDF Export Ready',
                "Your stockout report has been generated successfully.",
                $downloadUrl,
                $this->fileName
            );

            Log::info('PDF export completed successfully', [
                'file' => $this->fileName,
                'path' => $path
            ]);

            // Schedule deletion of the file after 24 hours
            $this->scheduleFileDeletion($path);

        } catch (\Exception $e) {
            Log::error('PDF export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $this->fileName
            ]);

            $this->notifyUser(
                'Export Failed',
                'An error occurred while generating your PDF report. Please try again.',
                'danger'
            );

            throw $e;
        }
    }

    /**
     * Get the export data
     */
    protected function getExportData()
    {
        return StockMovement::query()
            ->with([
                'productVariant.product.productCategory',
                'productVariant.product.productSubCategory',
                'productVariant.platform',
                'user'
            ])
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->join('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->where('stock_movements.movement_type', 'stock_out')
            ->whereDate('stock_movements.created_at', $this->date)
            ->when($this->platform, function ($query) {
                $query->where('platforms.name', $this->platform);
            })
            ->select('stock_movements.*')
            ->orderBy('stock_movements.created_at', 'desc')
            ->get();
    }

    /**
     * Generate unique filename
     */
    protected function generateFileName(): string
    {
        $date = Carbon::parse($this->date)->format('Y-m-d');
        $platform = $this->platform ? "_{$this->platform}" : '';
        $timestamp = now()->format('His');
        
        return "stockout_report_{$date}{$platform}_{$timestamp}.pdf";
    }

    /**
     * Notify user with download link
     */
    protected function notifyUserWithDownload(string $title, string $body, string $downloadUrl, string $fileName): void
    {
        $user = User::find($this->userId);
        
        if ($user) {
            Notification::make()
                ->title($title)
                ->body($body)
                ->success()
                ->persistent()
                ->actions([
                    Action::make('download')
                        ->label('Download PDF')
                        ->url($downloadUrl, shouldOpenInNewTab: true)
                        ->button()
                        ->color('danger'),
                ])
                ->sendToDatabase($user);
        }
    }

    /**
     * Notify user without download
     */
    protected function notifyUser(string $title, string $body, string $type = 'info'): void
    {
        $user = User::find($this->userId);
        
        if ($user) {
            $notification = Notification::make()
                ->title($title)
                ->body($body)
                ->persistent();

            match($type) {
                'success' => $notification->success(),
                'warning' => $notification->warning(),
                'danger' => $notification->danger(),
                default => $notification->info(),
            };

            $notification->sendToDatabase($user);
        }
    }

    /**
     * Schedule file deletion after 24 hours
     */
    protected function scheduleFileDeletion(string $path): void
    {
        // Dispatch a job to delete the file after 24 hours
        DeleteExportFile::dispatch($path)->delay(now()->addHours(24));
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PDF export job failed', [
            'error' => $exception->getMessage(),
            'file' => $this->fileName
        ]);

        $this->notifyUser(
            'Export Failed',
            'The export job failed after multiple attempts. Please contact support if the issue persists.',
            'danger'
        );
    }
}
