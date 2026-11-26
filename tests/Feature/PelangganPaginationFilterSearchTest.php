<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PelangganPaginationFilterSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name'     => 'Admin Test',
            'email'    => 'admintest@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'Super Admin',
        ]);

        $this->actingAs($user);
    }

    private function createPelangganRecords(int $count, string $gender = 'Male', string $prefix = 'Pelanggan'): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Pelanggan::create([
                'first_name' => "{$prefix}{$i}",
                'last_name'  => "User{$i}",
                'birthday'   => '2000-01-01',
                'gender'     => $gender,
                'email'      => strtolower("{$prefix}{$i}_{$gender}@example.com"),
                'phone'      => '08123456789',
            ]);
        }
    }

    public function test_pagination_displays_10_per_page_and_continuous_numbering_on_page_2(): void
    {
        $this->createPelangganRecords(15, 'Male', 'Alpha');

        $page1 = $this->get(route('pelanggan.index'));
        $page1->assertStatus(200);
        $page1->assertSee('Alpha1');
        $page1->assertDontSee('Alpha11');

        $page2 = $this->get(route('pelanggan.index', ['page' => 2]));
        $page2->assertStatus(200);
        $page2->assertSee('Alpha11');
        $page2->assertSee('Alpha15');
        $page2->assertDontSee('Alpha10');
        $page2->assertSee('<td>11</td>', false);
        $page2->assertSee('<td>15</td>', false);
    }

    public function test_filter_by_gender_and_preserve_query_string_in_pagination_links(): void
    {
        $this->createPelangganRecords(12, 'Female', 'Siti');
        $this->createPelangganRecords(5, 'Male', 'Budi');
        $this->createPelangganRecords(2, 'Other', 'Alex');

        $response = $this->get(route('pelanggan.index', ['gender' => 'Female']));
        $response->assertStatus(200);
        $response->assertSee('Siti1');
        $response->assertDontSee('Budi1');
        $response->assertDontSee('Alex1');
        $response->assertSee('gender=Female&amp;page=2', false);

        $otherResponse = $this->get(route('pelanggan.index', ['gender' => 'Other']));
        $otherResponse->assertStatus(200);
        $otherResponse->assertSee('Alex1');
        $otherResponse->assertDontSee('Siti1');
    }

    public function test_search_across_first_name_last_name_and_email_and_clear_link(): void
    {
        Pelanggan::create([
            'first_name' => 'Rina',
            'last_name'  => 'Kusuma',
            'birthday'   => '1999-05-10',
            'gender'     => 'Female',
            'email'      => 'rina.unique@example.com',
            'phone'      => '0811111111',
        ]);

        Pelanggan::create([
            'first_name' => 'Doni',
            'last_name'  => 'Pratama',
            'birthday'   => '1998-03-20',
            'gender'     => 'Male',
            'email'      => 'doni@example.com',
            'phone'      => '0822222222',
        ]);

        $byFirstName = $this->get(route('pelanggan.index', ['search' => 'Rina']));
        $byFirstName->assertStatus(200);
        $byFirstName->assertSee('Rina');
        $byFirstName->assertDontSee('Doni');
        $byFirstName->assertSee('id="clear-search"', false);

        $byLastName = $this->get(route('pelanggan.index', ['search' => 'Pratama']));
        $byLastName->assertStatus(200);
        $byLastName->assertSee('Doni');
        $byLastName->assertDontSee('Rina');

        $byEmail = $this->get(route('pelanggan.index', ['search' => 'rina.unique']));
        $byEmail->assertStatus(200);
        $byEmail->assertSee('Rina');
        $byEmail->assertDontSee('Doni');
    }
}
