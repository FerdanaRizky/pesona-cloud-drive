<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDriveSearchAndActivityIndexes extends Migration
{
    public function up()
    {
        $this->db->query('CREATE INDEX idx_file_shares_token_hash ON file_shares (token_hash)');
        $this->db->query('CREATE INDEX idx_activity_user_created ON activity_logs (user_id, created_at)');
        $this->db->query('CREATE INDEX idx_drive_files_search ON drive_files (user_id, is_trashed, original_name)');
        $this->db->query('CREATE INDEX idx_drive_folders_search ON drive_folders (user_id, is_trashed, name)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_file_shares_token_hash ON file_shares');
        $this->db->query('DROP INDEX idx_activity_user_created ON activity_logs');
        $this->db->query('DROP INDEX idx_drive_files_search ON drive_files');
        $this->db->query('DROP INDEX idx_drive_folders_search ON drive_folders');
    }
}
