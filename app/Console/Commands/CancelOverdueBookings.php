<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BookingService;

class CancelOverdueBookings extends Command
{
    protected $signature = 'bookings:cancel-overdue';
    protected $description = 'Cancel bookings with overdue deposits';

    private BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        parent::__construct();
        $this->bookingService = $bookingService;
    }

    public function handle()
    {
        $cancelledCount = $this->bookingService->cancelOverdueBookings();
        
        $this->info("Cancelled {$cancelledCount} overdue bookings");
        
        return Command::SUCCESS;
    }
}