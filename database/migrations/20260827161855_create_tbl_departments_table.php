<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use App\Support\Uuid;

final class CreateTblDepartmentsTable extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('tbl_departments');

        $table
            ->addColumn('uuid', 'char', ['length' => 36])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('status_id', 'integer', ['limit' => 1, 'default' => 1])
            ->addColumn('created_at', 'datetime')
            ->addColumn('created_by', 'integer', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addColumn('updated_by', 'integer', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('deleted_by', 'integer', ['null' => true])
            ->addIndex(['uuid'], ['unique' => true])
            ->addIndex(['name'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('tbl_departments')->drop()->save();
    }
}
