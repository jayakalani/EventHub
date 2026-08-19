<?php

namespace Tests\Unit;

use App\Support\SriLankaDistricts;
use PHPUnit\Framework\TestCase;

class SriLankaDistrictsTest extends TestCase
{
    public function test_it_maps_trailing_district_names(): void
    {
        $this->assertSame('Kurunegala', SriLankaDistricts::resolve('54 Kandy Road, Kurunegala'));
        $this->assertSame('Colombo', SriLankaDistricts::resolve('12 Temple Road, Colombo'));
        $this->assertSame('Nuwara Eliya', SriLankaDistricts::resolve('8 Lake Road, Nuwara Eliya'));
    }

    public function test_it_maps_town_aliases_without_commas(): void
    {
        $this->assertSame('Colombo', SriLankaDistricts::resolve('26/5 Liyanage Avenue Nawala'));
        $this->assertSame('Gampaha', SriLankaDistricts::resolve('Negombo'));
    }

    public function test_it_does_not_treat_street_names_as_districts(): void
    {
        $this->assertSame('Colombo', SriLankaDistricts::resolve('Kandy Road, Colombo'));
        $this->assertSame('Galle', SriLankaDistricts::resolve('15 Galle Road, Galle'));
    }

    public function test_it_returns_null_for_blank_addresses(): void
    {
        $this->assertNull(SriLankaDistricts::resolve(null));
        $this->assertNull(SriLankaDistricts::resolve('   '));
        $this->assertNull(SriLankaDistricts::resolve('Unknown overseas address'));
    }

    public function test_it_lists_all_twenty_five_districts(): void
    {
        $this->assertCount(25, SriLankaDistricts::NAMES);
    }

    public function test_it_maps_districts_to_provinces(): void
    {
        $this->assertCount(9, SriLankaDistricts::PROVINCES);
        $this->assertSame('Western', SriLankaDistricts::provinceFor('Colombo'));
        $this->assertSame('North Western', SriLankaDistricts::provinceFor('Kurunegala'));
        $this->assertSame('Northern', SriLankaDistricts::provinceFor('Jaffna'));
        $this->assertSame('Sabaragamuwa', SriLankaDistricts::provinceFor('Ratnapura'));
        $this->assertNull(SriLankaDistricts::provinceFor(null));
    }
}
