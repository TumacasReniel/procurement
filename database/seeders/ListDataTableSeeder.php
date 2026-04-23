<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ListDataTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('list_data')->delete();
        
        \DB::table('list_data')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'n/a',
                'type' => 'n/a',
                'is_active' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Single',
                'type' => 'Marital Status',
                'is_active' => 1,
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Married',
                'type' => 'Marital Status',
                'is_active' => 1,
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Widowed',
                'type' => 'Marital Status',
                'is_active' => 1,
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Divorced',
                'type' => 'Marital Status',
                'is_active' => 1,
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Separated',
                'type' => 'Marital Status',
                'is_active' => 1,
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'A+',
                'type' => 'Blood Type',
                'is_active' => 1,
            ),
            7 => 
            array (
                'id' => 8,
                'name' => 'A-',
                'type' => 'Blood Type',
                'is_active' => 1,
            ),
            8 => 
            array (
                'id' => 9,
                'name' => 'B+',
                'type' => 'Blood Type',
                'is_active' => 1,
            ),
            9 => 
            array (
                'id' => 10,
                'name' => 'B-',
                'type' => 'Blood Type',
                'is_active' => 1,
            ),
            10 => 
            array (
                'id' => 11,
                'name' => 'AB+',
                'type' => 'Blood Type',
                'is_active' => 1,
            ),
            11 => 
            array (
                'id' => 12,
                'name' => 'AB-',
                'type' => 'Blood Type',
                'is_active' => 1,
            ),
            12 => 
            array (
                'id' => 13,
                'name' => 'O+',
                'type' => 'Blood Type',
                'is_active' => 1,
            ),
            13 => 
            array (
                'id' => 14,
                'name' => 'O-',
                'type' => 'Blood Type',
                'is_active' => 1,
            ),
            14 => 
            array (
                'id' => 15,
                'name' => 'Plantilla',
                'type' => 'Employment Status',
                'is_active' => 1,
            ),
            15 => 
            array (
                'id' => 16,
                'name' => 'Contract of Service',
                'type' => 'Employment Status',
                'is_active' => 1,
            ),
            16 => 
            array (
                'id' => 17,
                'name' => 'Job Order',
                'type' => 'Employment Status',
                'is_active' => 1,
            ),
            17 => 
            array (
                'id' => 18,
                'name' => 'Agency',
                'type' => 'Employment Status',
                'is_active' => 1,
            ),
            18 => 
            array (
                'id' => 19,
                'name' => 'Roman Catholic',
                'type' => 'Religion',
                'is_active' => 1,
            ),
            19 => 
            array (
                'id' => 20,
                'name' => 'Islam',
                'type' => 'Religion',
                'is_active' => 1,
            ),
            20 => 
            array (
                'id' => 21,
                'name' => 'Iglesia ni Cristo',
                'type' => 'Religion',
                'is_active' => 1,
            ),
            21 => 
            array (
                'id' => 22,
                'name' => 'Philippine Independent Church',
                'type' => 'Religion',
                'is_active' => 1,
            ),
            22 => 
            array (
                'id' => 23,
                'name' => 'Seventh-day Adventist',
                'type' => 'Religion',
                'is_active' => 1,
            ),
            23 => 
            array (
                'id' => 24,
                'name' => 'Bible Baptist Church',
                'type' => 'Religion',
                'is_active' => 1,
            ),
            24 => 
            array (
                'id' => 25,
                'name' => 'United Church of Christ in the Philippines',
                'type' => 'Religion',
                'is_active' => 1,
            ),
            25 => 
            array (
                'id' => 26,
                'name' => 'Church of Christ',
                'type' => 'Religion',
                'is_active' => 1,
            ),
            26 => 
            array (
                'id' => 27,
                'name' => 'Other Religious Affiliations',
                'type' => 'Religion',
                'is_active' => 1,
            ),
            27 => 
            array (
                'id' => 28,
                'name' => 'Career Service Professional Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            28 => 
            array (
                'id' => 29,
                'name' => 'Career Service Subprofessional Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            29 => 
            array (
                'id' => 30,
                'name' => 'Career Service Executive Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            30 => 
            array (
                'id' => 31,
                'name' => 'Bar and Board Examination Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            31 => 
            array (
                'id' => 32,
                'name' => 'Barangay Health Worker Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            32 => 
            array (
                'id' => 33,
                'name' => 'Barangay Nutrition Scholar Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            33 => 
            array (
                'id' => 34,
                'name' => 'Barangay Official Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            34 => 
            array (
                'id' => 35,
                'name' => 'Electronic Data Processing Specialist Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            35 => 
            array (
                'id' => 36,
                'name' => 'Foreign School Honor Graduate Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            36 => 
            array (
                'id' => 37,
                'name' => 'Honor Graduate Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            37 => 
            array (
                'id' => 38,
                'name' => 'Sanggunian Member Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            38 => 
            array (
                'id' => 39,
                'name' => 'Scientific and Technological Specialist Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            39 => 
            array (
                'id' => 40,
                'name' => 'Skills Eligibility – Category II',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            40 => 
            array (
                'id' => 41,
                'name' => 'Veteran Preference Rating Eligibility',
                'type' => 'Eligibility',
                'is_active' => 1,
            ),
            41 => 
            array (
                'id' => 42,
                'name' => 'Aeronautical Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            42 => 
            array (
                'id' => 43,
                'name' => 'Agricultural Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            43 => 
            array (
                'id' => 44,
                'name' => 'Agriculturist',
                'type' => 'License',
                'is_active' => 1,
            ),
            44 => 
            array (
                'id' => 45,
                'name' => 'Architect',
                'type' => 'License',
                'is_active' => 1,
            ),
            45 => 
            array (
                'id' => 46,
                'name' => 'Certified Mill Foreman',
                'type' => 'License',
                'is_active' => 1,
            ),
            46 => 
            array (
                'id' => 47,
                'name' => 'Certified Mine Foreman',
                'type' => 'License',
                'is_active' => 1,
            ),
            47 => 
            array (
                'id' => 48,
                'name' => 'Certified Plant Mechanic',
                'type' => 'License',
                'is_active' => 1,
            ),
            48 => 
            array (
                'id' => 49,
            'name' => 'Certified Public Accountant (CPA)',
                'type' => 'License',
                'is_active' => 1,
            ),
            49 => 
            array (
                'id' => 50,
                'name' => 'Certified Quarry Foreman',
                'type' => 'License',
                'is_active' => 1,
            ),
            50 => 
            array (
                'id' => 51,
                'name' => 'Chemical Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            51 => 
            array (
                'id' => 52,
                'name' => 'Chemist',
                'type' => 'License',
                'is_active' => 1,
            ),
            52 => 
            array (
                'id' => 53,
                'name' => 'Civil Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            53 => 
            array (
                'id' => 54,
                'name' => 'Criminologist',
                'type' => 'License',
                'is_active' => 1,
            ),
            54 => 
            array (
                'id' => 55,
                'name' => 'Custom Broker',
                'type' => 'License',
                'is_active' => 1,
            ),
            55 => 
            array (
                'id' => 56,
                'name' => 'Dental Hygienist',
                'type' => 'License',
                'is_active' => 1,
            ),
            56 => 
            array (
                'id' => 57,
                'name' => 'Dental Technologist',
                'type' => 'License',
                'is_active' => 1,
            ),
            57 => 
            array (
                'id' => 58,
                'name' => 'Dentist',
                'type' => 'License',
                'is_active' => 1,
            ),
            58 => 
            array (
                'id' => 59,
                'name' => 'Electronics & Communication Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            59 => 
            array (
                'id' => 60,
                'name' => 'Electronics Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            60 => 
            array (
                'id' => 61,
                'name' => 'Electronics Technician',
                'type' => 'License',
                'is_active' => 1,
            ),
            61 => 
            array (
                'id' => 62,
                'name' => 'Environmental Planner',
                'type' => 'License',
                'is_active' => 1,
            ),
            62 => 
            array (
                'id' => 63,
                'name' => 'Fisheries Technologist',
                'type' => 'License',
                'is_active' => 1,
            ),
            63 => 
            array (
                'id' => 64,
                'name' => 'Forester',
                'type' => 'License',
                'is_active' => 1,
            ),
            64 => 
            array (
                'id' => 65,
                'name' => 'Geodetic Aide',
                'type' => 'License',
                'is_active' => 1,
            ),
            65 => 
            array (
                'id' => 66,
                'name' => 'Geodetic Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            66 => 
            array (
                'id' => 67,
                'name' => 'Geologist',
                'type' => 'License',
                'is_active' => 1,
            ),
            67 => 
            array (
                'id' => 68,
                'name' => 'Guidance Counsellor',
                'type' => 'License',
                'is_active' => 1,
            ),
            68 => 
            array (
                'id' => 69,
                'name' => 'Interior Design',
                'type' => 'License',
                'is_active' => 1,
            ),
            69 => 
            array (
                'id' => 70,
                'name' => 'Landscape Architect',
                'type' => 'License',
                'is_active' => 1,
            ),
            70 => 
            array (
                'id' => 71,
                'name' => 'Librarian',
                'type' => 'License',
                'is_active' => 1,
            ),
            71 => 
            array (
                'id' => 72,
                'name' => 'Master Plumber',
                'type' => 'License',
                'is_active' => 1,
            ),
            72 => 
            array (
                'id' => 73,
                'name' => 'Mechanical Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            73 => 
            array (
                'id' => 74,
                'name' => 'Mechanical Plant Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            74 => 
            array (
                'id' => 75,
                'name' => 'Med. Lab. Tech',
                'type' => 'License',
                'is_active' => 1,
            ),
            75 => 
            array (
                'id' => 76,
                'name' => 'Medical Technologist',
                'type' => 'License',
                'is_active' => 1,
            ),
            76 => 
            array (
                'id' => 77,
                'name' => 'Metallurgical Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            77 => 
            array (
                'id' => 78,
                'name' => 'Metallurgical Plant Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            78 => 
            array (
                'id' => 79,
            'name' => 'Midwife (New)',
                'type' => 'License',
                'is_active' => 1,
            ),
            79 => 
            array (
                'id' => 80,
                'name' => 'Mining Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            80 => 
            array (
                'id' => 81,
                'name' => 'Naval Architect',
                'type' => 'License',
                'is_active' => 1,
            ),
            81 => 
            array (
                'id' => 82,
                'name' => 'Nurse',
                'type' => 'License',
                'is_active' => 1,
            ),
            82 => 
            array (
                'id' => 83,
                'name' => 'Nutritionist-Dietitian',
                'type' => 'License',
                'is_active' => 1,
            ),
            83 => 
            array (
                'id' => 84,
                'name' => 'Occupational Therapy',
                'type' => 'License',
                'is_active' => 1,
            ),
            84 => 
            array (
                'id' => 85,
                'name' => 'Occupational Therapy Technician',
                'type' => 'License',
                'is_active' => 1,
            ),
            85 => 
            array (
                'id' => 86,
                'name' => 'Optometrist',
                'type' => 'License',
                'is_active' => 1,
            ),
            86 => 
            array (
                'id' => 87,
                'name' => 'Pharmacist',
                'type' => 'License',
                'is_active' => 1,
            ),
            87 => 
            array (
                'id' => 88,
                'name' => 'Physical Therapy',
                'type' => 'License',
                'is_active' => 1,
            ),
            88 => 
            array (
                'id' => 89,
                'name' => 'Physical Therapy Technician',
                'type' => 'License',
                'is_active' => 1,
            ),
            89 => 
            array (
                'id' => 90,
                'name' => 'Physician',
                'type' => 'License',
                'is_active' => 1,
            ),
            90 => 
            array (
                'id' => 91,
                'name' => 'Professional Electrical Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            91 => 
            array (
                'id' => 92,
            'name' => 'Professional Electronics Engineer (PECE)',
                'type' => 'License',
                'is_active' => 1,
            ),
            92 => 
            array (
                'id' => 93,
                'name' => 'Professional Mechanical Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            93 => 
            array (
                'id' => 94,
                'name' => 'Professional Teacher',
                'type' => 'License',
                'is_active' => 1,
            ),
            94 => 
            array (
                'id' => 95,
                'name' => 'Radiologic Technologist',
                'type' => 'License',
                'is_active' => 1,
            ),
            95 => 
            array (
                'id' => 96,
                'name' => 'Real Estate Appraiser',
                'type' => 'License',
                'is_active' => 1,
            ),
            96 => 
            array (
                'id' => 97,
                'name' => 'Real Estate Broker',
                'type' => 'License',
                'is_active' => 1,
            ),
            97 => 
            array (
                'id' => 98,
                'name' => 'Real Estate Consultant',
                'type' => 'License',
                'is_active' => 1,
            ),
            98 => 
            array (
                'id' => 99,
                'name' => 'Registered Electrical Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            99 => 
            array (
                'id' => 100,
                'name' => 'Registered Master Electrician',
                'type' => 'License',
                'is_active' => 1,
            ),
            100 => 
            array (
                'id' => 101,
                'name' => 'Sanitary Engineer',
                'type' => 'License',
                'is_active' => 1,
            ),
            101 => 
            array (
                'id' => 102,
                'name' => 'Social Worker',
                'type' => 'License',
                'is_active' => 1,
            ),
            102 => 
            array (
                'id' => 103,
                'name' => 'Sugar Technician',
                'type' => 'License',
                'is_active' => 1,
            ),
            103 => 
            array (
                'id' => 104,
                'name' => 'Veterinarian',
                'type' => 'License',
                'is_active' => 1,
            ),
            104 => 
            array (
                'id' => 105,
                'name' => 'X-Ray Technologist',
                'type' => 'License',
                'is_active' => 1,
            ),
            105 => 
            array (
                'id' => 106,
                'name' => 'Eligibility',
                'type' => 'Type',
                'is_active' => 1,
            ),
            106 => 
            array (
                'id' => 107,
                'name' => 'License',
                'type' => 'Type',
                'is_active' => 1,
            ),
            107 => 
            array (
                'id' => 108,
                'name' => 'Doctorate Degree',
                'type' => 'Level',
                'is_active' => 1,
            ),
            108 => 
            array (
                'id' => 109,
                'name' => 'Master’s Degree',
                'type' => 'Level',
                'is_active' => 1,
            ),
            109 => 
            array (
                'id' => 110,
                'name' => 'Bachelor’s Degree',
                'type' => 'Level',
                'is_active' => 1,
            ),
            110 => 
            array (
                'id' => 111,
                'name' => 'Associate Degree ',
                'type' => 'Level',
                'is_active' => 1,
            ),
            111 => 
            array (
                'id' => 112,
                'name' => 'Senior High School',
                'type' => 'Level',
                'is_active' => 1,
            ),
            112 => 
            array (
                'id' => 113,
                'name' => 'Junior High School',
                'type' => 'Level',
                'is_active' => 1,
            ),
            113 => 
            array (
                'id' => 114,
                'name' => 'Others',
                'type' => 'Level',
                'is_active' => 1,
            ),
            114 => 
            array (
                'id' => 115,
                'name' => 'Letter',
                'type' => 'Document Type',
                'is_active' => 1,
            ),
            115 => 
            array (
                'id' => 116,
                'name' => 'Announcement',
                'type' => 'Document Type',
                'is_active' => 1,
            ),
            116 => 
            array (
                'id' => 117,
                'name' => 'Notice Of Meeting',
                'type' => 'Document Type',
                'is_active' => 1,
            ),
            117 => 
            array (
                'id' => 118,
                'name' => 'Memorandum of Agreement',
                'type' => 'Document Type',
                'is_active' => 1,
            ),
            118 => 
            array (
                'id' => 119,
                'name' => 'Administrative Order',
                'type' => 'Document Type',
                'is_active' => 1,
            ),
            119 => 
            array (
                'id' => 120,
                'name' => 'Special Order',
                'type' => 'Document Type',
                'is_active' => 1,
            ),
            120 => 
            array (
                'id' => 121,
                'name' => 'Minutes Of Meeting',
                'type' => 'Document Type',
                'is_active' => 1,
            ),
            121 => 
            array (
                'id' => 122,
                'name' => 'Publication',
                'type' => 'Document Type',
                'is_active' => 1,
            ),
            122 => 
            array (
                'id' => 123,
                'name' => 'Please Rush',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            123 => 
            array (
                'id' => 124,
                'name' => 'Please Attend',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            124 => 
            array (
                'id' => 125,
                'name' => 'Please Draft Reply/memo/letter',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            125 => 
            array (
                'id' => 126,
                'name' => 'Please Acknowledge Receipt',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            126 => 
            array (
                'id' => 127,
                'name' => 'Please Discuss With Me',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            127 => 
            array (
                'id' => 128,
                'name' => 'Please Calendar',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            128 => 
            array (
                'id' => 129,
                'name' => 'Please Follow Up',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            129 => 
            array (
                'id' => 130,
                'name' => 'Please Act On This',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            130 => 
            array (
                'id' => 131,
                'name' => 'Please Post',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            131 => 
            array (
                'id' => 132,
                'name' => 'Please Give Me Feedback',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            132 => 
            array (
                'id' => 133,
                'name' => 'Please File',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            133 => 
            array (
                'id' => 134,
                'name' => 'For Your Information/study/reference',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            134 => 
            array (
                'id' => 135,
                'name' => 'For Your Comments',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            135 => 
            array (
                'id' => 136,
                'name' => 'For Your Initials/signature',
                'type' => 'Document Action',
                'is_active' => 1,
            ),
            136 => 
            array (
                'id' => 137,
                'name' => 'Vehicle',
                'type' => 'Asset',
                'is_active' => 1,
            ),
            137 => 
            array (
                'id' => 138,
                'name' => 'All-in-One Computer',
                'type' => 'Asset',
                'is_active' => 1,
            ),
            138 => 
            array (
                'id' => 139,
                'name' => 'Laptop',
                'type' => 'Asset',
                'is_active' => 1,
            ),
            139 => 
            array (
                'id' => 140,
                'name' => 'Desktop Computer',
                'type' => 'Asset',
                'is_active' => 1,
            ),
            140 => 
            array (
                'id' => 141,
                'name' => 'Server Computer',
                'type' => 'Asset',
                'is_active' => 1,
            ),
            141 => 
            array (
                'id' => 142,
                'name' => 'LCD Projector',
                'type' => 'Asset',
                'is_active' => 1,
            ),
            142 => 
            array (
                'id' => 143,
                'name' => 'Genset',
                'type' => 'Asset',
                'is_active' => 1,
            ),
            143 => 
            array (
                'id' => 144,
                'name' => 'Split Type - Air Conditioner',
                'type' => 'Asset',
                'is_active' => 1,
            ),
            144 => 
            array (
                'id' => 145,
                'name' => 'Air Conditioner',
                'type' => 'Asset',
                'is_active' => 1,
            ),
            145 => 
            array (
                'id' => 146,
                'name' => 'Philhealth',
                'type' => 'Deduction',
                'is_active' => 1,
            ),
            146 => 
            array (
                'id' => 147,
                'name' => 'Pag-ibig I',
                'type' => 'Deduction',
                'is_active' => 1,
            ),
            147 => 
            array (
                'id' => 148,
                'name' => 'Pag-ibig II',
                'type' => 'Deduction',
                'is_active' => 1,
            ),
            148 => 
            array (
                'id' => 149,
                'name' => 'HDMF Housing Loan',
                'type' => 'Deduction',
                'is_active' => 1,
            ),
            149 => 
            array (
                'id' => 150,
                'name' => 'Official Vehicle',
                'type' => 'Travel',
                'is_active' => 1,
            ),
            150 => 
            array (
                'id' => 151,
                'name' => 'Public Conveyance',
                'type' => 'Travel',
                'is_active' => 1,
            ),
            151 => 
            array (
                'id' => 152,
                'name' => 'Vehicle Rental',
                'type' => 'Travel',
                'is_active' => 1,
            ),
            152 => 
            array (
                'id' => 153,
                'name' => 'General Funds',
                'type' => 'Travel Expense',
                'is_active' => 1,
            ),
            153 => 
            array (
                'id' => 154,
                'name' => 'Project Funds',
                'type' => 'Travel Expense',
                'is_active' => 1,
            ),
            154 => 
            array (
                'id' => 155,
                'name' => 'Others',
                'type' => 'Travel Expense',
                'is_active' => 1,
            ),
            155 => 
            array (
                'id' => 156,
                'name' => 'Travel Order',
                'type' => 'Request Type',
                'is_active' => 1,
            ),
            156 => 
            array (
                'id' => 157,
                'name' => 'Vehicle Reservation',
                'type' => 'Request Type',
                'is_active' => 1,
            ),
            157 => 
            array (
                'id' => 158,
                'name' => 'Leave Form',
                'type' => 'Request Type',
                'is_active' => 1,
            ),
            158 => 
            array (
                'id' => 159,
            'name' => 'Air (Commercial Flight)',
                'type' => 'Public Conveyance',
                'is_active' => 1,
            ),
            159 => 
            array (
                'id' => 160,
            'name' => 'Sea (Ferry)',
                'type' => 'Public Conveyance',
                'is_active' => 1,
            ),
            160 => 
            array (
                'id' => 161,
            'name' => 'Land (Bus/Van)',
                'type' => 'Public Conveyance',
                'is_active' => 1,
            ),
            161 => 
            array (
                'id' => 162,
                'name' => 'Earn',
                'type' => 'Credit',
                'is_active' => 1,
            ),
            162 => 
            array (
                'id' => 163,
                'name' => 'Deduct',
                'type' => 'Credit',
                'is_active' => 1,
            ),
            163 => 
            array (
                'id' => 164,
                'name' => 'Adjust',
                'type' => 'Credit',
                'is_active' => 1,
            ),
            164 => 
            array (
                'id' => 165,
                'name' => 'Render Overtime Service',
                'type' => 'Request Type',
                'is_active' => 1,
            ),
            165 => 
            array (
                'id' => 166,
                'name' => 'Male',
                'type' => 'Sex',
                'is_active' => 1,
            ),
            166 => 
            array (
                'id' => 167,
                'name' => 'Female',
                'type' => 'Sex',
                'is_active' => 1,
            ),
            167 => 
            array (
                'id' => 168,
                'name' => 'Jr.',
                'type' => 'Suffix',
                'is_active' => 1,
            ),
            168 => 
            array (
                'id' => 169,
                'name' => 'Sr.',
                'type' => 'Suffix',
                'is_active' => 1,
            ),
            169 => 
            array (
                'id' => 170,
                'name' => 'II',
                'type' => 'Suffix',
                'is_active' => 1,
            ),
            170 => 
            array (
                'id' => 171,
                'name' => 'III',
                'type' => 'Suffix',
                'is_active' => 1,
            ),
            171 => 
            array (
                'id' => 172,
                'name' => 'IV',
                'type' => 'Suffix',
                'is_active' => 1,
            ),
            172 => 
            array (
                'id' => 173,
                'name' => 'School',
                'type' => 'Academic',
                'is_active' => 1,
            ),
            173 => 
            array (
                'id' => 174,
                'name' => 'Course',
                'type' => 'Academic',
                'is_active' => 1,
            ),
            174 => 
            array (
                'id' => 175,
                'name' => 'Certificate of Appearance',
                'type' => 'Attachment',
                'is_active' => 1,
            ),
            175 => 
            array (
                'id' => 176,
                'name' => 'Itinerary of Travel',
                'type' => 'Attachment',
                'is_active' => 1,
            ),
            176 => 
            array (
                'id' => 177,
                'name' => 'Travel Accomplishment Report',
                'type' => 'Attachment',
                'is_active' => 1,
            ),
            177 => 
            array (
                'id' => 178,
                'name' => 'Certificate of Travel Completed',
                'type' => 'Attachment',
                'is_active' => 1,
            ),
            178 => 
            array (
                'id' => 179,
                'name' => 'Certification of Expenses not Requiring Receipts',
                'type' => 'Attachment',
                'is_active' => 1,
            ),
            179 => 
            array (
                'id' => 180,
                'name' => 'Administrative / Operational',
                'type' => 'Event',
                'is_active' => 1,
            ),
            180 => 
            array (
                'id' => 181,
                'name' => 'Meetings / Coordination',
                'type' => 'Event',
                'is_active' => 1,
            ),
            181 => 
            array (
                'id' => 182,
                'name' => 'Training / Capacity Building',
                'type' => 'Event',
                'is_active' => 1,
            ),
            182 => 
            array (
                'id' => 183,
                'name' => 'Assessment / Evaluation',
                'type' => 'Event',
                'is_active' => 1,
            ),
            183 => 
            array (
                'id' => 184,
                'name' => 'Official / Ceremonial',
                'type' => 'Event',
                'is_active' => 1,
            ),
            184 => 
            array (
                'id' => 185,
                'name' => 'Conference / Forum',
                'type' => 'Event',
                'is_active' => 1,
            ),
            185 => 
            array (
                'id' => 186,
                'name' => 'Face-to-Face',
                'type' => 'Event Mode',
                'is_active' => 1,
            ),
            186 => 
            array (
                'id' => 187,
                'name' => 'Online',
                'type' => 'Event Mode',
                'is_active' => 1,
            ),
            187 => 
            array (
                'id' => 188,
                'name' => 'Hybrid',
                'type' => 'Event Mode',
                'is_active' => 1,
            ),
            188 => 
            array (
                'id' => 189,
                'name' => 'Internal',
                'type' => 'Audience',
                'is_active' => 1,
            ),
            189 => 
            array (
                'id' => 190,
                'name' => 'External',
                'type' => 'Audience',
                'is_active' => 1,
            ),
            190 => 
            array (
                'id' => 191,
                'name' => 'Selected',
                'type' => 'Audience',
                'is_active' => 1,
            ),
            191 => 
            array (
                'id' => 192,
                'name' => 'Official Business',
                'type' => 'Request',
                'is_active' => 1,
            ),
            192 => 
            array (
                'id' => 193,
                'name' => 'On-the-Job Training',
                'type' => 'Visitor',
                'is_active' => 1,
            ),
            193 => 
            array (
                'id' => 194,
                'name' => 'Immersion',
                'type' => 'Visitor',
                'is_active' => 1,
            ),
            194 => 
            array (
                'id' => 195,
                'name' => 'Visitor',
                'type' => 'Visitor',
                'is_active' => 1,
            ),
            195 => 
            array (
                'id' => 196,
                'name' => 'Procurement',
                'type' => 'NA',
                'is_active' => 1,
            ),
        ));
        
        
    }
}
