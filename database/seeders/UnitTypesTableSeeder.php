<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UnitTypesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('unit_types')->delete();

        \DB::table('unit_types')->insert(array (
            0 =>
            array (
                'id' => 1,
                'name_short' => 'piece',
                'name_long' => 'pieces',
                'created_at' => null,
                'updated_at' => null,
            ),
            1 =>
            array (
                'id' => 2,
                'name_short' => 'pax',
                'name_long' => 'pax',
                'created_at' => null,
                'updated_at' => null,
            ),
            2 =>
            array (
                'id' => 3,
                'name_short' => 'unit',
                'name_long' => 'units',
                'created_at' => null,
                'updated_at' => null,
            ),
            3 =>
            array (
                'id' => 4,
                'name_short' => 'bottle',
                'name_long' => 'bottles',
                'created_at' => null,
                'updated_at' => null,
            ),
            4 =>
            array (
                'id' => 5,
                'name_short' => 'vial',
                'name_long' => 'vials',
                'created_at' => null,
                'updated_at' => null,
            ),
            5 =>
            array (
                'id' => 6,
                'name_short' => 'tube',
                'name_long' => 'tubes',
                'created_at' => null,
                'updated_at' => null,
            ),
            6 =>
            array (
                'id' => 7,
                'name_short' => 'test tub',
                'name_long' => 'test tub',
                'created_at' => null,
                'updated_at' => null,
            ),
            7 =>
            array (
                'id' => 8,
                'name_short' => 'tank',
                'name_long' => 'tanks',
                'created_at' => null,
                'updated_at' => null,
            ),
            8 =>
            array (
                'id' => 9,
                'name_short' => 'set',
                'name_long' => 'sets',
                'created_at' => null,
                'updated_at' => null,
            ),
            9 =>
            array (
                'id' => 10,
                'name_short' => 'service',
                'name_long' => 'services',
                'created_at' => null,
                'updated_at' => null,
            ),
            10 =>
            array (
                'id' => 11,
                'name_short' => 'sack',
                'name_long' => 'sacks',
                'created_at' => null,
                'updated_at' => null,
            ),
            11 =>
            array (
                'id' => 12,
                'name_short' => 'room',
                'name_long' => 'rooms',
                'created_at' => null,
                'updated_at' => null,
            ),
            12 =>
            array (
                'id' => 13,
                'name_short' => 'rool',
                'name_long' => 'rools',
                'created_at' => null,
                'updated_at' => null,
            ),
            13 =>
            array (
                'id' => 14,
                'name_short' => 'rms',
                'name_long' => 'rms',
                'created_at' => null,
                'updated_at' => null,
            ),
            14 =>
            array (
                'id' => 15,
                'name_short' => 'ream',
                'name_long' => 'reams',
                'created_at' => null,
                'updated_at' => null,
            ),
            15 =>
            array (
                'id' => 16,
                'name_short' => 'pcs',
                'name_long' => 'pcs',
                'created_at' => null,
                'updated_at' => null,
            ),
            16 =>
            array (
                'id' => 17,
                'name_short' => 'pax',
                'name_long' => 'pax',
                'created_at' => null,
                'updated_at' => null,
            ),
            17 =>
            array (
                'id' => 18,
                'name_short' => 'pair',
                'name_long' => 'pairs',
                'created_at' => null,
                'updated_at' => null,
            ),
            18 =>
            array (
                'id' => 19,
                'name_short' => 'pad',
                'name_long' => 'pads',
                'created_at' => null,
                'updated_at' => null,
            ),
            19 =>
            array (
                'id' => 20,
                'name_short' => 'pack',
                'name_long' => 'packs',
                'created_at' => null,
                'updated_at' => null,
            ),
            20 =>
            array (
                'id' => 21,
                'name_short' => 'mtr',
                'name_long' => 'mtrs',
                'created_at' => null,
                'updated_at' => null,
            ),
            21 =>
            array (
                'id' => 22,
                'name_short' => 'meter',
                'name_long' => 'meters',
                'created_at' => null,
                'updated_at' => null,
            ),
            22 =>
            array (
                'id' => 23,
                'name_short' => 'lot',
                'name_long' => 'lots',
                'created_at' => null,
                'updated_at' => null,
            ),
            23 =>
            array (
                'id' => 24,
                'name_short' => 'loave',
                'name_long' => 'loaves',
                'created_at' => null,
                'updated_at' => null,
            ),
            24 =>
            array (
                'id' => 25,
                'name_short' => 'liter',
                'name_long' => 'liters',
                'created_at' => null,
                'updated_at' => null,
            ),
        ));
    }
}
