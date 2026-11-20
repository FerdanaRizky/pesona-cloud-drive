<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddOtpAttemptsToPasswordResetTokens extends Migration {
 public function up(){ $this->forge->addColumn('password_reset_tokens',['attempts'=>['type'=>'SMALLINT','unsigned'=>true,'default'=>0]]); }
 public function down(){ $this->forge->dropColumn('password_reset_tokens','attempts'); }
}