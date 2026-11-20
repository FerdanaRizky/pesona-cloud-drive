<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEncryptedShareTokens extends Migration
{
    public function up()
    {
        $this->forge->addColumn('file_shares', [
            'token_ciphertext' => ['type' => 'TEXT', 'null' => true, 'after' => 'token_hash'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('file_shares', 'token_ciphertext');
    }
}