<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDepartmentIdToTblEmployees extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('tbl_employees');

        $table
            ->addColumn('department_id', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'department',
            ])
            ->addForeignKey(
                'department_id',
                'tbl_departments',
                'id',
                [
                    'delete' => 'SET_NULL',
                    'update' => 'CASCADE',
                ]
            )
            ->update();
    }

    public function down(): void
    {
        $table = $this->table('tbl_employees');

        $table
            ->dropForeignKey('department_id')
            ->removeColumn('department_id')
            ->update();
    }
}
