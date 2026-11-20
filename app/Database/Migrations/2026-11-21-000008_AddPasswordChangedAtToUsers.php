<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddPasswordChangedAtToUsers extends Migration
{
 public function up()
 {
  $this->forge->addColumn('users',['password_changed_at'=>['type'=>'DATETIME','null'=>true,'after'=>'password_hash']]);
 }
 public function down(){ $this->forge->dropColumn('users','password_changed_at'); }
}