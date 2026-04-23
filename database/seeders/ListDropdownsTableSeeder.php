<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ListDropdownsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('list_dropdowns')->delete();
        
        \DB::table('list_dropdowns')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'n/a',
                'classification' => 'n/a',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Office of the Regional Director',
                'classification' => 'Division',
                'type' => 'Main',
                'color' => 'n/a',
                'others' => 'ORD',
                'is_active' => 1,
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Finance and Administrative Support Services',
                'classification' => 'Division',
                'type' => 'Main',
                'color' => 'n/a',
                'others' => 'FASS',
                'is_active' => 1,
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Technical Operations Services',
                'classification' => 'Division',
                'type' => 'Main',
                'color' => 'n/a',
                'others' => 'TOS',
                'is_active' => 1,
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Regional Office',
                'classification' => 'Station',
                'type' => 'Regional Office',
                'color' => 'n/a',
                'others' => 'RO-IX',
                'is_active' => 1,
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Zamboanga Sibugay',
                'classification' => 'Station',
                'type' => 'Provincial Science and Technology Office',
                'color' => 'n/a',
                'others' => 'PSTO-ZSP',
                'is_active' => 1,
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'Zamboanga Del Norte',
                'classification' => 'Station',
                'type' => 'Provincial Science and Technology Office',
                'color' => 'n/a',
                'others' => 'PSTO-ZDN',
                'is_active' => 1,
            ),
            7 => 
            array (
                'id' => 8,
                'name' => 'Zamboanga Del Sur',
                'classification' => 'Station',
                'type' => 'Provincial Science and Technology Office',
                'color' => 'n/a',
                'others' => 'PSTO-ZDS',
                'is_active' => 1,
            ),
            8 => 
            array (
                'id' => 9,
                'name' => 'Zamboanga City / Isabela City',
                'classification' => 'Station',
                'type' => 'City Science & Technology Center',
                'color' => 'n/a',
                'others' => 'CSTC-ZCIC',
                'is_active' => 1,
            ),
            9 => 
            array (
                'id' => 10,
                'name' => 'First Semester',
                'classification' => 'Period',
                'type' => 'Semester',
                'color' => 'n/a',
                'others' => '1-6',
                'is_active' => 1,
            ),
            10 => 
            array (
                'id' => 11,
                'name' => 'Second Semester',
                'classification' => 'Period',
                'type' => 'Semester',
                'color' => 'n/a',
                'others' => '7-12',
                'is_active' => 1,
            ),
            11 => 
            array (
                'id' => 12,
                'name' => 'First Quarter',
                'classification' => 'Period',
                'type' => 'Quarter',
                'color' => 'n/a',
                'others' => '1-3',
                'is_active' => 1,
            ),
            12 => 
            array (
                'id' => 13,
                'name' => 'Second Quarter',
                'classification' => 'Period',
                'type' => 'Quarter',
                'color' => 'n/a',
                'others' => '4-6',
                'is_active' => 1,
            ),
            13 => 
            array (
                'id' => 14,
                'name' => 'Third Quarter',
                'classification' => 'Period',
                'type' => 'Quarter',
                'color' => 'n/a',
                'others' => '7-9',
                'is_active' => 1,
            ),
            14 => 
            array (
                'id' => 15,
                'name' => 'Fourth Quarter',
                'classification' => 'Period',
                'type' => 'Quarter',
                'color' => 'n/a',
                'others' => '10-12',
                'is_active' => 1,
            ),
            15 => 
            array (
                'id' => 16,
                'name' => 'Within the Philippines',
                'classification' => 'Leave Details',
                'type' => 'Vacation Leave, Special Privilege Leave',
                'color' => 'n/a',
                'others' => 'specify',
                'is_active' => 1,
            ),
            16 => 
            array (
                'id' => 17,
                'name' => 'Abroad',
                'classification' => 'Leave Details',
                'type' => 'Vacation Leave, Special Privilege Leave',
                'color' => 'n/a',
                'others' => 'specify',
                'is_active' => 1,
            ),
            17 => 
            array (
                'id' => 18,
                'name' => 'In Hospital',
                'classification' => 'Leave Details',
                'type' => 'Sick Leave',
                'color' => 'n/a',
                'others' => 'specify illness',
                'is_active' => 1,
            ),
            18 => 
            array (
                'id' => 19,
                'name' => 'Out Patient',
                'classification' => 'Leave Details',
                'type' => 'Sick Leave',
                'color' => 'n/a',
                'others' => 'specify illness',
                'is_active' => 1,
            ),
            19 => 
            array (
                'id' => 20,
                'name' => 'Completion of Master\'s Degree',
                'classification' => 'Leave Details',
                'type' => 'Study Leave',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            20 => 
            array (
                'id' => 21,
                'name' => 'BAR/Board Examination Review',
                'classification' => 'Leave Details',
                'type' => 'Study Leave',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            21 => 
            array (
                'id' => 22,
                'name' => 'Monetization of Leave Credits',
                'classification' => 'Leave Details',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            22 => 
            array (
                'id' => 23,
                'name' => 'Terminal Leave',
                'classification' => 'Leave Details',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            23 => 
            array (
                'id' => 24,
                'name' => 'Others',
                'classification' => 'Leave Details',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            24 => 
            array (
                'id' => 26,
                'name' => 'Official Travel ',
                'classification' => 'Calendar',
                'type' => 'official',
                'color' => 'text-primary',
                'others' => 'bg-primary-subtle',
                'is_active' => 1,
            ),
            25 => 
            array (
                'id' => 27,
                'name' => 'Leave',
                'classification' => 'Calendar',
                'type' => 'personal',
                'color' => 'text-danger',
                'others' => 'bg-danger-subtle',
                'is_active' => 1,
            ),
            26 => 
            array (
                'id' => 28,
                'name' => 'Meeting',
                'classification' => 'Calendar',
                'type' => 'official',
                'color' => 'text-info',
                'others' => 'bg-info-subtle',
                'is_active' => 1,
            ),
            27 => 
            array (
                'id' => 29,
                'name' => 'Audit',
                'classification' => 'Calendar',
                'type' => 'official',
                'color' => 'text-warning',
                'others' => 'bg-warning-subtle',
                'is_active' => 1,
            ),
            28 => 
            array (
                'id' => 30,
                'name' => 'Review',
                'classification' => 'Calendar',
                'type' => 'official',
                'color' => 'text-primary',
                'others' => 'bg-primary-subtle',
                'is_active' => 1,
            ),
            29 => 
            array (
                'id' => 31,
                'name' => 'Holiday',
                'classification' => 'Calendar',
                'type' => 'n/a',
                'color' => 'text-dark',
                'others' => 'bg-dark-subtle',
                'is_active' => 1,
            ),
            30 => 
            array (
                'id' => 32,
                'name' => 'Others',
                'classification' => 'Calendar',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            31 => 
            array (
                'id' => 33,
                'name' => 'Reason',
                'classification' => 'Leave Details',
                'type' => 'Absent',
                'color' => 'n/a',
                'others' => 'specify reason',
                'is_active' => 1,
            ),
            32 => 
            array (
                'id' => 34,
                'name' => 'In case of SLBW',
                'classification' => 'Leave Details',
                'type' => 'Absent',
                'color' => 'n/a',
                'others' => 'specify illness',
                'is_active' => 1,
            ),
            33 => 
            array (
                'id' => 35,
                'name' => 'Security Guards',
                'classification' => 'Division',
                'type' => 'Sub',
                'color' => 'n/a',
                'others' => 'Agency',
                'is_active' => 1,
            ),
            34 => 
            array (
                'id' => 36,
                'name' => 'Regular Salary ',
                'classification' => 'Payroll',
                'type' => 'Regular',
                'color' => 'n/a',
                'others' => 'monthly',
                'is_active' => 1,
            ),
            35 => 
            array (
                'id' => 37,
                'name' => 'Contractual Salary',
                'classification' => 'Payroll',
                'type' => 'Contractual',
                'color' => 'n/a',
                'others' => 'monthly',
                'is_active' => 1,
            ),
            36 => 
            array (
                'id' => 38,
                'name' => 'Subsistence Allowance',
                'classification' => 'Payroll',
                'type' => 'Regular',
                'color' => 'n/a',
                'others' => 'monthly',
                'is_active' => 1,
            ),
            37 => 
            array (
                'id' => 39,
                'name' => 'Hazard Pay',
                'classification' => 'Payroll',
                'type' => 'Regular',
                'color' => 'n/a',
                'others' => 'monthly',
                'is_active' => 1,
            ),
            38 => 
            array (
                'id' => 40,
                'name' => 'Longevity Pay',
                'classification' => 'Payroll',
                'type' => 'Regular',
                'color' => 'n/a',
                'others' => 'monthly',
                'is_active' => 1,
            ),
            39 => 
            array (
                'id' => 41,
                'name' => 'PERA/RATA',
                'classification' => 'Payroll',
                'type' => 'Regular',
                'color' => 'n/a',
                'others' => 'monthly',
                'is_active' => 1,
            ),
            40 => 
            array (
                'id' => 42,
                'name' => 'Performance Based Bonus',
                'classification' => 'Payroll',
                'type' => 'Regular',
                'color' => 'n/a',
                'others' => 'yearly',
                'is_active' => 1,
            ),
            41 => 
            array (
                'id' => 43,
                'name' => 'Regional Director',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'RD',
                'is_active' => 1,
            ),
            42 => 
            array (
                'id' => 44,
                'name' => 'Assistant Regional Director',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'ARD',
                'is_active' => 1,
            ),
            43 => 
            array (
                'id' => 45,
                'name' => 'Provincial Director',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'PD',
                'is_active' => 1,
            ),
            44 => 
            array (
                'id' => 46,
                'name' => 'City Director',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            45 => 
            array (
                'id' => 47,
                'name' => 'Supervisor',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            46 => 
            array (
                'id' => 48,
                'name' => 'HRMO',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            47 => 
            array (
                'id' => 49,
                'name' => 'BAC Chairperson',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'BAC-CHAIR',
                'is_active' => 1,
            ),
            48 => 
            array (
                'id' => 50,
                'name' => 'BAC Vice Chairperson',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'BAC-VICE',
                'is_active' => 1,
            ),
            49 => 
            array (
                'id' => 51,
                'name' => 'BAC Member',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'BAC-MEMBER',
                'is_active' => 1,
            ),
            50 => 
            array (
                'id' => 52,
                'name' => 'IAR Chairperson',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'IAR-CHAIR',
                'is_active' => 1,
            ),
            52 => 
            array (
                'id' => 54,
                'name' => 'IAR Member',
                'classification' => 'Designation',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'IAR-MEMBER',
                'is_active' => 1,
            ),
            53 => 
            array (
                'id' => 55,
                'name' => 'Annual Procurement Plan',
                'classification' => 'APP Type',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            54 => 
            array (
                'id' => 56,
                'name' => 'Supplemental Procurement Plan',
                'classification' => 'APP Type',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            55 => 
            array (
                'id' => 57,
                'name' => 'Competitive Public Bidding',
                'classification' => 'mode_of_procurement',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'R.A. 9184',
                'is_active' => 1,
            ),
            56 => 
            array (
                'id' => 58,
                'name' => 'Limited Source Bidding',
                'classification' => 'mode_of_procurement',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            57 => 
            array (
                'id' => 59,
                'name' => 'Regular Fund',
                'classification' => 'Fund Cluster',
                'type' => 'RF',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            58 => 
            array (
                'id' => 60,
                'name' => 'Trust Fund',
                'classification' => 'Fund Cluster',
                'type' => 'TF',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            59 => 
            array (
                'id' => 61,
                'name' => 'MDS Trust Fund',
                'classification' => 'Fund Cluster',
                'type' => 'MDS-TF',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            60 => 
            array (
                'id' => 62,
                'name' => 'DOST IX Pettit Barracks Zamboanga City',
                'classification' => 'Place of Delivery',
                'type' => '',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            61 => 
            array (
                'id' => 63,
                'name' => 'DOST IX ZDS',
                'classification' => 'Place of Delivery',
                'type' => '',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            62 => 
            array (
                'id' => 64,
                'name' => 'Chief Accountant',
                'classification' => 'Designation',
                'type' => '',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            63 => 
            array (
                'id' => 65,
                'name' => 'Goods and Services',
                'classification' => 'Classification',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            64 => 
            array (
                'id' => 66,
                'name' => 'Infrastructure Services',
                'classification' => 'Classification',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
            65 => 
            array (
                'id' => 67,
                'name' => 'Consulting Services',
                'classification' => 'Classification',
                'type' => 'n/a',
                'color' => 'n/a',
                'others' => 'n/a',
                'is_active' => 1,
            ),
        ));
        
        
    }
}
