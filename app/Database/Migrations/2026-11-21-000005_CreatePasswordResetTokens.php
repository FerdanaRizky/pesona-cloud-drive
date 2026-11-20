<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePasswordResetTokens extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190],
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'expires_at' => ['type' => 'DATETIME'],
            'used_at' => ['type' => 'DATETIME', 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('token_hash');
        $this->forge->addKey(['email', 'created_at']);
        $this->forge->addKey(['ip_address', 'created_at']);
        $this->forge->addKey(['user_id', 'used_at']);
        $this->forge->createTable('password_reset_tokens', true);
    }

    public function down()
    {
        $this->forge->dropTable('password_reset_tokens', true);
    }
}
