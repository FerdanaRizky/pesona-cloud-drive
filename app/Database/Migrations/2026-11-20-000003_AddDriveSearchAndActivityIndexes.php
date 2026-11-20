<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDriveSearchAndActivityIndexes extends Migration
{
    public function up()
    {
        $this->forge->addKey('token_hash');
        $this->forge->processIndexes('file_shares');

        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->processIndexes('activity_logs');

        $this->forge->addKey(['user_id', 'is_trashed', 'original_name']);
        $this->forge->processIndexes('drive_files');

        $this->forge->addKey(['user_id', 'is_trashed', 'name']);
        $this->forge->processIndexes('drive_folders');
    }

    public function down()
    {
        $this->forge->dropKey('file_shares', 'token_hash');
        $this->forge->dropKey('activity_logs', ['user_id', 'created_at']);
        $this->forge->dropKey('drive_files', ['user_id', 'is_trashed', 'original_name']);
        $this->forge->dropKey('drive_folders', ['user_id', 'is_trashed', 'name']);
    }
}
