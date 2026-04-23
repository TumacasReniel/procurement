<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ListRolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('list_roles')->delete();
        
        \DB::table('list_roles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Administrator',
                'type' => 'Staff',
                'is_active' => 1,
                'definition' => 'Has full system access, including managing users, roles, and all system configurations',
                'created_at' => '2025-10-11 14:15:07',
                'updated_at' => '2025-10-11 14:15:07',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Employee',
                'type' => 'Staff',
                'is_active' => 1,
                'definition' => 'Standard user role with access to personal records, DTR, leave filing, and travel requests.',
                'created_at' => '2025-10-11 14:15:07',
                'updated_at' => '2025-10-11 14:15:07',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Human Resource Head',
                'type' => 'Staff',
                'is_active' => 1,
                'definition' => 'Oversees HR operations such as employee management, leave approvals, and record verification.',
                'created_at' => '2025-10-11 14:15:07',
                'updated_at' => '2025-10-11 14:15:07',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Human Resource Officer',
                'type' => 'Staff',
                'is_active' => 1,
                'definition' => 'Assists in processing HR-related tasks, maintaining employee records, and supporting HR officers.',
                'created_at' => '2025-10-11 14:15:07',
                'updated_at' => '2025-10-11 14:15:07',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Document Management Officer',
                'type' => 'Staff',
                'is_active' => 1,
                'definition' => 'Assists in processing HR-related tasks, maintaining employee records, and supporting HR officers.',
                'created_at' => '2026-01-23 10:32:43',
                'updated_at' => '2026-01-23 10:32:43',
            ),
            5 =>
            array (
                'id' => 6,
                'name' => 'Budget Officer',
                'type' => 'Finance',
                'is_active' => 1,
                'definition' => 'Reviews and approves additional PAP code budget allocation requests.',
                'created_at' => '2026-04-22 12:01:00',
                'updated_at' => '2026-04-22 12:01:00',
            ),
        ));
        
        
    }
}
