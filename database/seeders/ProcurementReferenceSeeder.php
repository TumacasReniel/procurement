<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reference data the Procurement and Inventory modules depend on at runtime but which
 * the auto-generated List*TableSeeder files never contained: the procurement/BAC/supply
 * roles, and the Inventory status set.
 *
 * Without these, ListStatus::getID(..., 'Inventory') returns null and every role check
 * in ProcurementGate fails closed, so the modules cannot be used on a freshly seeded DB.
 *
 * Idempotent — safe to re-run; it never deletes existing rows.
 */
class ProcurementReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();
        $this->seedInventoryStatuses();
    }

    protected function seedRoles(): void
    {
        $roles = [
            ['name' => 'Regional Director', 'type' => 'Executive', 'definition' => 'Approves purchase requests and signs procurement documents.'],
            ['name' => 'Procurement Officer', 'type' => 'Procurement', 'definition' => 'Manages PAP codes, APP/PPMP consolidation, RFQs, and approves purchase requests.'],
            ['name' => 'Procurement Staff', 'type' => 'Procurement', 'definition' => 'Encodes purchase requests, RFQs, and supporting procurement records.'],
            ['name' => 'BAC Chairperson', 'type' => 'Procurement', 'definition' => 'Chairs the Bids and Awards Committee and approves BAC resolutions.'],
            ['name' => 'BAC Vice Chairperson', 'type' => 'Procurement', 'definition' => 'Deputises for the BAC Chairperson and may approve BAC resolutions.'],
            ['name' => 'BAC Member', 'type' => 'Procurement', 'definition' => 'Evaluates bids and drafts BAC resolutions.'],
            ['name' => 'BAC User', 'type' => 'Procurement', 'definition' => 'BAC secretariat — prepares bid evaluations, resolutions, and notices of award.'],
            ['name' => 'Supply Officer', 'type' => 'Supply', 'definition' => 'Manages inventory issuance, RIS/ICS/PAR, and purchase order delivery.'],
            ['name' => 'Supply Staff', 'type' => 'Supply', 'definition' => 'Encodes inventory receipts, withdrawals, and issuances.'],
        ];

        foreach ($roles as $role) {
            DB::table('list_roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'type' => $role['type'],
                    'definition' => $role['definition'],
                    'is_active' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    protected function seedInventoryStatuses(): void
    {
        // Mirrors InventoryStockClass::inventoryStatuses()
        $statuses = [
            ['name' => 'Pending', 'color' => 'text-warning', 'bg' => 'bg-warning'],
            ['name' => 'Approved', 'color' => 'text-info', 'bg' => 'bg-info'],
            ['name' => 'Completed', 'color' => 'text-success', 'bg' => 'bg-success'],
            ['name' => 'Cancelled', 'color' => 'text-danger', 'bg' => 'bg-danger'],
            ['name' => 'Disapproved', 'color' => 'text-danger', 'bg' => 'bg-danger'],
        ];

        foreach ($statuses as $status) {
            DB::table('list_statuses')->updateOrInsert(
                [
                    'name' => $status['name'],
                    'classification' => 'Inventory',
                ],
                [
                    'type' => 'n/a',
                    'color' => $status['color'],
                    'bg' => $status['bg'],
                    'icon' => 'n/a',
                    'is_active' => 1,
                ]
            );
        }
    }
}
