<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call(ListDataTableSeeder::class);
        // $this->call(ListRolesTableSeeder::class);
        // $this->call(SurveyQuestionsTableSeeder::class);
        
        // User::create([
        //     'username' => 'rij0311',
        //     'email' => 'kradjumli@gmail.com',
        //     'kradworkz' => hash('sha256','kradjumli@gmail.com'),
        //     'password' => bcrypt('123456789'),
        //     'is_active' => 1,
        //     'must_change' => 1,
        //     'email_verified_at' => '2024-05-15 08:46:33',
        //     'created_at' => now(),
        //     'updated_at' => now()
        // ]);

        // UserProfile::create([
        //     'lastname' => 'Jumli',
        //     'firstname' => 'Ra-ouf',
        //     'middlename' => 'Indanan',
        //     'mobile' => '09171531652',
        //     'mobile_hash' => hash('sha256','09171531652'),
        //     'birthdate' => '1994-03-11',
        //     'birthmonth' => 3,
        //     'sex_id' => 1,
        //     'user_id' => 1,
        //     'marital_id' => 3,
        //     'religion_id' => 20,
        //     'blood_id' => 1,
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // $this->call(LocationRegionsTableSeeder::class);
        // $this->call(LocationProvincesTableSeeder::class);
        // $this->call(LocationMunicipalitiesTableSeeder::class);
        // $this->call(LocationBarangaysTableSeeder::class);
        // $this->call(ListSalariesTableSeeder::class);
        // $this->call(ListPositionsTableSeeder::class);
        // $this->call(ListLeavesTableSeeder::class);
        // $this->call(ListDeductionsTableSeeder::class);
        // $this->call(ListDropdownsTableSeeder::class);
        // $this->call(ListUnitsTableSeeder::class);
        // $this->call(ListStatusesTableSeeder::class);

        // \DB::table('user_organizations')->insert([
        //     'user_id' => 1,
        //     'status_id' => 2,
        //     'division_id' => 4,
        //     'station_id' => 5,
        //     'position_id' => 12,
        //     'salary_id' => 31,
        //     'type_id' => 16,
        //     'unit_id' => 15,
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // \DB::table('user_roles')->insert([
        //     'user_id' => 1,
        //     'role_id' => 1,
        //     'added_by' => 1,
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // \DB::table('user_roles')->insert([
        //     'user_id' => 1,
        //     'role_id' => 2,
        //     'added_by' => 1,
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        $this->call(OrgChartsTableSeeder::class);
        $this->call(OrgSignatoriesTableSeeder::class);
        // $this->call(UsersTableSeeder::class);
        // $this->call(UserFoldersTableSeeder::class);
        // $this->call(UserFolderFilesTableSeeder::class);
        // $this->call(UserOrganizationsTableSeeder::class);
        // $this->call(UserProfilesTableSeeder::class);
        // $this->call(UserRolesTableSeeder::class);
        // $this->call(UserFacesTableSeeder::class);
    }
}
