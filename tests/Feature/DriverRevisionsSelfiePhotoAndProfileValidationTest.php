<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriverRevisionsSelfiePhotoAndProfileValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $sales;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->driver = User::factory()->create([
            'name' => 'Budi Driver',
            'username' => 'budidriver',
            'email' => 'driver@company.co.id',
            'phone' => '081234567890',
        ]);
        $this->driver->assignRole('driver');

        $this->sales = User::factory()->create([
            'name' => 'Andi Sales',
            'username' => 'andisales',
            'email' => 'sales@company.co.id',
            'phone' => '08123456789',
        ]);
        $this->sales->assignRole('sales');

        $this->store = Store::create([
            'name' => 'Toko Sumber Rezeki',
            'code' => 'TKO-SR-001',
            'address' => 'Jl. Merdeka No. 10',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'sales_penanggung_jawab_id' => $this->sales->id,
            'is_delivery_destination' => true,
            'status' => 'active',
        ]);
    }

    private function dummyBase64Image(): string
    {
        $img = imagecreatetruecolor(10, 10);
        $bg = imagecolorallocate($img, 13, 164, 206);
        imagefill($img, 0, 0, $bg);
        ob_start();
        imagejpeg($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return 'data:image/jpeg;base64,'.base64_encode($data);
    }

    /**
     * TEST 1: Driver profile email validation - VALID formats
     */
    public function test_driver_profile_accepts_valid_email_formats(): void
    {
        $validEmails = [
            'user@gmail.com',
            'user@yahoo.com',
            'user@outlook.com',
            'nama@company.co.id',
            'driver.budi@perusahaan.id',
            'budi_123@sub.domain.co.id',
        ];

        foreach ($validEmails as $email) {
            $response = $this
                ->actingAs($this->driver)
                ->patch('/profile', [
                    'name' => 'Budi Driver',
                    'username' => 'budidriver',
                    'email' => $email,
                    'phone' => '081234567890',
                ]);

            $response->assertSessionHasNoErrors();
            $this->driver->refresh();
            $this->assertSame(strtolower($email), $this->driver->email);
        }
    }

    /**
     * TEST 2: Driver profile email validation - INVALID formats
     */
    public function test_driver_profile_rejects_invalid_email_formats(): void
    {
        $invalidEmails = [
            'gerar@',
            '@gmail.com',
            'gerar@gmail',
            'gerar gmail.com',
            'gerar@@gmail.com',
            'gerar@.com',
            'gerar@com',
            'string_tanpa_at',
            'gerar@gmail.',
            'gerar@gmail.c',
        ];

        foreach ($invalidEmails as $email) {
            $response = $this
                ->actingAs($this->driver)
                ->patch('/profile', [
                    'name' => 'Budi Driver',
                    'username' => 'budidriver',
                    'email' => $email,
                    'phone' => '081234567890',
                ]);

            $response->assertSessionHasErrors('email');
        }
    }

    /**
     * TEST 3: Driver profile phone validation - VALID numbers (12-13 digits, starting with 08)
     */
    public function test_driver_profile_accepts_valid_phone_numbers(): void
    {
        // 12 digits
        $response12 = $this
            ->actingAs($this->driver)
            ->patch('/profile', [
                'name' => 'Budi Driver',
                'username' => 'budidriver',
                'email' => 'driver@company.co.id',
                'phone' => '081234567890',
            ]);
        $response12->assertSessionHasNoErrors();
        $this->assertSame('081234567890', $this->driver->refresh()->phone);

        // 13 digits
        $response13 = $this
            ->actingAs($this->driver)
            ->patch('/profile', [
                'name' => 'Budi Driver',
                'username' => 'budidriver',
                'email' => 'driver@company.co.id',
                'phone' => '0812345678901',
            ]);
        $response13->assertSessionHasNoErrors();
        $this->assertSame('0812345678901', $this->driver->refresh()->phone);

        // Optional (empty / null)
        $responseEmpty = $this
            ->actingAs($this->driver)
            ->patch('/profile', [
                'name' => 'Budi Driver',
                'username' => 'budidriver',
                'email' => 'driver@company.co.id',
                'phone' => '',
            ]);
        $responseEmpty->assertSessionHasNoErrors();
        $this->assertNull($this->driver->refresh()->phone);
    }

    /**
     * TEST 4: Driver profile phone validation - INVALID numbers
     */
    public function test_driver_profile_rejects_invalid_phone_numbers(): void
    {
        $invalidPhones = [
            '08',
            '0812',
            '0812345678', // 10 digits
            '08123456789', // 11 digits
            '08123456789012', // 14 digits
            'abc',
            '08abc1234567',
            '+6281234567890',
            '0812 3456 7890',
            '0812-3456-7890',
        ];

        foreach ($invalidPhones as $phone) {
            $response = $this
                ->actingAs($this->driver)
                ->patch('/profile', [
                    'name' => 'Budi Driver',
                    'username' => 'budidriver',
                    'email' => 'driver@company.co.id',
                    'phone' => $phone,
                ]);

            $response->assertSessionHasErrors('phone');
        }
    }

    /**
     * TEST 5: Non-driver profile remains functional
     */
    public function test_non_driver_profile_remains_functional(): void
    {
        $response = $this
            ->actingAs($this->sales)
            ->patch('/profile', [
                'name' => 'Andi Sales Updated',
                'username' => 'andisales',
                'email' => 'sales.updated@company.co.id',
                'phone' => '081234567890', // 12 digits
            ]);

        $response->assertSessionHasNoErrors();
        $this->sales->refresh();
        $this->assertSame('Andi Sales Updated', $this->sales->name);
        $this->assertSame('sales.updated@company.co.id', $this->sales->email);
    }

    /**
     * TEST 6: Driver Check-in with selfie photo upload
     */
    public function test_driver_checkin_with_selfie_succeeds(): void
    {
        $route = Route::create([
            'name' => 'Rute Driver 1',
            'user_id' => $this->driver->id,
            'date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);

        $routeStop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $selfie = $this->dummyBase64Image();

        $response = $this
            ->actingAs($this->driver)
            ->postJson(route('visit.store'), [
                'route_stop_id' => $routeStop->id,
                'route_id' => $route->id,
                'store_id' => $this->store->id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'address' => 'Jl. Merdeka No. 10',
                'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
                'selfie' => $selfie,
                'initial_notes' => 'Catatan awal pengiriman',
            ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $visit = Visit::where('route_stop_id', $routeStop->id)->first();
        $this->assertNotNull($visit);
        $this->assertNotNull($visit->check_in_selfie);
        Storage::disk('public')->assertExists($visit->check_in_selfie);
    }

    /**
     * TEST 7: Driver Check-out with multiple photos (up to 6)
     */
    public function test_driver_checkout_with_multiple_photos_succeeds(): void
    {
        $route = Route::create([
            'name' => 'Rute Driver 1',
            'user_id' => $this->driver->id,
            'date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);

        $routeStop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        $selfie = $this->dummyBase64Image();

        $visit = Visit::create([
            'route_stop_id' => $routeStop->id,
            'route_id' => $route->id,
            'store_id' => $this->store->id,
            'user_id' => $this->driver->id,
            'check_in_at' => now(),
            'check_in_lat' => -6.200000,
            'check_in_lng' => 106.816666,
            'status' => 'in_progress',
        ]);

        $photos = [
            $this->dummyBase64Image(),
            $this->dummyBase64Image(),
            $this->dummyBase64Image(),
        ];

        $response = $this
            ->actingAs($this->driver)
            ->postJson(route('visit.check-out', $visit->id), [
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'address' => 'Jl. Merdeka No. 10',
                'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
                'visit_result' => 'Pengiriman barang selesai 3 koli',
                'transaction_status' => 'none',
                'photos' => $photos,
            ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $visit->refresh();
        $this->assertSame('completed', $visit->status);

        $savedPhotos = VisitPhoto::where('visit_id', $visit->id)->get();
        $this->assertCount(3, $savedPhotos);
        foreach ($savedPhotos as $vp) {
            Storage::disk('public')->assertExists($vp->photo_path);
        }
    }
}
