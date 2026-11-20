<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddWhatsappPhoneToUsers extends Migration {
 public function up(){ $this->forge->addColumn('users',['phone'=>['type'=>'VARCHAR','constraint'=>20,'null'=>true,'after'=>'email']]); $this->forge->addUniqueKey('phone','users_phone_unique'); }
 public function down(){ $this->forge->dropColumn('users','phone'); }
}