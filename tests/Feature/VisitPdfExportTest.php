<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitPdfExportTest extends TestCase
{
    use RefreshDatabase;

    private function salesUser(): User
    {
        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('sales');

        return $user;
    }

    private function store(string $name): Store
    {
        return Store::create([
            'code' => 'TST-' . strtoupper(substr(uniqid(), -4)),
            'name' => $name,
            'type' => 'toko',
            'address' => 'Jl. Test No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'status' => 'active',
        ]);
    }

    private function route(User $user, string $storeName = 'Toko Test'): Route
    {
        return Route::create([
            'user_id' => $user->id,
            'name' => 'Rute Test',
            'date' => now()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function visit(User $user, array $overrides = []): Visit
    {
        $store = $this->store($overrides['store_name'] ?? 'Toko Test');
        $route = $this->route($user);
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'visited',
        ]);

        $attributes = array_merge([
            'user_id' => $user->id,
            'route_stop_id' => $stop->id,
            'route_id' => $route->id,
            'store_id' => $store->id,
            'status' => 'completed',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now(),
            'check_in_lat' => -6.2088,
            'check_in_lng' => 106.8456,
            'check_in_maps_url' => 'https://maps.google.com/?q=-6.2088,106.8456',
            'delivered_goods' => 'Produk A',
            'cash_received' => 150000,
            'visit_result' => 'Berhasil',
            'final_notes' => 'Catatan akhir kunjungan',
        ], $overrides);

        unset($attributes['store_name']);

        return Visit::create($attributes);
    }

    private function skippedStop(User $user, string $storeName = 'Toko Dilewati'): RouteStop
    {
        $store = $this->store($storeName);
        $route = $this->route($user);

        return RouteStop::create([
            'route_id' => $route->id,
            'store_id' => $store->id,
            'sequence' => 1,
            'status' => 'skipped',
            'notes' => 'Toko sedang tutup',
        ]);
    }

    private function pdfPageCount(string $content): int
    {
        preg_match_all('/\/Type\s*\/Page(?![a-zA-Z])/', $content, $matches);

        return count($matches[0]);
    }

    private function pdfStreams(string $content): array
    {
        $streams = [];
        preg_match_all('/\/Length\s+(\d+)\s*>>\s*stream\r?\n/s', $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $i => $match) {
            $length = (int) $matches[1][$i][0];
            $start = $match[1] + strlen($match[0]);
            $streams[] = substr($content, $start, $length);
        }

        return $streams;
    }

    private function pdfText(string $content): string
    {
        $text = '';

        foreach ($this->pdfStreams($content) as $stream) {
            $decoded = @gzuncompress($stream);
            if ($decoded === false) {
                $decoded = @gzinflate($stream);
            }
            if ($decoded !== false) {
                $text .= str_replace("\x00", '', $decoded);
            }
        }

        return $text;
    }

    public function test_detail_pdf_is_downloadable_for_completed_visit(): void
    {
        $user = $this->salesUser();
        $visit = $this->visit($user);

        $response = $this->actingAs($user)->get(route('visit.pdf.detail', $visit->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
        $this->assertSame(2, $this->pdfPageCount($content));
        $this->assertStringContainsString($visit->store->name, $this->pdfText($content));
    }

    public function test_detail_pdf_rejected_for_incomplete_visit(): void
    {
        $user = $this->salesUser();
        $visit = $this->visit($user, [
            'status' => 'in_progress',
            'check_out_at' => null,
        ]);

        $this->actingAs($user)->get(route('visit.pdf.detail', $visit->id))->assertNotFound();
    }

    public function test_detail_pdf_rejected_when_check_in_missing(): void
    {
        $user = $this->salesUser();
        $visit = $this->visit($user, [
            'check_in_at' => null,
        ]);

        $this->actingAs($user)->get(route('visit.pdf.detail', $visit->id))->assertNotFound();
    }

    public function test_show_page_shows_export_button_only_for_completed_visit(): void
    {
        $user = $this->salesUser();
        $completed = $this->visit($user);

        $this->actingAs($user)->get(route('visit.show', $completed->id))
            ->assertOk()
            ->assertSee('Export PDF Laporan Kunjungan');

        $inProgress = $this->visit($user, [
            'status' => 'in_progress',
            'check_out_at' => null,
        ]);

        $this->actingAs($user)->get(route('visit.show', $inProgress->id))
            ->assertOk()
            ->assertDontSee('Export PDF Laporan Kunjungan');
    }

    public function test_rekap_includes_completed_and_skipped_but_excludes_incomplete(): void
    {
        $user = $this->salesUser();
        $first = $this->visit($user, ['store_name' => 'Toko Selesai Satu']);
        $second = $this->visit($user, ['store_name' => 'Toko Selesai Dua']);
        $this->visit($user, [
            'store_name' => 'Toko Belum Selesai',
            'status' => 'in_progress',
            'check_out_at' => null,
        ]);
        $skipped = $this->skippedStop($user, 'Toko Dilewati');

        $from = now()->subDays(2)->toDateString();
        $to = now()->toDateString();

        $response = $this->actingAs($user)->get(route('visit.pdf.rekap', ['from_date' => $from, 'to_date' => $to]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
        $this->assertSame(5, $this->pdfPageCount($content));

        $text = $this->pdfText($content);
        $this->assertStringContainsString($first->store->name, $text);
        $this->assertStringContainsString($second->store->name, $text);
        $this->assertStringContainsString($skipped->store->name, $text);
        $this->assertStringNotContainsString('Toko Belum Selesai', $text);
    }

    public function test_rekap_respects_date_range(): void
    {
        $user = $this->salesUser();
        $this->visit($user, ['store_name' => 'Toko Bulan Lalu', 'check_in_at' => now()->subDays(10)]);
        $recent = $this->visit($user, ['store_name' => 'Toko Hari Ini']);

        $from = now()->subDays(2)->toDateString();
        $to = now()->toDateString();

        $response = $this->actingAs($user)->get(route('visit.pdf.rekap', ['from_date' => $from, 'to_date' => $to]));

        $response->assertOk();
        $text = $this->pdfText($response->getContent());
        $this->assertStringContainsString($recent->store->name, $text);
        $this->assertStringNotContainsString('Toko Bulan Lalu', $text);
    }

    public function test_rekap_requires_at_least_one_filter(): void
    {
        $user = $this->salesUser();

        $pdf = $this->actingAs($user)->get(route('visit.pdf.rekap'));
        $pdf->assertStatus(422);
        $pdf->assertJsonPath('status', 'error');

        $xlsx = $this->actingAs($user)->get(route('visit.excel.rekap'));
        $xlsx->assertStatus(422);
        $xlsx->assertJsonPath('status', 'error');
    }

    public function test_rekap_accepts_date_filter(): void
    {
        $user = $this->salesUser();
        $this->visit($user, ['store_name' => 'Toko Filter Tanggal']);

        $from = now()->subDays(2)->toDateString();
        $to = now()->toDateString();

        $this->actingAs($user)->get(route('visit.pdf.rekap', ['from_date' => $from, 'to_date' => $to]))->assertOk();
        $this->actingAs($user)->get(route('visit.excel.rekap', ['from_date' => $from, 'to_date' => $to]))->assertOk();
    }

    public function test_rekap_accepts_status_filter(): void
    {
        $user = $this->salesUser();
        $this->visit($user, ['store_name' => 'Toko Filter Status']);

        $this->actingAs($user)->get(route('visit.pdf.rekap', ['status' => 'completed']))->assertOk();
        $this->actingAs($user)->get(route('visit.excel.rekap', ['status' => 'completed']))->assertOk();
    }

    public function test_rekap_accepts_date_and_status_filter(): void
    {
        $user = $this->salesUser();
        $this->visit($user, ['store_name' => 'Toko Filter Gabungan']);

        $from = now()->subDays(2)->toDateString();
        $to = now()->toDateString();

        $this->actingAs($user)
            ->get(route('visit.pdf.rekap', ['from_date' => $from, 'to_date' => $to, 'status' => 'completed']))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('visit.excel.rekap', ['from_date' => $from, 'to_date' => $to, 'status' => 'completed']))
            ->assertOk();
    }

    public function test_rekap_uses_custom_filename(): void
    {
        $user = $this->salesUser();
        $this->visit($user);

        $from = now()->subDays(2)->toDateString();
        $to = now()->toDateString();
        $custom = 'Rekap Kunjungan Hadi Agustus 2026';

        $pdf = $this->actingAs($user)->get(route('visit.pdf.rekap', [
            'from_date' => $from, 'to_date' => $to, 'filename' => $custom,
        ]));
        $pdf->assertOk();
        $this->assertStringContainsString($custom . '.pdf', $pdf->headers->get('content-disposition'));

        $xlsx = $this->actingAs($user)->get(route('visit.excel.rekap', [
            'from_date' => $from, 'to_date' => $to, 'filename' => $custom,
        ]));
        $xlsx->assertOk();
        $this->assertStringContainsString($custom . '.xlsx', $xlsx->headers->get('content-disposition'));
    }
}
