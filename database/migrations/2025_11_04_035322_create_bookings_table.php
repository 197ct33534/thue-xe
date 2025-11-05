<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 50)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained();
            $table->foreignId('promotion_id')->nullable()->constrained();
            $table->text('pickup_location');
            $table->text('return_location')->nullable();
            $table->dateTime('pickup_date');
            $table->dateTime('return_date');
            $table->integer('total_days')->storedAs('TIMESTAMPDIFF(DAY, pickup_date, return_date)');
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'refunded'])
                  ->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['pickup_date', 'return_date']);
        });

        // Tạo bảng phụ để lưu số thứ tự
        Schema::create('booking_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement();
            $table->string('code', 50)->unique();
        });

        // Insert giá trị đầu tiên
        DB::table('booking_sequences')->insert(['code' => 'BK00000000']);

        // Tạo trigger MySQL để tự động sinh booking_code
        DB::unprepared("
            CREATE TRIGGER trg_bookings_before_insert
            BEFORE INSERT ON bookings
            FOR EACH ROW
            BEGIN
                DECLARE next_num INT;
                DECLARE new_code VARCHAR(50);

                -- Lấy và tăng số
                UPDATE booking_sequences 
                SET id = LAST_INSERT_ID(id + 1), 
                    code = CONCAT('BK', LPAD(LAST_INSERT_ID(id + 1), 8, '0'))
                WHERE id = (SELECT MAX(id) FROM (SELECT id FROM booking_sequences) AS t);

                SET next_num = LAST_INSERT_ID();
                SET new_code = CONCAT('BK', LPAD(next_num, 8, '0'));

                SET NEW.booking_code = new_code;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS trg_bookings_before_insert");
        Schema::dropIfExists('booking_sequences');
        Schema::dropIfExists('bookings');
    }
};