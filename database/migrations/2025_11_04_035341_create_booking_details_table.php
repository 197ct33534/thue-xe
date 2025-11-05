<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->decimal('price_per_day', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total_price', 12, 2)->nullable(); // Sẽ được trigger tính
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['booking_id', 'vehicle_id']);
        });

        // Trigger: Tính total_price khi INSERT
        DB::unprepared("
            CREATE TRIGGER trg_booking_details_before_insert
            BEFORE INSERT ON booking_details
            FOR EACH ROW
            BEGIN
                DECLARE days_diff INT;
                SELECT TIMESTAMPDIFF(DAY, b.pickup_date, b.return_date) INTO days_diff
                FROM bookings b
                WHERE b.id = NEW.booking_id;

                SET NEW.total_price = NEW.price_per_day * NEW.quantity * days_diff;
            END
        ");

        // Trigger: Cập nhật khi UPDATE (nếu thay đổi price/quantity)
        DB::unprepared("
            CREATE TRIGGER trg_booking_details_before_update
            BEFORE UPDATE ON booking_details
            FOR EACH ROW
            BEGIN
                DECLARE days_diff INT;
                SELECT TIMESTAMPDIFF(DAY, b.pickup_date, b.return_date) INTO days_diff
                FROM bookings b
                WHERE b.id = NEW.booking_id;

                SET NEW.total_price = NEW.price_per_day * NEW.quantity * days_diff;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS trg_booking_details_before_update");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_booking_details_before_insert");
        Schema::dropIfExists('booking_details');
    }
};