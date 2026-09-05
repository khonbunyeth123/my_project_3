<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTblUserRolesTable extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('tbl_user_roles')) {
            $table = $this->table('tbl_user_roles', ['signed' => false]);
            $table
                ->addColumn('user_id', 'integer', ['signed' => false])
                ->addColumn('role_id', 'integer', ['signed' => false])
                ->addColumn('created_at', 'datetime')
                ->addIndex(['user_id'])
                ->addIndex(['role_id'])
                ->addIndex(['user_id', 'role_id'], ['unique' => true])
                ->addForeignKey('user_id', 'tbl_users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->addForeignKey('role_id', 'tbl_roles', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->create();
        }

        if ($this->hasTable('tbl_users')) {
            $this->execute(
                'INSERT IGNORE INTO tbl_user_roles (user_id, role_id, created_at)
                 SELECT id, role_id, NOW()
                 FROM tbl_users
                 WHERE role_id IS NOT NULL'
            );
        }
    }

    public function down(): void
    {
        if ($this->hasTable('tbl_user_roles')) {
            $this->table('tbl_user_roles')->drop()->save();
        }
    }
}
